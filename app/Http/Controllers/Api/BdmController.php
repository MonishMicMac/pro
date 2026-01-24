<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\LeadVisit;
use App\Models\LeadVisitBdm;
use App\Models\UserMapping;
use App\Models\User;
use App\Models\Lead;
use App\Models\Zone;
use App\Models\Brand;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\Area;
use App\Models\Pincodes;
use App\Models\JointWorkRequest;
use App\Models\UserAttendance;


use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

class BdmController extends Controller
{

/**
     * Get BDM Profile Details
     */
    public function getBdmProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            // 1. Fetch User
            $user = User::find($request->user_id);

            // 2. Fetch Related Data (Handling potential nulls or JSON arrays if stored that way)
            
            // Brand (Assuming brand_id might be a single ID or JSON array. Adjust if needed)
            $brandName = null;
            if ($user->brand_id) {
                // If brand_id is stored as a JSON string like "[\"10\",\"11\"]", decode it.
                // If it's a simple integer, just use find.
                // Assuming simple integer based on your description:
                $brand = Brand::find($user->brand_id);
                $brandName = $brand ? $brand->brand_name : null; // Check your Brand table column name
            }

            // Zone
            $zoneName = null;
            if ($user->zone_id) {
                $zone = Zone::find($user->zone_id);
                $zoneName = $zone ? $zone->name : null;
            }

            // State
            $stateName = null;
            if ($user->state_id) {
                $state = State::find($user->state_id);
                $stateName = $state ? $state->name : null;
            }

            // District
            $districtName = null;
            if ($user->district_id) {
                $district = District::find($user->district_id);
                $districtName = $district ? $district->district_name : null;
            }

            // City
            $cityName = null;
            if ($user->city_id) {
                $city = City::find($user->city_id);
                $cityName = $city ? $city->city_name : null;
            }

            // Area
            $areaName = null;
            if ($user->area_id) {
                // Assuming area_id is a single ID. If it's a JSON array of IDs, you'd need loop/implode.
                $area = Area::find($user->area_id);
                $areaName = $area ? $area->area_name : null;
            }

            // Pincode
            $pincodeValue = null;
            if ($user->pincode_id) {
                $pincode = Pincodes::find($user->pincode_id);
                $pincodeValue = $pincode ? $pincode->pincode : null; // Assuming column is 'pincode' or 'code'
            }

            // 3. Construct Response
            $data = [
                'user_id'       => $user->id,
                'name'          => $user->name,
                'designation'   => $user->designation,
                'phone'         => $user->phone, // Ensure this matches your users table column (e.g. 'mobile' or 'phone')
                'email'         => $user->email,
                'doj'           => $user->doj,   // Date of Joining
                'address'       => $user->address ?? null, // Assuming you have an address column
                
                // IDs
                'brand_id'      => $user->brand_id,
                'zone_id'       => $user->zone_id,
                'state_id'      => $user->state_id,
                'district_id'   => $user->district_id,
                'city_id'       => $user->city_id,
                'area_id'       => $user->area_id,
                'pincode_id'    => $user->pincode_id,

                // Resolved Names
                'brand_name'    => $brandName,
                'zone_name'     => $zoneName,
                'state_name'    => $stateName,
                'district_name' => $districtName,
                'city_name'     => $cityName,
                'area_name'     => $areaName,
                'pincode'       => $pincodeValue,
                'total_leads'   => 0,
                'won'           => 0,
                'loss'          => 0,

            ];

            return response()->json([
                'status'  => true,
                'message' => 'BDM profile retrieved successfully',
                'data'    => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

//     /**
//      * Create a Visit Schedule BDM
//      */
// public function storeBdmSchedule(Request $request)
// {
//     $today = Carbon::today()->format('Y-m-d');
//     $maxDate = Carbon::today()->addDays(25)->format('Y-m-d');

//     // 1. Validate
//     $validator = Validator::make($request->all(), [
//         'user_id'        => 'required|exists:users,id',
//         'food_allowance' => 'required|in:1,2,3,4',
//         'schedule_date'  => [
//             'required',
//             'date_format:Y-m-d',
//             'after_or_equal:' . $today,
//             'before_or_equal:' . $maxDate
//         ],
//         'visits'              => 'required|array|min:1',
//         'visits.*.visit_type' => 'required|in:1,2,3',
//         'visits.*.work_type'  => 'required|in:Individual,Joint Work',
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'status'  => false,
//             'message' => $validator->errors()->first(),
//         ], 200);
//     }

//     try {
//         // --- NEW STEP: Fetch User and Role via Spatie ---
//         $user = User::find($request->user_id);
        
//         // getRoleNames() returns a collection (e.g., ['BDM']), so we take the first() one.
//         $userRole = $user && $user->roles->first() ? $user->roles->first()->id : null;
//         // ------------------------------------------------

//         $createdVisits = [];

//         foreach ($request->visits as $visitItem) {

//             $visit = LeadVisitBdm::create([
//                 'user_id'        => $request->user_id,
                
//                 // --- Save the fetched role here ---
//                 'user_role'      => $userRole, 
//                 // ----------------------------------

//                 'type'           => 'planned',
//                 'account_id'     => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
//                 'lead_id'        => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
//                 'fabricator_id'  => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,
//                 'visit_type'     => $visitItem['visit_type'],
//                 'work_type'      => $visitItem['work_type'] ?? 'Individual',
//                 'bdm_id'         => $request->user_id,
//                 'bdo_id'         => $visitItem['bdo_id'] ?? null,
//                 'food_allowance' => $request->food_allowance,
//                 'schedule_date'  => $request->schedule_date,
//                 'action'         => '0',
//             ]);

//             $createdVisits[] = $visit;
//         }

//         return response()->json([
//             'status'  => true,
//             'message' => count($createdVisits) . ' visits scheduled successfully',
//             'data'    => $createdVisits
//         ], 201);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status'  => false,
//             'message' => 'Error: ' . $e->getMessage()
//         ], 200);
//     }
// }

public function storeBdmSchedule(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $maxDate = Carbon::today()->addDays(25)->format('Y-m-d');

        // 1. Validate
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'food_allowance' => 'required|in:1,2,3,4',
            'schedule_date'  => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . $today,
                'before_or_equal:' . $maxDate
            ],
            'work_type'      => 'required|in:Individual,Joint Work',
            'bdo_id'         => 'required_if:work_type,Joint Work|nullable|exists:users,id',
            'visits'         => 'required|array|min:1',
        ], [
            'bdo_id.required_if' => 'You must select a BDO for Joint Work.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            DB::beginTransaction();

            $user = User::find($request->user_id);
            $userRole = $user && $user->roles->first() ? $user->roles->first()->id : null;
            $createdVisits = [];

            // ====================================================
            // STEP 1: Handle JOINT WORK (Import BDO's Planned Data)
            // ====================================================
            if ($request->work_type === 'Joint Work') {
                
                // Fetch ONLY 'planned' visits for the BDO on this date
                $bdoVisits = LeadVisit::where('user_id', $request->bdo_id)
                    ->whereDate('schedule_date', $request->schedule_date)
                    ->where('type', 'planned') 
                    ->get();

                // Validation: If BDO hasn't planned anything, BLOCK the request
                if ($bdoVisits->isEmpty()) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Cannot schedule Joint Work. The selected BDO (ID: $request->bdo_id) has no 'Planned' visits for " . $request->schedule_date . "."
                    ], 200);
                }

                // Import BDO's visits into BDM's table
                foreach ($bdoVisits as $bdoVisit) {
                    $exists = LeadVisitBdm::where('user_id', $request->user_id)
                        ->where('schedule_date', $request->schedule_date)
                        ->where('lead_id', $bdoVisit->lead_id)
                        ->where('account_id', $bdoVisit->account_id)
                        ->where('fabricator_id', $bdoVisit->fabricator_id)
                        ->exists();

                    if (!$exists) {
                        $importedVisit = LeadVisitBdm::create([
                            'user_id'        => $request->user_id, 
                            'user_role'      => $userRole,
                            'type'           => 'planned',
                            
                            // --- CHANGED: Store Source Here ---
                            'data_comes_from'=> 'Joint Work: Imported from BDO Schedule', 
                            
                            // Copy Data from BDO
                            'account_id'     => $bdoVisit->account_id,
                            'lead_id'        => $bdoVisit->lead_id,
                            'fabricator_id'  => $bdoVisit->fabricator_id,
                            'visit_type'     => $bdoVisit->visit_type,
                            
                            'work_type'      => 'Joint Work',
                            'bdm_id'         => $request->user_id,
                            'bdo_id'         => $request->bdo_id,
                            'food_allowance' => $request->food_allowance,
                            'schedule_date'  => $request->schedule_date,
                            'action'         => '0',
                        ]);
                        $createdVisits[] = $importedVisit;
                    }
                }
            }

            // ====================================================
            // STEP 2: Handle BDM's MANUAL Visits (From JSON Array)
            // ====================================================
            foreach ($request->visits as $visitItem) {
                
                $manualVisit = LeadVisitBdm::create([
                    'user_id'        => $request->user_id,
                    'user_role'      => $userRole,
                    'type'           => 'planned',
                    
                    // --- CHANGED: Store Source Here ---
                    'data_comes_from'=> 'Manual Entry', 
                    
                    'account_id'     => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
                    'lead_id'        => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
                    'fabricator_id'  => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,
                    'visit_type'     => $visitItem['visit_type'],
                    
                    'work_type'      => $request->work_type, 
                    'bdm_id'         => $request->user_id,
                    'bdo_id'         => ($request->work_type === 'Joint Work') ? $request->bdo_id : null,
                    
                    'food_allowance' => $request->food_allowance,
                    'schedule_date'  => $request->schedule_date,
                    'action'         => '0',
                ]);

                $createdVisits[] = $manualVisit;
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => count($createdVisits) . ' visits scheduled successfully.',
                'data'    => $createdVisits
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

    /**
     * Get Today's Schedule List for a User
     */
    // public function getBdmScheduleList(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $validator->errors()->first(),
    //         ], 200);
    //     }

    //     // 1. Define today's date
    //     $today = Carbon::today()->toDateString();

    //     // 2. Fetch only today's records for this user
    //     $schedules = LeadVisitBdm::where('user_id', $request->user_id)
    //         ->whereDate('schedule_date', $today)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Today schedule list retrieved successfully',
    //         'count'   => $schedules->count(),
    //         'data'    => $schedules
    //     ], 200);
    // }
    public function getBdmScheduleList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $today = Carbon::today()->toDateString();

        // Added 'user' and 'bdo' relationships to fetch role and bdo_name
        $schedules = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'user', 'bdo'])
            ->where('user_id', $request->user_id)
            ->whereDate('schedule_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        $response = [
            'leads'       => [],
            'accounts'    => [],
            'fabricators' => []
        ];

        foreach ($schedules as $row) {
            // Construct the specific data structure you requested
            $data = [
                'visit_id'        => $row->id,
                'user_id'         => $row->user_id,
                'user_role'       => $row->user ? $row->user->designation : null, // Fetched from User table
                'type'            => $row->type,
                
                // IDs
                'lead_id'         => $row->lead_id,
                'account_id'      => $row->account_id,
                'fabricator_id'   => $row->fabricator_id,
                
                // Names (Resolved from relationships)
                'lead_name'       => $row->lead ? $row->lead->name : null,
                'account_name'    => $row->account ? $row->account->name : null,
                'fabricator_name' => $row->fabricator ? $row->fabricator->shop_name : null,
                
                'visit_type'      => $row->visit_type,
                'schedule_date'   => $row->schedule_date,
                'visit_date'      => $row->visit_date,
                'intime'          => $row->intime,
                'outtime'         => $row->outtime,
                'work_type'       => $row->work_type,
                'remarks'         => $row->remarks,
                'vehicle_type'    => $row->vehicle_type,
                
                // Coordinates
                'in_lat'          => $row->inlat,
                'in_long'         => $row->inlong,
                
                'created_at'      => $row->created_at,
                
                // BDO Details
                'bdo_id'          => $row->bdo_id,
                'bdo_name'        => $row->bdo ? $row->bdo->name : null,
            ];

            // Segregate based on visit_type
            if ($row->visit_type == '1') { // Account
                $response['accounts'][] = $data;
            } elseif ($row->visit_type == '2') { // Lead
                $response['leads'][] = $data;
            } elseif ($row->visit_type == '3') { // Fabricator
                $response['fabricators'][] = $data;
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Today schedule list retrieved successfully',
            'data'    => $response
        ], 200);
    }


    /**
     * Check-in to an existing schedule
     * Updates intime, visit_date, inlat, inlong, and image for bdm
     */
    public function leadBdmCheckIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id' => 'required|exists:lead_visits_bdm,id', // Changed table name in validation to be safe
            'inlat'    => 'required',
            'inlong'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 1. Find the existing schedule record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 3. Update the existing record
            $visit->update([
                'visit_date' => Carbon::now()->format('Y-m-d'), // Stores only date
                'intime'     => Carbon::now()->format('H:i:s'), // CHANGED: intime_time -> intime
                'inlat'      => $request->inlat,
                'inlong'     => $request->inlong,
                'action'     => '0', // As per your requirement to keep/update action to '0'
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Check-in successful',
                'data'    => $visit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }


    /**
     * Check-out to an existing schedule
     * Updates outtime, outlat, outlong, vehicle_type, remarks and image for bdm
     */
    public function leadBdmCheckOut(Request $request)
    {
        // 1. Validate the request (including the image file)
        $validator = Validator::make($request->all(), [
            'visit_id'     => 'required|exists:lead_visits_bdm,id',
            'outlat'       => 'required',
            'outlong'      => 'required',
            'vehicle_type' => 'required|in:1,2,3', // Ensure your DB has this column
            'remarks'      => 'nullable|string',
            'image'        => 'required|file|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 2. Find the existing schedule record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 3. Handle Image Upload
            $imageName = null;
            if ($request->hasFile('image')) {
                // Stores in storage/app/public/visits
                // Ensure you have run: php artisan storage:link
                $path = $request->file('image')->store('visits', 'public');

                // Get just the filename (e.g., "hash.jpg") to store in DB
                $imageName = basename($path);
            }

            // 4. Update the existing record
            $visit->update([
                'outtime'      => Carbon::now()->format('H:i:s'), // Update Out Time
                'outlat'       => $request->outlat,
                'outlong'      => $request->outlong,
                'vehicle_type' => $request->vehicle_type,
                'remarks'      => $request->remarks ?? null,
                'image'        => $imageName, // Store only the filename
                'action'       => '1', // Mark as '1' (Visited/Completed)
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Check-out successful',
                'data'    => $visit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Step 1: Create Unplanned Visit(s)
     * Allows multiple visits in one request array for bdm
     */
    public function storeUnplannedBdmSchedule(Request $request)
    {
        // ============================================================
        // 1. FAIL-SAFE: Force JSON Decoding if Header is Missing
        // ============================================================
        if (empty($request->all()) && !empty($request->getContent())) {
            $data = json_decode($request->getContent(), true);
            if (is_array($data)) {
                $request->merge($data);
            }
        }

        // ============================================================
        // 2. VALIDATION
        // ============================================================
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'visits'  => 'required|array|min:1',

            'visits.*.visit_type' => 'required|in:1,2,3',
            'visits.*.work_type'  => 'required|in:Individual,Joint Work',

            // Dynamic requirements
            'visits.*.account_id'    => 'nullable|required_if:visits.*.visit_type,1',
            'visits.*.lead_id'       => 'nullable|required_if:visits.*.visit_type,2',
            'visits.*.fabricator_id' => 'nullable|required_if:visits.*.visit_type,3',

            'visits.*.bdm_id' => 'nullable|string',
            'visits.*.bdo_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            $today = Carbon::today()->toDateString();

            // 3. AUTO-CALCULATE FOOD ALLOWANCE
            $plannedVisit = LeadVisitBdm::where('user_id', $request->user_id)
                ->where('type', 'planned')
                ->whereDate('schedule_date', $today)
                ->first();

            $autoFoodAllowance = $plannedVisit ? $plannedVisit->food_allowance : '1';

            $createdVisits = [];

            // 4. CREATE VISITS LOOP
            foreach ($request->visits as $visitItem) {

                $visit = LeadVisitBdm::create([
                    'user_id'        => $request->user_id,
                    'type'           => 'unplanned',
                    'schedule_date'  => $today,
                    'food_allowance' => $autoFoodAllowance,
                    'action'         => '0',

                    // Data from JSON array
                    'visit_type'     => $visitItem['visit_type'],
                    'work_type'      => $visitItem['work_type'] ?? 'Individual',

                    // Conditional IDs
                    'account_id'     => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
                    'lead_id'        => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
                    'fabricator_id'  => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,

                    // Manager IDs
                    'bdm_id'         => $visitItem['bdm_id'] ?? $request->user_id,
                    'bdo_id'         => $visitItem['bdo_id'] ?? null,
                ]);

                $createdVisits[] = $visit;
            }

            return response()->json([
                'status'  => true,
                'message' => count($createdVisits) . ' unplanned visit(s) created successfully.',
                'data'    => $createdVisits
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

/**
     * Get Unplanned Schedule List for a User (Formatted)
     */
    public function getUnplannedBdmScheduleList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $today = Carbon::today()->toDateString();

        // 1. Fetch Unplanned visits for Today with relationships
        $schedules = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'user', 'bdo'])
            ->where('user_id', $request->user_id)
            ->where('type', 'unplanned')        // Filter for Unplanned
            ->whereDate('schedule_date', $today) // Filter for Today
            ->orderBy('created_at', 'desc')
            ->get();

        $response = [
            'leads'       => [],
            'accounts'    => [],
            'fabricators' => []
        ];

        foreach ($schedules as $row) {
            // 2. Construct the specific data structure
            $data = [
                'visit_id'        => $row->id,
                'user_id'         => $row->user_id,
                'user_role'       => $row->user ? $row->user->designation : null,
                'type'            => $row->type, // will be 'unplanned'
                
                // IDs
                'lead_id'         => $row->lead_id,
                'account_id'      => $row->account_id,
                'fabricator_id'   => $row->fabricator_id,
                
                // Names (Resolved from relationships)
                'lead_name'       => $row->lead ? $row->lead->name : null,
                'account_name'    => $row->account ? $row->account->name : null,
                'fabricator_name' => $row->fabricator ? $row->fabricator->shop_name : null,
                
                'visit_type'      => $row->visit_type,
                'schedule_date'   => $row->schedule_date,
                'visit_date'      => $row->visit_date,
                'intime'          => $row->intime,
                'outtime'         => $row->outtime,
                'work_type'       => $row->work_type,
                'remarks'         => $row->remarks,
                'vehicle_type'    => $row->vehicle_type,
                
                // Coordinates
                'in_lat'          => $row->inlat,
                'in_long'         => $row->inlong,
                
                'created_at'      => $row->created_at,
                
                // BDO Details
                'bdo_id'          => $row->bdo_id,
                'bdo_name'        => $row->bdo ? $row->bdo->name : null,
            ];

            // 3. Segregate based on visit_type
            if ($row->visit_type == '1') { // Account
                $response['accounts'][] = $data;
            } elseif ($row->visit_type == '2') { // Lead
                $response['leads'][] = $data;
            } elseif ($row->visit_type == '3') { // Fabricator
                $response['fabricators'][] = $data;
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Unplanned schedule list retrieved successfully',
            'data'    => $response
        ], 200);
    }

    /**
     * Unplanned Check-in
     * Updates intime, visit_date, inlat, inlong, and image for an unplanned visit bdm
     */
    public function unplannedBdmCheckIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id' => 'required|exists:lead_visits_bdm,id', // Changed table name in validation
            'inlat'    => 'required',
            'inlong'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 1. Find the existing unplanned record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 2. Security Check
            if ($visit->type !== 'unplanned') {
                return response()->json([
                    'status' => false,
                    'message' => 'This record is not an unplanned visit.'
                ], 200);
            }

            // --- NEW CHECK: Prevent duplicate In-Time ---
            // CHANGED: intime_time -> intime
            if (!empty($visit->intime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Check-in already done for this visit.'
                ], 200);
            }

            // 4. Update the record
            $visit->update([
                'visit_date' => Carbon::now()->format('Y-m-d'),
                'intime'     => Carbon::now()->format('H:i:s'), // CHANGED: intime_time -> intime
                'inlat'      => $request->inlat,
                'inlong'     => $request->inlong,
                'action'     => '1', // Setting to '1' (In-Progress)
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Unplanned Check-in successful',
                'data'    => $visit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Unplanned Check-out
     * Updates outtime, outlat, outlong, vehicle_type, remarks and image for an unplanned visit bdm
     */
    public function unplannedBdmCheckOut(Request $request)
    {
        // 1. Validate inputs (including image and vehicle type)
        $validator = Validator::make($request->all(), [
            'visit_id'     => 'required|exists:lead_visits_bdm,id',
            'outlat'       => 'required',
            'outlong'      => 'required',
            'vehicle_type' => 'required|in:1,2,3',
            'remarks'      => 'nullable|string',
            'image'        => 'required|file|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // 2. Find the existing record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 3. Security Check: Is this actually an Unplanned visit?
            if ($visit->type !== 'unplanned') {
                return response()->json([
                    'status'  => false,
                    'message' => 'This record is not an unplanned visit.'
                ], 200);
            }

            // 4. Logic Check: Ensure they have Checked-In first
            if (empty($visit->intime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You must check-in before checking out.'
                ], 200);
            }

            // 5. Logic Check: Prevent duplicate Check-Out
            if (!empty($visit->outtime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Check-out already completed for this visit.'
                ], 200);
            }

            // 6. Handle Image Upload
            $imageName = null;
            if ($request->hasFile('image')) {
                // Stores in storage/app/public/visits
                $path = $request->file('image')->store('visits', 'public');
                $imageName = basename($path);
            }

            // 7. Update the record
            $visit->update([
                'outtime'      => Carbon::now()->format('H:i:s'),
                'outlat'       => $request->outlat,
                'outlong'      => $request->outlong,
                'vehicle_type' => $request->vehicle_type,
                'remarks'      => $request->remarks ?? null,
                'image'        => $imageName,
                'action'       => '1', // Mark as Completed/Visited
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Unplanned Check-out successful',
                'data'    => $visit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 200);
        }
    }

/**
     * Get Assigned BDO List for a specific BDM
     */
    public function getAssignedBdoList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bdm_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false, 
                'message' => $validator->errors()->first()
            ], 200);
        }

        $bdos = UserMapping::where('bdm_id', $request->bdm_id)
            // FIX: Specify table name to avoid ambiguous column error
            ->where('user_mappings.action', '0') 
            ->join('users', 'user_mappings.bdo_id', '=', 'users.id')
            ->select(
                'users.id', 
                'users.name', 
            )
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Assigned BDO list retrieved successfully',
            'data'    => $bdos
        ], 200);
    }

/**
     * Get Leads generated by the BDM's assigned BDOs with Zone Name
     */
    public function getBdmTeamLeadList(Request $request)
    {
        // 1. Validate (BDM ID)
        $validator = Validator::make($request->all(), [
            'bdm_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        // 2. Get BDO IDs assigned to this BDM
        $bdoIds = UserMapping::where('bdm_id', $request->bdm_id)
            ->where('action', '0') // Active mappings only
            ->pluck('bdo_id')
            ->toArray();

        // 3. Fetch Leads with Zone Name
        $leads = Lead::whereIn('leads.user_id', $bdoIds)
            ->leftJoin('zones', 'leads.zone', '=', 'zones.id') // Join Leads(zone) -> Zones(id)
            ->select(
                'leads.id',
                'leads.user_id',
                'leads.name',
                'leads.phone_number',
                'leads.site_owner_name',
                'leads.site_owner_mobile_number',
                'leads.zone', // The Zone ID (e.g., "5")
                'zones.name as zone_name' // The Zone Name (e.g., "test-123")
            )
            ->orderBy('leads.created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Team lead list retrieved successfully',
            'count'   => count($leads),
            'data'    => $leads
        ], 200);
    }

    /**
     * Get Full Lead Details for a specific Lead (Security Checked)
     */
    public function getLeadDetailsForBdm(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'bdm_id'  => 'required|exists:users,id',
            'lead_id' => 'required|exists:leads,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            // 2. Security Check: Get allowed BDO IDs for this BDM
            $allowedBdoIds = UserMapping::where('bdm_id', $request->bdm_id)
                ->where('action', '0') // Active mappings only
                ->pluck('bdo_id')
                ->toArray();

            // 3. Fetch the Lead with all necessary relationships
            // We use 'whereIn' to ensure this BDM is authorized to view this Lead
            $lead = Lead::with(['user', 'zoneRelation', 'stateRelation', 'districtRelation', 'cityRelation', 'areaRelation', 'pincodeRelation']) // Assuming you have these relationships defined in Lead model
                ->where('id', $request->lead_id)
                ->whereIn('user_id', $allowedBdoIds) 
                ->first();

            if (!$lead) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Lead not found or you are not authorized to view this lead.'
                ], 200);
            }

            // 4. Construct Full Response
            $data = [
                'lead_id'                  => $lead->id,
                'created_by_bdo'           => $lead->user ? $lead->user->name : 'Unknown',
                'lead_source'              => $lead->lead_source,
                'lead_type'                => $lead->lead_type,
                'name'                     => $lead->name,
                'phone_number'             => $lead->phone_number,
                'email'                    => $lead->email_id,
                
                // Location Details
                'address'                  => $lead->address,
                'zone_name'                => $lead->zoneRelation ? $lead->zoneRelation->name : null,
                'state_name'               => $lead->stateRelation ? $lead->stateRelation->name : null,
                'district_name'            => $lead->districtRelation ? $lead->districtRelation->name : null,
                'city_name'                => $lead->cityRelation ? $lead->cityRelation->name : null,
                'area_name'                => $lead->areaRelation ? $lead->areaRelation->name : null,
                'pincode'                  => $lead->pincodeRelation ? $lead->pincodeRelation->code : null, // Assuming column is 'code' or 'pincode'

                // Site Details
                'site_owner_name'          => $lead->site_owner_name,
                'site_owner_mobile_number' => $lead->site_owner_mobile_number,
                'site_stage'               => $lead->site_stage,
                'sales_stage'              => $lead->sales_stage,
                'competitor_brand'         => $lead->competitor_brand,
                'remarks'                  => $lead->remarks,
                
                'created_at'               => $lead->created_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'status'  => true,
                'message' => 'Lead details retrieved successfully',
                'data'    => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

    /**
     * Get List of Active Zones
     */
    public function getZoneList(Request $request)
    {
        // Fetch only active zones (action = '0')
        $zones = Zone::where('action', '0')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Zone list retrieved successfully',
            'data'    => $zones
        ], 200);
    }

    /**
     * Get Tour Plan Calendar (Monthly View)
     * Shows the status (Local, Out Station, Meeting, Leave) for each day
     */
    public function getTourPlanCalendar(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'month'   => 'required|numeric|min:1|max:12',
            'year'    => 'required|numeric|min:2024|max:2030',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        // 2. Define Date Range
        $year = $request->year;
        $month = $request->month;
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // 3. Fetch Existing Plans from DB
        // We group by schedule_date to ensure we get one status per day
        $dbPlans = LeadVisitBdm::where('user_id', $request->user_id)
            ->whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('created_at', 'desc') // Get latest entry if multiple exist
            ->get()
            ->keyBy('schedule_date'); // Key the collection by date for easy lookup

        // 4. Generate Calendar Data for all days in the month
        $calendar = [];
        $currentDate = $startDate->copy();

        // Mapping for Food Allowance Status
        $statusMap = [
            '0' => ['label' => 'Not Planned',  'color' => '#808080'], // Gray
            '1' => ['label' => 'Local Station','color' => '#28a745'], // Green
            '2' => ['label' => 'Out Station',  'color' => '#007bff'], // Blue
            '3' => ['label' => 'Meeting',      'color' => '#ffc107'], // Yellow
            '4' => ['label' => 'Leave',        'color' => '#dc3545'], // Red
        ];

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Check if DB has a record for this date
            if ($dbPlans->has($dateString)) {
                $record = $dbPlans->get($dateString);
                $statusId = $record->food_allowance; // '1','2','3','4'
            } else {
                $statusId = '0'; // Default to Not Planned
            }

            $calendar[] = [
                'date'   => $dateString,
                'day'    => $currentDate->format('l'), // e.g., Monday
                'status' => $statusId,
                'label'  => $statusMap[$statusId]['label'] ?? 'Unknown',
                'color'  => $statusMap[$statusId]['color'] ?? '#000000',
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Tour plan calendar retrieved successfully',
            'data'    => $calendar
        ], 200);
    }

    /**
     * View Tour Plan for a Specific Date
     * Segregates data into Joint Work and Individual Work
     */
    public function viewTourPlan(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        // 2. Fetch Visits for that specific date
        $visits = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'bdo'])
            ->where('user_id', $request->user_id)
            ->whereDate('schedule_date', $request->date)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Initialize Response Structure
        $jointWork = [];
        $individualWork = [];
        
        // Default status map
        $statusMap = [
            '0' => 'Not Planned',
            '1' => 'Local Station',
            '2' => 'Out Station',
            '3' => 'Meeting',
            '4' => 'Leave',
        ];

        // Determine Overall Day Status (Food Allowance) from the first record found
        // If no records, default to '0' (Not Planned)
        $dayStatusId = $visits->isNotEmpty() ? $visits->first()->food_allowance : '0';
        $dayStatusLabel = $statusMap[$dayStatusId] ?? 'Unknown';

        // 4. Loop and Segregate
        foreach ($visits as $row) {
            
            // Resolve the Name based on visit type
            $name = null;
            $location = null; // Optional: Add address/location if available in relations

            if ($row->visit_type == '1') { // Account
                $name = $row->account ? $row->account->name : 'Unknown Account';
            } elseif ($row->visit_type == '2') { // Lead
                $name = $row->lead ? $row->lead->name : 'Unknown Lead';
            } elseif ($row->visit_type == '3') { // Fabricator
                $name = $row->fabricator ? $row->fabricator->shop_name : 'Unknown Fabricator';
            }

            // Build the data object
            $data = [
                'visit_id'      => $row->id,
                'visit_type_id' => $row->visit_type, // 1, 2, or 3
                'visit_type'    => match($row->visit_type) {
                                    '1' => 'Account',
                                    '2' => 'Lead',
                                    '3' => 'Fabricator',
                                    default => 'Unknown'
                                   },
                'name'          => $name,
                
                // Specific IDs
                'lead_id'       => $row->lead_id,
                'account_id'    => $row->account_id,
                'fabricator_id' => $row->fabricator_id,

                // BDO Info (if joint work usually involves a BDO)
                'bdo_name'      => $row->bdo ? $row->bdo->name : null,
                
                'remarks'       => $row->remarks,
                'status'        => ($row->action == '1') ? 'Visited' : 'Pending',
            ];

            // Segregate logic
            if ($row->work_type === 'Joint Work') {
                $jointWork[] = $data;
            } else {
                // Default to individual if null or 'Individual'
                $individualWork[] = $data;
            }
        }

        // 5. Return Response
        return response()->json([
            'status'  => true,
            'message' => 'Tour plan details retrieved successfully',
            'data'    => [
                'date'            => $request->date,
                'day_status'      => $dayStatusLabel, // Local, Out Station, Meeting, etc.
                'total_visits'    => $visits->count(),
                'joint_work'      => $jointWork,
                'individual_work' => $individualWork,
            ]
        ], 200);
    }

    /**
     * Get Pending Joint Work Requests for a BDM
     */
public function getJointWorkRequests(Request $request)
{
    $validator = Validator::make($request->all(), [
        'bdm_id' => 'required|exists:users,id', // This is the BDM ID
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
    }

    $requests = \App\Models\JointWorkRequest::where('bdm_id', $request->bdm_id)
        ->where('status', '0')
        ->with(['bdo', 'visit.lead', 'visit.account', 'visit.fabricator'])
        ->orderBy('created_at', 'desc')
        ->get();

    $data = [];

    // Map for readable names
    $typeMap = [
        '1' => 'Account',
        '2' => 'Lead',
        '3' => 'Fabricator',
    ];

    foreach ($requests as $req) {
        
        $visitTargetName = 'Unknown';
        $visitTypeId     = $req->visit->visit_type ?? null;

        // 1. Resolve Client Name based on ID
        if ($req->visit) {
            if ($visitTypeId == 1) $visitTargetName = $req->visit->account->name ?? 'Unknown Account';
            elseif ($visitTypeId == 2) $visitTargetName = $req->visit->lead->name ?? 'Unknown Lead';
            elseif ($visitTypeId == 3) $visitTargetName = $req->visit->fabricator->shop_name ?? 'Unknown Fabricator';
        }

        // 2. Resolve Visit Type Name (e.g., "3" -> "Fabricator")
        $visitTypeName = $typeMap[$visitTypeId] ?? 'Unknown';

        $data[] = [
            'request_id'      => $req->id,
            'visit_id'        => $req->visit_id,
            'bdo_name'        => $req->bdo->name ?? 'Unknown',
            
            'visit_type'      => $visitTypeId,      // Returns "1", "2", "3"
            'visit_type_name' => $visitTypeName,    // Returns "Account", "Lead", "Fabricator" etc.
            
            'client_name'     => $visitTargetName,
            'requested_at'    => $req->created_at->format('Y-m-d H:i:s'),
            'status'          => 'Pending'
        ];
    }

    return response()->json([
        'status'  => true,
        'message' => 'Pending joint work requests retrieved successfully',
        'data'    => $data
    ], 200);
}

    /**
     * Approve or Decline a Joint Work Request
     */
    public function updateJointWorkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:joint_work_requests,id',
            'bdm_id'     => 'required|exists:users,id', // Security check
            'status'     => 'required|in:1,2', // 1 = Approve, 2 = Decline
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            DB::beginTransaction();

            // 1. Find the Request
            $jointRequest = JointWorkRequest::find($request->request_id);

            // 2. Security Check: Ensure this request belongs to this BDM
            if ($jointRequest->bdm_id != $request->bdm_id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized: This request does not belong to you.'], 200);
            }

            // 3. Check if already processed
            if ($jointRequest->status != '0') {
                return response()->json(['status' => false, 'message' => 'This request has already been processed.'], 200);
            }

            // 4. Update Request Status
            // '1' = Approved, '2' = Declined
            $jointRequest->update([
                'status' => $request->status
            ]);

            // 5. IF APPROVED: Update the Main LeadVisit Table
            if ($request->status == '1') {
                $visit = LeadVisit::find($jointRequest->visit_id);
                
                if ($visit) {
                    $visit->update([
                        'work_type' => 'Joint Work', // Change from Individual to Joint Work
                        'bdm_id'    => $request->bdm_id  // Assign the BDM ID
                    ]);
                }
            }
            // IF DECLINED: We do nothing to LeadVisit. It stays as "Individual" and bdm_id stays null.

            DB::commit();

            $statusMsg = ($request->status == '1') ? 'Approved' : 'Declined';

            return response()->json([
                'status'  => true,
                'message' => 'Joint work request ' . $statusMsg . ' successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }

    // /**
    //  * Get Team Report for BDM
    //  * Shows Plans (Individual/Joint) for all assigned BDOs on a specific date
    //  */
    // public function getBdmTeamReport(Request $request)
    // {
    //     // 1. Validate Inputs
    //     $validator = Validator::make($request->all(), [
    //         'bdm_id' => 'required|exists:users,id',
    //         'date'   => 'required|date_format:Y-m-d',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
    //     }

    //     try {
    //         // 2. Get All Active BDOs assigned to this BDM
    //         $assignedBdos = UserMapping::where('bdm_id', $request->bdm_id)
    //             ->where('user_mappings.action', '0') // Active mappings only
    //             ->join('users', 'user_mappings.bdo_id', '=', 'users.id')
    //             ->select('users.id as bdo_id', 'users.name as bdo_name', 'users.designation')
    //             ->get();

    //         if ($assignedBdos->isEmpty()) {
    //             return response()->json([
    //                 'status' => true, 
    //                 'message' => 'No BDOs assigned to this BDM', 
    //                 'data' => []
    //             ], 200);
    //         }

    //         // 3. Fetch Visits for these BDOs on the specific Date
    //         // Note: We query 'LeadVisit' table (where BDOs store plans), not 'LeadVisitBdm'
    //         $bdoIds = $assignedBdos->pluck('bdo_id')->toArray();

    //         $visits = LeadVisit::with(['lead', 'account', 'fabricator'])
    //             ->whereIn('user_id', $bdoIds)
    //             ->whereDate('schedule_date', $request->date)
    //             ->get()
    //             ->groupBy('user_id'); // Group visits by BDO ID for easy mapping

    //         // 4. Construct the Report
    //         $teamReport = [];

    //         foreach ($assignedBdos as $bdo) {
    //             $bdoId = $bdo->bdo_id;
                
    //             // Initialize BDO Data structure
    //             $bdoData = [
    //                 'bdo_id'          => $bdoId,
    //                 'bdo_name'        => $bdo->bdo_name,
    //                 'designation'     => $bdo->designation,
    //                 'report_status'   => 'No Plan', // Default
    //                 'food_allowance'  => null,
    //                 'individual_work' => [],
    //                 'joint_work'      => []
    //             ];

    //             // Check if this BDO has visits in the fetched collection
    //             if (isset($visits[$bdoId])) {
    //                 $bdoVisits = $visits[$bdoId];
                    
    //                 // Determine Overall Status (Planned vs Unplanned)
    //                 // If at least one visit is 'planned', we consider the day Planned.
    //                 // Otherwise if data exists, it's Unplanned.
    //                 $firstVisit = $bdoVisits->first();
    //                 $bdoData['report_status'] = ucfirst($firstVisit->type); // 'Planned' or 'Unplanned'
                    
    //                 // Map Food Allowance (1=Local, 2=Outstation, etc)
    //                 $foodAllowanceMap = [
    //                     '1' => 'Local Station',
    //                     '2' => 'Out Station',
    //                     '3' => 'Meeting',
    //                     '4' => 'Leave'
    //                 ];
    //                 $bdoData['food_allowance'] = $foodAllowanceMap[$firstVisit->food_allowance] ?? 'Unknown';

    //                 // Loop through visits and categorize
    //                 foreach ($bdoVisits as $visit) {
                        
    //                     // Resolve Client Name
    //                     $clientName = 'Unknown';
    //                     $location = null;
                        
    //                     if ($visit->visit_type == '1') { // Account
    //                         $clientName = $visit->account ? $visit->account->name : 'Unknown Account';
    //                     } elseif ($visit->visit_type == '2') { // Lead
    //                         $clientName = $visit->lead ? $visit->lead->name : 'Unknown Lead';
    //                         $location = $visit->lead ? ($visit->lead->city ?? $visit->lead->site_address) : null;
    //                     } elseif ($visit->visit_type == '3') { // Fabricator
    //                         $clientName = $visit->fabricator ? $visit->fabricator->shop_name : 'Unknown Fabricator';
    //                     }

    //                     $visitDetails = [
    //                         'visit_id'    => $visit->id,
    //                         'client_name' => $clientName,
    //                         'visit_type'  => match((string)$visit->visit_type) {
    //                             '1' => 'Account',
    //                             '2' => 'Lead',
    //                             '3' => 'Fabricator',
    //                             default => 'Unknown'
    //                         },
    //                         'location'    => $location,
    //                         'status'      => ($visit->action == '1') ? 'Visited' : 'Pending',
    //                         'intime'      => $visit->intime_time,
    //                         'outtime'     => $visit->out_time, // Note: LeadController uses out_time
    //                         'remarks'     => $visit->remarks
    //                     ];

    //                     // Segregate into Individual or Joint
    //                     if ($visit->work_type === 'Joint Work') {
    //                         $bdoData['joint_work'][] = $visitDetails;
    //                     } else {
    //                         $bdoData['individual_work'][] = $visitDetails;
    //                     }
    //                 }
    //             }

    //             $teamReport[] = $bdoData;
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Team report retrieved successfully',
    //             'date'    => $request->date,
    //             'data'    => $teamReport
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
    //     }
    // }

/**
     * Get Team Report for BDM
     * Shows Plans (Individual/Joint) for all assigned BDOs on a specific date
     */
    public function getBdmTeamReport(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'bdm_id' => 'required|exists:users,id',
            'date'   => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            // 2. Get All Active BDOs assigned to this BDM
            $assignedBdos = UserMapping::where('bdm_id', $request->bdm_id)
                ->where('user_mappings.action', '0') // Active mappings only
                ->join('users', 'user_mappings.bdo_id', '=', 'users.id')
                ->select('users.id as bdo_id', 'users.name as bdo_name', 'users.designation')
                ->get();

            if ($assignedBdos->isEmpty()) {
                return response()->json([
                    'status' => true, 
                    'message' => 'No BDOs assigned to this BDM', 
                    'data' => []
                ], 200);
            }

            // 3. Fetch Visits for these BDOs on the specific Date
            $bdoIds = $assignedBdos->pluck('bdo_id')->toArray();

            $visits = LeadVisit::with(['lead', 'account', 'fabricator'])
                ->whereIn('user_id', $bdoIds)
                ->whereDate('schedule_date', $request->date)
                ->get()
                ->groupBy('user_id'); 

            // 4. Construct the Report
            $teamReport = [];

            foreach ($assignedBdos as $bdo) {
                $bdoId = $bdo->bdo_id;
                
                // Initialize BDO Data structure
                $bdoData = [
                    'bdo_id'          => $bdoId,
                    'bdo_name'        => $bdo->bdo_name,
                    'designation'     => $bdo->designation,
                    'report_status'   => 'No Plan',
                    'food_allowance'  => null,
                    'individual_work' => [],
                    'joint_work'      => []
                ];

                // Check if this BDO has visits
                if (isset($visits[$bdoId])) {
                    $bdoVisits = $visits[$bdoId];
                    
                    // Determine Overall Status
                    $hasPlanned = $bdoVisits->contains('type', 'planned');
                    $bdoData['report_status'] = $hasPlanned ? 'Planned' : 'Unplanned';
                    
                    // Map Food Allowance
                    $firstVisit = $bdoVisits->first();
                    $foodAllowanceMap = [
                        '1' => 'Local Station',
                        '2' => 'Out Station',
                        '3' => 'Meeting',
                        '4' => 'Leave'
                    ];
                    $bdoData['food_allowance'] = $foodAllowanceMap[$firstVisit->food_allowance] ?? 'Unknown';

                    // Loop through visits
                    foreach ($bdoVisits as $visit) {
                        
                        // --- 1. Resolve Name & Address ---
                        $clientName = 'Unknown';
                        $location   = null;
                        
                        if ($visit->visit_type == '1') { // Account
                            $clientName = $visit->account ? $visit->account->name : 'Unknown Account';
                            // Use 'address' column for Accounts
                            $location   = $visit->account ? $visit->account->address : null; 

                        } elseif ($visit->visit_type == '2') { // Lead
                            $clientName = $visit->lead ? $visit->lead->name : 'Unknown Lead';
                            // Use 'site_address' column for Leads
                            $location   = $visit->lead ? $visit->lead->site_address : null; 

                        } elseif ($visit->visit_type == '3') { // Fabricator
                            $clientName = $visit->fabricator ? $visit->fabricator->shop_name : 'Unknown Fabricator';
                            // Use 'address' column for Fabricators
                            $location   = $visit->fabricator ? $visit->fabricator->address : null;
                        }

                        // --- 2. Calculate Spending Time ---
                        $spendingTime = '-';
                        if ($visit->intime_time && $visit->out_time) {
                            $in  = Carbon::parse($visit->intime_time);
                            $out = Carbon::parse($visit->out_time);
                            // Format: "2h 30m"
                            $spendingTime = $out->diff($in)->format('%Hh %Im'); 
                        }

                        $visitDetails = [
                            'visit_id'        => $visit->id,
                            'client_name'     => $clientName,
                            'visit_type'      => match((string)$visit->visit_type) {
                                '1' => 'Account',
                                '2' => 'Lead',
                                '3' => 'Fabricator',
                                default => 'Unknown'
                            },
                            'planning_status' => ucfirst($visit->type), // "Planned" or "Unplanned"
                            'location'        => $location, // Shows specific address now
                            'status'          => ($visit->action == '1') ? 'Visited' : 'Pending',
                            'intime'          => $visit->intime_time,
                            'outtime'         => $visit->out_time,
                            'spending_time'   => $spendingTime, // New Field
                            'remarks'         => $visit->remarks
                        ];

                        // Segregate into Individual or Joint
                        if ($visit->work_type === 'Joint Work') {
                            $bdoData['joint_work'][] = $visitDetails;
                        } else {
                            $bdoData['individual_work'][] = $visitDetails;
                        }
                    }
                }

                $teamReport[] = $bdoData;
            }

            return response()->json([
                'status'  => true,
                'message' => 'Team report retrieved successfully',
                'date'    => $request->date,
                'data'    => $teamReport
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

  /**
     * Get Kilometers Coverage Data
     * Lists all locations (Check-in/out) + Attendance Punch In/Out details
     */
    public function getKmCoverage(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            // =========================================================
            // PART 1: Get Attendance Data (Punch In/Out Coordinates)
            // =========================================================
            $attendanceRecord = UserAttendance::where('user_id', $request->user_id)
                ->where('date', $request->date)
                ->first();

            $attendanceData = [
                'status'         => 'Absent', // Default
                'punch_in_time'  => null,
                'punch_in_lat'   => null,
                'punch_in_long'  => null,
                'punch_out_time' => null,
                'punch_out_lat'  => null,
                'punch_out_long' => null,
            ];

            if ($attendanceRecord) {
                $attendanceData = [
                    'status'         => $attendanceRecord->status, // e.g., 'Present'
                    'punch_in_time'  => $attendanceRecord->punch_in_time,
                    'punch_in_lat'   => $attendanceRecord->in_lat,
                    'punch_in_long'  => $attendanceRecord->in_long,
                    'punch_out_time' => $attendanceRecord->punch_out_time,
                    'punch_out_lat'  => $attendanceRecord->out_lat,
                    'punch_out_long' => $attendanceRecord->out_long,
                ];
            }

            // =========================================================
            // PART 2: Get Visit Data (In-Progress or Completed)
            // =========================================================
            $visits = LeadVisitBdm::with(['lead', 'account', 'fabricator', 'bdo'])
                ->where('user_id', $request->user_id)
                ->whereDate('schedule_date', $request->date)
                ->whereNotNull('intime') // Must have checked in
                ->orderBy('intime', 'asc')
                ->get();

            $individualWork = [];
            $jointWork      = [];
            $totalVisits    = 0;

            foreach ($visits as $row) {
                // Resolve Client Name
                $clientName = 'Unknown';
                
                if ($row->visit_type == '1') {
                    $clientName = $row->account ? $row->account->name : 'Unknown Account';
                } elseif ($row->visit_type == '2') {
                    $clientName = $row->lead ? $row->lead->name : 'Unknown Lead';
                } elseif ($row->visit_type == '3') {
                    $clientName = $row->fabricator ? $row->fabricator->shop_name : 'Unknown Fabricator';
                }

                $visitStatus = $row->outtime ? 'Completed' : 'In Progress';

                $data = [
                    'visit_id'     => $row->id,
                    'client_name'  => $clientName,
                    'visit_type'   => match((string)$row->visit_type) {
                        '1' => 'Account',
                        '2' => 'Lead',
                        '3' => 'Fabricator',
                        default => 'Unknown'
                    },
                    'work_type'    => $row->work_type,
                    'bdo_name'     => ($row->work_type === 'Joint Work' && $row->bdo) ? $row->bdo->name : null,
                    'visit_status' => $visitStatus,
                    
                    // Visit Coordinates
                    'in_lat'       => $row->inlat,
                    'in_long'      => $row->inlong,
                    'out_lat'      => $row->outlat,
                    'out_long'     => $row->outlong,
                    
                    // Visit Timings
                    'intime'       => $row->intime,
                    'outtime'      => $row->outtime,
                ];

                if ($row->work_type === 'Joint Work') {
                    $jointWork[] = $data;
                } else {
                    $individualWork[] = $data;
                }
                $totalVisits++;
            }

            // =========================================================
            // PART 3: Return Combined Response
            // =========================================================
            return response()->json([
                'status'  => true,
                'message' => 'KM Coverage data retrieved successfully',
                'date'    => $request->date,
                'attendance' => $attendanceData, // <--- New Section
                'counts'  => [
                    'total_visited' => $totalVisits,
                    'individual'    => count($individualWork),
                    'joint'         => count($jointWork)
                ],
                'data'    => [
                    'individual_work' => $individualWork,
                    'joint_work'      => $jointWork,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 200);
        }
    }
}
