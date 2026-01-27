<?php

namespace App\Http\Controllers;

use App\Models\BdoTarget;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadVisit;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\UserMapping;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BdoTargetController extends Controller
{
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

        // 5. Load Initial Users (BDOs)
        $bdos = User::where('action', '0')->role('BDO')->orderBy('name')->get();

        return view('bdo_targets.index', compact('zones', 'states', 'districts', 'cities', 'bdos'));
    }

    /**
     * AJAX: Cascading Logic for both Geography and Hierarchy
     */
    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    // Geographic Data
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // Organizational Data: Fetch ZSMs in this Zone
                    'zsms'   => User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // Fallback: Fetch direct BDOs if no hierarchy
                    'bdos'   => User::role('BDO')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);

            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);

            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);

            case 'zsm':
                // Fetch BDMs mapped to this ZSM
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']);

            case 'manager': // BDM
                // Fetch BDOs mapped to this BDM
                $bdoIds = UserMapping::where('bdm_id', $id)->distinct()->pluck('bdo_id');
                return User::whereIn('id', $bdoIds)->orderBy('name')->get(['id', 'name']);

            default:
                return response()->json([]);
        }
    }

    public function getData(Request $request)
    {
        $query = BdoTarget::with('user')
            ->select('bdo_targets.*')
            ->join('users', 'bdo_targets.user_id', '=', 'users.id');

        // --- Geographic Filters ---
        if ($request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
        if ($request->filled('state_id')) $query->where('users.state_id', $request->state_id);
        if ($request->filled('district_id')) $query->where('users.district_id', $request->district_id);
        if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

        // --- Organizational Hierarchy Filters ---
        if ($request->filled('zsm_id')) {
            $bdoIds = UserMapping::where('zsm_id', $request->zsm_id)->pluck('bdo_id');
            $query->whereIn('bdo_targets.user_id', $bdoIds);
        }
        
        if ($request->filled('manager_id')) { // BDM Filter
            $bdoIds = UserMapping::where('bdm_id', $request->manager_id)->pluck('bdo_id');
            $query->whereIn('bdo_targets.user_id', $bdoIds);
        }

        if ($request->filled('user_id')) { // Direct BDO Filter
            $query->where('bdo_targets.user_id', $request->user_id);
        }
        
        // --- Other Filters ---
        if ($request->filled('month')) {
            $query->where('target_month', $request->month);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('user_name', fn($row) => $row->user->name ?? 'N/A')
            
            // ... (Your existing calculation logic for columns) ...
            
            // NEW CALLS
            ->addColumn('new_calls_actual', function($row) {
                 return LeadVisit::where('user_id', $row->user_id)
                    ->where('lead_stage', 1)
                    ->where('visit_date', 'like', $row->target_month . '%')
                    ->count();
            })
            ->addColumn('new_calls_percent', function($row) {
                $actual = LeadVisit::where('user_id', $row->user_id)->where('lead_stage', 1)->where('visit_date', 'like', $row->target_month . '%')->count();
                return $this->calcPercent($actual, $row->target_new_calls);
            })
            
            // QUOTATIONS
            ->addColumn('quotes_actual', function($row) {
                 return Lead::where('user_id', $row->user_id)
                    ->where(function($q) { $q->where('lead_stage', '>=', 3)->orWhereHas('fabricatorRequests'); })
                    ->where('updated_at', 'like', $row->target_month . '%')
                    ->count();
            })
             ->addColumn('quotes_percent', function($row) {
                 $actual = Lead::where('user_id', $row->user_id)->where(function($q) { $q->where('lead_stage', '>=', 3)->orWhereHas('fabricatorRequests'); })->where('updated_at', 'like', $row->target_month . '%')->count();
                return $this->calcPercent($actual, $row->target_quotations);
            })

            // FOLLOW UPS
            ->addColumn('followups_actual', function($row) {
                return LeadVisit::where('user_id', $row->user_id)->where('lead_stage', 2)->where('visit_date', 'like', $row->target_month . '%')->count();
            })
             ->addColumn('followups_percent', function($row) {
                 $actual = LeadVisit::where('user_id', $row->user_id)->where('lead_stage', 2)->where('visit_date', 'like', $row->target_month . '%')->count();
                 return $this->calcPercent($actual, $row->target_followups);
            })

            // CONVERSION
            ->addColumn('conversion_actual', function($row) {
                return Lead::where('user_id', $row->user_id)->where('lead_stage', 5)->where('won_date', 'like', $row->target_month . '%')->sum('total_required_area_sqft');
            })
             ->addColumn('conversion_percent', function($row) {
                 $actual = Lead::where('user_id', $row->user_id)->where('lead_stage', 5)->where('won_date', 'like', $row->target_month . '%')->sum('total_required_area_sqft');
                 return $this->calcPercent($actual, $row->target_conversion_sqft);
            })
            
            // SALES
             ->addColumn('sales_value_actual', fn($row) => 0)
             ->addColumn('sales_value_percent', fn($row) => $this->calcPercent(0, $row->target_sales_value))
            
            ->addColumn('action', function ($row) {
                 return '<div class="flex gap-2 justify-center">
                        <button onclick="editTarget('.$row->id.')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button></div>';
            })
            ->rawColumns(['new_calls_percent', 'quotes_percent', 'followups_percent', 'conversion_percent', 'sales_value_percent', 'action'])
            ->make(true);
    }

    private function calcPercent($actual, $target) {
        if($target <= 0) return '<span class="text-slate-400">-</span>';
        $p = ($actual / $target) * 100;
        $color = 'text-red-500';
        if($p >= 100) $color = 'text-green-600';
        elseif($p >= 50) $color = 'text-orange-500';
        return '<span class="font-bold '.$color.'">'.number_format($p, 0).'%</span>';
    }

    public function store(Request $request)
    {
        $request->validate([ 'user_id' => 'required', 'target_month' => 'required' ]);
        $data = $request->all();
        $data['assigned_by'] = Auth::id();
        BdoTarget::updateOrCreate(['user_id' => $request->user_id, 'target_month' => $request->target_month], $data);
        return response()->json(['success' => 'Target saved successfully.']);
    }

    public function edit($id) { return response()->json(BdoTarget::findOrFail($id)); }
}