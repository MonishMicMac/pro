<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisitBdm; // Using the BDM specific model
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BdmTourPlanController extends Controller
{
    public function index()
    {
        // Fetch users with BDM role
        $bdms = User::role('BDM')->get(['id', 'name']);
        return view('bdm_tourplan.index', compact('bdms'));
    }

    /**
     * 1. Main Table Data (Grouped by Day & User)
     */
    public function getData(Request $request)
    {
        // Querying LeadVisitBdm table
        $query = LeadVisitBdm::select(
                'user_id', 
                'schedule_date', 
                'food_allowance', 
                DB::raw('count(*) as total_visits'),
                DB::raw('GROUP_CONCAT(DISTINCT work_type ORDER BY work_type SEPARATOR ", ") as work_modes')
            )
            ->with('user')
            ->groupBy('user_id', 'schedule_date', 'food_allowance');

        // --- FILTERS ---

        // 1. User (BDM)
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

        // 3. Location Status
        if ($request->filled('location_status')) {
            $query->where('food_allowance', $request->location_status);
        }

        // 4. Work Mode
        if ($request->filled('work_mode')) {
            $query->where('work_type', $request->work_mode);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
            })
            ->addColumn('bdm_name', function ($row) {
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
            ->rawColumns(['bdm_name', 'location_status', 'work_modes', 'action'])
            ->make(true);
    }

    /**
     * 2. Modal Data (Includes BDO relationship for Joint Work)
     */
    public function getDetails(Request $request)
    {
        // Eager load 'bdo' because if BDM does joint work, they do it with a BDO
        $visits = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'bdo'])
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
                'status'    => ($row->action == '1') ? 'Visited' : 'Pending', // Note: Check your DB action logic (1 or 2)
                'work_type' => $row->work_type,
                // For BDM Reports, we show the BDO Name in joint work
                'partner_name' => $row->bdo ? $row->bdo->name : 'N/A' 
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