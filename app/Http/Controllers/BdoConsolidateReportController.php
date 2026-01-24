<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisit; // Using BDO Model
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BdoConsolidateReportController extends Controller
{
    public function index()
    {
        $bdos = User::role('BDO')->get(['id', 'name']);
        return view('bdo_consolidate_report.index', compact('bdos'));
    }

    /**
     * Get Consolidated Data with Detailed Breakdown + Work Types
     */
    public function getData(Request $request)
    {
        $query = LeadVisit::select(
                'user_id', 
                'schedule_date',
                
                // --- 1. PLANNED METRICS ---
                DB::raw("SUM(CASE WHEN type = 'planned' THEN 1 ELSE 0 END) as planned_total"),
                // Visited: For BDOs, action '2' denotes Visited
                DB::raw("SUM(CASE WHEN type = 'planned' AND action = '2' THEN 1 ELSE 0 END) as planned_visited"),
                // Missed: Planned but NOT visited
                DB::raw("SUM(CASE WHEN type = 'planned' AND action != '2' THEN 1 ELSE 0 END) as planned_missed"),

                // --- 2. UNPLANNED METRICS ---
                DB::raw("SUM(CASE WHEN type = 'unplanned' THEN 1 ELSE 0 END) as unplanned_total"),
                DB::raw("SUM(CASE WHEN type = 'unplanned' AND action = '2' THEN 1 ELSE 0 END) as unplanned_visited"),
                DB::raw("SUM(CASE WHEN type = 'unplanned' AND action != '2' THEN 1 ELSE 0 END) as unplanned_missed"),

                // --- 3. WORK TYPE COUNTS (New Addition) ---
                // Counts ALL visits (Planned + Unplanned) by work type
                DB::raw("SUM(CASE WHEN work_type = 'Individual' THEN 1 ELSE 0 END) as individual_count"),
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
            ->addColumn('bdo_name', function ($row) {
                return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
            })
            
            // 4. CALCULATED TOTAL COLUMNS
            ->addColumn('total_visit_count', function ($row) {
                return $row->planned_visited + $row->unplanned_visited;
            })
            ->addColumn('total_missed_count', function ($row) {
                return $row->planned_missed + $row->unplanned_missed;
            })
            
            ->rawColumns(['bdo_name'])
            ->make(true);
    }
}