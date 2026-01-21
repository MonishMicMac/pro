<?php

namespace App\Http\Controllers;

use App\Models\LeadVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SiteVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('site_visits.index', compact('users'));
    }

    /**
     * Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = LeadVisit::with(['user', 'lead'])->select('lead_visits.*');

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('intime_time', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('intime_time', '<=', $request->to_date);
            }

            return DataTables::of($query)
            ->addColumn('visit_date', function ($row) { // MATCHED TO JS
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
            ->rawColumns(['image', 'map_data']) // Add this to ensure HTML renders
            ->make(true);
        }
    }

    /**
     * Display the site visit status report.
     */
    public function report()
    {
        $users = User::orderBy('name')->where('action', '0')->get();
        return view('site_visits.report', compact('users'));
    }

    /**
     * Process site visit report datatables ajax request.
     */
    public function reportData(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::today()->toDateString();
        $toDate = $request->to_date ?? Carbon::today()->toDateString();
        $userId = $request->user_id;
        $statusFilter = $request->status; // 'Visited', 'Pending'

        // 1. Get Scheduled Leads for the period
        $scheduledQuery = \App\Models\Lead::with(['assignedUser'])
            ->whereBetween('follow_up_date', [$fromDate, $toDate]);
        
        if ($userId) {
            $scheduledQuery->where('user_id', $userId);
        }

        $scheduledItems = $scheduledQuery->get()->map(function($lead) {
            // Find if there was a visit for this lead on its scheduled date
            $visit = LeadVisit::where('lead_id', $lead->id)
                ->whereDate('created_at', $lead->follow_up_date)
                ->first();

            return [
                'id' => $lead->id,
                'lead_name' => $lead->name,
                'user_name' => $lead->assignedUser->name ?? 'N/A',
                'city' => $lead->city,
                'date' => $lead->follow_up_date,
                'type' => 'Scheduled',
                'status' => $visit ? 'Visited' : 'Pending',
                'check_in' => $visit ? Carbon::parse($visit->intime_time)->format('h:i A') : '-',
                'check_out' => ($visit && $visit->out_time) ? Carbon::parse($visit->out_time)->format('h:i A') : '-',
                'remarks' => $visit->remarks ?? '-',
                'visit_id' => $visit->id ?? null
            ];
        });

        // 2. Get UN-scheduled visits for the period
        $unscheduledQuery = LeadVisit::with(['lead', 'user'])
            ->whereBetween(\DB::raw('DATE(created_at)'), [$fromDate, $toDate])
            ->where(function($q) {
                $q->whereNull('lead_id') // Should not happen but safe
                  ->orWhereHas('lead', function($l) {
                      $l->whereRaw('DATE(leads.follow_up_date) != DATE(lead_visits.created_at)');
                  });
            });

        if ($userId) {
            $unscheduledQuery->where('user_id', $userId);
        }

        $unscheduledItems = $unscheduledQuery->get()->map(function($visit) {
            return [
                'id' => $visit->lead->id ?? 0,
                'lead_name' => $visit->lead->name ?? 'Direct Visit',
                'user_name' => $visit->user->name ?? 'N/A',
                'city' => $visit->lead->city ?? '-',
                'date' => Carbon::parse($visit->created_at)->toDateString(),
                'type' => 'Unscheduled',
                'status' => 'Visited',
                'check_in' => Carbon::parse($visit->intime_time)->format('h:i A'),
                'check_out' => $visit->out_time ? Carbon::parse($visit->out_time)->format('h:i A') : '-',
                'remarks' => $visit->remarks ?? '-',
                'visit_id' => $visit->id
            ];
        });

        $combined = $scheduledItems->concat($unscheduledItems);

        // Filter by status if requested
        if ($statusFilter) {
            $combined = $combined->where('status', $statusFilter);
        }

        return DataTables::of($combined)
            ->make(true);
    }
}
