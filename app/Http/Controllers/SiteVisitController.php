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
}
