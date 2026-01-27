<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserMappingController extends Controller
{
    public function index()
    {
        $data['mds'] = User::role('MD')->get();
        $data['vps'] = User::role('VP')->get();
        $data['isms'] = User::role('ISM')->get();
        $data['zsms'] = User::role('ZSM')->get();
        $data['bdms'] = User::role('BDM')->get();
        $data['bdos'] = User::role('BDO')->get();
        return view('masters.user_mappings.index', $data);
    }

    public function getData()
    {
        $query = UserMapping::with(['md', 'vp', 'ism', 'zsm', 'bdm', 'bdo'])->select('user_mappings.*');

        $role_users = [
            'md_id'  => User::role('MD')->get(['id', 'name']),
            'vp_id'  => User::role('VP')->get(['id', 'name']),
            'ism_id' => User::role('ISM')->get(['id', 'name']),
            'zsm_id' => User::role('ZSM')->get(['id', 'name']),
            'bdm_id' => User::role('BDM')->get(['id', 'name']),
            'bdo_id' => User::role('BDO')->get(['id', 'name']),
        ];

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('can_edit', function () {
                return auth()->user()->can('user-mappings.edit');
            })
            ->addColumn('can_delete', function () {
                return auth()->user()->can('user-mappings.delete');
            })
            ->with('role_users', $role_users)
            ->addColumn('zsm_names', function($row) {
                return $row->zsms->map(function($user) { return $user->name; })->implode(', ');
            })
            ->make(true);
    }

   public function store(Request $request)
    {
        // Default mapping type if not specified
        $type = $request->mapping_type ?? 'bdm_bdo';

        // ---------------------------------------------------------
        // CASE 1: ISM -> ZSM (ZSMs are checkboxes/Array here)
        // ---------------------------------------------------------
        if ($type === 'ism_zsm') {
            $request->validate([
                'ism_id' => 'required',
                'zsm_id' => 'required|array', // Keep array here (Checkbox Grid)
            ]);

            $alreadyMapped = [];
            foreach ($request->zsm_id as $zsm_id) {
                $exists = UserMapping::whereRaw("FIND_IN_SET(?, zsm_id)", [$zsm_id])
                    ->whereNotNull('ism_id')
                    ->where('ism_id', '!=', $request->ism_id)
                    ->first();
                if ($exists) {
                    $zsmName = User::find($zsm_id)->name ?? 'Unknown';
                    $alreadyMapped[] = $zsmName;
                }
            }

            if (!empty($alreadyMapped)) {
                return response()->json([
                    'message' => "The following ZSMs are already mapped to a different ISM: " . implode(', ', array_unique($alreadyMapped))
                ], 422);
            }

            UserMapping::where('ism_id', $request->ism_id)->update(['ism_id' => null]);

            foreach ($request->zsm_id as $zsm_id) {
                $updatedCount = UserMapping::whereRaw("FIND_IN_SET(?, zsm_id)", [$zsm_id])
                    ->update(['ism_id' => $request->ism_id]);
                
                if ($updatedCount === 0) {
                    UserMapping::create([
                        'ism_id' => $request->ism_id,
                        'zsm_id' => $zsm_id,
                        'bdo_id' => null
                    ]);
                }
            }
            return response()->json(['success' => 'ISM to ZSM mapping saved.']);
        }

        // ---------------------------------------------------------
        // CASE 2: ZSM -> BDM (FIXED: ZSM is now Single Select)
        // ---------------------------------------------------------
        if ($type === 'zsm_bdm') {
            $request->validate([
                'zsm_id'  => 'required', // CHANGED: Removed '|array'
                'bdm_ids' => 'required|array',
            ]);

            // CHANGED: No need to implode, it's already a single ID string/int
            $newZsmString = $request->zsm_id; 
            
            // Validation
            $existing = UserMapping::whereIn('bdm_id', $request->bdm_ids)
                ->whereNotNull('zsm_id')
                ->where('zsm_id', '!=', '')
                ->where('zsm_id', '!=', $newZsmString)
                ->with('bdm')->get();

            if ($existing->count() > 0) {
                $names = $existing->map(fn($m) => $m->bdm->name ?? 'Unknown')->unique()->implode(', ');
                return response()->json(['message' => "The following BDMs are already mapped to different ZSMs: $names"], 422);
            }

            // Un-mapping logic
            UserMapping::where('zsm_id', $newZsmString)
                ->whereNotIn('bdm_id', $request->bdm_ids)
                ->update(['zsm_id' => null]);

            // INHERITANCE: Find MD/VP/ISM associated with this ZSM
            // CHANGED: Use $request->zsm_id directly (not as array index [0])
            $parent = UserMapping::whereRaw("FIND_IN_SET(?, zsm_id)", [$request->zsm_id])
                ->whereNotNull('ism_id')->first();

            foreach ($request->bdm_ids as $bdm_id) {
                UserMapping::updateOrCreate(
                    ['bdm_id' => $bdm_id],
                    [
                        'zsm_id' => $newZsmString,
                        'ism_id' => $parent->ism_id ?? null,
                        'vp_id'  => $parent->vp_id  ?? null,
                        'md_id'  => $parent->md_id  ?? null,
                    ]
                );
            }
            return response()->json(['success' => 'ZSM to BDM mapping saved.']);
        }

        // ---------------------------------------------------------
        // CASE 3: BDM -> BDO (Partial)
        // ---------------------------------------------------------
        if ($type === 'bdm_bdo') {
            $request->validate([
                'bdm_id' => 'required',
                'bdo_ids' => 'required|array|min:1',
            ]);

            $parent = UserMapping::where('bdm_id', $request->bdm_id)
                ->whereNotNull('zsm_id')->first();

            UserMapping::where('bdm_id', $request->bdm_id)
                ->whereNotIn('bdo_id', $request->bdo_ids)
                ->whereNotNull('bdo_id')
                ->update(['bdm_id' => null]);

            foreach ($request->bdo_ids as $bdo_id) {
                UserMapping::updateOrCreate(
                    ['bdo_id' => $bdo_id],
                    [
                        'bdm_id' => $request->bdm_id,
                        'zsm_id' => $parent->zsm_id ?? null,
                        'ism_id' => $parent->ism_id ?? null,
                        'vp_id'  => $parent->vp_id  ?? null,
                        'md_id'  => $parent->md_id  ?? null,
                    ]
                );
            }
            return response()->json(['success' => 'BDOs assigned to BDM.']);
        }

        // ---------------------------------------------------------
        // CASE 4: Full Map (Default)
        // ---------------------------------------------------------
        $request->validate([
            'bdo_ids' => 'required|array|min:1',
            'bdm_id'  => 'required',
            'zsm_id'  => 'required|array', // Keep array here (Multi-select ZSM in Full Map)
            'ism_id'  => 'required',
            'vp_id'   => 'required',
            'md_id'   => 'required',
        ], [
            'bdo_ids.required' => 'Please select at least one BDO to map.',
        ]);

        if (!empty($request->id)) {
            // Edit Mode
            $mapping = UserMapping::findOrFail($request->id);
            $newBdoId = $request->bdo_ids[0];

            if ($mapping->bdo_id != $newBdoId) {
                $exists = UserMapping::where('bdo_id', $newBdoId)->exists();
                if ($exists) {
                    return response()->json(['message' => 'The selected BDO is already mapped elsewhere.'], 422);
                }
            }

            $mapping->update([
                'bdo_id' => $newBdoId,
                'md_id'  => $request->md_id,
                'vp_id'  => $request->vp_id,
                'ism_id' => $request->ism_id,
                'zsm_id' => implode(',', $request->zsm_id),
                'bdm_id' => $request->bdm_id,
            ]);

        } else {
            // Add Mode
            $existingMappings = UserMapping::whereIn('bdo_id', $request->bdo_ids)->with('bdo')->get();

            if ($existingMappings->count() > 0) {
                $names = $existingMappings->map(function($map) {
                    return $map->bdo ? $map->bdo->name : 'Unknown ID';
                })->implode(', ');

                return response()->json([
                    'message' => "The following BDOs are already mapped: $names. Please remove them or edit their existing entry."
                ], 422);
            }

            foreach ($request->bdo_ids as $bdo_id) {
                UserMapping::create([
                    'bdo_id' => $bdo_id,
                    'md_id'  => $request->md_id,
                    'vp_id'  => $request->vp_id,
                    'ism_id' => $request->ism_id,
                    'zsm_id' => implode(',', $request->zsm_id),
                    'bdm_id' => $request->bdm_id,
                ]);
            }
        }

        return response()->json(['success' => 'Hierarchy mapping saved.']);
    }

    public function edit($id)
    {
        $mapping = UserMapping::findOrFail($id);
        $data = $mapping->toArray();
        $data['zsm_ids'] = explode(',', $mapping->zsm_id);
        return response()->json($data);
    }

    public function updateField(Request $request)
    {
        if ($request->field == 'bdo_id') {
            $exists = UserMapping::where('bdo_id', $request->value)->where('id', '!=', $request->id)->exists();
            if ($exists) return response()->json(['error' => 'This BDO is already mapped elsewhere.'], 422);
        }

        $mapping = UserMapping::findOrFail($request->id);
        $mapping->{$request->field} = $request->value;
        $mapping->save();
        return response()->json(['success' => 'Updated']);
    }

    public function clearField(Request $request)
    {
        $mapping = UserMapping::findOrFail($request->id);
        $mapping->{$request->field} = null;
        $mapping->save();
        return response()->json(['success' => 'Cleared']);
    }

    public function bulkDelete(Request $request)
    {
        UserMapping::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => 'Deleted']);
    }

    public function destroy($id)
    {
        UserMapping::findOrFail($id)->delete();
        return response()->json(['success' => 'Deleted']);
    }

    public function getMappedUsers(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $ids = [];

        if ($type === 'ism_zsm') {
            // Find all ZSMs mapped to this ISM
            $mappings = UserMapping::where('ism_id', $id)->get(['zsm_id']);
            foreach ($mappings as $m) {
                if ($m->zsm_id) {
                    $ids = array_merge($ids, explode(',', $m->zsm_id));
                }
            }
        } elseif ($type === 'zsm_bdm') {
            // Find all BDMs mapped to this ZSM
            $mappings = UserMapping::whereRaw("FIND_IN_SET(?, zsm_id)", [$id])->get(['bdm_id']);
            $ids = $mappings->pluck('bdm_id')->filter()->toArray();
        } elseif ($type === 'bdm_bdo') {
            // Find all BDOs mapped to this BDM
            $mappings = UserMapping::where('bdm_id', $id)->get(['bdo_id']);
            $ids = $mappings->pluck('bdo_id')->filter()->toArray();
        }

        return response()->json(array_values(array_unique(array_map('trim', $ids))));
    }

    public function getTreeData()
    {
        $mappings = UserMapping::with(['md', 'vp', 'ism', 'bdm', 'bdo'])->get();

        $tree = ['name' => 'Organization', 'role' => 'ROOT', 'children' => []];
        $mds = [];

        foreach ($mappings as $map) {
            $mdId = $map->md_id ?: 'unassigned_md';
            $mdName = $map->md->name ?? 'Unassigned MD';
            
            if (!isset($mds[$mdId])) {
                $mds[$mdId] = ['name' => $mdName, 'role' => 'MD', 'children' => []];
            }
            
            $vpId = $map->vp_id ?: 'unassigned_vp';
            $vpName = $map->vp->name ?? 'Unassigned VP';
            if (!isset($mds[$mdId]['children'][$vpId])) {
                $mds[$mdId]['children'][$vpId] = ['name' => $vpName, 'role' => 'VP', 'children' => []];
            }
            
            $ismId = $map->ism_id ?: 'unassigned_ism';
            $ismName = $map->ism->name ?? 'Unassigned ISM';
            if (!isset($mds[$mdId]['children'][$vpId]['children'][$ismId])) {
                $mds[$mdId]['children'][$vpId]['children'][$ismId] = ['name' => $ismName, 'role' => 'ISM', 'children' => []];
            }
            
            // Handle ZSMs (comma separated)
            $zsmIds = explode(',', $map->zsm_id);
            foreach ($zsmIds as $zId) {
                $zId = trim($zId);
                if (!$zId) continue;
                
                if (!isset($mds[$mdId]['children'][$vpId]['children'][$ismId]['children'][$zId])) {
                    $zsmUser = User::find($zId);
                    $zsmName = $zsmUser->name ?? 'Unassigned ZSM';
                    $mds[$mdId]['children'][$vpId]['children'][$ismId]['children'][$zId] = ['name' => $zsmName, 'role' => 'ZSM', 'children' => []];
                }
                
                $bdmId = $map->bdm_id ?: 'unassigned_bdm';
                $bdmName = $map->bdm->name ?? 'Unassigned BDM';
                if (!isset($mds[$mdId]['children'][$vpId]['children'][$ismId]['children'][$zId]['children'][$bdmId])) {
                    $mds[$mdId]['children'][$vpId]['children'][$ismId]['children'][$zId]['children'][$bdmId] = ['name' => $bdmName, 'role' => 'BDM', 'children' => []];
                }
                
                if ($map->bdo_id) {
                    $bdoId = $map->bdo_id;
                    $bdoName = $map->bdo->name ?? 'Unassigned BDO';
                    $mds[$mdId]['children'][$vpId]['children'][$ismId]['children'][$zId]['children'][$bdmId]['children'][$bdoId] = ['name' => $bdoName, 'role' => 'BDO'];
                }
            }
        }

        // Convert associative arrays to indexed arrays recursively
        $formatNode = function($node) use (&$formatNode) {
            if (isset($node['children']) && is_array($node['children'])) {
                $node['children'] = array_values($node['children']);
                foreach ($node['children'] as &$child) {
                    if (is_array($child)) {
                        $child = $formatNode($child);
                    }
                }
            }
            return $node;
        };

        $tree['children'] = array_values($mds);
        foreach ($tree['children'] as &$md) {
            $md = $formatNode($md);
        }

        return response()->json($tree);
    }
}