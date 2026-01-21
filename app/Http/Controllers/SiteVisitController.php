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
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $userId = $request->user_id;
        $statusFilter = $request->status; // 'Visited', 'Pending'
        $typeFilter = $request->type; // 'planned', 'unplanned'
        $visitTypeFilter = $request->visit_type; // 1, 2, 3
        $foodAllowanceFilter = $request->food_allowance; // 1, 2
        $workTypeFilter = $request->work_type; // 'Individual', 'Joint Work'

        $query = LeadVisit::with(['user', 'lead', 'account', 'fabricator'])
            ->select('lead_visits.*');

        // Apply Filters
        if ($fromDate && $toDate) {
            $query->whereBetween('schedule_date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->whereDate('schedule_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('schedule_date', '<=', $toDate);
        } else {
            // Default to today if no date range is provided
            $query->whereDate('schedule_date', Carbon::today()->toDateString());
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        if ($visitTypeFilter) {
            $query->where('visit_type', $visitTypeFilter);
        }

        if ($foodAllowanceFilter) {
            $query->where('food_allowance', $foodAllowanceFilter);
        }

        if ($workTypeFilter) {
            $query->where('work_type', $workTypeFilter);
        }

        if ($statusFilter) {
            if ($statusFilter === 'Visited') {
                $query->whereNotNull('intime_time');
            } elseif ($statusFilter === 'Pending') {
                $query->whereNull('intime_time');
            }
        }

        return DataTables::of($query)
            ->addColumn('date', function ($row) {
                return $row->schedule_date;
            })
            ->addColumn('entity_name', function ($row) {
                if ($row->visit_type == 1) {
                    return $row->account->name ?? 'N/A';
                } elseif ($row->visit_type == 2) {
                    return $row->lead->site_owner_name ?? 'N/A';
                } elseif ($row->visit_type == 3) {
                    return $row->fabricator->name ?? 'N/A';
                }
                return 'N/A';
            })
            ->editColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : 'N/A';
            })
            ->addColumn('category', function ($row) {
                $categories = [1 => 'Account', 2 => 'Lead', 3 => 'Fabricator'];
                return $categories[$row->visit_type] ?? 'N/A';
            })
            ->addColumn('type_label', function ($row) {
                return ucfirst($row->type);
            })
            ->addColumn('status_label', function ($row) {
                return $row->intime_time ? 'Visited' : 'Pending';
            })
            ->addColumn('check_in', function ($row) {
                return $row->intime_time ? Carbon::parse($row->intime_time)->format('h:i A') : '-';
            })
            ->addColumn('check_out', function ($row) {
                return $row->out_time ? Carbon::parse($row->out_time)->format('h:i A') : '-';
            })
            ->addColumn('food_label', function ($row) {
                $labels = [1 => 'Local Station', 2 => 'Out Station'];
                return $labels[$row->food_allowance] ?? '-';
            })
            ->addColumn('image_url', function ($row) {
                return $row->image ? asset('storage/' . $row->image) : null;
            })
            ->make(true);
    }
}
