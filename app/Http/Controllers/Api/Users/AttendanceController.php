<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\UserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Punch In
     * Logic: Check if user already has a record for today's date.
     */
   public function punchIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'in_lat'                => 'required',
            'in_long'               => 'required',
            'start_km'              => 'required',
            'in_time_vehicle_type'  => 'required|in:1,2,3',
            'start_km_photo'        => 'required|image|mimes:jpeg,png,jpg',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => "false", 'message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();
        $todayDate = Carbon::now()->format('Y-m-d');

        // 1. Check if the user already punched in today
        $existingRecord = UserAttendance::where('user_id', $user->id)
            ->where('date', $todayDate)
            ->first();

        if ($existingRecord) {
            return response()->json([
                'status' => "false", 
                'message' => 'Attendance already recorded for today.'
            ], 400);
        }

        // 2. Handle Image Upload
        $photoName = null;
        if ($request->hasFile('start_km_photo')) {
            $path = $request->file('start_km_photo')->store('users_punch_in_images', 'public');
            $photoName = basename($path);
        }

        // 3. Get User Role ID (Changed from Name to ID)
        // Checks if role exists, gets ID, otherwise defaults to null
        $roleId = $user->roles->first() ? $user->roles->first()->id : null;

        // 4. Create Attendance Record
        $attendance = UserAttendance::create([
            'user_id'              => $user->id,
            'user_role'            => $roleId, // Storing ID now
            'date'                 => $todayDate,               
            'punch_in_time'        => Carbon::now()->format('H:i:s'), 
            'in_lat'               => $request->in_lat,
            'in_long'              => $request->in_long,
            'start_km'             => $request->start_km,
            'start_km_photo'       => $photoName,
            'in_time_vehicle_type' => $request->in_time_vehicle_type,
            'status'               => '0', 
        ]);

        return response()->json([
            'status' => "true",
            'message' => 'Punched in successfully',
            'data' => $attendance
        ], 200);
    }

    /**
     * Punch Out
     * Logic: Update the existing record for today.
     */
public function punchOut(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id'                => 'required|exists:users,id',
        'out_lat'                => 'required',
        'out_long'               => 'required',
        'end_km'                 => 'required',
        'out_time_vehicle_type'  => 'required|in:1,2,3',
        'end_km_photo'           => 'required|image|mimes:jpeg,png,jpg',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $userId = $request->user_id;
    $date   = Carbon::now('Asia/Kolkata')->toDateString();

    // 1️⃣ Fetch today's attendance for this user
    $attendance = UserAttendance::where('user_id', $userId)
        ->where('date', $date)
        ->first();

    if (!$attendance) {
        return response()->json([
            'status'  => false,
            'message' => 'Punch-in not found for today.'
        ], 404);
    }

    // 2️⃣ Already punched out check
    if ($attendance->status == '1') {
        return response()->json([
            'status'  => false,
            'message' => 'Already punched out for today.'
        ], 400);
    }

    // 3️⃣ Image upload
    $photoName = null;
    if ($request->hasFile('end_km_photo')) {
        $path = $request->file('end_km_photo')
            ->store('users_punch_out_images', 'public');
        $photoName = basename($path);
    }

    // 4️⃣ Update attendance
    $attendance->update([
        'punch_out_time'        => Carbon::now('Asia/Kolkata')->format('H:i:s'),
        'out_lat'               => $request->out_lat,
        'out_long'              => $request->out_long,
        'end_km'                => $request->end_km,
        'end_km_photo'          => $photoName,
        'out_time_vehicle_type' => $request->out_time_vehicle_type,
        'status'                => '1',
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Punched out successfully',
        'data'    => $attendance
    ], 200);
}
}