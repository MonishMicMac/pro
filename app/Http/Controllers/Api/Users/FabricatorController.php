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
            'shop_name'       => 'required',
            'contact_person'  => 'required',
            'mobile'          => 'required|unique:fabricators,mobile',
            'email'           => 'required',
            'address'         => 'required',

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
}
