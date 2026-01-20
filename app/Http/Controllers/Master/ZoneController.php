<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ZoneController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of active zones.
     */
    public function index()
    {
    $this->authorize('zones.view');
    // Get all states for the mapping checkbox list in the modal
    $states = State::orderBy('name')->get();
    return view('masters.zones.index', compact('states'));
    }

    public function getData(Request $request)
    {
    $this->authorize('zones.view');

    // Only fetch active zones where action = '0'
    $data = Zone::where('action', '0')->with('states');

    return DataTables::of($data)
        ->addIndexColumn()
        ->addColumn('mapped_states', function($row) {
            return $row->states->pluck('name')->implode(', ');
        })
        ->make(true);
    }

    /**
     * Store a newly created zone.
     */
    public function store(Request $request)
    {
        $this->authorize('zones.create');

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique check: Only compare against other ACTIVE zones
                Rule::unique('zones')->where(function ($query) {
                    return $query->where('action', '0');
                }),
            ],
            'state_ids' => 'nullable|array'
        ]);

        $zone = Zone::create([
            'name' => $request->name,
            'action' => '0' // Default to active
        ]);

        // Map selected states to this new zone
        if ($request->has('state_ids')) {
            State::whereIn('id', $request->state_ids)->update(['zone_id' => $zone->id]);
        }

        return response()->json(['success' => true, 'message' => 'Zone created successfully']);
    }

    /**
     * Show the form for editing the specified zone.
     */
    public function edit($id)
    {
        $this->authorize('zones.edit');

        $zone = Zone::with('states')->findOrFail($id);

        return response()->json([
            'zone' => $zone,
            'selected_states' => $zone->states->pluck('id')
        ]);
    }

    /**
     * Update the specified zone.
     */
    public function update(Request $request, $id)
    {
        $this->authorize('zones.edit');

        $zone = Zone::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique check: Ignore current ID, and only compare against ACTIVE zones
                Rule::unique('zones')->where(function ($query) {
                    return $query->where('action', '0');
                })->ignore($id),
            ],
            'state_ids' => 'nullable|array'
        ]);

        $zone->update([
            'name' => $request->name
        ]);

        // Mapping Logic:
        // 1. Remove this zone_id from all states currently mapped to it
        State::where('zone_id', $zone->id)->update(['zone_id' => null]);

        // 2. Assign this zone_id to the newly selected states
        if ($request->has('state_ids')) {
            State::whereIn('id', $request->state_ids)->update(['zone_id' => $zone->id]);
        }

        return response()->json(['success' => true, 'message' => 'Zone updated successfully']);
    }

    /**
     * Bulk Deactivate zones (Action = '1').
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('zones.delete');

        if ($request->has('ids') && is_array($request->ids)) {
            // Set action to '1' (Inactive)
            Zone::whereIn('id', $request->ids)->update(['action' => '1']);

            // Cleanup: Unmap states from these deactivated zones
            State::whereIn('zone_id', $request->ids)->update(['zone_id' => null]);

            return response()->json(['success' => true, 'message' => 'Zones deactivated']);
        }

        return response()->json(['error' => 'No IDs selected'], 400);
    }
}
