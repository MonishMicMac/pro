<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class PropertyTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('property-types.view');

        if ($request->ajax()) {

            $data = PropertyType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.property_types.index');
    }

    public function store(Request $request)
    {
        $this->authorize('property-types.create');

        $request->validate([
            'name' => 'required|unique:property_types,name,NULL,id,action,0'
        ]);

        PropertyType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Property Type added']);
    }

    public function edit(PropertyType $propertyType)
    {
        $this->authorize('property-types.edit');

        return response()->json($propertyType);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('property-types.edit');

        $request->validate([
            'name' => 'required|unique:property_types,name,' . $id . ',id,action,0'
        ]);

        PropertyType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('property-types.delete');
        PropertyType::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Property Type deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('property-types.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        PropertyType::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected expence type deactivated'
        ]);
    }
}
