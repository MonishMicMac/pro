<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\UserAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * BDO Consolidated Report
     */
    public function getConsolidatedReport(Request $request)
    {
        $user = $request->user();
        
        // 1. DATE LOGIC: Use passed date OR default to Today (Asia/Kolkata)
        $date = $request->query('date', Carbon::now('Asia/Kolkata')->format('Y-m-d'));

        // 2. Fetch Attendance
        $attendance = UserAttendance::where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json(['status' => "false", 'message' => 'No attendance record found.'], 404);
        }

        // 3. Fetch Rates Map
        $rates = DB::table('travel_allowance_masters')
            ->where('action', '0')
            ->pluck('amount', 'vehicle_type')
            ->toArray();

        // 4. Fetch Visits (BDO Table: lead_visits)
        $visits = DB::table('lead_visits as lv')
            ->leftJoin('accounts as acc', 'lv.account_id', '=', 'acc.id')
            ->leftJoin('fabricators as fab', 'lv.fabricator_id', '=', 'fab.id')
            ->leftJoin('leads as ld', 'lv.lead_id', '=', 'ld.id')
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
        $totalTravelAllowance = 0;

        // --- FOOD ALLOWANCE ---
        $allowanceTypes = $visits->pluck('food_allowance')->unique()->toArray(); 
        $dayAllowanceTypeId = 0;

        if (in_array('2', $allowanceTypes) || in_array(2, $allowanceTypes)) {
            $dayAllowanceTypeId = 2; // Outstation
        } elseif (in_array('1', $allowanceTypes) || in_array(1, $allowanceTypes)) {
            $dayAllowanceTypeId = 1; // Local
        }

        $userRoleId = $attendance->user_role; 

        $foodAllowanceRecord = DB::table('station_allowance_masters')
            ->where('role_id', $userRoleId)
            ->where('station_type', $dayAllowanceTypeId)
            ->where('action', '0')
            ->first();

        $totalFoodAllowance = $foodAllowanceRecord ? $foodAllowanceRecord->amount : 0;
        
        $stationTypeNames = ['0' => 'NA', '1' => 'Local Station', '2' => 'Out Station', '3' => 'Meeting', '4' => 'Leave'];
        $foodAllowanceName = $stationTypeNames[$dayAllowanceTypeId] ?? 'Unknown';

        // --- EXPENSES ---
        $totalOtherExpense = DB::table('expenses')
            ->where('user_id', $user->id)
            ->whereDate('expense_date', $date)
            ->where('action', '0')
            ->sum('expense_amount');

        // --- LEG-BY-LEG CALCULATION ---
        $lastLat = $attendance->in_lat;
        $lastLong = $attendance->in_long;
        $hasPoint = ($lastLat && $lastLong);
        $lastVehicleType = '0'; 

        foreach ($visits as $visit) {
            $legDistance = 0;
            if ($hasPoint && $visit->inlat && $visit->inlong) {
                $legDistance = $this->haversine($lastLat, $lastLong, $visit->inlat, $visit->inlong);
            }

            $totalTravelKm += $legDistance;

            $vehicleType = (string)$visit->vehicle_type; 
            $lastVehicleType = $vehicleType; 

            if ($vehicleType === '1' || $vehicleType === '2') {
                $rate = $rates[$vehicleType] ?? 0;
                $totalTravelAllowance += ($legDistance * $rate);
            }

            // Update Coordinates
            if ($visit->outlat && $visit->outlong) {
                $lastLat = $visit->outlat;
                $lastLong = $visit->outlong;
                $hasPoint = true;
            } elseif ($visit->inlat && $visit->inlong) {
                $lastLat = $visit->inlat;
                $lastLong = $visit->inlong;
                $hasPoint = true;
            } else {
                $hasPoint = false; 
            }

            // Format Data
            $displayId = null; $site_name = null; $account_name = null; $fabricator_name = null; $typeName = "Unknown";
            if ($visit->visit_type == 1) { $displayId = $visit->account_id; $account_name = $visit->account_name; $typeName = "accounts"; }
            elseif ($visit->visit_type == 2) { $displayId = $visit->lead_id; $site_name = $visit->lead_site_name; $typeName = "leads"; }
            elseif ($visit->visit_type == 3) { $displayId = $visit->fabricator_id; $fabricator_name = $visit->fabricator_shop_name; $typeName = "fabricators"; }

            $vehicleMap = ['0'=>'None', '1'=>'Bike', '2'=>'Car', '3'=>'Others'];

            $visitData = [
                "id"                => (string)$displayId,
                "site_name"         => $site_name,
                "account_name"      => $account_name,
                "fabricator_name"   => $fabricator_name,
                "visit_type_name"   => $typeName,
                "vehicle_used"      => $vehicleMap[$vehicleType] ?? 'None',
                "intime"            => $visit->intime_time,
                "outtime"           => $visit->out_time,
                "travel_km_to_site" => round($legDistance, 2),
            ];

            if ($visit->type === 'planned') $planned[] = $visitData;
            else $unplanned[] = $visitData;
        }

        // Return Leg
        if ($attendance->status == '1' && $attendance->out_lat && $hasPoint) {
            $returnLegDist = $this->haversine($lastLat, $lastLong, $attendance->out_lat, $attendance->out_long);
            $totalTravelKm += $returnLegDist;

            if ($lastVehicleType === '1' || $lastVehicleType === '2') {
                $rate = $rates[$lastVehicleType] ?? 0;
                $totalTravelAllowance += ($returnLegDist * $rate);
            }
        }

        // Grand Total
        $grandTotalAllowance = $totalTravelAllowance + $totalFoodAllowance + $totalOtherExpense;

        // Odometer
        $odometerDiff = 0;
        if ($attendance->start_km && $attendance->end_km) {
            $odometerDiff = (float)$attendance->end_km - (float)$attendance->start_km;
        }

        // Time
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
                "odometer_start" => $attendance->start_km,
                "odometer_end" => $attendance->end_km,
                "total_odometer" => (string)$odometerDiff,

                "food_allowance_type" => $foodAllowanceName,
                "total_food_allowance" => number_format((float)$totalFoodAllowance, 2, '.', ''),
                "total_travel_allowance" => number_format((float)$totalTravelAllowance, 2, '.', ''),
                "other_expenses_total" => number_format((float)$totalOtherExpense, 2, '.', ''),
                "grand_total_allowance" => number_format((float)$grandTotalAllowance, 2, '.', ''),
                
                "planned" => $planned,
                "unplanned" => $unplanned
            ]
        ], 200);
    }

    /**
     * BDM Consolidated Report
     */
    public function getBdmConsolidatedReport(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $userId = $request->user_id;
        
        // 2. DATE LOGIC: Use passed date OR default to Today (Asia/Kolkata)
        $date = $request->query('date', Carbon::now('Asia/Kolkata')->format('Y-m-d'));

        // 3. Fetch Attendance
        $attendance = UserAttendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => false, 
                'message' => 'No attendance found for today (' . $date . ').'
            ], 404);
        }

        // 4. Fetch Rates
        $rates = DB::table('travel_allowance_masters')
            ->where('action', '0')
            ->pluck('amount', 'vehicle_type')
            ->toArray();

        // 5. Fetch Visits (BDM Table: lead_visits_bdm)
        $visits = DB::table('lead_visits_bdm as lv')
            ->leftJoin('accounts as acc', 'lv.account_id', '=', 'acc.id')
            ->leftJoin('fabricators as fab', 'lv.fabricator_id', '=', 'fab.id')
            ->leftJoin('leads as ld', 'lv.lead_id', '=', 'ld.id')
            ->select(
                'lv.*',
                'acc.name as account_name',
                'fab.shop_name as fabricator_shop_name',
                'ld.name as lead_site_name'
            )
            ->where('lv.user_id', $userId)
            ->whereDate('lv.visit_date', $date)
            ->orderBy('lv.intime_time', 'asc') 
            ->get();

        $planned = [];
        $unplanned = [];
        $totalTravelKm = 0;
        $totalTravelAllowance = 0;

        // --- FOOD ALLOWANCE ---
        $allowanceTypes = $visits->pluck('food_allowance')->unique()->toArray(); 
        $dayAllowanceTypeId = 0;

        if (in_array('2', $allowanceTypes) || in_array(2, $allowanceTypes)) {
            $dayAllowanceTypeId = 2; 
        } elseif (in_array('1', $allowanceTypes) || in_array(1, $allowanceTypes)) {
            $dayAllowanceTypeId = 1; 
        }

        $userRoleId = $attendance->user_role; 

        $foodAllowanceRecord = DB::table('station_allowance_masters')
            ->where('role_id', $userRoleId)
            ->where('station_type', $dayAllowanceTypeId)
            ->where('action', '0')
            ->first();

        $totalFoodAllowance = $foodAllowanceRecord ? $foodAllowanceRecord->amount : 0;
        
        $stationTypeNames = ['0' => 'NA', '1' => 'Local Station', '2' => 'Out Station', '3' => 'Meeting', '4' => 'Leave'];
        $foodAllowanceName = $stationTypeNames[$dayAllowanceTypeId] ?? 'Unknown';

        // --- EXPENSES ---
        $totalOtherExpense = DB::table('expenses')
            ->where('user_id', $userId)
            ->whereDate('expense_date', $date)
            ->where('action', '0')
            ->sum('expense_amount');

        // --- LEG-BY-LEG ---
        $lastLat = $attendance->in_lat;
        $lastLong = $attendance->in_long;
        $hasPoint = ($lastLat && $lastLong);
        $lastVehicleType = '0'; 

        foreach ($visits as $visit) {
            $legDistance = 0;
            if ($hasPoint && $visit->inlat && $visit->inlong) {
                $legDistance = $this->haversine($lastLat, $lastLong, $visit->inlat, $visit->inlong);
            }

            $totalTravelKm += $legDistance;

            $vehicleType = (string)$visit->vehicle_type; 
            $lastVehicleType = $vehicleType; 

            if ($vehicleType === '1' || $vehicleType === '2') {
                $rate = $rates[$vehicleType] ?? 0;
                $totalTravelAllowance += ($legDistance * $rate);
            }

            if ($visit->outlat && $visit->outlong) {
                $lastLat = $visit->outlat;
                $lastLong = $visit->outlong;
                $hasPoint = true;
            } elseif ($visit->inlat && $visit->inlong) {
                $lastLat = $visit->inlat;
                $lastLong = $visit->inlong;
                $hasPoint = true;
            } else {
                $hasPoint = false; 
            }

            $displayId = null; $site_name = null; $account_name = null; $fabricator_name = null; $typeName = "Unknown";
            if ($visit->visit_type == 1) { $displayId = $visit->account_id; $account_name = $visit->account_name; $typeName = "accounts"; }
            elseif ($visit->visit_type == 2) { $displayId = $visit->lead_id; $site_name = $visit->lead_site_name; $typeName = "leads"; }
            elseif ($visit->visit_type == 3) { $displayId = $visit->fabricator_id; $fabricator_name = $visit->fabricator_shop_name; $typeName = "fabricators"; }

            $vehicleMap = ['0'=>'None', '1'=>'Bike', '2'=>'Car', '3'=>'Others'];

            $visitData = [
                "id"                => (string)$displayId,
                "site_name"         => $site_name,
                "account_name"      => $account_name,
                "fabricator_name"   => $fabricator_name,
                "visit_type_name"   => $typeName,
                "vehicle_used"      => $vehicleMap[$vehicleType] ?? 'None',
                "intime"            => $visit->intime_time,
                "outtime"           => $visit->out_time,
                "travel_km_to_site" => round($legDistance, 2),
            ];

            if ($visit->type === 'planned') $planned[] = $visitData;
            else $unplanned[] = $visitData;
        }

        // Return Leg
        if ($attendance->status == '1' && $attendance->out_lat && $hasPoint) {
            $returnLegDist = $this->haversine($lastLat, $lastLong, $attendance->out_lat, $attendance->out_long);
            $totalTravelKm += $returnLegDist;

            if ($lastVehicleType === '1' || $lastVehicleType === '2') {
                $rate = $rates[$lastVehicleType] ?? 0;
                $totalTravelAllowance += ($returnLegDist * $rate);
            }
        }

        // Grand Total
        $grandTotalAllowance = $totalTravelAllowance + $totalFoodAllowance + $totalOtherExpense;

        $odometerDiff = 0;
        if ($attendance->start_km && $attendance->end_km) {
            $odometerDiff = (float)$attendance->end_km - (float)$attendance->start_km;
        }

        $spentTime = '00:00:00';
        if ($attendance->punch_out_time) {
            $start = Carbon::parse($attendance->punch_in_time);
            $end = Carbon::parse($attendance->punch_out_time);
            $spentTime = $end->diff($start)->format('%H:%I:%S');
        }

        // User Name
        $userName = DB::table('users')->where('id', $userId)->value('name') ?? 'Unknown User';

        return response()->json([
            "status" => "true",
            "message" => "BDM consolidated report fetched successfully",
            "data" => [
                "user_id" => (string)$userId,
                "name" => $userName,
                "role" => $attendance->user_role,
                "date" => $date,
                "punch_in" => $attendance->punch_in_time,
                "punch_out" => $attendance->punch_out_time ?? "N/A",
                "total_spent_time" => $spentTime,
                
                "total_travel_km" => round($totalTravelKm, 2),
                "odometer_start" => $attendance->start_km,
                "odometer_end" => $attendance->end_km,
                "total_odometer" => (string)$odometerDiff,

                // --- FINANCIALS ---
                "food_allowance_type" => $foodAllowanceName,
                "total_food_allowance" => number_format((float)$totalFoodAllowance, 2, '.', ''),
                "total_travel_allowance" => number_format((float)$totalTravelAllowance, 2, '.', ''),
                "other_expenses_total" => number_format((float)$totalOtherExpense, 2, '.', ''),
                "grand_total_allowance" => number_format((float)$grandTotalAllowance, 2, '.', ''),
                
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