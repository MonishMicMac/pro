<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\UserAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getConsolidatedReport(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', Carbon::now()->format('Y-m-d'));

        // 1. Fetch Attendance
        $attendance = UserAttendance::where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json(['status' => "false", 'message' => 'No attendance record found.'], 404);
        }

        // 2. Fetch Visits with Joins
        $visits = DB::table('lead_visits as lv')
            ->leftJoin('accounts as acc', 'lv.account_id', '=', 'acc.id')
            ->leftJoin('fabricators as fab', 'lv.fabricator_id', '=', 'fab.id')
            ->leftJoin('leads as ld', 'lv.lead_id', '=', 'ld.id') // Assuming 'leads' table exists
            ->select(
                'lv.*',
                'acc.name as account_name',
                'fab.shop_name as fabricator_shop_name',
                'ld.name as lead_site_name'
            )
            ->where('lv.user_id', $user->id)
            ->whereDate('lv.visit_date', $date)
            ->orderBy('lv.intime_time', 'asc')
            ->get();

        $planned = [];
        $unplanned = [];
        $totalTravelKm = 0;
        $totalFoodAllowance = 0;

        // Initialize coordinates for distance chaining
        $lastLat = $attendance->in_lat;
        $lastLong = $attendance->in_long;

        foreach ($visits as $visit) {
            // Distance Calculation (From last point to current site IN)
            $distanceToSite = $this->haversine($lastLat, $lastLong, $visit->inlat, $visit->inlong);
            $totalTravelKm += $distanceToSite;

            // Food Allowance Calculation
            $allowance = DB::table('station_allowance_masters')
                ->where('station_type', $visit->food_allowance)
                ->where('action', 0)
                ->value('amount') ?? 0;
            $totalFoodAllowance += $allowance;

            // Logic for Dynamic Site Name based on type
            $displayId = null;
            $displayName = "Unknown";
            $typeName = "Unknown";

            if ($visit->visit_type == 1) { // Accounts
                $displayId = $visit->account_id;
                $displayName = $visit->account_name;
                $typeName = "accounts";
            } elseif ($visit->visit_type == 2) { // Leads
                $displayId = $visit->lead_id;
                $displayName = $visit->lead_site_name;
                $typeName = "leads";
            } elseif ($visit->visit_type == 3) { // Fabricators
                $displayId = $visit->fabricator_id;
                $displayName = $visit->fabricator_shop_name;
                $typeName = "fabricators";
            }

            $visitData = [
                "id" => (string)$displayId,
                "site_name" => $displayName ?? "N/A",
                "account_name" => $displayName ?? "N/A",
                "fabricator_name" => $displayName ?? "N/A",
                "visit_type_name" => $typeName,
                "intime" => $visit->intime_time,
                "outtime" => $visit->out_time,
                "remarks" => $visit->remarks,
                "work_type" => $visit->work_type,
                "travel_km_to_site" => round($distanceToSite, 2)
            ];

            if ($visit->type === 'planned') {
                $planned[] = $visitData;
            } else {
                $unplanned[] = $visitData;
            }

            // Update last coordinates to this visit's exit point
            $lastLat = $visit->outlat ?? $visit->inlat;
            $lastLong = $visit->outlong ?? $visit->inlong;
        }

        // Final Return Leg
        if ($attendance->status == '1' && $attendance->out_lat) {
            $totalTravelKm += $this->haversine($lastLat, $lastLong, $attendance->out_lat, $attendance->out_long);
        }

        // Odometer Logic
        $odometerDiff = 0;
        if ($attendance->start_km && $attendance->end_km) {
            $odometerDiff = (float)$attendance->end_km - (float)$attendance->start_km;
        }

        // Spent Time Calculation
        $spentTime = '00:00:00';
        if ($attendance->punch_out_time) {
            $start = Carbon::parse($attendance->punch_in_time);
            $end = Carbon::parse($attendance->punch_out_time);
            $spentTime = $end->diff($start)->format('%H:%I:%S');
        }

        return response()->json([
            "status" => "true",
            "message" => "consolidated report fetched successfully",
            "data" => [
                "user_id" => (string)$user->id,
                "name" => $user->name,
                "role" => $attendance->user_role,
                "date" => $date,
                "punch_in" => $attendance->punch_in_time,
                "punch_out" => $attendance->punch_out_time ?? "N/A",
                "total_spent_time" => $spentTime,
                "total_travel_km" => round($totalTravelKm, 2),
                "total_food_allowance" => number_format($totalFoodAllowance, 2),
                "odometer_start" => $attendance->start_km,
                "odometer_end" => $attendance->end_km,
                "total_odometer" => (string)$odometerDiff,
                "planned" => $planned,
                "unplanned" => $unplanned
            ]
        ], 200);
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}