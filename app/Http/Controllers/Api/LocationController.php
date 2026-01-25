<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\Area;
use App\Models\Pincodes;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Get States
     * Optional Params: ?zone_id=1
     */
    public function getStates(Request $request)
    {
        $query = State::where('action', '0')->orderBy('name', 'asc');

        // Check if zone_id is passed in URL
        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $states = $query->get(['id', 'name', 'zone_id']);

        return response()->json([
            'status' => true,
            'count' => $states->count(),
            'data' => $states
        ]);
    }

    /**
     * Get Districts
     * Required Params: ?state_id=1
     */
    public function getDistricts(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|exists:states,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $districts = District::where('state_id', $request->state_id)
            ->where('action', '0')
            ->orderBy('district_name', 'asc')
            ->get(['id', 'district_name', 'state_id']);

        return response()->json([
            'status' => true,
            'count' => $districts->count(),
            'data' => $districts
        ]);
    }

    /**
     * Get Cities
     * Required Params: ?district_id=1
     */
    public function getCities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'district_id' => 'required|exists:districts,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $cities = City::where('district_id', $request->district_id)
            ->where('action', '0')
            ->orderBy('city_name', 'asc')
            ->get(['id', 'city_name', 'district_id']);

        return response()->json([
            'status' => true,
            'count' => $cities->count(),
            'data' => $cities
        ]);
    }

    /**
     * Get Areas
     * Required Params: ?city_id=1
     */
    public function getAreas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $areas = Area::where('city_id', $request->city_id)
            ->where('action', '0')
            ->orderBy('area_name', 'asc')
            ->get(['id', 'area_name', 'city_id']);

        return response()->json([
            'status' => true,
            'count' => $areas->count(),
            'data' => $areas
        ]);
    }

    /**
     * Get Pincodes
     * Required Params: ?area_id=1
     */
    public function getPincodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_id' => 'required|exists:areas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $pincodes = Pincodes::where('area_id', $request->area_id)
            ->where('action', '0')
            ->orderBy('pincode', 'asc')
            ->get(['id', 'pincode', 'area_id']);

        return response()->json([
            'status' => true,
            'count' => $pincodes->count(),
            'data' => $pincodes
        ]);
    }
}