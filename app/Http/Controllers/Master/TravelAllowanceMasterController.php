<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TravelAllowanceMaster;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TravelAllowanceMasterController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('travel-allowance.view');

        if ($request->ajax()) {
            $data = TravelAllowanceMaster::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('vehicle_type', function ($row) {
                    return TravelAllowanceMaster::getVehicleTypes()[$row->vehicle_type] ?? 'Unknown';
                })
                ->make(true);
        }

        return view('masters.travel_allowance.index');
    }

    public function store(Request $request)
    {
        $this->authorize('travel-allowance.create');

        $request->validate([
            'vehicle_type' => 'required|in:0,1',
            'amount'       => 'required|numeric|min:0'
        ]);

        TravelAllowanceMaster::create([
            'vehicle_type' => $request->vehicle_type,
            'amount'       => $request->amount,
            'action'       => '0'
        ]);

        return response()->json(['success' => 'Travel Allowance added']);
    }

    public function edit(TravelAllowanceMaster $travelAllowance)
    {
        $this->authorize('travel-allowance.edit');

        return response()->json($travelAllowance);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('travel-allowance.edit');

        $request->validate([
            'vehicle_type' => 'required|in:0,1',
            'amount'       => 'required|numeric|min:0'
        ]);

        TravelAllowanceMaster::where('id', $id)->update([
            'vehicle_type' => $request->vehicle_type,
            'amount'       => $request->amount
        ]);

        return response()->json(['success' => 'Updated successfully']);
    }

    public function destroy($id)
    {
        $this->authorize('travel-allowance.delete');
        TravelAllowanceMaster::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Travel Allowance deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('travel-allowance.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        TravelAllowanceMaster::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected travel allowances deactivated'
        ]);
    }
}
