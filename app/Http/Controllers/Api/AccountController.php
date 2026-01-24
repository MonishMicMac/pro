<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        // 1. Validate the Zone ID
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 2. Fetch Accounts filtered by Zone ID
        $accounts = Account::with(['zone', 'state', 'district', 'accountType'])
            ->where('action', '0') // Active accounts
            ->where('zone_id', $request->zone_id) // <--- Added Filter
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count'   => count($accounts),
            'data'    => $accounts
        ]);
    }

    /**
     * Get Full Details for a specific Account
     */
    public function getAccountDetails(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 2. Fetch Account with relationships
            // Ensuring we only fetch active accounts (action = '0')
            $account = Account::with(['zone', 'state', 'district', 'accountType'])
                ->where('id', $request->account_id)
                ->where('action', '0') 
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or inactive.'
                ], 404);
            }

            // 3. Construct Response Data
            // Note: Adjust attribute names (e.g., district_name) if your DB is different
            $data = [
                'account_id'      => $account->id,
                'name'            => $account->name,
                'mobile_number'   => $account->mobile_number,
                
                // Account Type Details
                'account_type_id' => $account->account_type_id,
                'account_type'    => $account->accountType ? $account->accountType->name : null,

                // Location Details
                'zone_id'         => $account->zone_id,
                'zone_name'       => $account->zone ? $account->zone->name : null,
                
                'state_id'        => $account->state_id,
                'state_name'      => $account->state ? $account->state->name : null,
                
                'district_id'     => $account->district_id,
                'district_name'   => $account->district ? $account->district->district_name : null,

                'created_at'      => $account->created_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Account details retrieved successfully',
                'data'    => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTypes()
    {
        $types = AccountType::where('action', '0')->get();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'mobile_number' => 'required',
            'zone_id' => 'required|exists:zones,id',
            'state_id' => 'required|exists:states,id',
            'district_id' => 'required|exists:districts,id',
            'account_type_id' => 'required|exists:account_types,id',
        ]);

        $account = Account::create($request->all() + ['action' => '0']);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    public function getLocations(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zones':
                $data = Zone::where('action', '0')->get(['id', 'name']);
                break;
            case 'states':
                $data = State::where('zone_id', $id)->get(['id', 'name']);
                break;
            case 'districts':
                $data = District::where('state_id', $id)->get(['id', 'district_name as name']);
                break;
            default:
                $data = [];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
