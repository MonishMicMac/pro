<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        // Fetch active brands (action = '0') and select only id and name
        $brands = Brand::where('action', '0')
            ->select('id', 'name')
            ->orderBy('name', 'asc') // Optional: Sort alphabetically
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $brands->count(),
            'data'    => $brands
        ], 200);
    }
}