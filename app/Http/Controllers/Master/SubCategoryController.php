<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\SubCategory;
use App\Models\Category;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubCategoryController extends Controller
{
    use AuthorizesRequests;


    public function index(Request $request)
    {
        $this->authorize('sub-categories.view');
        if ($request->ajax()) {
            $data = SubCategory::with('category')
                ->where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('category', fn($r) => $r->category->name)
                ->make(true);
        }

        $categories = Category::where('action', '0')->get();
        return view('masters.sub_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('sub-categories.create');
        $request->validate([
            'category_id' => 'required',
            'name' => 'required|unique:sub_categories,name,NULL,id,category_id,'
                . $request->category_id . ',action,0'
        ]);

        SubCategory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Added']);
    }

    public function edit(SubCategory $sub_category)
    {
        $this->authorize('sub-categories.edit');
        return response()->json($sub_category);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('sub-categories.edit');
        $request->validate([
            'category_id' => 'required',
            'name' => 'required|unique:sub_categories,name,' . $id .
                ',id,category_id,' . $request->category_id . ',action,0'
        ]);

        SubCategory::where('id', $id)->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('sub-categories.delete');
        SubCategory::where('id', $id)->update(['action' => '1']);
        return response()->json(['success' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('sub-categories.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        SubCategory::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected sub categories deactivated'
        ]);
    }
}
