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

        // 2. Fetch Visits and Join with respective tables for names
        $visits = DB::table('lead_visits as lv')
            ->leftJoin('accounts as acc', 'lv.account_id', '=', 'acc.id')
            ->leftJoin('fabricators as fab', 'lv.fabricator_id', '=', 'fab.id')
            // Assuming a leads table exists; if not, this will return null for lead names
            ->leftJoin('leads as ld', 'lv.lead_id', '=', 'ld.id') 
            ->select(
                'lv.*',
                'acc.name as account_name',
                'fab.shop_name as fabricator_name',
                'ld.name as lead_name'
            )
            ->where('lv.user_id', $user->id)
            ->whereDate('lv.visit_date', $date)
            ->orderBy('lv.intime_time', 'asc')
            ->get();

        $planned = [];
        $unplanned = [];
        $totalTravelKm = 0;
        $totalFoodAllowance = 0;

        // Start Chain for Distance: Punch In Location
        $lastLat = $attendance->in_lat;
        $lastLong = $attendance->in_long;

        foreach ($visits as $visit) {
            // Calculate Distance from last point to this Visit In
            $distanceToSite = $this->haversine($lastLat, $lastLong, $visit->inlat, $visit->inlong);
            $totalTravelKm += $distanceToSite;

            // Fetch Allowance from Master
            $allowance = DB::table('station_allowance_masters')
                ->where('station_type', $visit->food_allowance)
                ->where('action', 0)
                ->value('amount') ?? 0;
            $totalFoodAllowance += $allowance;

            // Determine Site Name and ID
            $siteData = $this->getSiteDetails($visit);

            $visitItem = [
                "id" => $siteData['id'],
                "sitename" => $siteData['name'],
                "visit_type_name" => $siteData['type_label'],
                "intime" => $visit->intime_time,
                "outtime" => $visit->out_time,
                "remarks" => $visit->remarks,
                "work_type" => $visit->work_type,
                "travel_km_to_site" => round($distanceToSite, 2)
            ];

            if ($visit->type === 'planned') {
                $planned[] = $visitItem;
            } else {
                $unplanned[] = $visitItem;
            }

            // Update chain to Visit Out location
            $lastLat = $visit->outlat ?? $visit->inlat;
            $lastLong = $visit->outlong ?? $visit->inlong;
        }

        // Final Leg distance (Last Visit to Punch Out)
        if ($attendance->status == '1' && $attendance->out_lat) {
            $totalTravelKm += $this->haversine($lastLat, $lastLong, $attendance->out_lat, $attendance->out_long);
        }

        // Calculate Odometer Total
        $totalOdometer = 0;
        if ($attendance->start_km && $attendance->end_km) {
            $totalOdometer = (float)$attendance->end_km - (float)$attendance->start_km;
        }

        // Calculate Spent Time
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
                "total_odometer" => (string)$totalOdometer,
                "planned" => $planned,
                "unplanned" => $unplanned
            ]
        ], 200);
    }

    private function getSiteDetails($visit)
    {
        // 1-accounts, 2-leads, 3-fabricators
        switch ($visit->visit_type) {
            case 1:
                return ['id' => $visit->account_id, 'name' => $visit->account_name ?? 'N/A', 'type_label' => 'accounts'];
            case 2:
                return ['id' => $visit->lead_id, 'name' => $visit->lead_name ?? 'N/A', 'type_label' => 'leads'];
            case 3:
                return ['id' => $visit->fabricator_id, 'name' => $visit->fabricator_name ?? 'N/A', 'type_label' => 'fabricators'];
            default:
                return ['id' => null, 'name' => 'Unknown', 'type_label' => 'Unknown'];
        }
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