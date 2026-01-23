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
