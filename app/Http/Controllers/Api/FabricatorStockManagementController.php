<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FabricatorStockManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FabricatorStockManagementController extends Controller
{
    /**
     * 1. LIST API (GET)
     * URL: api/fabricator-stock/list
     */
    public function list(Request $request)
    {
        // Fetch all records, ordered by newest first
        $stocks = FabricatorStockManagement::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count'   => $stocks->count(),
            'data'    => $stocks
        ], 200);
    }

    /**
     * 2. STORE API (POST)
     * URL: api/fabricator-stock/store
     */
    public function store(Request $request)
    {
        // 1. Validate inputs
        $validator = Validator::make($request->all(), [
            'product_id'    => 'required', 
            'fabricator_id' => 'required',
            'user_id'       => 'required',
            // Added 'min:0' to prevent negative numbers
            'closing_stock' => 'required|numeric|min:0', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 2. Fetch the Previous Record
            $lastRecord = FabricatorStockManagement::where('product_id', $request->product_id)
                ->where('fabricator_id', $request->fabricator_id)
                ->latest('created_at')
                ->first();

            // 3. Logic
            $openingStock = $lastRecord ? $lastRecord->current_stock : 0;
            $closingStock = $request->closing_stock;
            $currentStock = $closingStock;

            // 4. Create Record
            $stock = FabricatorStockManagement::create([
                'product_id'    => $request->product_id,
                'fabricator_id' => $request->fabricator_id,
                'opening_stock' => (string) $openingStock,
                'closing_stock' => (string) $closingStock,
                'current_stock' => (string) $currentStock,
                'updated_by'    => $request->user_id,
                'action'        => '0',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'data'    => $stock
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}