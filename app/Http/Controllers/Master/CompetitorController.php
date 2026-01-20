<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Competitor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class CompetitorController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('competitors.view');

        if ($request->ajax()) {

            $data = Competitor::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.competitor.index');
    }

    public function store(Request $request)
    {
        $this->authorize('competitors.create');

        $request->validate([
            'name' => 'required|unique:competitors,name,NULL,id,action,0'
        ]);

        Competitor::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Competitor added']);
    }

    public function edit(Competitor $competitor)
    {
        $this->authorize('competitors.edit');

        return response()->json($competitor);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('competitors.edit');

        $request->validate([
            'name' => 'required|unique:competitors,name,' . $id . ',id,action,0'
        ]);

        Competitor::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('competitors.delete');
        Competitor::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Competitor deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('competitors.delete');
        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        Competitor::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected competitor deactivated'
        ]);
    }
}
