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
            ->make(true);
    }

public function store(Request $request)
{
    // 1. Validation
    $request->validate([
        'bdo_ids' => 'required|array|min:1',
        'bdm_id'  => 'required',
        'zsm_id'  => 'required',
        'ism_id'  => 'required',
        'vp_id'   => 'required',
        'md_id'   => 'required',
    ], [
        'bdo_ids.required' => 'Please select at least one BDO to map.',
    ]);

    // 2. Check if this is an Update (Edit Mode) or Insert (Add Mode)
    // The hidden input 'id' is passed from the form.
    if (!empty($request->id)) {
        
        // --- EDIT MODE ---
        $mapping = UserMapping::findOrFail($request->id);
        
        // Take the first BDO ID (since edit mode usually deals with one row)
        $newBdoId = $request->bdo_ids[0];

        // Check if we are changing the BDO to someone else who is ALREADY mapped
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
            'zsm_id' => $request->zsm_id,
            'bdm_id' => $request->bdm_id,
        ]);

    } else {
        
        // --- ADD MODE ---
        
        // 1. Pre-check: Do any of these BDOs already exist in the table?
        $existingMappings = UserMapping::whereIn('bdo_id', $request->bdo_ids)
                            ->with('bdo') // Load user to get name
                            ->get();

        if ($existingMappings->count() > 0) {
            // Get names of BDOs that are already mapped
            $names = $existingMappings->map(function($map) {
                return $map->bdo ? $map->bdo->name : 'Unknown ID';
            })->implode(', ');

            return response()->json([
                'message' => "The following BDOs are already mapped: $names. Please remove them or edit their existing entry."
            ], 422);
        }

        // 2. If no duplicates found, Create New Records
        foreach ($request->bdo_ids as $bdo_id) {
            UserMapping::create([
                'bdo_id' => $bdo_id,
                'md_id'  => $request->md_id,
                'vp_id'  => $request->vp_id,
                'ism_id' => $request->ism_id,
                'zsm_id' => $request->zsm_id,
                'bdm_id' => $request->bdm_id,
            ]);
        }
    }

    return response()->json(['success' => 'Hierarchy mapping saved.']);
}

    public function edit($id)
    {
        $mapping = UserMapping::findOrFail($id);
        return response()->json($mapping);
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
}