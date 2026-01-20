<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Category;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $this->authorize('categories.view');
        if ($request->ajax()) {
            $data = Category::where('action', '0')->latest();
            return DataTables::of($data)->addIndexColumn()->make(true);
        }
        return view('masters.categories.index');
    }

    public function store(Request $request)
    {
        $this->authorize('categories.create');
        $request->validate([
            'name' => 'required|unique:categories,name,NULL,id,action,0'
        ]);

        Category::create(['name' => $request->name, 'action' => '0']);
        return response()->json(['success' => 'Added']);
    }

    public function edit(Category $category)
    {
        $this->authorize('categories.edit');
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('categories.edit');
        $request->validate([
            'name' => 'required|unique:categories,name,' . $id . ',id,action,0'
        ]);

        Category::where('id', $id)->update(['name' => $request->name]);
        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('categories.delete');
        Category::where('id', $id)->update(['action' => '1']);
        return response()->json(['success' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('categories.delete');
        Category::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json(['success' => 'Bulk deleted']);
    }
}
