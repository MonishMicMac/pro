<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BdmCall;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BdmCallController extends Controller
{
    /**
     * Store a new Call Log via API
     */
    public function store(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|exists:users,id',
            'client_type'  => 'required|in:1,2,3,4', // 1=Account, 2=Lead, 3=Fabricator, 4=BDO
            'client_id'    => 'required',
            'phone_number' => 'nullable|string',
            'duration'     => 'nullable|string',
            'call_status'  => 'required|string',
            'called_at'    => 'required|date_format:Y-m-d H:i:s',
            'remarks'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // 2. CONVERSION: Map Integer (1,2,3) to Class Name
        // We do not store '1' or '2' in the DB. We store the Model Class.
        $modelClass = $this->getModelClass($request->client_type);

        if (!$modelClass) {
            return response()->json(['status' => false, 'message' => 'Invalid Client Type'], 400);
        }

        try {
            // 3. Create Record
            $call = BdmCall::create([
                'user_id'       => $request->user_id,
                'client_type'   => $request->client_type,
                'callable_type' => $modelClass,      
                'callable_id'   => $request->client_id,
                'phone_number'  => $request->phone_number,
                'duration'      => $request->duration,
                'call_status'   => $request->call_status,
                'called_at'     => $request->called_at,
                'remarks'       => $request->remarks,
            ]);

            // 4. Send back the integer so the frontend knows what it saved
            $call->client_type = $request->client_type; 

            return response()->json([
                'status' => true,
                'message' => 'Call logged successfully',
                'data' => $call
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List Calls (Filtered by User ID and Date)
     */
    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date'    => 'nullable|date_format:Y-m-d', // Optional Date Filter
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // 1. Start Query
        $query = BdmCall::where('user_id', $request->user_id)
            ->with('callable'); // Load Client Details

        // 2. Apply Date Filter (If passed)
        if ($request->filled('date')) {
            $query->whereDate('called_at', $request->date);
        }

        $calls = $query->orderBy('called_at', 'desc')->get();

        // 3. Format Data (Convert Class Name back to Integer 1,2,3)
        $formattedData = $calls->map(function ($call) {
            
            $typeId = 0;
            $typeName = 'Unknown';
            $clientName = 'Unknown';

            // Check what class is stored in 'callable_type'
            if ($call->callable_type == 'App\Models\Account') {
                $typeId = 1; $typeName = 'Account';
                $clientName = $call->callable->name ?? '-';
            } elseif ($call->callable_type == 'App\Models\Lead') {
                $typeId = 2; $typeName = 'Lead';
                $clientName = $call->callable->name ?? '-';
            } elseif ($call->callable_type == 'App\Models\Fabricator') {
                $typeId = 3; $typeName = 'Fabricator';
                $clientName = $call->callable->shop_name ?? '-';
            } elseif ($call->callable_type == 'App\Models\User') {
                $typeId = 4; $typeName = 'BDO';
                $clientName = $call->callable->name ?? '-';
            }

            return [
                'id'           => $call->id,
                'client_type'  => $typeId,       // <--- Sends '3' back to App
                'client_type_name' => $typeName, // <--- Sends 'Fabricator'
                'client_id'    => $call->callable_id,
                'client_name'  => $clientName,
                'phone_number' => $call->phone_number,
                'duration'     => $call->duration,
                'call_status'  => $call->call_status,
                'called_at'    => $call->called_at->format('Y-m-d H:i:s'),
                'remarks'      => $call->remarks,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Calls retrieved successfully',
            'count'   => $formattedData->count(),
            'data'    => $formattedData
        ], 200);
    }

    /**
     * Helper: Convert Integer to Model Class
     */
    private function getModelClass($type)
    {
        return match ((int)$type) {
            1 => 'App\Models\Account',
            2 => 'App\Models\Lead',
            3 => 'App\Models\Fabricator',
            4 => 'App\Models\User',
            default => null,
        };
    }
}