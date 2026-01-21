<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Step 1: Send OTP to Mobile
     * Validates BDM or BDO role and generates a 4-digit OTP.
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => "false", 
                'message' => $validator->errors()->first()
            ], 200);
        }

        // 1. Find user by phone and verify they have either the BDM or BDO role
        $user = User::where('phone', $request->phone)
                    ->whereHas('roles', function($q) {
                        // Changed where to whereIn to support multiple roles
                        $q->whereIn('name', ['BDM', 'BDO']); 
                    })->first();

        if (!$user) {
            return response()->json([
                'status' => "false", 
                'message' => 'No authorized BDM or BDO account found with this phone number.'
            ], 200);
        }

        // 2. Generate random 4-digit OTP
        $otp = rand(1000, 9999);

        // 3. Save OTP to the user's otp column
        $user->otp = $otp;
        $user->save();

        // 4. Return response
        return response()->json([
            'status' => "true", 
            'message' => 'OTP sent successfully.', 
            'otp_debug' => $otp 
        ]);
    }

    /**
     * Step 2: Verify OTP and Login
     * (No changes needed here as it verifies the OTP saved in Step 1)
     */
public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'phone' => 'required|numeric|digits:10',
        'otp'   => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => "false", 
            'message' => $validator->errors()->first()
        ], 200);
    }

    // 1. Verify user and OTP
    $user = User::with('roles')
                ->where('phone', $request->phone)
                ->where('otp', $request->otp) 
                ->first();

    if (!$user) {
        return response()->json([
            'status' => "false", 
            'message' => 'Invalid OTP or Phone number.'
        ], 200);
    }

    // 2. Clear OTP
    $user->otp = null;
    $user->save();

    // 3. Create Token
    $token = $user->createToken('auth_token')->plainTextToken;

    // 4. Prepare Response Data
    $userData = $user->toArray();
    $userData['token'] = $token;

    // Get the first role name (e.g., "BDM" or "BDO")
    $roleName = $user->roles->first() ? $user->roles->first()->name : null;

    // Set the specific keys as requested
    $userData['type'] = strtolower($roleName); // "bdm"
    $userData['roles'] = $roleName;            // "BDM"

    return response()->json([
        'status' => "true",
        'message' => 'Login successful',
        'data' => $userData 
    ], 200);
}
}