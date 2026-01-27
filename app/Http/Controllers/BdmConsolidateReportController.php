<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisitBdm; 
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

class BdmConsolidateReportController extends Controller
{
    public function index()
    {
        // 1. Load Top-Level Locations Only
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $districts = District::orderBy('district_name')->get();
        $cities = City::orderBy('city_name')->get();

        // 2. Initialize Empty Collections for Hierarchy (Loaded via AJAX)
        // These will be populated only after user selects a Zone -> ZSM
        $zsms = collect();
        $bdms = collect();

        return view('bdm_consolidate_report.index', compact('bdms', 'zsms', 'zones', 'states', 'districts', 'cities'));
    }

    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // Fetch ZSMs in this Zone
                    'zsms'   => User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);
            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            case 'zsm':
                // Fetch BDMs under this ZSM
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']);
            default:
                return response()->json([]);
        }
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            
            $query = LeadVisitBdm::select(
                    'lead_visits_bdm.user_id', 
                    'lead_visits_bdm.schedule_date',
                    
                    // Metrics (Prefix with table name to avoid ambiguity)
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'planned' THEN 1 ELSE 0 END) as planned_total"),
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'planned' AND lead_visits_bdm.action = '1' THEN 1 ELSE 0 END) as planned_visited"),
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'planned' AND lead_visits_bdm.action = '0' THEN 1 ELSE 0 END) as planned_missed"),

                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'unplanned' THEN 1 ELSE 0 END) as unplanned_total"),
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'unplanned' AND lead_visits_bdm.action = '1' THEN 1 ELSE 0 END) as unplanned_visited"),
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.type = 'unplanned' AND lead_visits_bdm.action = '0' THEN 1 ELSE 0 END) as unplanned_missed"),

                    DB::raw("SUM(CASE WHEN lead_visits_bdm.work_type = 'Individual' THEN 1 ELSE 0 END) as individual_count"),
                    DB::raw("SUM(CASE WHEN lead_visits_bdm.work_type = 'Joint Work' THEN 1 ELSE 0 END) as joint_count")
                )
                ->leftJoin('users', 'lead_visits_bdm.user_id', '=', 'users.id')
                ->with('user')
                ->groupBy('lead_visits_bdm.user_id', 'lead_visits_bdm.schedule_date');

            // Filters
            if ($request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
            if ($request->filled('state_id')) $query->where('users.state_id', $request->state_id);
            if ($request->filled('district_id')) $query->where('users.district_id', $request->district_id);
            if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

            if ($request->filled('zsm_id')) {
                $bdmIds = UserMapping::where('zsm_id', $request->zsm_id)->distinct()->pluck('bdm_id');
                $query->whereIn('lead_visits_bdm.user_id', $bdmIds);
            }

            if ($request->filled('user_id')) {
                $query->where('lead_visits_bdm.user_id', $request->user_id);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('lead_visits_bdm.schedule_date', [$request->start_date, $request->end_date]);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('schedule_date', fn($row) => $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-')
                ->addColumn('bdm_name', fn($row) => $row->user ? $row->user->name : '-')
                ->addColumn('total_visit_count', fn($row) => $row->planned_visited + $row->unplanned_visited)
                ->addColumn('total_missed_count', fn($row) => $row->planned_missed + $row->unplanned_missed)
                ->rawColumns(['bdm_name'])
                ->make(true);
        }
    }
}