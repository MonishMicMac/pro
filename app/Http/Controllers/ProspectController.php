<?php

namespace App\Http\Controllers;

use App\Models\DigitalMarketingLead;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\LeadHelper;
use Illuminate\Support\Facades\Auth;

class ProspectController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Load Zones (Restricted if user has zone_id)
        $zones = Zone::where('action', '0')->orderBy('name')
            ->when($user->zone_id, fn($q) => $q->where('id', $user->zone_id))
            ->pluck('name', 'id');

        // 2. Load States (Restricted if user has state_id or zone_id)
        $states = State::orderBy('name')
            ->when($user->state_id, fn($q) => $q->where('id', $user->state_id))
            ->when($user->zone_id && !$user->state_id, fn($q) => $q->where('zone_id', $user->zone_id))
            ->pluck('name', 'id');

        // 3. Load Districts (Restricted if user has district_id or state_id)
        $districts = District::orderBy('district_name')
            ->when($user->district_id, fn($q) => $q->where('id', $user->district_id))
            ->when($user->state_id && !$user->district_id, fn($q) => $q->where('state_id', $user->state_id))
            ->pluck('district_name', 'id');

        // 4. Load Cities (Restricted if user has city_id or district_id)
        $cities = City::orderBy('city_name')
            ->when($user->city_id, fn($q) => $q->where('id', $user->city_id))
            ->when($user->district_id && !$user->city_id, fn($q) => $q->where('district_id', $user->district_id))
            ->pluck('city_name', 'id');

        return view('reports.prospect-report', compact('zones', 'states', 'districts', 'cities'));
    }

    public function data(Request $request)
    {
        $leadStages = LeadHelper::getLeadStages();
        $user = Auth::user();

        // Join with users table to filter by BDO location if the lead table doesn't have direct columns
        // Assuming we are filtering based on the Assigned User's (BDO) location
        $query = DigitalMarketingLead::with([
            'telecaller',
            'assignedUser',
            'lead',
            'zoneDetails'
        ])
        ->leftJoin('users', 'digital_marketing_leads.assigned_to', '=', 'users.id')
        ->select('digital_marketing_leads.*');

        // --- AUTH RESTRICTIONS ---
        if ($user->zone_id) $query->where('users.zone_id', $user->zone_id);
        if ($user->state_id) $query->where('users.state_id', $user->state_id);
        if ($user->district_id) $query->where('users.district_id', $user->district_id);

        // --- FILTERS ---
        
        // Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('digital_marketing_leads.created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }

        // Location Filters (Manual Selection)
        if ($request->filled('zone')) $query->where('users.zone_id', $request->zone);
        if ($request->filled('state')) $query->where('users.state_id', $request->state);
        if ($request->filled('district')) $query->where('users.district_id', $request->district);
        if ($request->filled('city')) $query->where('users.city_id', $request->city);

        // Telecaller filter
        if ($request->filled('telecaller')) {
            $query->where('digital_marketing_leads.updated_by', $request->telecaller);
        }

        // Telecaller Stage filter
        if ($request->filled('tc_stage')) {
            $query->where('digital_marketing_leads.stage', $request->tc_stage);
        }

        // BDO Lead Stage filter
        if ($request->filled('bdo_stage')) {
            $query->whereHas('lead', function ($q) use ($request) {
                $q->where('lead_stage', $request->bdo_stage);
            });
        }

        return DataTables::of($query)
            ->addColumn('telecaller', fn($r) => optional($r->telecaller)->name ?? '-')
            ->addColumn('telecaller_stage', fn($r) => $leadStages[$r->stage] ?? '-')
            ->addColumn('bdo', fn($r) => optional($r->assignedUser)->name ?? '-')
            ->addColumn('lead_stage', function ($r) {
                $stages = [
                    0 => 'Site Identification', 1 => 'Intro', 2 => 'FollowUp',
                    3 => 'Quote Pending', 4 => 'Quote Sent', 5 => 'Won',
                    6 => 'Site Handed Over', 7 => 'Lost'
                ];
                return $stages[optional($r->lead)->lead_stage] ?? '-';
            })
            ->addColumn('zone_name', fn($r) => optional($r->zoneDetails)->name ?? '-')
            ->editColumn('created_at', fn($r) => $r->created_at->format('d-m-Y'))
            ->editColumn('assigned_at', fn($r) => $r->assigned_at ? \Carbon\Carbon::parse($r->assigned_at)->format('d-m-Y h:i A') : '-')
            ->make(true);
    }

    // Helper for Cascading Dropdowns
public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // ✅ Added Telecallers Fetch Logic here
                    'telecallers' => User::where('zone_id', $id)
                        ->whereHas('roles', fn($q) => $q->where('name', 'Telecaller'))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                ]);
            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            default:
                return response()->json([]);
        }
}
}