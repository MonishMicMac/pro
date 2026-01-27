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
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TourPlanController extends Controller
{
    /**
     * Display the Tour Plan index with filters.
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
            ->role('BDO') // 🟢 Added specific role check
            ->get();

        return view('tourplan.index', compact('bdos', 'zones', 'states', 'districts', 'cities'));
    }

    /**
     * AJAX: Get Cascading Location Data & Users
     */
    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // 🟢 Fetch ONLY BDOs in this Zone
                    'users' => User::where('zone_id', $id)
                        ->role('BDO') // Ensure only BDO role
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

    /**
     * Main Table Data with New Filters
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $authUser = Auth::user();

            $query = LeadVisit::select(
                    'lead_visits.user_id', 
                    'lead_visits.schedule_date', 
                    'lead_visits.food_allowance', 
                    DB::raw('count(*) as total_visits'),
                    DB::raw('GROUP_CONCAT(DISTINCT work_type ORDER BY work_type SEPARATOR ", ") as work_modes')
                )
                ->leftJoin('users', 'lead_visits.user_id', '=', 'users.id')
                ->with('user')
                ->groupBy('lead_visits.user_id', 'lead_visits.schedule_date', 'lead_visits.food_allowance');

            // Geographic Security
            if ($authUser->zone_id) $query->where('users.zone_id', $authUser->zone_id);
            if ($authUser->state_id) $query->where('users.state_id', $authUser->state_id);
            if ($authUser->district_id) $query->where('users.district_id', $authUser->district_id);

            // Manual Filters
            if (!$authUser->zone_id && $request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
            if (!$authUser->state_id && $request->filled('state_id')) $query->where('users.state_id', $request->state_id);
            if (!$authUser->district_id && $request->filled('district_id')) $query->where('users.district_id', $request->district_id);
            if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

            // User Filter
            if ($request->filled('user_id')) {
                $query->where('lead_visits.user_id', $request->user_id);
            }

            // Date Range
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
            } elseif ($request->filled('start_date')) {
                $query->where('schedule_date', '>=', $request->start_date);
            } elseif ($request->filled('end_date')) {
                $query->where('schedule_date', '<=', $request->end_date);
            }

            // Location Status
            if ($request->filled('location_status')) {
                $query->where('food_allowance', $request->location_status);
            }

            // Work Mode
            if ($request->filled('work_mode')) {
                $query->where('work_type', $request->work_mode);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('schedule_date', function ($row) {
                    return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
                })
                ->addColumn('bdo_name', function ($row) {
                    return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
                })
                ->editColumn('location_status', function ($row) {
                    $statuses = [
                        '0' => ['label' => 'Not Planned', 'class' => 'bg-slate-100 text-slate-500'],
                        '1' => ['label' => 'Local',       'class' => 'bg-emerald-50 text-emerald-600'],
                        '2' => ['label' => 'Out Station', 'class' => 'bg-blue-50 text-blue-600'],
                        '3' => ['label' => 'Meeting',     'class' => 'bg-amber-50 text-amber-600'],
                        '4' => ['label' => 'Leave',       'class' => 'bg-rose-50 text-rose-600'],
                    ];
                    $s = $statuses[$row->food_allowance] ?? $statuses['0'];
                    return "<span class='px-2 py-1 rounded text-[10px] font-bold uppercase border border-transparent {$s['class']}'>{$s['label']}</span>";
                })
                ->addColumn('work_modes', function ($row) {
                    return "<span class='text-[10px] font-bold uppercase text-slate-600 tracking-wide'>{$row->work_modes}</span>";
                })
                ->addColumn('action', function ($row) {
                    $rawDate = substr($row->schedule_date, 0, 10); 
                    return '<button onclick="openTourModal('.$row->user_id.', \''.$rawDate.'\')" 
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-[10px] font-bold uppercase transition-all flex items-center gap-1 mx-auto">
                            <span class="material-symbols-outlined text-[14px]">visibility</span> View Plan
                            </button>';
                })
                ->rawColumns(['bdo_name', 'location_status', 'work_modes', 'action'])
                ->make(true);
        }
    }

    public function getDetails(Request $request)
    {
        $visits = LeadVisit::with(['lead', 'account', 'fabricator', 'bdm'])
            ->where('user_id', $request->user_id)
            ->where('schedule_date', 'like', $request->date . '%')
            ->get();

        $jointWork = [];
        $individualWork = [];

        foreach ($visits as $row) {
            $clientName = '-';
            $typeLabel  = '-';
            
            if ($row->visit_type == '1') {
                $clientName = $row->account->name ?? 'Unknown Account';
                $typeLabel  = 'Account';
            } elseif ($row->visit_type == '2') {
                $clientName = $row->lead->name ?? 'Unknown Lead';
                $typeLabel  = 'Lead';
            } elseif ($row->visit_type == '3') {
                $clientName = $row->fabricator->shop_name ?? 'Unknown Fabricator';
                $typeLabel  = 'Fabricator';
            }

            $item = [
                'type'      => $typeLabel,
                'name'      => $clientName,
                'status'    => ($row->action == '2') ? 'Visited' : 'Pending',
                'work_type' => $row->work_type,
                'bdm_name'  => $row->bdm ? $row->bdm->name : 'N/A' 
            ];

            if ($row->work_type === 'Joint Work') {
                $jointWork[] = $item;
            } else {
                $individualWork[] = $item;
            }
        }

        return response()->json([
            'joint'      => $jointWork,
            'individual' => $individualWork
        ]);
    }
}