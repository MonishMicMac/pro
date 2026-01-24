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
    /**
     * Display the Joint Work Report Page
     */
    public function index()
    {
        // Fetch BDOs for the filter dropdown
        $bdos = User::role('BDO')->orderBy('name')->get(['id', 'name']);
        
        // Fetch BDMs for the filter (Optional, if you want to filter by Partner)
        $bdms = User::role('BDM')->orderBy('name')->get(['id', 'name']);

        return view('bdo_joint_work.index', compact('bdos', 'bdms'));
    }

    /**
     * Get Data for DataTable
     */
    public function getData(Request $request)
    {
        // 1. Base Query: Fetch only "Joint Work" visits
        // We assume 'bdm' relationship exists in LeadVisit (belongsTo User as 'bdm_id')
        $query = LeadVisit::with(['user', 'bdm', 'lead', 'account', 'fabricator'])
            ->where('work_type', 'Joint Work')
            ->select('lead_visits.*');

        // --- FILTERS ---

        // Filter by BDO
        if ($request->filled('bdo_id')) {
            $query->where('user_id', $request->bdo_id);
        }

        // Filter by Partner (BDM)
        if ($request->filled('bdm_id')) {
            $query->where('bdm_id', $request->bdm_id);
        }

        // Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('schedule_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('schedule_date', '<=', $request->end_date);
        }

        // --- DATATABLE RESPONSE ---
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? Carbon::parse($row->schedule_date)->format('d M Y') : '-';
            })
            // Column: The BDO (Primary User)
            ->addColumn('bdo_name', function ($row) {
                return $row->user ? $row->user->name : '<span class="text-slate-400 italic">Unknown</span>';
            })
            // Column: The Partner (BDM)
            ->addColumn('partner_name', function ($row) {
                return $row->bdm ? $row->bdm->name : '<span class="text-slate-400 italic">N/A</span>';
            })
            // Column: Client Type (Lead/Account/Fabricator)
            ->addColumn('client_type', function ($row) {
                return match((string)$row->visit_type) {
                    '1' => '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">Account</span>',
                    '2' => '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-bold uppercase">Lead</span>',
                    '3' => '<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase">Fabricator</span>',
                    default => '<span class="text-slate-400">Unknown</span>'
                };
            })
            // Column: Client Name
            ->addColumn('client_name', function ($row) {
                if ($row->visit_type == '1') return $row->account->name ?? '-';
                if ($row->visit_type == '2') return $row->lead->name ?? '-';
                if ($row->visit_type == '3') return $row->fabricator->shop_name ?? '-';
                return '-';
            })
            // Column: Status
            ->addColumn('status', function ($row) {
                // Assuming action '2' or '1' means visited. Adjust based on your specific ID logic.
                // Commonly: 0=Pending, 1/2=Visited
                if ($row->action == '0') {
                    return '<span class="inline-flex items-center gap-1 text-amber-500 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">schedule</span> Pending</span>';
                }
                return '<span class="inline-flex items-center gap-1 text-emerald-600 font-bold text-[10px] uppercase"><span class="material-symbols-outlined text-[14px]">check_circle</span> Visited</span>';
            })
            // Column: Location/Address
            ->addColumn('location', function ($row) {
                if ($row->visit_type == '1') return $row->account->address ?? '-';
                if ($row->visit_type == '2') return $row->lead->site_address ?? '-';
                if ($row->visit_type == '3') return $row->fabricator->address ?? '-';
                return '-';
            })
            ->rawColumns(['bdo_name', 'partner_name', 'client_type', 'status'])
            ->make(true);
    }
}