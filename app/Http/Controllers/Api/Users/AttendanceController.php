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
            'start_km_photo'        => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => "false", 'message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();
        $todayDate = Carbon::now()->format('Y-m-d'); // Current date for the 'date' column

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

        // 3. Get User Role Name
        $role = $user->roles->first() ? $user->roles->first()->name : 'Unknown';

        // 4. Create Attendance Record
        $attendance = UserAttendance::create([
            'user_id'              => $user->id,
            'user_role'            => $role,
            'date'                 => $todayDate,               // Store Y-m-d
            'punch_in_time'        => Carbon::now()->format('H:i:s'), // Store Time only
            'in_lat'               => $request->in_lat,
            'in_long'              => $request->in_long,
            'start_km'             => $request->start_km,
            'start_km_photo'       => $photoName,
            'in_time_vehicle_type' => $request->in_time_vehicle_type,
            'status'               => '0', // 0: In Progress
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
        'attendance_id'         => 'required|exists:users_attendances,id', // Use ID from Punch In response
        'out_lat'               => 'required',
        'out_long'              => 'required',
        'end_km'                => 'required',
        'out_time_vehicle_type' => 'required|in:1,2,3',
        'end_km_photo'          => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => "false", 'message' => $validator->errors()->first()], 422);
    }

    // 1. Find the specific record by ID
    $attendance = UserAttendance::find($request->attendance_id);

    // 2. Security Check: Ensure this attendance belongs to the logged-in user
    if ($attendance->user_id !== $request->user()->id) {
        return response()->json(['status' => "false", 'message' => 'Unauthorized.'], 403);
    }

    // 3. Status Check: Ensure they haven't already punched out
    if ($attendance->status === '1') {
        return response()->json(['status' => "false", 'message' => 'Already punched out for this record.'], 400);
    }

    // 4. Handle Image Upload
    $photoName = null;
    if ($request->hasFile('end_km_photo')) {
        $path = $request->file('end_km_photo')->store('users_punch_out_images', 'public');
        $photoName = basename($path);
    }

    // 5. Update Record
    $attendance->update([
        'punch_out_time'        => Carbon::now()->format('H:i:s'),
        'out_lat'               => $request->out_lat,
        'out_long'              => $request->out_long,
        'end_km'                => $request->end_km,
        'end_km_photo'          => $photoName,
        'out_time_vehicle_type' => $request->out_time_vehicle_type,
        'status'                => '1', 
    ]);

    return response()->json([
        'status' => "true",
        'message' => 'Punched out successfully',
        'data' => $attendance
    ], 200);
}
}