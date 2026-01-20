<?php
namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = State::query()->latest();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function($row){
                    return ($row->action == 0) ? 0 : 1; // Return raw values for JS rendering
                })
                ->make(true);
        }
        return view('masters.states.index');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:states,name']);
        State::create([
            'name' => $request->name,
            'action' => $request->has('action') ? '0' : '1'
        ]);
        return response()->json(['success' => 'State added!']);
    }

  public function edit(State $state) // Laravel will automatically find the record for you
{
    return response()->json($state);
}

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255|unique:states,name,'.$id]);
        $state = State::findOrFail($id);
        $state->update([
            'name' => $request->name,
            'action' => $request->has('action') ? '0' : '1'
        ]);
        return response()->json(['success' => 'State updated!']);
    }

    public function destroy($id)
    {
        State::findOrFail($id)->delete();
        return response()->json(['success' => 'State deleted!']);
    }

    // New Bulk Delete Method
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if(!empty($ids)) {
            State::whereIn('id', $ids)->delete();
            return response()->json(['success' => 'Selected states deleted!']);
        }
        return response()->json(['error' => 'Nothing selected'], 400);
    }
}