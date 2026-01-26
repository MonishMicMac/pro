<?php

namespace App\Http\Controllers;

use App\Models\Fabricator;
use App\Models\FabricatorStockManagement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FabricatorStockReportController extends Controller implements HasMiddleware
{
    /**
     * Define Middleware
     */
    public static function middleware(): array
    {
        return [
            // Adjust permission as needed, e.g., 'fabricator-report.view' or create a new one
            new Middleware('permission:fabricator-report.view'), 
        ];
    }

    public function index()
    {
        $fabricators = Fabricator::where('status', '0')->orderBy('shop_name')->get(); 
        $zones = \App\Models\Zone::where('action', '0')->orderBy('name')->get();
        
        return view('reports.fabricator_stock_report', compact('fabricators', 'zones'));
    }


    /**
     * Yajra DataTable endpoint for stock records
     */
    public function data(Request $request)
    {
        $startDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : null;
        $endDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : null;
        
        // Latest stock logic: Group by fabricator and product, taking the max ID
        $latestIds = FabricatorStockManagement::selectRaw('MAX(id) as id')
            ->groupBy('fabricator_id', 'product_id')
            ->pluck('id');

        $query = FabricatorStockManagement::with(['fabricator.zone', 'product.category', 'product.subCategory'])
            ->whereIn('id', $latestIds);

        // Filters
        // Note: Date filter on "Current Stock" report usually implies "Stock AS OF Date", but since we are showing "Current Stock",
        // we essentially just show the latest record. If filtering by date is required for HISTORY, it conflicts with "Current Stock Only".
        // Assuming user wants the LATEST status, but maybe filtered by when it was updated?
        // Let's keep date filter as "Updated Between" for now, but applied to the latest records? 
        // Or does date filter *find* the latest record *within* that date?
        // "show current stock only" usually implies ignoring date range and showing absolute latest.
        // However, if the user explicitly picks dates, we should respect it. 
        // For now, I will Apply filters to the MAIN query.
        
        if($startDate && $endDate) {
            $query->whereBetween('updated_at', [$startDate, $endDate]);
        }

        // Filter by Fabricator
        if ($request->fabricator_id) {
            $query->where('fabricator_id', $request->fabricator_id);
        }
        
        // Filter by Zone
        if ($request->zone_id) {
            $query->whereHas('fabricator.zone', function($q) use ($request) {
                $q->where('id', $request->zone_id);
            });
        }

        // Product Filters
        if ($request->category_id) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        if ($request->sub_category_id) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('sub_category_id', $request->sub_category_id);
            });
        }
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        return DataTables::of($query)
            ->addColumn('date', fn($row) => $row->created_at->format('d-M-Y')) // Will be renamed to Last Updated in View
            ->addColumn('fabricator_zone', fn($row) => $row->fabricator->zone->name ?? '-')
            ->addColumn('fabricator_name', fn($row) => $row->fabricator->shop_name ?? '-')
            
            ->addColumn('category_name', fn($row) => $row->product->category->name ?? '-')
            ->addColumn('sub_category_name', fn($row) => $row->product->subCategory->name ?? '-')
            ->addColumn('product_name', fn($row) => $row->product->name ?? '-')
            ->addColumn('current_stock', fn($row) => $row->current_stock)
            ->addColumn('updated_by', fn($row) => \App\Models\User::find($row->updated_by)->name ?? '-')
            ->rawColumns(['fabricator_name'])
            ->make(true);
    }

    public function getCategories()
    {
        $data = \App\Models\Category::where('action', '0')->orderBy('name')->select('id', 'name')->get();
        return response()->json($data);
    }

    public function getSubCategories(Request $request)
    {
        $query = \App\Models\SubCategory::where('action', '0')->orderBy('name')->select('id', 'name', 'category_id');
        if($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        return response()->json($query->get());
    }

    public function getProducts(Request $request)
    {
        $query = \App\Models\Product::where('action', '0')->orderBy('name')->select('id', 'name', 'item_code');
        if($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if($request->sub_category_id) {
            $query->where('sub_category_id', $request->sub_category_id);
        }
        return response()->json($query->get());
    }
}

