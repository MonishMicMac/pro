<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lead;
use App\Models\LeadVisit;
use App\Models\City;
use App\Models\UserMapping;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BdoPerformanceReportController extends Controller
{
    public function index()
    {
        $zones = \App\Models\Zone::where('action', '0')->get();
        return view('reports.bdo_performance_report', compact('zones'));
    }

    public function data(Request $request)
    {
        $query = User::whereHas('roles', function($q) {
            $q->where('id', 3);
        })->where('action', '0'); // Active users

        if ($request->has('zone_id') && $request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }

        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-t'));

        // Pre-fetch cities to avoid N+1
        $cities = City::pluck('city_name', 'id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('base_city', function ($user) use ($cities) {
                return $cities[$user->city_id] ?? '-';
            })
            ->editColumn('doj', function ($user) {
                return $user->doj ? Carbon::parse($user->doj)->format('d-M-y') : '-';
            })
            ->addColumn('ho_lead_assigned', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->whereIn('lead_source', ['HO', 'Digital', 'Corporate']) // Adjust based on actual values
                    ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                    ->count();
            })
            ->addColumn('own_lead_generation', function ($user) use ($fromDate, $toDate) {
                return Lead::where('created_by', $user->id)
                    ->where('lead_source', 'Own') // Adjust based on actual values, or check if created_by matches
                    ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                    ->count();
            })
            ->addColumn('intro_meeting', function ($user) use ($fromDate, $toDate) {
                return LeadVisit::where('user_id', $user->id)
                    ->where('lead_stage', 1) // 1 = Intro
                    ->whereBetween('visit_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('follow_up_meeting', function ($user) use ($fromDate, $toDate) {
                return LeadVisit::where('user_id', $user->id)
                    ->where('lead_stage', 2) // 2 = FollowUp
                    ->whereBetween('visit_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('total_meetings', function ($user) use ($fromDate, $toDate) {
                return LeadVisit::where('user_id', $user->id)
                    ->whereIn('lead_stage', [1, 2])
                    ->whereBetween('visit_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('quote_given', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where(function($q) {
                        $q->where('lead_stage', '3')
                          ->orWhereHas('fabricatorRequests'); // Or check fabricator requests
                    })
                    ->whereBetween('updated_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']) // Use updated_at for stage change approx
                    ->count();
            })
            ->addColumn('quote_total_sqft', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                     ->where(function($q) {
                        $q->where('lead_stage', '3')
                          ->orWhereHas('fabricatorRequests');
                    })
                    ->whereBetween('updated_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                    ->sum('total_required_area_sqft');
            })
            ->addColumn('won_quote', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where('lead_stage', '5')
                    ->whereBetween('won_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('won_white', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where('lead_stage', '5')
                    ->where('color_preference', 'White') // Verify logic
                    ->whereBetween('won_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('won_laminate', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where('lead_stage', '5')
                    ->where('color_preference', 'Laminate') // Verify logic
                    ->whereBetween('won_date', [$fromDate, $toDate])
                    ->count();
            })
            ->addColumn('won_total_sqft', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where('lead_stage', '5')
                    ->whereBetween('won_date', [$fromDate, $toDate])
                    ->sum('total_required_area_sqft');
            })
             ->addColumn('ho_lead_won', function ($user) use ($fromDate, $toDate) {
                return Lead::where('user_id', $user->id)
                    ->where('lead_stage', '5')
                    ->whereIn('lead_source', ['HO', 'Digital', 'Corporate'])
                    ->whereBetween('won_date', [$fromDate, $toDate])
                    ->count();
            })
             ->addColumn('pipeline_sqft', function ($user) {
                return Lead::where('user_id', $user->id)
                    ->whereNotIn('lead_stage', ['5', '7'])
                    ->sum('total_required_area_sqft');
            })
            ->addColumn('working_days', function ($user) use ($fromDate, $toDate) {
                 // Simple calculation excluding Sundays
                $start = Carbon::parse($fromDate);
                $end = Carbon::parse($toDate);
                $days = $start->diffInDaysFiltered(function(Carbon $date) {
                    return !$date->isSunday();
                }, $end);
                return $days + 1; // inclusive
            })
            ->addColumn('google_review', function ($user) {
                return '-'; // Not implemented yet
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
