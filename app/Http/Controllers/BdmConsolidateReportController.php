<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisitBdm; // Using BDM Model
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BdmConsolidateReportController extends Controller
{
    public function index()
    {
        // Fetch all BDMs for the filter dropdown
        $bdms = User::role('BDM')->get(['id', 'name']);
        return view('bdm_consolidate_report.index', compact('bdms'));
    }

    /**
     * Get Consolidated Data for BDMs
     */
    public function getData(Request $request)
    {
        $query = LeadVisitBdm::select(
                'user_id', 
                'schedule_date',
                
                // --- 1. PLANNED METRICS ---
                // Total Planned
                DB::raw("SUM(CASE WHEN type = 'planned' THEN 1 ELSE 0 END) as planned_total"),
                // Planned & Visited (Action = '1' is Completed for BDMs)
                DB::raw("SUM(CASE WHEN type = 'planned' AND action = '1' THEN 1 ELSE 0 END) as planned_visited"),
                // Planned & Missed (Action = '0' is Pending)
                DB::raw("SUM(CASE WHEN type = 'planned' AND action = '0' THEN 1 ELSE 0 END) as planned_missed"),

                // --- 2. UNPLANNED METRICS ---
                // Total Unplanned
                DB::raw("SUM(CASE WHEN type = 'unplanned' THEN 1 ELSE 0 END) as unplanned_total"),
                // Unplanned & Visited
                DB::raw("SUM(CASE WHEN type = 'unplanned' AND action = '1' THEN 1 ELSE 0 END) as unplanned_visited"),
                // Unplanned & Missed (Rare, but possible if created and not checked out)
                DB::raw("SUM(CASE WHEN type = 'unplanned' AND action = '0' THEN 1 ELSE 0 END) as unplanned_missed"),

                // --- 3. WORK TYPE COUNTS (For Analysis) ---
                // Total Individual Work (Planned + Unplanned)
                DB::raw("SUM(CASE WHEN work_type = 'Individual' THEN 1 ELSE 0 END) as individual_count"),
                // Total Joint Work (Planned + Unplanned)
                DB::raw("SUM(CASE WHEN work_type = 'Joint Work' THEN 1 ELSE 0 END) as joint_count")
            )
            ->with('user')
            ->groupBy('user_id', 'schedule_date');

        // --- FILTERS ---
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('schedule_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('schedule_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
            })
            ->addColumn('bdm_name', function ($row) {
                return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
            })
            
            // Calculated Totals
            ->addColumn('total_visit_count', function ($row) {
                return $row->planned_visited + $row->unplanned_visited;
            })
            ->addColumn('total_missed_count', function ($row) {
                return $row->planned_missed + $row->unplanned_missed;
            })
            
            ->rawColumns(['bdm_name'])
            ->make(true);
    }
}