<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * 1. Get All Categories
     * Fetches all active categories.
     */
    public function getCategories(Request $request)
    {
        try {
            // Fetch only active categories (action = '0')
            // Adjust 'action' logic if '1' is active in your system. 
            // Based on your previous code, '0' seems to be active.
            $categories = Category::where('action', '0')
                ->select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Categories retrieved successfully',
                'data'    => $categories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * 2. Get Sub-Categories by Category ID
     * Requires 'category_id' input.
     */
    public function getSubCategories(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 2. Fetch Sub-Categories for the specific Category
            $subCategories = SubCategory::where('category_id', $request->category_id)
                ->where('action', '0') // Active only
                ->select('id', 'name', 'category_id')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Sub-categories retrieved successfully',
                'count'   => $subCategories->count(),
                'data'    => $subCategories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * 3. Get Products
     * Filters by 'category_id' OR 'sub_category_id' OR Both.
     */
    public function getProducts(Request $request)
    {
        // 1. Validate (Inputs are optional, but if provided, must exist)
        $validator = Validator::make($request->all(), [
            'category_id'     => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 2. Start Query
            $query = Product::with(['brand', 'category', 'subCategory']) // Eager load relationships
                ->where('action', '0'); // Active products only

            // 3. Apply Filters if provided
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('sub_category_id')) {
                $query->where('sub_category_id', $request->sub_category_id);
            }

            // 4. Get Results
            $products = $query->orderBy('name', 'asc')->get();

            // 5. Format the data (Optional: Make it cleaner)
            $formattedData = $products->map(function ($product) {
                return [
                    'id'              => $product->id,
                    'name'            => $product->name,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Products retrieved successfully',
                'count'   => $formattedData->count(),
                'data'    => $formattedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }
}