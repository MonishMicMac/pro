<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Fabricator;
use App\Models\User;
use App\Models\UserMapping;
use App\Models\Zone;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class InstallationReportController extends Controller
{
    /**
     * Display the installation site report landing page.
     */
    public function index()
    {
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        return view('reports.installation_report', compact('zones', 'fabricators'));
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
     * Yajra DataTable endpoint for installation records.
     */
    public function data(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();
        $fabricatorId = $request->fabricator_id;
        $zoneId = $request->zone_id;
        $zsmId = $request->zsm_id;
        $bdmId = $request->bdm_id;

        $query = Lead::with(['assignedUser', 'fabricator'])
            ->whereNotNull('installed_date')
            ->whereBetween('installed_date', [$startDate, $endDate]);

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
                // Since Lead model has 'zone' as a direct column sometimes, but we prefer hierarchy
                $query->whereHas('fabricator.district.state', function($q) use ($zoneId) {
                    $q->where('zone_id', $zoneId);
                });
            }
        }

        return DataTables::of($query)
            ->addColumn('sno', function() { static $i = 0; return ++$i; })
            ->editColumn('name', function($row) {
                return $row->name ?: '-';
            })
            ->editColumn('phone_number', function($row) {
                return $row->phone_number ?: '-';
            })
            ->addColumn('sqft', function($row) {
                return number_format($row->total_required_area_sqft, 2);
            })
            ->addColumn('fabricator_name', function($row) {
                return $row->fabricator->shop_name ?? '-';
            })
            ->editColumn('installed_date', function($row) {
                return $row->installed_date ? Carbon::parse($row->installed_date)->format('d-M-Y') : '-';
            })
            ->addColumn('bdo_name', function($row) {
                return $row->assignedUser ? $row->assignedUser->name : '-';
            })
            ->make(true);
    }
}
