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

class BdoJointWorkReportController extends Controller
{
    /**
     * Display the Joint Work Report index.
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

        return view('bdo_joint_work.index', compact('bdos', 'zones', 'states', 'districts', 'cities'));
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
     * Get Data for DataTable
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $authUser = Auth::user();

            // Select lead_visits.* to ensure we don't get ID collisions with joined users table
            $query = LeadVisit::select('lead_visits.*')
                ->leftJoin('users', 'lead_visits.user_id', '=', 'users.id') // Join for location filtering
                ->with(['user', 'bdm', 'lead', 'account', 'fabricator', 'jointWorkRequest.bdm']);

            // --- CORE LOGIC: Show if 'Joint Work' OR has a Request ---
            $query->where(function($q) {
                $q->where('lead_visits.work_type', 'Joint Work')
                  ->orWhereHas('jointWorkRequest');
            });

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

            // 2. Hierarchy Filters (Filtering the BDOs)
            if ($request->filled('zsm_id')) {
                $bdoIds = UserMapping::where('zsm_id', $request->zsm_id)->distinct()->pluck('bdo_id');
                $query->whereIn('lead_visits.user_id', $bdoIds);
            }
            if ($request->filled('manager_id')) {
                $bdoIds = UserMapping::where('bdm_id', $request->manager_id)->distinct()->pluck('bdo_id');
                $query->whereIn('lead_visits.user_id', $bdoIds);
            }

            // 3. Direct BDO Filter
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
                    return $row->user ? $row->user->name : '-';
                })
                ->addColumn('manager_name', function ($row) {
                    // 1. If BDM is already assigned in the main table
                    if ($row->bdm) {
                        return $row->bdm->name;
                    }
                    // 2. If not assigned, show who was Requested
                    if ($row->jointWorkRequest && $row->jointWorkRequest->bdm) {
                        return $row->jointWorkRequest->bdm->name . ' <span class="text-[9px] text-slate-400">(Req)</span>';
                    }
                    return '<span class="text-slate-400 italic">N/A</span>';
                })
                ->addColumn('client_type', function ($row) {
                    return match((string)$row->visit_type) {
                        '1' => '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">Account</span>',
                        '2' => '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-bold uppercase">Lead</span>',
                        '3' => '<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase">Fabricator</span>',
                        default => '<span class="text-slate-400">Unknown</span>'
                    };
                })
                ->addColumn('client_name', function ($row) {
                    if ($row->visit_type == '1') return $row->account->name ?? '-';
                    if ($row->visit_type == '2') return $row->lead->name ?? '-';
                    if ($row->visit_type == '3') return $row->fabricator->shop_name ?? '-';
                    return '-';
                })
                ->addColumn('request_status', function ($row) {
                    if ($row->jointWorkRequest) {
                        $status = $row->jointWorkRequest->status; 
                        if ($status == '0') return '<span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold uppercase border border-amber-200">Pending</span>';
                        if ($status == '1') return '<span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase border border-emerald-200">Approved</span>';
                        if ($status == '2') return '<div class="flex flex-col"><span class="px-2 py-1 rounded-lg bg-red-100 text-red-700 text-[10px] font-bold uppercase border border-red-200 w-fit">Declined</span><span class="text-[9px] text-slate-400 font-semibold mt-0.5">Individual Work</span></div>';
                    }
                    if ($row->work_type == 'Joint Work') {
                        return '<span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase border border-slate-200">Direct Joint</span>';
                    }
                    return '<span class="text-slate-300 text-[10px]">Individual</span>';
                })
                ->addColumn('visit_status', function ($row) {
                    if ($row->action == '0') return '<span class="inline-flex items-center gap-1 text-slate-400 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">schedule</span> Pending Visit</span>';
                    return '<span class="inline-flex items-center gap-1 text-blue-600 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">check_circle</span> Completed</span>';
                })
                ->addColumn('location', function ($row) {
                    if ($row->visit_type == '1') return $row->account->address ?? '-';
                    if ($row->visit_type == '2') return $row->lead->site_address ?? '-';
                    if ($row->visit_type == '3') return $row->fabricator->address ?? '-';
                    return '-';
                })
                ->rawColumns(['bdo_name', 'manager_name', 'client_type', 'request_status', 'visit_status'])
                ->make(true);
        }
    }
}