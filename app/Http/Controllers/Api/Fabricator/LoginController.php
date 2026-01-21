<?php

namespace App\Http\Controllers\Api\Fabricator;

use App\Http\Controllers\Controller;
use App\Models\Fabricator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class LoginController extends Controller
{


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        $fabricator = Fabricator::where('mobile', $request->mobile)
            ->where('password', $request->password)
            ->first();

        if (!$fabricator) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid mobile number or password'
            ], 200);
        }

        if ($fabricator->status != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Account inactive'
            ], 200);
        }
        $token = $fabricator->createToken(
            'fabricator-token',
            ['*'],
            Carbon::now()->addDays(7)
        )->plainTextToken;
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'data' => [
                'id' => $fabricator->id,
                'shop_name'        => $fabricator->shop_name,
                'contact_person'   => $fabricator->contact_person,
                'role' => "fabricator"
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $fabricator = Fabricator::find($id);

        if (!$fabricator) {
            return response()->json([
                'status' => false,
                'message' => 'Fabricator not found'
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'shop_name' => 'required',
            'mobile' => 'required',
            'email' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        $fabricator->update([
            'shop_name'        => $request->shop_name,
            'contact_person'   => $request->contact_person,
            'mobile'           => $request->mobile,
            'email'            => $request->email,
            'gst'              => $request->gst,
            'address'          => $request->address,
            'shipping_address' => $request->shipping_address,
            'billing_address'  => $request->billing_address,
            'bank_name'        => $request->bank_name,
            'ifsc_code'        => $request->ifsc_code,
            'account_number'   => $request->account_number,
            'branch'           => $request->branch,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Fabricator updated successfully',
            'data' => $fabricator
        ], 200);
    }

    public function logout(Request $request)
    {
        // delete current token only
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout successful'
        ], 200);
    }
}
