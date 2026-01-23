<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Fabricator;
use App\Models\FabricatorRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
// 1. Import these required classes for Laravel 11 Middleware
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

// 2. Add 'implements HasMiddleware' to the class definition
class FabricatorReportController extends Controller implements HasMiddleware
{
    /**
     * 3. Define Middleware using the new Static Method
     * This replaces the old __construct() method
     */
    public static function middleware(): array
    {
        return [
            // This applies the permission check to ALL methods in this controller
            new Middleware('permission:fabricator-report.view'),
        ];
    }

    public function index()
    {
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        $zones = \App\Models\Zone::where('action', '0')->orderBy('name')->get();
        return view('reports.fabricator_report', compact('fabricators', 'zones'));
    }

    /**
     * Hierarchical location/user data
     */
    public function getHierarchicalData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                $bdmIds = \App\Models\User::role('BDM')->where('zone_id', $id)->pluck('id');
                return response()->json([
                    'zsms' => \App\Models\User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    'fabricators' => Fabricator::whereIn('created_by', $bdmIds)->where('status', '1')->orderBy('shop_name')->get(['id', 'shop_name as name'])
                ]);
            case 'zsm':
                $bdmIds = \App\Models\UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return response()->json([
                    'bdms' => \App\Models\User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']),
                    'fabricators' => Fabricator::whereIn('created_by', $bdmIds)->where('status', '1')->orderBy('shop_name')->get(['id', 'shop_name as name'])
                ]);
            case 'bdm':
                return response()->json([
                    'fabricators' => Fabricator::where('created_by', $id)->where('status', '1')->orderBy('shop_name')->get(['id', 'shop_name as name'])
                ]);
            default:
                return response()->json([]);
        }
    }

    /**
     * Fetch high-level summary counts for cards
     */
    public function summaryData(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();
        $fabricatorId = $request->fabricator_id;
        $zoneId = $request->zone_id;
        $zsmId = $request->zsm_id;
        $bdmId = $request->bdm_id;

        // Base query for fabricators affected by filters
        $fabQuery = Fabricator::query();
        if ($fabricatorId) {
            $fabQuery->where('id', $fabricatorId);
        } else {
            if ($bdmId) {
                $fabQuery->where('created_by', $bdmId);
            } elseif ($zsmId) {
                $bdmIds = \App\Models\UserMapping::where('zsm_id', $zsmId)->distinct()->pluck('bdm_id');
                $fabQuery->whereIn('created_by', $bdmIds);
            } elseif ($zoneId) {
                $fabQuery->whereHas('district.state', function($q) use ($zoneId) {
                    $q->where('zone_id', $zoneId);
                });
            }
        }
        
        $fabricatorIds = $fabQuery->pluck('id');

        // 1. Quote Stats
        $quoteQuery = FabricatorRequest::whereIn('fabricator_id', $fabricatorIds)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        $quoteLeads = $quoteQuery->pluck('lead_id')->unique();
        $quoteGivenCount = $quoteLeads->count();
        $quoteSqft = Lead::whereIn('id', $quoteLeads)->sum('total_required_area_sqft');

        $wonLeads = Lead::whereIn('id', $quoteLeads)
            ->whereIn('lead_stage', [5, 6])
            ->get();
        $wonCount = $wonLeads->count();
        $wonSqft = $wonLeads->sum('total_required_area_sqft');

        // 2. Installation Stats
        $installQuery = Lead::whereIn('fabricator_id', $fabricatorIds)
            ->whereBetween('installed_date', [$startDate, $endDate]);

        $completed = (clone $installQuery)->whereNotNull('handovered_date')->get();
        $pending = (clone $installQuery)->whereNull('handovered_date')->get();

        return response()->json([
            'status' => true,
            'summary' => [
                'quote_given' => $quoteGivenCount,
                'quote_sqft' => (float)$quoteSqft,
                'won' => $wonCount,
                'won_sqft' => (float)$wonSqft,
                'completed' => $completed->count(),
                'completed_sqft' => (float)$completed->sum('total_required_area_sqft'),
                'pending' => $pending->count(),
                'pending_sqft' => (float)$pending->sum('total_required_area_sqft'),
            ]
        ]);
    }

    /**
     * Yajra DataTable endpoint for detailed records
     */
    public function data(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();
        $fabricatorId = $request->fabricator_id;
        $zoneId = $request->zone_id;
        $zsmId = $request->zsm_id;
        $bdmId = $request->bdm_id;
        $statusFilter = $request->status_filter; // 'quote_given', 'won', 'completed', 'pending'

        $query = Lead::with(['assignedUser', 'fabricatorRequests' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }]);

        // Filter by Fabricator/Zone/User
        $fabIdsQuery = Fabricator::query();
        if ($fabricatorId) {
            $fabIdsQuery->where('id', $fabricatorId);
        } else {
            if ($bdmId) {
                $fabIdsQuery->where('created_by', $bdmId);
            } elseif ($zsmId) {
                $bdmIds = \App\Models\UserMapping::where('zsm_id', $zsmId)->distinct()->pluck('bdm_id');
                $fabIdsQuery->whereIn('created_by', $bdmIds);
            } elseif ($zoneId) {
                $fabIdsQuery->whereHas('district.state', function($q) use ($zoneId) { $q->where('zone_id', $zoneId); });
            }
        }
        $fabIds = $fabIdsQuery->pluck('id');

        if (in_array($statusFilter, ['completed', 'pending'])) {
            $query->whereIn('fabricator_id', $fabIds)
                  ->whereBetween('installed_date', [$startDate, $endDate]);
        } else {
            $query->whereHas('fabricatorRequests', function($q) use ($fabIds, $startDate, $endDate) {
                $q->whereIn('fabricator_id', $fabIds)->whereBetween('created_at', [$startDate, $endDate]);
            });
        }

        // Apply Status Logic
        if ($statusFilter === 'won') {
            $query->whereIn('lead_stage', [5, 6]);
        } elseif ($statusFilter === 'completed') {
            $query->whereNotNull('handovered_date');
        } elseif ($statusFilter === 'pending') {
            $query->whereNull('handovered_date');
        }

        return DataTables::of($query)
            ->addColumn('fab_name', function($row) {
                if ($row->fabricator_id) return Fabricator::find($row->fabricator_id)->shop_name ?? '-';
                $req = $row->fabricatorRequests->first();
                return $req ? Fabricator::find($req->fabricator_id)->shop_name ?? '-' : '-';
            })
            ->editColumn('lead_stage', function($row) {
                $statusLabels = [
                    5 => ['Won', 'bg-emerald-100 text-emerald-600'],
                    6 => ['Handed Over', 'bg-blue-100 text-blue-600'],
                ];
                $label = $statusLabels[$row->lead_stage] ?? ['Quote Sent', 'bg-purple-100 text-purple-600'];
                return "<span class='px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {$label[1]}'>{$label[0]}</span>";
            })
            ->addColumn('bdo_name', function($row) {
                return $row->assignedUser ? $row->assignedUser->name : '-';
            })
            ->addColumn('installation_date_label', function($row) {
                return $row->installed_date ? Carbon::parse($row->installed_date)->format('d-M-y') : '-';
            })
            ->rawColumns(['lead_stage'])
            ->make(true);
    }
}