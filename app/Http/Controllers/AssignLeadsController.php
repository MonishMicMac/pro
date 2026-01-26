<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DigitalMarketingLead;
use App\Models\User;
use App\Models\Zone; // Ensure Zone model is imported
use Illuminate\Support\Facades\DB;

class AssignLeadsController extends Controller
{
    /**
     * Display the list of leads for assignment.
     */
    public function index(Request $request)
    {
        // 1. Fetch Data for Filters
        // Fetch Telecallers (Role ID 16) - For the initial filter dropdown
        $telecallers = User::whereHas('roles', function($q) {
            $q->where('id', 16); 
        })->get();

        // Fetch Active Zones
        $zones = Zone::where('action', '0')->orderBy('name')->get();

        // 2. Start Query for Leads
        $query = DigitalMarketingLead::with(['telecaller', 'assignedUser', 'zoneDetails']);

        // 3. Filter: Assigned vs Unassigned
        if ($request->has('status')) {
            if ($request->status == 'assigned') {
                $query->whereNotNull('telecaller_id');
            } elseif ($request->status == 'unassigned') {
                $query->whereNull('telecaller_id');
            }
        }

        // 4. Filter: By specific Telecaller ID
        if ($request->filled('telecaller_id')) {
            $query->where('telecaller_id', $request->telecaller_id);
        }

        // 5. Filter: Date Range (From Date - To Date)
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // 6. Get Results
        $leads = $query->latest()->paginate(50);

        return view('assign_leads.index', compact('leads', 'telecallers', 'zones'));
    }

    /**
     * Process the assignment of leads to a telecaller.
     */
    public function assignTelecaller(Request $request)
    {
        $request->validate([
            'lead_ids'      => 'required|array|min:1',
            'lead_ids.*'    => 'exists:digital_marketing_leads,id',
            'telecaller_id' => 'required|exists:users,id',
        ], [
            'lead_ids.required' => 'Please select at least one lead.',
            'telecaller_id.required' => 'Please select a Telecaller.'
        ]);

        try {
            DigitalMarketingLead::whereIn('id', $request->lead_ids)->update([
                'telecaller_id' => $request->telecaller_id,
                'updated_by'    => auth()->id(),
                'updated_at'    => now(),
            ]);

            return back()->with('success', 'Leads assigned successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Get Telecallers based on Zone selection
     */
    public function getTelecallersByZone(Request $request)
    {
        $zoneId = $request->zone_id;

        // Base query: Users with Role ID 16 (Telecaller)
        $query = User::whereHas('roles', function($q) {
            $q->where('id', 16); 
        });

        // Filter by Zone if provided
        if ($zoneId) {
            // Assuming 'zone_id' column in users table stores JSON like ["1","2"] or single ID "1"
            // We search if the stringified ID exists in the column
            $query->where(function($q) use ($zoneId) {
                $q->where('zone_id', 'LIKE', '%"' . $zoneId . '"%') // JSON format check
                  ->orWhere('zone_id', $zoneId); // Simple ID check
            });
        }

        $telecallers = $query->select('id', 'name')->get();

        return response()->json($telecallers);
    }
}