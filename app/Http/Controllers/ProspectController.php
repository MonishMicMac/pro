<?php

namespace App\Http\Controllers;

use App\Models\DigitalMarketingLead;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\LeadHelper;

class ProspectController extends Controller
{
    public function index()
    {
        return view('reports.prospect-report');
    }


    public function data(Request $request)
    {
        $leadStages = LeadHelper::getLeadStages();

        $query = DigitalMarketingLead::with([
            'telecaller',
            'assignedUser',
            'lead',
            'zoneDetails'
        ]);

        // Date filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }

        // Zone filter
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }

        //  Telecaller filter
        if ($request->filled('telecaller')) {
            $query->where('updated_by', $request->telecaller);
        }

        //  Telecaller Stage filter
        if ($request->filled('tc_stage')) {
            $query->where('stage', $request->tc_stage);
        }

        //  BDO Lead Stage filter
        if ($request->filled('bdo_stage')) {
            $query->whereHas('lead', function ($q) use ($request) {
                $q->where('lead_stage', $request->bdo_stage);
            });
        }

        return DataTables::of($query)

            ->addColumn(
                'telecaller',
                fn($r) =>
                optional($r->telecaller)->name ?? '-'
            )

            ->addColumn(
                'telecaller_stage',
                fn($r) =>
                $leadStages[$r->stage] ?? '-'
            )

            ->addColumn(
                'bdo',
                fn($r) =>
                optional($r->assignedUser)->name ?? '-'
            )

            ->addColumn('lead_stage', function ($r) {
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
                return $stages[optional($r->lead)->lead_stage] ?? '-';
            })

            ->addColumn(
                'zone_name',
                fn($r) =>
                optional($r->zoneDetails)->name ?? '-'
            )

            ->editColumn(
                'created_at',
                fn($r) =>
                $r->created_at->format('d-m-Y h:i A')
            )

            ->make(true);
    }
}
