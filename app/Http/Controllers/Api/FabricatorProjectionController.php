<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FabricatorProjection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FabricatorProjectionController extends Controller
{
    public function index(Request $request)
    {
        $query = FabricatorProjection::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('fabricator_id')) {
            $query->where('fabricator_id', $request->fabricator_id);
        }

        $projections = $query->with(['fabricator', 'user'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $projections
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fabricator_id' => 'required|exists:fabricators,id',
            'projection_month' => 'required',
            'sale_projection_tonnage' => 'required|numeric',
            'fabricator_collection' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $projection = FabricatorProjection::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Fabricator projection saved successfully',
            'data' => $projection
        ]);
    }
}
