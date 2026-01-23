<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\LeadVisitBdm;
use App\Models\UserMapping;
use App\Models\User;
use App\Models\Lead;
use App\Models\Zone;



use Illuminate\Support\Facades\Validator;

class BdmController extends Controller
{

    /**
     * Create a Visit Schedule BDM
     */
    public function storeBdmSchedule(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $maxDate = Carbon::today()->addDays(25)->format('Y-m-d');

        // 1. Validate the structure (Top level + Visits array)
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'food_allowance' => 'required|in:1,2,3,4',
            'schedule_date'  => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . $today,
                'before_or_equal:' . $maxDate
            ],
            'visits'         => 'required|array|min:1',
            'visits.*.visit_type' => 'required|in:1,2,3',
            'visits.*.work_type'  => 'required|in:Individual,Joint Work',
        ], [
            'schedule_date.before_or_equal' => 'You can only schedule visits up to 25 days in advance.',
            'schedule_date.after_or_equal'  => 'The schedule date cannot be in the past.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $createdVisits = [];

            // Loop through each visit in the JSON array
            foreach ($request->visits as $index => $visitItem) {

                $visit = LeadVisitBdm::create([
                    'user_id'        => $request->user_id,
                    'type'           => 'planned',

                    // Mapping IDs based on visit_type
                    'account_id'     => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
                    'lead_id'        => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
                    'fabricator_id'  => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,

                    'visit_type'     => $visitItem['visit_type'],
                    'work_type'      => $visitItem['work_type'] ?? 'Individual',

                    // Use user_id as BDM if it's the manager creating it
                    'bdm_id'         => $request->user_id,
                    'bdo_id'         => $visitItem['bdo_id'] ?? null,

                    // Shared values from the top level
                    'food_allowance' => $request->food_allowance,
                    'schedule_date'  => $request->schedule_date,

                    'action'         => '0',
                ]);

                $createdVisits[] = $visit;
            }

            return response()->json([
                'status'  => true,
                'message' => count($createdVisits) . ' visits scheduled successfully',
                'data'    => $createdVisits
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
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
    //         ], 422);
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
            ], 422);
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
            ], 422);
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
            ], 500);
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
            ], 422);
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
            ], 500);
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
            ], 422);
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
            ], 500);
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
            ], 422);
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
            ], 422);
        }

        try {
            // 1. Find the existing unplanned record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 2. Security Check
            if ($visit->type !== 'unplanned') {
                return response()->json([
                    'status' => false,
                    'message' => 'This record is not an unplanned visit.'
                ], 400);
            }

            // --- NEW CHECK: Prevent duplicate In-Time ---
            // CHANGED: intime_time -> intime
            if (!empty($visit->intime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Check-in already done for this visit.'
                ], 400);
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
            ], 500);
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
            ], 422);
        }

        try {
            // 2. Find the existing record
            $visit = LeadVisitBdm::find($request->visit_id);

            // 3. Security Check: Is this actually an Unplanned visit?
            if ($visit->type !== 'unplanned') {
                return response()->json([
                    'status'  => false,
                    'message' => 'This record is not an unplanned visit.'
                ], 400);
            }

            // 4. Logic Check: Ensure they have Checked-In first
            if (empty($visit->intime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You must check-in before checking out.'
                ], 400);
            }

            // 5. Logic Check: Prevent duplicate Check-Out
            if (!empty($visit->outtime)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Check-out already completed for this visit.'
                ], 400);
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
            ], 500);
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
            ], 422);
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
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
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
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
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
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
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
}
