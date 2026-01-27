<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisitBdm;
use App\Models\Lead;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\UserMapping;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadVisitBdmController extends Controller
{
    public function fieldActivity()
    {
        $authUser = Auth::user();
        
        $leads = Lead::whereNotIn('lead_stage', [5, 6, 7])->orderBy('name')->get();
        $accounts = \App\Models\Account::where('action', '0')->orderBy('name')->get();
        $fabricators = \App\Models\Fabricator::where('status', '1')->orderBy('shop_name')->get();
        $users = User::where('action', '0')->orderBy('name')->get();

        $scheduledVisits = LeadVisitBdm::where('user_id', $authUser->id)
            ->whereDate('schedule_date', Carbon::today())
            ->where('action', 'Pending')
            ->with(['lead', 'account', 'fabricator'])
            ->get();

        $activeVisit = LeadVisitBdm::where('user_id', $authUser->id)
            ->where('action', 'In-Progress')
            ->with(['lead', 'account', 'fabricator'])
            ->first();

        return view('bdm_visits.on_field', compact('leads', 'scheduledVisits', 'activeVisit', 'accounts', 'fabricators', 'users'));
    }

    public function report()
    {
        // 1. Load Locations
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $districts = District::orderBy('district_name')->get();
        $cities = City::orderBy('city_name')->get();

        // 2. Initialize Empty Collections for Hierarchy
        $zsms = collect();
        $bdms = collect();

        return view('bdm_visits.report', compact('zones', 'states', 'districts', 'cities', 'zsms', 'bdms'));
    }

    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    'zsms'   => User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);
            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            case 'zsm':
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']);
            default:
                return response()->json([]);
        }
    }

    public function reportData(Request $request)
    {
        // ✅ FIXED: Using correct table name 'lead_visits_bdm' everywhere
        $query = LeadVisitBdm::select('lead_visits_bdm.*')
            ->leftJoin('users', 'lead_visits_bdm.user_id', '=', 'users.id')
            ->with(['lead', 'account.district', 'fabricator.city', 'user', 'bdm', 'bdo']);

        // --- GEOGRAPHIC FILTERS ---
        if ($request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
        if ($request->filled('state_id')) $query->where('users.state_id', $request->state_id);
        if ($request->filled('district_id')) $query->where('users.district_id', $request->district_id);
        if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

        // --- HIERARCHY FILTERS ---
        if ($request->filled('zsm_id')) {
            $bdmIds = UserMapping::where('zsm_id', $request->zsm_id)->distinct()->pluck('bdm_id');
            // ✅ FIXED: Table name
            $query->whereIn('lead_visits_bdm.user_id', $bdmIds);
        }

        if ($request->filled('user_id')) {
            // ✅ FIXED: Table name
            $query->where('lead_visits_bdm.user_id', $request->user_id);
        }

        // --- DATE FILTER ---
        if ($request->filled('from_date') && $request->filled('to_date')) {
            // ✅ FIXED: Table name. Also checking 'visit_date' (or use 'schedule_date' if preferred)
            $query->whereBetween('lead_visits_bdm.visit_date', [$request->from_date, $request->to_date]);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('target_name', function($row) {
                if ($row->lead_id) return $row->lead->name ?? '-';
                if ($row->account_id) return $row->account->name ?? '-';
                if ($row->fabricator_id) return $row->fabricator->shop_name ?? '-';
                return '-';
            })
            ->addColumn('visit_type_label', function($row) {
                return [1 => 'Account', 2 => 'Leads', 3 => 'Fabricator'][$row->visit_type] ?? '-';
            })
            ->addColumn('food_label', function($row) {
                return [1 => 'Local Station', 2 => 'Outstation'][$row->food_allowance] ?? '-';
            })
            ->addColumn('work_type_label', function($row) {
                $label = $row->work_type;
                if ($row->work_type == 'Joint Work') {
                    $names = [];
                    if ($row->bdm) $names[] = $row->bdm->name . ' (BDM)';
                    if ($row->bdo) $names[] = $row->bdo->name . ' (BDO)';
                    if (!empty($names)) $label .= ' (' . implode(', ', $names) . ')';
                }
                return $label;
            })
            ->editColumn('visit_date', function($row) {
                return $row->visit_date ? Carbon::parse($row->visit_date)->format('Y-m-d') : '-';
            })
            ->editColumn('intime_time', function($row) {
                return $row->intime_time ? Carbon::parse($row->intime_time)->format('h:i A') : '-';
            })
            ->editColumn('out_time', function($row) {
                return $row->out_time ? Carbon::parse($row->out_time)->format('h:i A') : '-';
            })
            ->rawColumns(['work_type_label'])
            ->make(true);
    }
}