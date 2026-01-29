<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Fabricator;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\User;
use App\Models\UserMapping;

class RegionalFootprintController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();

        // If user has a zone assigned, only show that zone in the dropdown
        $zones = Zone::orderBy('name')->where('action', '0')
            ->when($authUser->zone_id, function ($q) use ($authUser) {
                return $q->where('id', $authUser->zone_id);
            })->get();

        return view('marketing.regional_footprint', compact('zones'));
    }

    public function getData(Request $request)
    {
        $authUser = Auth::user();

        $query = Lead::with('assignedUser')
            ->leftJoin('users', 'leads.user_id', '=', 'users.id')
            ->select('leads.*')
            ->whereNotNull('leads.latitude')
            ->whereNotNull('leads.longitude');

        // --- AUTO GEOGRAPHIC SECURITY ---
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

        // Manager & BDO Filters
        if ($request->filled('zsm_id')) {
            $zsmId = $request->zsm_id;
            $mappingIds = UserMapping::where('zsm_id', $zsmId)->get(['bdm_id', 'bdo_id']);
            $subIds = $mappingIds->pluck('bdm_id')->merge($mappingIds->pluck('bdo_id'))->unique()->filter()->toArray();
            $query->whereIn('leads.user_id', array_merge([$zsmId], $subIds));
        }
        if ($request->filled('manager_id')) {
            $managerId = $request->manager_id;
            $subIds = UserMapping::where('bdm_id', $managerId)->pluck('bdo_id')->unique()->filter()->toArray();
            $query->whereIn('leads.user_id', array_merge([$managerId], $subIds));
        }
        if ($request->filled('bdo_id')) {
            $query->where('leads.user_id', $request->bdo_id);
        }

        // Date Filters
        if ($request->filled('from_date')) {
            $query->whereDate('leads.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('leads.created_at', '<=', $request->to_date);
        }

        $leads = $query->get();

        $stages = [
            0 => ['Site Identification', '#6b7280'], // gray-500
            1 => ['Intro', '#2563eb'], // blue-600
            2 => ['FollowUp', '#4f46e5'], // indigo-600
            3 => ['Quote Pending', '#d97706'], // amber-600
            4 => ['Quote Sent', '#9333ea'], // purple-600
            5 => ['Won', '#16a34a'], // green-600
            6 => ['Site Handed Over', '#059669'], // emerald-600
            7 => ['Lost', '#e11d48'] // rose-600
        ];

        $leadMarkers = $leads->map(function ($lead) use ($stages) {
            $stageInfo = $stages[$lead->lead_stage] ?? ['Unknown', '#94a3b8'];
            $color = $stageInfo[1];

            return [
                'type' => 'lead',
                'name' => $lead->name,
                'lat' => (float)$lead->latitude,
                'lng' => (float)$lead->longitude,
                'color' => $color,
                'details' => "Lead: {$lead->name}<br>City: {$lead->city}<br>Stage: {$stageInfo[0]}"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $leadMarkers
        ]);
    }
}
