<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TravelType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class TravelTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('travel-types.view');

        if ($request->ajax()) {

            $data = TravelType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.travel_types.index');
    }

    public function store(Request $request)
    {
        $this->authorize('travel-types.create');

        $request->validate([
            'name' => 'required|unique:travel_types,name,NULL,id,action,0'
        ]);

        TravelType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Travel Type added']);
    }

    public function edit(TravelType $travelType)
    {
        $this->authorize('travel-types.edit');

        return response()->json($travelType);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('travel-types.edit');

        $request->validate([
            'name' => 'required|unique:travel_types,name,' . $id . ',id,action,0'
        ]);

        TravelType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('travel-types.delete');
        TravelType::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Travel Type deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('travel-types.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        TravelType::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected expence type deactivated'
        ]);
    }
}
