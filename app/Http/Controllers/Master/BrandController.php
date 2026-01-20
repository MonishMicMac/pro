<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BrandController extends Controller
{

    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('brands.view');
        if ($request->ajax()) {

            $data = Brand::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.brands.index');
    }

    public function store(Request $request)
    {
        $this->authorize('brands.create');
        $request->validate([
            'name' => 'required|unique:brands,name,NULL,id,action,0'
        ]);

        Brand::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Brand added']);
    }

    public function edit(Brand $brand)
    {
        $this->authorize('brands.edit');
        return response()->json($brand);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('brands.edit');
        $request->validate([
            'name' => 'required|unique:brands,name,' . $id . ',id,action,0'
        ]);

        Brand::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('brands.delete');
        Brand::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Brand deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('brands.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        Brand::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected brands deactivated'
        ]);
    }
}
