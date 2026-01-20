<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District; 
use App\Models\City;
use App\Models\Pincode;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    // public function index()
    // {
    //     $users = \App\Models\User::orderBy('name')->get();
    //     return view('leads.index', compact('users'));
    // }

public function index()
    {
        $authUser = Auth::user();
        
        // Fetch users and zones
        $users = User::orderBy('name')->where('action', '0')->get();
        
        // If user has a zone assigned, only show that zone in the dropdown
        $zones = Zone::orderBy('name')->where('action', '0')
            ->when($authUser->zone_id, function($q) use ($authUser) {
                return $q->where('id', $authUser->zone_id);
            })->get();

        return view('leads.index', compact('users', 'zones'));
    }

    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']);
            case 'state':
                return District::where('state_id', $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            default:
                return response()->json([]);
        }
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $authUser = Auth::user();
            
            $query = Lead::with('assignedUser')
                ->leftJoin('users', 'leads.user_id', '=', 'users.id')
                ->select('leads.*');

            // --- AUTO GEOGRAPHIC SECURITY ---
            // Force filter based on Logged-in User profile
            if ($authUser->zone_id) {
                $query->where('users.zone_id', $authUser->zone_id);
            }
            if ($authUser->state_id) {
                $query->where('users.state_id', $authUser->state_id);
            }
            if ($authUser->district_id) {
                $query->where('users.district_id', $authUser->district_id);
            }

            // --- MANUAL FILTERS ---
            // Only apply manual location filter if user is Admin (no pre-assigned location)
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

            // Stage & User Filters
            if ($request->filled('lead_stage')) {
                $query->where('leads.lead_stage', $request->lead_stage);
            }
            if ($request->filled('user_id')) {
                $query->where('leads.user_id', $request->user_id);
            }

            // Date Filters
            if ($request->filled('from_date')) {
                $query->whereDate('leads.created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('leads.created_at', '<=', $request->to_date);
            }

            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d-m-Y h:i A') : '-';
                })
                ->addColumn('assigned_to', function ($row) {
                    return $row->assignedUser ? $row->assignedUser->name : 'Unassigned';
                })
                ->editColumn('lead_stage', function($row) {
                    $stages = [
                        0 => ['Site Identification', 'bg-gray-100 text-gray-600'],
                        1 => ['Intro', 'bg-blue-50 text-blue-600'],
                        2 => ['FollowUp', 'bg-indigo-50 text-indigo-600'],
                        3 => ['Quote Pending', 'bg-amber-50 text-amber-600'],
                        4 => ['Quote Sent', 'bg-purple-50 text-purple-600'],
                        5 => ['Won', 'bg-green-50 text-green-600'],
                        6 => ['Site Handed Over', 'bg-emerald-100 text-emerald-700'],
                        7 => ['Lost', 'bg-rose-50 text-rose-600']
                    ];
                    $stage = $stages[$row->lead_stage] ?? ['Unknown', 'bg-slate-100 text-slate-500'];
                    return '<span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider '.$stage[1].'">'.$stage[0].'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('leads.show', $row->id) . '" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </a>';
                })
                ->rawColumns(['action', 'lead_stage'])
                ->make(true);
        }
    }


    /**
     * Display the specified lead.
     */
    public function show($id)
    {
        $lead = Lead::with(['measurements', 'fabricatorRequests.fabricator', 'assignedUser', 'creator', 'handoverPhotos', 'images'])->findOrFail($id);
        return view('leads.show', compact('lead'));
    }
}
