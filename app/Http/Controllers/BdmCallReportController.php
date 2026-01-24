<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BdmCall;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class BdmCallReportController extends Controller
{
    public function index()
    {
        $bdms = User::role('BDM')->orderBy('name')->get(['id', 'name']);
        return view('bdm_call_reports.index', compact('bdms'));
    }

    public function getData(Request $request)
    {
        $query = BdmCall::with(['bdm', 'callable'])
            ->select('bdm_calls.*');

        // --- FILTERS ---
        if ($request->filled('bdm_id')) {
            $query->where('user_id', $request->bdm_id);
        }
        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('called_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('called_at', function ($row) {
                return $row->called_at ? $row->called_at->format('d M Y h:i A') : '-';
            })
            ->addColumn('bdm_name', function ($row) {
                return $row->bdm->name ?? '<span class="text-slate-400 italic">Unknown</span>';
            })
            ->addColumn('client_type', function ($row) {
                $type = class_basename($row->callable_type); 
                return match($type) {
                    'Account' => '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">Account</span>',
                    'Lead' => '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-bold uppercase">Lead</span>',
                    'Fabricator' => '<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase">Fabricator</span>',
                    default => '<span class="text-slate-400">Unknown</span>'
                };
            })
            ->addColumn('client_name', function ($row) {
                if (!$row->callable) return '<span class="text-slate-400">-</span>';
                if ($row->callable_type == 'App\Models\Fabricator') {
                    return $row->callable->shop_name ?? $row->callable->name;
                }
                return $row->callable->name ?? '-';
            })
            ->editColumn('duration', function ($row) {
                return '<span class="font-mono text-slate-600 font-bold">' . ($row->duration ?? '00:00') . '</span>';
            })
            // --- FIX 1: UPDATED STATUS ICONS & HTML ---
            ->editColumn('call_status', function ($row) {
                $status = strtolower($row->call_status);
                
                if (str_contains($status, 'connect')) {
                    return '<div class="inline-flex items-center gap-1.5 text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded-md border border-emerald-100"><span class="material-symbols-outlined text-[16px]">call</span> Connected</div>';
                } 
                elseif (str_contains($status, 'busy')) {
                    // Changed icon to 'phone_paused' for better compatibility
                    return '<div class="inline-flex items-center gap-1.5 text-amber-600 font-bold bg-amber-50 px-2 py-1 rounded-md border border-amber-100"><span class="material-symbols-outlined text-[16px]">phone_paused</span> Busy</div>';
                } 
                else {
                    return '<div class="inline-flex items-center gap-1.5 text-red-500 font-bold bg-red-50 px-2 py-1 rounded-md border border-red-100"><span class="material-symbols-outlined text-[16px]">phone_missed</span> No Answer</div>';
                }
            })
            // --- FIX 2: REMOVED LIMIT ON REMARKS ---
            ->editColumn('remarks', function ($row) {
                return $row->remarks ?? '-'; 
            })
            ->rawColumns(['bdm_name', 'client_type', 'client_name', 'duration', 'call_status', 'remarks'])
            ->make(true);
    }
}