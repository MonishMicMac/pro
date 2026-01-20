<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = Branch::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.branches.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:branches,name,NULL,id,action,0'
        ]);

        Branch::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Branch added']);
    }

    public function edit(Branch $branch)
    {
        return response()->json($branch);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:branches,name,' . $id . ',id,action,0'
        ]);

        Branch::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        Branch::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Branch deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        Branch::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected branches deactivated'
        ]);
    }
}
