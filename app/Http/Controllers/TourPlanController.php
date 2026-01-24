<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisit;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TourPlanController extends Controller
{
    public function index()
    {
        $bdos = User::role('BDO')->get(['id', 'name']);
        return view('tourplan.index', compact('bdos'));
    }

    /**
     * 1. Main Table Data with New Filters
     */
    public function getData(Request $request)
    {
        $query = LeadVisit::select(
                'user_id', 
                'schedule_date', 
                'food_allowance', 
                DB::raw('count(*) as total_visits'),
                DB::raw('GROUP_CONCAT(DISTINCT work_type ORDER BY work_type SEPARATOR ", ") as work_modes')
            )
            ->with('user')
            ->groupBy('user_id', 'schedule_date', 'food_allowance');

        // --- FILTERS ---

        // 1. User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 2. Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('schedule_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('schedule_date', '<=', $request->end_date);
        }

        // 3. Location Status (0=Not Planned, 1=Local, 2=Out Station, 3=Meeting, 4=Leave)
        if ($request->filled('location_status')) {
            $query->where('food_allowance', $request->location_status);
        }

        // 4. Work Mode (Individual / Joint Work)
        if ($request->filled('work_mode')) {
            $query->where('work_type', $request->work_mode);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
            })
            ->addColumn('bdo_name', function ($row) {
                return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
            })
            ->editColumn('location_status', function ($row) {
                $statuses = [
                    '0' => ['label' => 'Not Planned', 'class' => 'bg-slate-100 text-slate-500'],
                    '1' => ['label' => 'Local',       'class' => 'bg-emerald-50 text-emerald-600'],
                    '2' => ['label' => 'Out Station', 'class' => 'bg-blue-50 text-blue-600'],
                    '3' => ['label' => 'Meeting',     'class' => 'bg-amber-50 text-amber-600'],
                    '4' => ['label' => 'Leave',       'class' => 'bg-rose-50 text-rose-600'],
                ];
                $s = $statuses[$row->food_allowance] ?? $statuses['0'];
                return "<span class='px-2 py-1 rounded text-[10px] font-bold uppercase border border-transparent {$s['class']}'>{$s['label']}</span>";
            })
            ->addColumn('work_modes', function ($row) {
                return "<span class='text-[10px] font-bold uppercase text-slate-600 tracking-wide'>{$row->work_modes}</span>";
            })
            ->addColumn('action', function ($row) {
                $rawDate = substr($row->schedule_date, 0, 10); 
                return '<button onclick="openTourModal('.$row->user_id.', \''.$rawDate.'\')" 
                        class="px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-[10px] font-bold uppercase transition-all flex items-center gap-1 mx-auto">
                        <span class="material-symbols-outlined text-[14px]">visibility</span> View Plan
                        </button>';
            })
            ->rawColumns(['bdo_name', 'location_status', 'work_modes', 'action'])
            ->make(true);
    }

    /**
     * 2. Modal Data (Added BDM Relationship)
     */
    public function getDetails(Request $request)
    {
        // Added 'bdm' relationship to eager load
        $visits = LeadVisit::with(['lead', 'account', 'fabricator', 'bdm'])
            ->where('user_id', $request->user_id)
            ->where('schedule_date', 'like', $request->date . '%')
            ->get();

        $jointWork = [];
        $individualWork = [];

        foreach ($visits as $row) {
            $clientName = '-';
            $typeLabel  = '-';
            
            if ($row->visit_type == '1') {
                $clientName = $row->account->name ?? 'Unknown Account';
                $typeLabel  = 'Account';
            } elseif ($row->visit_type == '2') {
                $clientName = $row->lead->name ?? 'Unknown Lead';
                $typeLabel  = 'Lead';
            } elseif ($row->visit_type == '3') {
                $clientName = $row->fabricator->shop_name ?? 'Unknown Fabricator';
                $typeLabel  = 'Fabricator';
            }

            $item = [
                'type'      => $typeLabel,
                'name'      => $clientName,
                'status'    => ($row->action == '2') ? 'Visited' : 'Pending',
                'work_type' => $row->work_type,
                // Add BDM Name if it exists
                'bdm_name'  => $row->bdm ? $row->bdm->name : 'N/A' 
            ];

            if ($row->work_type === 'Joint Work') {
                $jointWork[] = $item;
            } else {
                $individualWork[] = $item;
            }
        }

        return response()->json([
            'joint'      => $jointWork,
            'individual' => $individualWork
        ]);
    }
}