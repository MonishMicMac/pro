<?php

namespace App\Http\Controllers;

use App\Models\Fabricator;
use App\Models\FabricatorInvoice;
use App\Models\FabricatorPayment;
use App\Models\InvoiceCollection;
use App\Models\User;
use App\Models\UserMapping;
use App\Models\Zone;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FabricatorAccountingReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:fabricator-report.view'),
        ];
    }

    /* =========================================================================
       INVOICE REPORT
    ========================================================================= */
    public function invoiceIndex()
    {
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        return view('reports.fabricator_invoice_report', compact('fabricators', 'zones'));
    }

    public function getInvoicesData(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();
        
        $query = FabricatorInvoice::query()
            ->whereBetween('invoice_date', [$startDate, $endDate]);

         
        $this->applyHierarchyFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('shop_name', function($row) {
                return Fabricator::where('cust_id', $row->cust_id)->value('shop_name') ?? $row->cust_id;
            })
            ->editColumn('invoice_date', function($row) {
                return $row->invoice_date->format('d-M-Y');
            })
            ->editColumn('amount', function($row) {
                return number_format($row->amount, 2);
            })
            ->editColumn('debit', function($row) {
                return $row->debit > 0 ? number_format($row->debit, 2) : '-';
            })
            ->editColumn('credit', function($row) {
                return $row->credit > 0 ? number_format($row->credit, 2) : '-';
            })
            ->make(true);
    }

    /* =========================================================================
       PAYMENT REPORT
    ========================================================================= */
    public function paymentIndex()
    {
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        return view('reports.fabricator_payment_report', compact('fabricators', 'zones'));
    }

    public function getPaymentsData(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();

        $query = FabricatorPayment::query()
            ->whereBetween('payment_date', [$startDate, $endDate]);

        $this->applyHierarchyFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('shop_name', function($row) {
                return Fabricator::where('cust_id', $row->cust_id)->value('shop_name') ?? $row->cust_id;
            })
            ->editColumn('payment_date', function($row) {
                return Carbon::parse($row->payment_date)->format('d-M-Y');
            })
            ->editColumn('amount', function($row) {
                return number_format($row->amount, 2);
            })
            ->make(true);
    }

    /* =========================================================================
       COLLECTION REPORT
    ========================================================================= */
    public function collectionIndex()
    {
        $fabricators = Fabricator::where('status', '1')->orderBy('shop_name')->get();
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        return view('reports.fabricator_collection_report', compact('fabricators', 'zones'));
    }

    public function getCollectionsData(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();

        $query = InvoiceCollection::query()
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        $this->applyHierarchyFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('shop_name', function($row) {
                return Fabricator::where('cust_id', $row->cust_id)->value('shop_name') ?? $row->cust_id;
            })
            ->editColumn('invoice_date', function($row) {
                return Carbon::parse($row->invoice_date)->format('d-M-Y');
            })
            ->editColumn('due_date', function($row) {
                return Carbon::parse($row->due_date)->format('d-M-Y');
            })
            ->editColumn('invoice_amount', function($row) {
                return number_format($row->invoice_amount, 2);
            })
            ->editColumn('collected_amount', function($row) {
                return number_format($row->collected_amount, 2);
            })
            ->editColumn('due_amount', function($row) {
                return number_format($row->due_amount, 2);
            })
            ->make(true);
    }

    /* =========================================================================
       SHARED UTILITIES
    ========================================================================= */
    private function applyHierarchyFilters($query, Request $request)
    {
        $fabricatorId = $request->fabricator_id;
        $zoneId = $request->zone_id;
        $zsmId = $request->zsm_id;
        $bdmId = $request->bdm_id;

        $fabQuery = Fabricator::query();

        if ($fabricatorId) {
            $cust_id = Fabricator::where('id', $fabricatorId)->value('cust_id');
            $query->where('cust_id', $cust_id);
            return;
        }

        if ($bdmId) {
            $fabQuery->where('created_by', $bdmId);
        } elseif ($zsmId) {
            $bdmIds = UserMapping::where('zsm_id', $zsmId)->distinct()->pluck('bdm_id');
            $fabQuery->whereIn('created_by', $bdmIds);
        } elseif ($zoneId) {
            $fabQuery->whereHas('district.state', function($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        $custIds = $fabQuery->pluck('cust_id');
        $query->whereIn('cust_id', $custIds);
    }

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
}
