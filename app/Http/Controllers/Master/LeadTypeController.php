<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadType;
use Yajra\DataTables\Facades\DataTables;

use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LeadTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('lead-types.view');
        if ($request->ajax()) {
            $data = LeadType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.lead-types.index');
    }

    public function store(Request $request)
    {
        $this->authorize('lead-types.create');
        $request->validate([
            'name' => 'required|unique:lead_types,name,NULL,id,action,0'
        ]);

        LeadType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Added']);
    }

    public function edit(LeadType $lead_type)
    {
        $this->authorize('lead-types.edit');
        return response()->json($lead_type);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('lead-types.edit');
        $request->validate([
            'name' => 'required|unique:lead_types,name,' . $id . ',id,action,0'
        ]);

        LeadType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('lead-types.delete');
        LeadType::where('id', $id)->update(['action' => '1']);
        return response()->json(['success' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('lead-types.delete');
        LeadType::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json(['success' => 'Deleted']);
    }
}
