<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\SalesStage;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class SalesStageController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('sales-stages.view');

        if ($request->ajax()) {
            $data = SalesStage::where('action', '0')->latest();
            return DataTables::of($data)->addIndexColumn()->make(true);
        }
        return view('masters.sales_stages.index');
    }

    public function store(Request $request)
    {
        $this->authorize('sales-stages.create');

        $request->validate([
            'name' => 'required|unique:sales_stages,name,NULL,id,action,0'
        ]);

        SalesStage::create(['name' => $request->name, 'action' => '0']);
        return response()->json(['success' => 'Added']);
    }

    public function edit(SalesStage $sales_stage)
    {
        $this->authorize('sales-stages.edit');

        return response()->json($sales_stage);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('sales-stages.edit');

        $request->validate([
            'name' => 'required|unique:sales_stages,name,' . $id . ',id,action,0'
        ]);

        SalesStage::where('id', $id)->update(['name' => $request->name]);
        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('sales-stages.delete');
        SalesStage::where('id', $id)->update(['action' => '1']);
        return response()->json(['success' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('sales-stages.delete');
        SalesStage::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json(['success' => 'Bulk deleted']);
    }
}
