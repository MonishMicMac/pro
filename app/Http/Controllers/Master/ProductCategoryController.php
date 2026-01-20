<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class ProductCategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('product-category.view');

        if ($request->ajax()) {

            // SHOW ONLY ACTIVE
            $data = ProductCategory::where('action', '0')
                ->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.product-category.index');
    }

    // CREATE
    public function store(Request $request)
    {
        $this->authorize('product-category.create');

        $request->validate([
            // UNIQUE ONLY AMONG ACTIVE
            'name' => 'required|unique:product_categories,name,NULL,id,action,0'
        ]);

        ProductCategory::create([
            'name'   => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Category added']);
    }

    public function edit(ProductCategory $product_category)
    {
        $this->authorize('product-category.edit');

        return response()->json($product_category);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $this->authorize('product-category.edit');

        $request->validate([
            // UNIQUE ONLY AMONG ACTIVE (EXCEPT CURRENT)
            'name' => 'required|unique:product_categories,name,' . $id . ',id,action,0'
        ]);

        ProductCategory::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    // SOFT DELETE
    public function destroy($id)
    {
        $this->authorize('product-category.delete');
        ProductCategory::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Category deactivated']);
    }

    // BULK SOFT DELETE
    public function bulkDelete(Request $request)
    {
        $this->authorize('product-category.delete');
        if (!$request->has('ids') || !is_array($request->ids) || count($request->ids) == 0) {
            return response()->json([
                'error' => 'No records selected'
            ], 422);
        }

        ProductCategory::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected categories deactivated'
        ]);
    }
}
