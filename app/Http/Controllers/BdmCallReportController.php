<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BdmCall;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\UserMapping;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BdmCallReportController extends Controller
{
    /**
     * Display the Call Report index with initial location data.
     * ZSM and BDM lists start empty and are populated via AJAX.
     */
    public function index()
    {
        // 1. Load Locations
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $districts = District::orderBy('district_name')->get();
        $cities = City::orderBy('city_name')->get();

        // 2. Initialize Empty Roles (Loaded via AJAX based on selection)
        $zsms = collect();
        $bdms = collect();

        return view('bdm_call_reports.index', compact('zsms', 'bdms', 'zones', 'states', 'districts', 'cities'));
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
                    'bdms'   => User::role('BDM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
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

    /**
     * Get Data for DataTable with Filters
     */
    public function getData(Request $request)
    {
        $query = BdmCall::select('bdm_calls.*')
            ->leftJoin('users', 'bdm_calls.user_id', '=', 'users.id') // Join for location filtering
            ->with(['bdm', 'callable']);

        // --- FILTERS ---
        
        // Geographic Filters
        if ($request->filled('zone_id')) $query->where('users.zone_id', $request->zone_id);
        if ($request->filled('state_id')) $query->where('users.state_id', $request->state_id);
        if ($request->filled('district_id')) $query->where('users.district_id', $request->district_id);
        if ($request->filled('city_id')) $query->where('users.city_id', $request->city_id);

        // Hierarchy Filter: ZSM
        if ($request->filled('zsm_id')) {
            $bdmIds = UserMapping::where('zsm_id', $request->zsm_id)->distinct()->pluck('bdm_id');
            $query->whereIn('bdm_calls.user_id', $bdmIds);
        }

        // BDM Filter
        if ($request->filled('bdm_id')) {
            $query->where('bdm_calls.user_id', $request->bdm_id);
        }

        // Call Status Filter
        if ($request->filled('call_status')) {
            $query->where('bdm_calls.call_status', $request->call_status);
        }

        // Date Range Filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('bdm_calls.called_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('called_at', function ($row) {
                return $row->called_at ? Carbon::parse($row->called_at)->format('d M Y h:i A') : '-';
            })
            ->addColumn('bdm_name', function ($row) {
                return $row->bdm->name ?? '<span class="text-slate-400 italic">Unknown</span>';
            })
            ->addColumn('client_type', function ($row) {
                $type = class_basename($row->callable_type); 
                return match($type) {
                    'Account' => '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">Account</span>',
                    'Lead' => '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-bold uppercase">Lead</span>',
                    'Fabricator' => '<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase">Fabricator</span>',
                    'User' => '<span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase">BDO</span>',
                    default => '<span class="text-slate-400">Unknown</span>'
                };
            })
            ->addColumn('client_name', function ($row) {
                if (!$row->callable) return '<span class="text-slate-400">-</span>';
                if ($row->callable_type == 'App\Models\Fabricator') {
                    return $row->callable->shop_name ?? $row->callable->name;
                }
                return $row->callable->name ?? '-';
            })
            ->editColumn('duration', function ($row) {
                return '<span class="font-mono text-slate-600 font-bold">' . ($row->duration ?? '00:00') . '</span>';
            })
            ->editColumn('call_status', function ($row) {
                $status = strtolower($row->call_status);
                if (str_contains($status, 'connect')) return '<div class="inline-flex items-center gap-1.5 text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded-md border border-emerald-100"><span class="material-symbols-outlined text-[16px]">call</span> Connected</div>';
                elseif (str_contains($status, 'busy')) return '<div class="inline-flex items-center gap-1.5 text-amber-600 font-bold bg-amber-50 px-2 py-1 rounded-md border border-amber-100"><span class="material-symbols-outlined text-[16px]">phone_paused</span> Busy</div>';
                else return '<div class="inline-flex items-center gap-1.5 text-red-500 font-bold bg-red-50 px-2 py-1 rounded-md border border-red-100"><span class="material-symbols-outlined text-[16px]">phone_missed</span> No Answer</div>';
            })
            ->editColumn('remarks', function ($row) {
                return $row->remarks ?? '-'; 
            })
            ->rawColumns(['bdm_name', 'client_type', 'client_name', 'duration', 'call_status', 'remarks'])
            ->make(true);
    }
}