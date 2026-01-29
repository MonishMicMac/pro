<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\StationAllowanceMaster;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Models\Role; 

class StationAllowanceMasterController extends Controller
{
    use AuthorizesRequests;


public function index(Request $request)
{
    $this->authorize('station-allowance.view');

    if ($request->ajax()) {
        $data = StationAllowanceMaster::with('role')
            ->where('action', '0')
            ->latest();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('station_type', fn ($row) =>
                StationAllowanceMaster::getStationTypes()[$row->station_type] ?? 'Unknown'
            )
            ->addColumn('role_name', fn ($row) =>
                $row->role?->name ?? '-'
            )
            ->make(true);
    }

    $roles = Role::orderBy('name')->get();

    return view('masters.station_allowance.index', compact('roles'));
}


    public function store(Request $request)
    {
        $this->authorize('station-allowance.create');

        $request->validate([
            'role_id'      => 'required|exists:roles,id',
            'station_type' => 'required|in:1,2',
            'amount'       => 'required|numeric|min:0'
        ]);

        StationAllowanceMaster::create([
            'role_id'      => $request->role_id,
            'station_type' => $request->station_type,
            'amount'       => $request->amount,
            'action'       => '0'
        ]);

        return response()->json(['success' => 'Station Allowance added']);
    }

    public function edit(StationAllowanceMaster $stationAllowance)
    {
        $this->authorize('station-allowance.edit');

        return response()->json($stationAllowance);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('station-allowance.edit');

        $request->validate([
            'station_type' => 'required|in:1,2',
            'amount'       => 'required|numeric|min:0'
        ]);

        StationAllowanceMaster::where('id', $id)->update([
            'role_id'      => $request->role_id,
            'station_type' => $request->station_type,
            'amount'       => $request->amount
        ]);

        return response()->json(['success' => 'Updated successfully']);
    }

    public function destroy($id)
    {
        $this->authorize('station-allowance.delete');
        StationAllowanceMaster::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Station Allowance deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('station-allowance.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        StationAllowanceMaster::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected station allowances deactivated'
        ]);
    }
}
