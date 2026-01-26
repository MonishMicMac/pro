<?php

namespace App\Http\Controllers;

use App\Models\LeadVisit;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SiteVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $authUser = Auth::user();

        // 1. Load Zones (Restricted if user has zone_id)
        $zones = Zone::where('action', '0')->orderBy('name')
            ->when($authUser->zone_id, fn($q) => $q->where('id', $authUser->zone_id))
            ->get();

        // 2. Load States (Restricted if user has state_id OR belongs to a specific zone)
        $states = State::orderBy('name')
            ->when($authUser->state_id, fn($q) => $q->where('id', $authUser->state_id))
            ->when($authUser->zone_id && !$authUser->state_id, fn($q) => $q->where('zone_id', $authUser->zone_id))
            ->get();

        // 3. Load Districts (Restricted if user has district_id OR belongs to a specific state)
        $districts = District::orderBy('district_name')
            ->when($authUser->district_id, fn($q) => $q->where('id', $authUser->district_id))
            ->when($authUser->state_id && !$authUser->district_id, fn($q) => $q->where('state_id', $authUser->state_id))
            ->get();

        // 4. Load Cities (Restricted if user has city_id OR belongs to a specific district)
        $cities = City::orderBy('city_name')
            ->when($authUser->city_id, fn($q) => $q->where('id', $authUser->city_id))
            ->when($authUser->district_id && !$authUser->city_id, fn($q) => $q->where('district_id', $authUser->district_id))
            ->get();

        // Load Users
        $users = User::orderBy('name')->where('action', '0')->get();

        return view('site_visits.index', compact('users', 'zones', 'states', 'districts', 'cities'));
    }

/**
     * AJAX: Get Cascading Location Data & Users based on Zone
     */
    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    // Fetch Users in this Zone, EXCLUDING Telecallers
                    'users' => User::where('zone_id', $id)
                        ->whereDoesntHave('roles', fn($q) => $q->where('name', 'Telecaller'))
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
     * Process datatables ajax request.
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $authUser = Auth::user();

            $query = LeadVisit::with(['user', 'lead'])
                ->leftJoin('users', 'lead_visits.user_id', '=', 'users.id')
                ->select('lead_visits.*');

            // --- AUTO GEOGRAPHIC SECURITY ---
            if ($authUser->zone_id) $query->where('users.zone_id', $authUser->zone_id);
            if ($authUser->state_id) $query->where('users.state_id', $authUser->state_id);
            if ($authUser->district_id) $query->where('users.district_id', $authUser->district_id);

            // --- MANUAL FILTERS ---
            
            // Location
            if (!$authUser->zone_id && $request->filled('zone_id')) {
                $query->where('users.zone_id', $request->zone_id);
            }
            if (!$authUser->state_id && $request->filled('state_id')) {
                $query->where('users.state_id', $request->state_id);
            }
            if (!$authUser->district_id && $request->filled('district_id')) {
                $query->where('users.district_id', $request->district_id);
            }
            if ($request->filled('city_id')) {
                $query->where('users.city_id', $request->city_id);
            }

            // Standard Filters
            if ($request->filled('user_id')) {
                $query->where('lead_visits.user_id', $request->user_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('lead_visits.intime_time', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('lead_visits.intime_time', '<=', $request->to_date);
            }

            return DataTables::of($query)
                ->addColumn('visit_date', function ($row) {
                    return $row->intime_time ? Carbon::parse($row->intime_time)->format('Y-m-d') : '-';
                })
                ->editColumn('user_name', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->editColumn('lead_name', function ($row) {
                    return $row->lead ? $row->lead->name : 'N/A';
                })
                ->editColumn('intime_time', function ($row) {
                    return $row->intime_time ? Carbon::parse($row->intime_time)->format('h:i A') : '-';
                })
                ->editColumn('out_time', function ($row) {
                    return $row->out_time ? Carbon::parse($row->out_time)->format('h:i A') : '-';
                })
                ->addColumn('image', function ($row) {
                    return $row->image ? asset('storage/' . $row->image) : null;
                })
                ->addColumn('map_data', function ($row) {
                    return [
                        'lat' => $row->inlat,
                        'lng' => $row->inlong,
                        'check_out_lat' => $row->outlat,
                        'check_out_lng' => $row->outlong
                    ];
                })
                ->rawColumns(['image', 'map_data'])
                ->make(true);
        }
    }
}