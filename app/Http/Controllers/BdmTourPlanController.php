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

class BdmTourPlanController extends Controller
{
    public function index()
    {
        // 1. Load Top-Level Locations Only
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $districts = District::orderBy('district_name')->get();
        $cities = City::orderBy('city_name')->get();

        // 2. Initialize Empty Collections for Hierarchy (Loaded via AJAX)
        $zsms = collect();
        $bdms = collect();
        $bdos = collect();

        return view('bdm_tourplan.index', compact('zsms', 'bdms', 'bdos', 'zones', 'states', 'districts', 'cities'));
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
                    // Fallbacks if user skips hierarchy
                    'bdms'   => User::role('BDM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    'bdos'   => User::role('BDO')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);

            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);

            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);

            case 'zsm':
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']);

            case 'manager': 
                $bdoIds = UserMapping::where('bdm_id', $id)->distinct()->pluck('bdo_id');
                return User::whereIn('id', $bdoIds)->orderBy('name')->get(['id', 'name']);

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
                    'lead_visits_bdm.food_allowance', 
                    DB::raw('count(*) as total_visits'),
                    DB::raw('GROUP_CONCAT(DISTINCT work_type ORDER BY work_type SEPARATOR ", ") as work_modes')
                )
                ->leftJoin('users', 'lead_visits_bdm.user_id', '=', 'users.id')
                ->with('user')
                ->groupBy('lead_visits_bdm.user_id', 'lead_visits_bdm.schedule_date', 'lead_visits_bdm.food_allowance');

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

            if ($request->filled('bdo_id')) {
                $query->where('lead_visits_bdm.bdo_id', $request->bdo_id);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('lead_visits_bdm.schedule_date', [$request->start_date, $request->end_date]);
            }
            if ($request->filled('location_status')) {
                $query->where('lead_visits_bdm.food_allowance', $request->location_status);
            }
            if ($request->filled('work_mode')) {
                $query->where('lead_visits_bdm.work_type', $request->work_mode);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('schedule_date', fn($row) => $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-')
                ->addColumn('bdm_name', fn($row) => $row->user ? $row->user->name : '-')
                ->editColumn('location_status', function ($row) {
                    $statuses = [
                        '0' => ['label' => 'Not Planned', 'class' => 'bg-slate-100 text-slate-500'],
                        '1' => ['label' => 'Local', 'class' => 'bg-emerald-50 text-emerald-600'],
                        '2' => ['label' => 'Out Station', 'class' => 'bg-blue-50 text-blue-600'],
                        '3' => ['label' => 'Meeting', 'class' => 'bg-amber-50 text-amber-600'],
                        '4' => ['label' => 'Leave', 'class' => 'bg-rose-50 text-rose-600'],
                    ];
                    $s = $statuses[$row->food_allowance] ?? $statuses['0'];
                    return "<span class='px-2 py-1 rounded text-[10px] font-bold uppercase border border-transparent {$s['class']}'>{$s['label']}</span>";
                })
                ->addColumn('work_modes', fn($row) => "<span class='text-[10px] font-bold uppercase text-slate-600'>{$row->work_modes}</span>")
                ->addColumn('action', function ($row) {
                    $rawDate = substr($row->schedule_date, 0, 10); 
                    return '<button onclick="openTourModal('.$row->user_id.', \''.$rawDate.'\')" 
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-[10px] font-bold uppercase flex items-center gap-1 mx-auto"><span class="material-symbols-outlined text-[14px]">visibility</span> View</button>';
                })
                ->rawColumns(['bdm_name', 'location_status', 'work_modes', 'action'])
                ->make(true);
        }
    }

    public function getDetails(Request $request)
    {
        $visits = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'bdo'])
            ->where('user_id', $request->user_id)
            ->where('schedule_date', 'like', $request->date . '%')
            ->get();

        $jointWork = [];
        $individualWork = [];

        foreach ($visits as $row) {
            $clientName = '-';
            $typeLabel  = '-';
            
            if ($row->visit_type == '1') { $clientName = $row->account->name ?? 'Unknown Account'; $typeLabel = 'Account'; } 
            elseif ($row->visit_type == '2') { $clientName = $row->lead->name ?? 'Unknown Lead'; $typeLabel = 'Lead'; } 
            elseif ($row->visit_type == '3') { $clientName = $row->fabricator->shop_name ?? 'Unknown Fabricator'; $typeLabel = 'Fabricator'; }

            $item = [
                'type' => $typeLabel, 'name' => $clientName,
                'status' => ($row->action == '1') ? 'Visited' : 'Pending',
                'work_type' => $row->work_type,
                'partner_name' => $row->bdo ? $row->bdo->name : 'N/A' 
            ];

            if ($row->work_type === 'Joint Work') $jointWork[] = $item;
            else $individualWork[] = $item;
        }

        return response()->json(['joint' => $jointWork, 'individual' => $individualWork]);
    }
}