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
    // Custom error messages for better alerts
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

    foreach ($request->bdo_ids as $bdo_id) {
        UserMapping::updateOrCreate(
            ['bdo_id' => $bdo_id],
            [
                'md_id'  => $request->md_id,
                'vp_id'  => $request->vp_id,
                'ism_id' => $request->ism_id,
                'zsm_id' => $request->zsm_id,
                'bdm_id' => $request->bdm_id,
            ]
        );
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