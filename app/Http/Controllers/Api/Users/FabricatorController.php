<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Fabricator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\FabricatorRequest;
use Illuminate\Support\Facades\Auth;


class FabricatorController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'         => 'required',
            'shop_name'       => 'required',
            'contact_person'  => 'required',
            'mobile'          => 'required|unique:fabricators,mobile',
            'email'           => 'required',
            'address'         => 'required',
            'zone_id'         => 'required',
            'state_id'        => 'nullable',
            'district_id'     => 'nullable',
            'city_id'         => 'nullable',
            'area_id'         => 'nullable',
            'pincode_id'      => 'nullable',

            'latitude'        => 'nullable',
            'longitude'       => 'nullable',
            'shop_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $imagePath = null;

        if ($request->hasFile('shop_image')) {
            $imagePath = $request->file('shop_image')
                ->store('fabricators', 'public');
        }
        $fabricator = Fabricator::create([

            'shop_name'      => $request->shop_name,
            'contact_person' => $request->contact_person,
            'mobile'         => $request->mobile,
            'email'          => $request->email,


            'gst'            => $request->gst,
            'address'        => $request->address,

            'zone_id'        => $request->zone_id,
            'state_id'       => $request->state_id,
            'district_id'    => $request->district_id,
            'city_id'        => $request->city_id,
            'area_id'        => $request->area_id,
            'pincode_id'     => $request->pincode_id,

            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,


            'shop_image' => $imagePath,
            // DEFAULTS
            'is_existing' => '0', // NEW
            'status'      => '0', // APPROVED
            'action'      => '0',
            'request_date' => now(),
            'approved_date' => now(),
            'created_by' => $request->user_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Fabricator created successfully',
            'data' => [
                'id' => $fabricator->id,
                'shop_name' => $fabricator->shop_name,
                'shop_image' => $imagePath
                    ? url('storage/' . $imagePath)
                    : null
            ]
        ], 200);
    }

    /**
     * Get list of fabricators based on Zone ID
     */
    public function getFabricatorList(Request $request)
    {
        // 1. Validate Zone ID
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 2. Fetch Fabricators for this Zone
        // Assuming the 'fabricators' table has a 'zone_id' column or is linked geographically.
        // If not, and you want ALL fabricators regardless of user but filtered by zone logic:
        $fabricators = Fabricator::where('zone_id', $request->zone_id) // Ensure this column exists in fabricators table
            ->select(
                'id',
                'shop_name',
                'contact_person',
                'mobile',
                'address',
                'latitude',
                'longitude',
                'shop_image',
                'is_existing'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Transform the collection
        $formattedData = $fabricators->map(function ($item) {
            return [
                'id'             => $item->id,
                'shop_name'      => $item->shop_name,
                'contact_person' => $item->contact_person,
                'mobile'         => $item->mobile,
                'address'        => $item->address,
                'latitude'       => $item->latitude,
                'longitude'      => $item->longitude,
                'is_existing'    => $item->is_existing,
                'shop_image_url' => $item->shop_image
                    ? url('storage/' . $item->shop_image)
                    : null,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Fabricator list retrieved successfully',
            'data'    => $formattedData
        ], 200);
    }

    /**
     * Get full details of a specific fabricator
     * Requires both user_id and fabricator_id for security verification
     */
    public function getFabricatorDetails(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            // 'user_id'       => 'required|exists:users,id',
            'fabricator_id' => 'required|exists:fabricators,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 2. Fetch specific fabricator
        // We add 'created_by' check to ensure the user is allowed to view this fabricator
        $fabricator = Fabricator::where('id', $request->fabricator_id)
            // ->where('created_by', $request->user_id)
            ->first();

        if (!$fabricator) {
            return response()->json([
                'status'  => false,
                'message' => 'Fabricator not found or not assigned to this user'
            ], 404);
        }

        // 3. Add Image URL
        $fabricator->shop_image_url = $fabricator->shop_image
            ? url('storage/' . $fabricator->shop_image)
            : null;

        return response()->json([
            'status'  => true,
            'message' => 'Fabricator details retrieved successfully',
            'data'    => $fabricator
        ], 200);
    }



    public function dashboard(Request $request)
    {
        $fabricatorId = Auth::id();

        $total = FabricatorRequest::where('fabricator_id', $fabricatorId)->count();

        $pending = FabricatorRequest::where('fabricator_id', $fabricatorId)
            ->where('status', '0')
            ->count();

        $completed = FabricatorRequest::where('fabricator_id', $fabricatorId)
            ->where('status', '1')
            ->count();

        return response()->json([
            'status' => true,
            'data' => [
                'total_requests' => $total,
                'pending_requests' => $pending,
                'completed_requests' => $completed,

            ]
        ]);
    }
    public function fabricatorProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fabricator_id' => 'required|exists:fabricators,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $fabricator = Fabricator::where('id', $request->fabricator_id)
            ->where('action', '0')
            ->first();

        if (!$fabricator) {
            return response()->json([
                'status'  => false,
                'message' => 'Fabricator not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id'             => $fabricator->id,
                'cust_id'       => $fabricator->cust_id,
                'shop_name'      => $fabricator->shop_name,
                'contact_person' => $fabricator->contact_person,
                'mobile'         => $fabricator->mobile,
                'address'        => $fabricator->address,
            ]
        ], 200);
    }
}
