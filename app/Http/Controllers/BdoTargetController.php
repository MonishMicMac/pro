<?php

namespace App\Http\Controllers;

use App\Models\BdoTarget;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadVisit;
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
        $zones = \App\Models\Zone::orderBy('name')->where('action', '0')
             ->when($authUser->zone_id, function($q) use ($authUser) {
                return $q->where('id', $authUser->zone_id);
            })->get();
            
        // Pre-fetch commonly used lists for dropdowns
        $users = User::role(['BDO'])->where('action', '0')->orderBy('name')->get();
        
        return view('bdo_targets.index', compact('zones', 'users'));
    }

    public function getData(Request $request)
    {
        $query = BdoTarget::with('user')
            ->select('bdo_targets.*')
            ->join('users', 'bdo_targets.user_id', '=', 'users.id');

         // Hierarchical Filtering
        if ($request->filled('zone_id')) {
            $query->where('users.zone_id', $request->zone_id); // Assuming BDO has zone_id directly
        }

        if ($request->filled('zsm_id')) {
            $zsmId = $request->zsm_id;
            // Get BDOs under this ZSM
             $bdoIds = UserMapping::where('zsm_id', $zsmId)->distinct()->pluck('bdo_id');
             $query->whereIn('bdo_targets.user_id', $bdoIds);
        }
        
        if ($request->filled('manager_id')) {
             $managerId = $request->manager_id;
             $bdoIds = UserMapping::where('bdm_id', $managerId)->distinct()->pluck('bdo_id');
             $query->whereIn('bdo_targets.user_id', $bdoIds);
        }

        if ($request->filled('user_id')) {
            $query->where('bdo_targets.user_id', $request->user_id);
        }
        
        if ($request->filled('month')) {
            $query->where('target_month', $request->month);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('user_name', function($row) {
                return $row->user->name ?? 'N/A';
            })
            ->filterColumn('user_name', function($query, $keyword) {
                $query->where('users.name', 'like', "%{$keyword}%");
            })
            // NEW CALLS
            ->addColumn('new_calls_actual', function($row) {
                 return LeadVisit::where('user_id', $row->user_id)
                    ->where('lead_stage', 1) // Intro/New
                    ->where('visit_date', 'like', $row->target_month . '%')
                    ->count();
            })
            ->addColumn('new_calls_percent', function($row) {
                $actual = LeadVisit::where('user_id', $row->user_id)
                    ->where('lead_stage', 1)
                    ->where('visit_date', 'like', $row->target_month . '%')
                    ->count();
                return $this->calcPercent($actual, $row->target_new_calls);
            })
            
            // QUOTATIONS
            ->addColumn('quotes_actual', function($row) {
                 return Lead::where('user_id', $row->user_id)
                    ->where(function($q) {
                        $q->where('lead_stage', '>=', 3) // Assuming 3+ is quote given
                          ->orWhereHas('fabricatorRequests');
                    })
                    ->where('updated_at', 'like', $row->target_month . '%') // Approx
                    ->count();
            })
             ->addColumn('quotes_percent', function($row) {
                 $actual = Lead::where('user_id', $row->user_id)
                    ->where(function($q) { $q->where('lead_stage', '>=', 3)->orWhereHas('fabricatorRequests'); })
                    ->where('updated_at', 'like', $row->target_month . '%')
                    ->count();
                return $this->calcPercent($actual, $row->target_quotations);
            })

            // FOLLOW UPS
            ->addColumn('followups_actual', function($row) {
                return LeadVisit::where('user_id', $row->user_id)
                    ->where('lead_stage', 2) // Followup
                    ->where('visit_date', 'like', $row->target_month . '%')
                    ->count();
            })
             ->addColumn('followups_percent', function($row) {
                 $actual = LeadVisit::where('user_id', $row->user_id)
                    ->where('lead_stage', 2)
                    ->where('visit_date', 'like', $row->target_month . '%')
                    ->count();
                 return $this->calcPercent($actual, $row->target_followups);
            })

            // CONVERSION SQFT
            ->addColumn('conversion_actual', function($row) {
                return Lead::where('user_id', $row->user_id)
                    ->where('lead_stage', 5) // Won
                    ->where('won_date', 'like', $row->target_month . '%')
                    ->sum('total_required_area_sqft');
            })
             ->addColumn('conversion_percent', function($row) {
                 $actual = Lead::where('user_id', $row->user_id)
                    ->where('lead_stage', 5)
                    ->where('won_date', 'like', $row->target_month . '%')
                    ->sum('total_required_area_sqft');
                 return $this->calcPercent($actual, $row->target_conversion_sqft);
            })
            
            // SALES VALUE
             ->addColumn('sales_value_actual', function($row) {
                return 0; // Placeholder
            })
             ->addColumn('sales_value_percent', function($row) {
                 return $this->calcPercent(0, $row->target_sales_value);
            })
            
            ->addColumn('action', function ($row) {
                 $btn = '<div class="flex gap-2 justify-center">';
                $btn .= '<button onclick="editTarget('.$row->id.')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>';
                $btn .= '</div>';
                return $btn;
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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'target_month' => 'required',
            'target_new_calls' => 'required|numeric',
            'target_quotations' => 'required|numeric',
            'target_followups' => 'required|numeric',
            'target_conversion_sqft' => 'required|numeric',
            'target_sales_value' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['assigned_by'] = Auth::id();

        BdoTarget::updateOrCreate(
            ['user_id' => $request->user_id, 'target_month' => $request->target_month],
            $data
        );

        return response()->json(['success' => 'Target saved successfully.']);
    }

    public function edit($id)
    {
        return response()->json(BdoTarget::findOrFail($id));
    }
}
