<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Fabricator;
use App\Models\FabricatorRequest;
use App\Models\User;
use App\Models\UserMapping;
use App\Models\Zone;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class QuotationReportController extends Controller
{
    /**
     * Display the quotation report landing page.
     */
    public function index()
    {
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        return view('reports.quotation_report', compact('zones', 'fabricators'));
    }

    /**
     * Hierarchical location/user data (Shared with FabricatorReport)
     */
    public function getHierarchicalData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                $bdmIds = User::role('BDM')->where('zone_id', $id)->pluck('id');
                return response()->json([
                    'zsms' => User::role('ZSM')->where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                    'fabricators' => Fabricator::whereIn('created_by', $bdmIds)->where('status', '1')->orderBy('shop_name')->get(['id', 'shop_name as name'])
                ]);
            case 'zsm':
                $bdmIds = UserMapping::where('zsm_id', $id)->distinct()->pluck('bdm_id');
                return response()->json([
                    'bdms' => User::whereIn('id', $bdmIds)->orderBy('name')->get(['id', 'name']),
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
     * Yajra DataTable endpoint for quotation records.
     */
    public function data(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();
        $fabricatorId = $request->fabricator_id;
        $zoneId = $request->zone_id;
        $zsmId = $request->zsm_id;
        $bdmId = $request->bdm_id;

        $query = FabricatorRequest::with(['lead', 'fabricator'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Hierarchical Filters
        if ($fabricatorId) {
            $query->where('fabricator_id', $fabricatorId);
        } else {
            if ($bdmId) {
                $query->whereHas('fabricator', function($q) use ($bdmId) {
                    $q->where('created_by', $bdmId);
                });
            } elseif ($zsmId) {
                $bdmIds = UserMapping::where('zsm_id', $zsmId)->distinct()->pluck('bdm_id');
                $query->whereHas('fabricator', function($q) use ($bdmIds) {
                    $q->whereIn('created_by', $bdmIds);
                });
            } elseif ($zoneId) {
                $query->whereHas('fabricator.district.state', function($q) use ($zoneId) {
                    $q->where('zone_id', $zoneId);
                });
            }
        }

        return DataTables::of($query)
            ->addColumn('quotation_date', function($row) {
                return $row->created_at->format('d-M-Y');
            })
            ->addColumn('lead_name', function($row) {
                return $row->lead->name ?? '-';
            })
            ->addColumn('phone_number', function($row) {
                return $row->lead->phone_number ?? '-';
            })
            ->addColumn('lead_source', function($row) {
                return $row->lead->lead_source ?? '-';
            })
            ->addColumn('fabricator_name', function($row) {
                return $row->fabricator->shop_name ?? '-';
            })
            ->editColumn('approx_sqft', function($row) {
                return number_format($row->approx_sqft, 2);
            })
            ->editColumn('rate_per_sqft', function($row) {
                return number_format($row->rate_per_sqft, 2);
            })
            ->addColumn('quotation_pdf', function($row) {
                if ($row->fabrication_pdf) {
                    $url = asset('storage/' . $row->fabrication_pdf);
                    return '<a href="'.$url.'" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                            </a>';
                }
                return '-';
            })
            ->rawColumns(['quotation_pdf'])
            ->make(true);
    }
}
