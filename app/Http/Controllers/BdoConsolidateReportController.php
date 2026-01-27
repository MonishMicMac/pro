<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisit; 
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\UserMapping;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BdoConsolidateReportController extends Controller
{
    /**
     * Display the report page with initial filters.
     */
    public function index()
    {
        $authUser = Auth::user();

        // 1. Load Zones
        $zones = Zone::where('action', '0')->orderBy('name')
            ->when($authUser->zone_id, fn($q) => $q->where('id', $authUser->zone_id))
            ->get();

        // 2. Load States
        $states = State::orderBy('name')
            ->when($authUser->state_id, fn($q) => $q->where('id', $authUser->state_id))
            ->when($authUser->zone_id && !$authUser->state_id, fn($q) => $q->where('zone_id', $authUser->zone_id))
            ->get();

        // 3. Load Districts
        $districts = District::orderBy('district_name')
            ->when($authUser->district_id, fn($q) => $q->where('id', $authUser->district_id))
            ->when($authUser->state_id && !$authUser->district_id, fn($q) => $q->where('state_id', $authUser->state_id))
            ->get();

        // 4. Load Cities
        $cities = City::orderBy('city_name')
            ->when($authUser->city_id, fn($q) => $q->where('id', $authUser->city_id))
            ->when($authUser->district_id && !$authUser->city_id, fn($q) => $q->where('district_id', $authUser->district_id))
            ->get();

        // 5. Load BDOs initially (Only Role: BDO)
        $bdos = User::orderBy('name')
            ->where('action', '0')
            ->role('BDO')
            ->get();

        return view('bdo_consolidate_report.index', compact('bdos', 'zones', 'states', 'districts', 'cities'));
    }

    /**
     * AJAX: Get Cascading Location Data & Hierarchy Users
     */
    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    'zsms'   => User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // Fallback BDOs
                    'bdos'   => User::role('BDO')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);
            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            case 'zsm':
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']); // Return BDMs
            case 'manager':
                $bdoIds = UserMapping::where('bdm_id', $id)->distinct()->pluck('bdo_id');
                return User::whereIn('id', $bdoIds)->orderBy('name')->get(['id', 'name']); // Return BDOs
            default:
                return response()->json([]);
        }
    }

    /**
     * Get Consolidated Data with Detailed Breakdown + Work Types
     */
 public function getData(Request $request)
{
    if ($request->ajax()) {
        $authUser = Auth::user();

        $query = LeadVisit::select(
                'lead_visits.user_id', 
                'lead_visits.schedule_date',
                
                // --- 1. PLANNED METRICS (Prefix columns with table name) ---
                DB::raw("SUM(CASE WHEN lead_visits.type = 'planned' THEN 1 ELSE 0 END) as planned_total"),
                
                // Fix: explicit 'lead_visits.action'
                DB::raw("SUM(CASE WHEN lead_visits.type = 'planned' AND lead_visits.action = '2' THEN 1 ELSE 0 END) as planned_visited"),
                DB::raw("SUM(CASE WHEN lead_visits.type = 'planned' AND lead_visits.action != '2' THEN 1 ELSE 0 END) as planned_missed"),

                // --- 2. UNPLANNED METRICS ---
                DB::raw("SUM(CASE WHEN lead_visits.type = 'unplanned' THEN 1 ELSE 0 END) as unplanned_total"),
                DB::raw("SUM(CASE WHEN lead_visits.type = 'unplanned' AND lead_visits.action = '2' THEN 1 ELSE 0 END) as unplanned_visited"),
                DB::raw("SUM(CASE WHEN lead_visits.type = 'unplanned' AND lead_visits.action != '2' THEN 1 ELSE 0 END) as unplanned_missed"),

                // --- 3. WORK TYPE COUNTS ---
                DB::raw("SUM(CASE WHEN lead_visits.work_type = 'Individual' THEN 1 ELSE 0 END) as individual_count"),
                DB::raw("SUM(CASE WHEN lead_visits.work_type = 'Joint Work' THEN 1 ELSE 0 END) as joint_count")
            )
            ->leftJoin('users', 'lead_visits.user_id', '=', 'users.id') 
            ->with('user')
            ->groupBy('lead_visits.user_id', 'lead_visits.schedule_date');

        // --- AUTO GEOGRAPHIC SECURITY ---
        if ($authUser->zone_id) $query->where('users.zone_id', $authUser->zone_id);
        if ($authUser->state_id) $query->where('users.state_id', $authUser->state_id);
        if ($authUser->district_id) $query->where('users.district_id', $authUser->district_id);

        // --- FILTERS ---

        // 1. Geographic Filters
        if ($request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
        if ($request->filled('state_id')) $query->where('users.state_id', $request->state_id);
        if ($request->filled('district_id')) $query->where('users.district_id', $request->district_id);
        if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

        // 2. Hierarchy Filters
        if ($request->filled('zsm_id')) {
            $bdoIds = \App\Models\UserMapping::where('zsm_id', $request->zsm_id)->distinct()->pluck('bdo_id');
            $query->whereIn('lead_visits.user_id', $bdoIds);
        }
        if ($request->filled('manager_id')) {
            $bdoIds = \App\Models\UserMapping::where('bdm_id', $request->manager_id)->distinct()->pluck('bdo_id');
            $query->whereIn('lead_visits.user_id', $bdoIds);
        }

        // 3. Direct User Filter
        if ($request->filled('user_id')) {
            $query->where('lead_visits.user_id', $request->user_id);
        }

        // 4. Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('lead_visits.schedule_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('lead_visits.schedule_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('lead_visits.schedule_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
            })
            ->addColumn('bdo_name', function ($row) {
                return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
            })
            
            // 4. CALCULATED TOTAL COLUMNS
            ->addColumn('total_visit_count', function ($row) {
                return $row->planned_visited + $row->unplanned_visited;
            })
            ->addColumn('total_missed_count', function ($row) {
                return $row->planned_missed + $row->unplanned_missed;
            })
            
            ->rawColumns(['bdo_name'])
            ->make(true);
    }
}
}