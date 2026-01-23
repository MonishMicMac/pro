<?php

namespace App\Http\Controllers;

use App\Models\DigitalMarketingLead;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ProspectController extends Controller
{
    public function index()
    {
        return view('reports.prospect-report');
    }

    public function data(Request $request)
    {
        $query = DigitalMarketingLead::with('telecaller'); // your online leads

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }

        return DataTables::of($query)

            ->addColumn('telecaller', function ($r) {
                return optional($r->telecaller)->name ?? '-';
            })

            ->editColumn('lead_stage', function ($r) {
                $stages = [
                    0 => 'Site Identification',
                    1 => 'Intro',
                    2 => 'FollowUp',
                    3 => 'Quote Pending',
                    4 => 'Quote Sent',
                    5 => 'Won',
                    6 => 'Site Handed Over',
                    7 => 'Lost'
                ];
                return $stages[$r->lead_stage] ?? '-';
            })

            ->editColumn(
                'created_at',
                fn($r) =>
                $r->created_at->format('d-m-Y h:i A')
            )

            ->editColumn(
                'follow_up_date',
                fn($r) =>
                $r->follow_up_date
                    ? date('d-m-Y', strtotime($r->follow_up_date))
                    : '-'
            )

            ->editColumn(
                'handovered_date',
                fn($r) =>
                $r->handovered_date
                    ? date('d-m-Y', strtotime($r->handovered_date))
                    : '-'
            )

            ->make(true);
    }
}
