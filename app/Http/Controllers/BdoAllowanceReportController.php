<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserAttendance;

class BdoAllowanceReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Logic: Defaults
        $userId = $request->query('user_id', Auth::id());
        $date = $request->query('date', Carbon::now('Asia/Kolkata')->format('Y-m-d'));

        // Load User
        $user = User::find($userId);

        if (!$user) {
            return back()->with('error', 'User not found');
        }

        // 2. Fetch Attendance (Eloquent)
        $attendance = UserAttendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        // Initialize Data Structure
        $summary = [
            'total_travel_km' => 0,
            'total_food_allowance' => 0,
            'total_travel_allowance' => 0,
            'other_expenses_total' => 0,
            'grand_total_allowance' => 0,
            'food_allowance_type' => 'NA',
            'punch_in' => 'N/A',
            'punch_out' => 'N/A',
            'total_spent_time' => '00:00:00',
            'odometer_start' => 0,
            'odometer_end' => 0,
            'total_odometer' => 0
        ];
        
        $planned = [];
        $unplanned = [];

        // 3. Calculation Logic (Matches your API exactly)
        if ($attendance) {
            
            // Basics
            $summary['punch_in'] = $attendance->punch_in_time;
            $summary['punch_out'] = $attendance->punch_out_time;
            $summary['odometer_start'] = $attendance->start_km;
            $summary['odometer_end'] = $attendance->end_km;

            // Rates
            $rates = DB::table('travel_allowance_masters')
                ->where('action', '0')
                ->pluck('amount', 'vehicle_type')
                ->toArray();

            // Visits Query
            $visits = DB::table('lead_visits as lv')
                ->leftJoin('accounts as acc', 'lv.account_id', '=', 'acc.id')
                ->leftJoin('fabricators as fab', 'lv.fabricator_id', '=', 'fab.id')
                ->leftJoin('leads as ld', 'lv.lead_id', '=', 'ld.id')
                ->select(
                    'lv.*',
                    'acc.name as account_name',
                    'fab.shop_name as fabricator_shop_name',
                    'ld.name as lead_site_name' // Using 'name' based on your provided source code
                )
                ->where('lv.user_id', $user->id)
                ->whereDate('lv.visit_date', $date)
                ->orderBy('lv.intime_time', 'asc') 
                ->get();

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

            // Fallback for role ID
            $userRoleId = $attendance->user_role ?? $user->roles->first()->id ?? 0;

            $foodAllowanceRecord = DB::table('station_allowance_masters')
                ->where('role_id', $userRoleId)
                ->where('station_type', $dayAllowanceTypeId)
                ->where('action', '0')
                ->first();

            $totalFoodAllowance = $foodAllowanceRecord ? $foodAllowanceRecord->amount : 0;
            $stationTypeNames = ['0' => 'NA', '1' => 'Local Station', '2' => 'Out Station', '3' => 'Meeting', '4' => 'Leave'];
            $summary['food_allowance_type'] = $stationTypeNames[$dayAllowanceTypeId] ?? 'Unknown';

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

                // Format Data for View
                $site_name = null; $account_name = null; $fabricator_name = null; $typeName = "Unknown";
                if ($visit->visit_type == 1) { $account_name = $visit->account_name; $typeName = "Account"; }
                elseif ($visit->visit_type == 2) { $site_name = $visit->lead_site_name; $typeName = "Lead"; }
                elseif ($visit->visit_type == 3) { $fabricator_name = $visit->fabricator_shop_name; $typeName = "Fabricator"; }

                $vehicleMap = ['0'=>'None', '1'=>'Bike', '2'=>'Car', '3'=>'Others'];

                $visitData = (object)[
                    "id"                => $visit->id,
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

            // Odometer Diff
            $odometerDiff = 0;
            if ($attendance->start_km && $attendance->end_km) {
                $odometerDiff = (float)$attendance->end_km - (float)$attendance->start_km;
            }

            // Time Spent
            $spentTime = '00:00:00';
            if ($attendance->punch_out_time) {
                $start = Carbon::parse($attendance->punch_in_time);
                $end = Carbon::parse($attendance->punch_out_time);
                $spentTime = $end->diff($start)->format('%H:%I:%S');
            }

            // Finalize Summary
            $summary['total_travel_km'] = round($totalTravelKm, 2);
            $summary['total_odometer'] = (string)$odometerDiff;
            $summary['total_spent_time'] = $spentTime;
            $summary['total_food_allowance'] = number_format((float)$totalFoodAllowance, 2, '.', '');
            $summary['total_travel_allowance'] = number_format((float)$totalTravelAllowance, 2, '.', '');
            $summary['other_expenses_total'] = number_format((float)$totalOtherExpense, 2, '.', '');
            $summary['grand_total_allowance'] = number_format((float)($totalTravelAllowance + $totalFoodAllowance + $totalOtherExpense), 2, '.', '');
        }

        // --- FETCH ONLY BDO USERS (ROLE ID 3) ---
        $bdoRoleId = 3; 
        
        $users = User::whereHas('roles', function($q) use ($bdoRoleId) {
                $q->where('id', $bdoRoleId);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('bdo_allowance_report.index', compact('summary', 'planned', 'unplanned', 'date', 'user', 'users', 'attendance'));
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        if(!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
        $earthRadius = 6371; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}