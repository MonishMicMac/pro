<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\LeadVisitBdm;
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
            'food_allowance' => 'required|in:1,2',
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

        $schedules = LeadVisitBdm::with(['lead', 'account', 'fabricator'])
            ->where('user_id', $request->user_id)
            ->whereDate('schedule_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        $response = [
            'leads' => [],
            'accounts' => [],
            'fabricators' => []
        ];

        foreach ($schedules as $row) {

            // 1 = accounts
            if ($row->visit_type == '1') {
                $response['accounts'][] = [
                    'id' => $row->account_id,
                    'name' => $row->account?->name,
                    'details' => $row
                ];
            }

            // 2 = leads
            if ($row->visit_type == '2') {
                $response['leads'][] = [
                    'id' => $row->lead_id,
                    'name' => $row->lead?->name,
                    'details' => $row
                ];
            }

            // 3 = fabricators
            if ($row->visit_type == '3') {
                $response['fabricators'][] = [
                    'id' => $row->fabricator_id,
                    'name' => $row->fabricator?->shop_name,
                    'details' => $row
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Today schedule list retrieved successfully',
            'data' => $response
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
     * Get Unplanned Schedule List for a User for bdm
     */
    public function getUnplannedBdmScheduleList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $schedules = LeadVisitBdm::where('user_id', $request->user_id)
            ->where('type', 'unplanned')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Unplanned schedule list retrieved successfully',
            'count'   => $schedules->count(),
            'data'    => $schedules
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
}
