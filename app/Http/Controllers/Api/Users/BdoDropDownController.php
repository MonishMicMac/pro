<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountType;
use App\Models\Fabricator;
use App\Models\Brand;
use App\Models\BuildingStatus; // 1. Import the Model

class BdoDropDownController extends Controller
{
    public function index(Request $request)
    {
        $zoneId = $request->input('zone_id');

        // 1️⃣ Account Types (Customer Types)
        $accountTypes = AccountType::where('action', '0')
            ->get(['id', 'name']);

        // 2️⃣ Fabricators (Filtered by Zone)
        $fabricators = Fabricator::where('action', '0')
            ->when($zoneId, function ($query) use ($zoneId) {
                $query->where('zone_id', $zoneId);
            })
            ->get([
                'id',
                'shop_name as name'
            ]);

        // 3️⃣ Brands
        $brands = Brand::where('action', '0')
            ->get(['id', 'name']);

        // 4️⃣ Building Status (Newly Added)
        $buildingStatuses = BuildingStatus::where('action', '0')
            ->get(['id', 'name']);

        return response()->json([
            'status'  => true,
            'message' => 'Dropdown data fetched successfully.',
            'data'    => [
                'customer_type'   => $accountTypes,
                'fabricators'     => $fabricators,
                'brands'          => $brands,
                'building_status' => $buildingStatuses, // 2. Add to response
            ]
        ], 200);
    }
}