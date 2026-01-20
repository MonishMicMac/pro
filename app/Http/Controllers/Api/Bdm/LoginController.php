<?php

namespace App\Http\Controllers\Api\Bdm;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Step 1: Send OTP to Mobile
     * Validates BDM role and generates a 4-digit OTP.
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
            ], 422);
        }

        // 1. Find user by phone and verify they have the BDM role
        $user = User::where('phone', $request->phone)
                    ->whereHas('roles', function($q) {
                        $q->where('name', 'BDM'); 
                    })->first();

        if (!$user) {
            return response()->json([
                'status' => "false", 
                'message' => 'No BDM account found with this phone number.'
            ], 404);
        }

        // 2. Generate random 4-digit OTP
        $otp = rand(1000, 9999);

        // 3. Save OTP to the user's otp column
        $user->otp = $otp;
        $user->save();

        // 4. Return response (In production, you would trigger your SMS API here)
        return response()->json([
            'status' => "true", 
            'message' => 'OTP sent successfully.', 
            'otp_debug' => $otp // Remove this key in live production
        ]);
    }

    /**
     * Step 2: Verify OTP and Login
     * Validates the OTP and returns a flat data object with user info and token.
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
            ], 422);
        }

        // 1. Verify if the phone and OTP match
        $user = User::where('phone', $request->phone)
                    ->where('otp', $request->otp) 
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => "false", 
                'message' => 'Invalid OTP or Phone number.'
            ], 401);
        }

        // 2. Clear the OTP column after successful verification
        $user->otp = null;
        $user->save();

        // 3. Create the Sanctum Token
        $token = $user->createToken('bdm_token')->plainTextToken;

        // 4. Flatten the response: Merge user array with the token
        $userData = $user->toArray(); 
        $userData['token'] = $token;

        return response()->json([
            'status' => "true",
            'message' => 'Login successful',
            'data' => $userData 
        ], 200);
    }
}