<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadVisit;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class BdoJointWorkReportController extends Controller
{
    public function index()
    {
        $bdos = User::role('BDO')->orderBy('name')->get(['id', 'name']);
        // Fetch BDMs (Managers)
        $bdms = User::role('BDM')->orderBy('name')->get(['id', 'name']);

        return view('bdo_joint_work.index', compact('bdos', 'bdms'));
    }

    public function getData(Request $request)
    {
        // Eager load relationships
        $query = LeadVisit::with(['user', 'bdm', 'lead', 'account', 'fabricator', 'jointWorkRequest.bdm'])
            ->select('lead_visits.*');

        // CORE LOGIC: Show if 'Joint Work' OR has a Request
        $query->where(function($q) {
            $q->where('work_type', 'Joint Work')
              ->orWhereHas('jointWorkRequest');
        });

        // --- FILTERS ---
        if ($request->filled('bdo_id')) {
            $query->where('user_id', $request->bdo_id);
        }

        if ($request->filled('bdm_id')) {
            $query->where(function($q) use ($request) {
                $q->where('bdm_id', $request->bdm_id) // Direct Assignment
                  ->orWhereHas('jointWorkRequest', function($sq) use ($request) {
                      $sq->where('bdm_id', $request->bdm_id); // Requested Manager
                  });
            });
        }

        // Date Range
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
                return $row->user ? $row->user->name : '-';
            })
            // RENAMED COLUMN: manager_name
            ->addColumn('manager_name', function ($row) {
                // 1. If BDM is already assigned in the main table
                if ($row->bdm) {
                    return $row->bdm->name;
                }
                // 2. If not assigned, show who was Requested
                if ($row->jointWorkRequest && $row->jointWorkRequest->bdm) {
                    return $row->jointWorkRequest->bdm->name . ' <span class="text-[9px] text-slate-400">(Req)</span>';
                }
                return '<span class="text-slate-400 italic">N/A</span>';
            })
            ->addColumn('client_type', function ($row) {
                return match((string)$row->visit_type) {
                    '1' => '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">Account</span>',
                    '2' => '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-bold uppercase">Lead</span>',
                    '3' => '<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase">Fabricator</span>',
                    default => '<span class="text-slate-400">Unknown</span>'
                };
            })
            ->addColumn('client_name', function ($row) {
                if ($row->visit_type == '1') return $row->account->name ?? '-';
                if ($row->visit_type == '2') return $row->lead->name ?? '-';
                if ($row->visit_type == '3') return $row->fabricator->shop_name ?? '-';
                return '-';
            })
            // Request Status Column
            ->addColumn('request_status', function ($row) {
                // A. Has a specific Request record
                if ($row->jointWorkRequest) {
                    $status = $row->jointWorkRequest->status; 
                    if ($status == '0') return '<span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold uppercase border border-amber-200">Pending</span>';
                    if ($status == '1') return '<span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase border border-emerald-200">Approved</span>';
                    if ($status == '2') return '<div class="flex flex-col"><span class="px-2 py-1 rounded-lg bg-red-100 text-red-700 text-[10px] font-bold uppercase border border-red-200 w-fit">Declined</span><span class="text-[9px] text-slate-400 font-semibold mt-0.5">Individual Work</span></div>';
                }

                // B. No Request record, but marked as Joint Work in main table
                if ($row->work_type == 'Joint Work') {
                    return '<span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase border border-slate-200">Direct Joint</span>';
                }

                return '<span class="text-slate-300 text-[10px]">Individual</span>';
            })
            ->addColumn('visit_status', function ($row) {
                if ($row->action == '0') return '<span class="inline-flex items-center gap-1 text-slate-400 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">schedule</span> Pending Visit</span>';
                return '<span class="inline-flex items-center gap-1 text-blue-600 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">check_circle</span> Completed</span>';
            })
            ->addColumn('location', function ($row) {
                if ($row->visit_type == '1') return $row->account->address ?? '-';
                if ($row->visit_type == '2') return $row->lead->site_address ?? '-';
                if ($row->visit_type == '3') return $row->fabricator->address ?? '-';
                return '-';
            })
            ->rawColumns(['bdo_name', 'manager_name', 'client_type', 'request_status', 'visit_status'])
            ->make(true);
    }
}