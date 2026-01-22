<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use App\Models\Lead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\LeadHandoverPhoto;
use App\Models\FabricatorRequest;
use App\Models\LeadVisit;
use App\Models\MeasurementDetail;
use App\Helpers\LeadHelper;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
public function storeSiteIdentification(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id'          => 'required|exists:users,id',
        'city'             => 'required|string|max:255',
        'site_owner_name'  => 'required|string|max:255',
        'site_owner_mobile_number'  => 'required|string|max:255',
        'deciding_authority'  => 'required|string|max:255',
        'deciding_authority_mobile_number'  => 'required|string|max:255',
        'site_area'        => 'required|string|max:255',
        'site_address'     => 'required|string',
        'latitude'         => 'required|numeric|between:-90,90',
        'longitude'        => 'required|numeric|between:-180,180',
        'type_of_building' => 'required|string|max:255',
        'building_status'  => 'required|string|max:255',
        'image'            => 'required|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    $lead = Lead::create([
        'user_id'          => $request->user_id,
        'city'             => $request->city,
        'site_area'        => $request->site_area,
        'site_address'     => $request->site_address,
        'latitude'         => $request->latitude,
        'longitude'        => $request->longitude,
        'type_of_building' => $request->type_of_building,
        'building_status'  => $request->building_status,

        // System-controlled fields
        'lead_stage'  => 0,
        'lead_source' => 'OWN',
        'status'      => '0',
        'created_by'  => $request->user_id,
    ]);

    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('lead_images', 'public');
        \App\Models\LeadImage::create([
            'lead_id'    => $lead->id,
            'lead_stage' => '0',
            'img_path'   => $imagePath,
            'action'     => '0'
        ]);
    }

    return response()->json([
        'status'  => true,
        'message' => 'Site identification created successfully',
        'data'    => [
            'id'         => $lead->id,
            'lead_stage' => $lead->lead_stage,
            'created_at' => $lead->created_at,
        ],
    ], 200);
}



// Add this to your existing LeadController

public function getLeadsByUser(Request $request)
{
    // 1. Validate the input
    $validator = Validator::make($request->all(), [
        'user_id'   => 'required|exists:users,id',
        'from_date' => 'nullable|date',
        'to_date'   => 'nullable|date|after_or_equal:from_date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // 2. Start the query
    $query = Lead::where('user_id', $request->user_id);

    // 3. Apply optional date filters (using created_at)
    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->from_date);
    }

    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->to_date);
    }

    // 4. Get the results (ordered by newest first)
    $leads = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'status'  => true,
        'message' => 'Leads retrieved successfully',
        'count'   => $leads->count(),
        'data'    => $leads,
    ], 200);
}

/**
 * Create a Visit Schedule BDO
 */
public function storeSchedule(Request $request)
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
            
            $visit = LeadVisit::create([
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
public function getScheduleList(Request $request)
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

    // 1. Define today's date
    $today = Carbon::today()->toDateString();

    // 2. Fetch only today's records for this user
    $schedules = LeadVisit::where('user_id', $request->user_id)
        ->whereDate('schedule_date', $today)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status'  => true,
        'message' => 'Today schedule list retrieved successfully',
        'count'   => $schedules->count(),
        'data'    => $schedules
    ], 200);
}


/**
 * Check-in to an existing schedule
 * Updates intime_time, visit_date, inlat, inlong, and image for bdo
 */
public function leadCheckIn(Request $request)
{
    $validator = Validator::make($request->all(), [
        'visit_id' => 'required|exists:lead_visits,id', // Use the primary ID of the table
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
        $visit = LeadVisit::find($request->visit_id);

        // 3. Update the existing record
        $visit->update([
            'visit_date'  => Carbon::now()->format('Y-m-d'), // Stores only date
            'intime_time' => Carbon::now()->format('H:i:s'), // Stores only time
            'inlat'       => $request->inlat,
            'inlong'      => $request->inlong,
            'action'      => '0', // As per your requirement to keep/update action to '0'
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

public function leadCheckOut(Request $request)
{
    // 1. Base Validation Rules
    $rules = [
        'visit_id'    => 'required|exists:lead_visits,id',
        'outlat'      => 'required',
        'outlong'     => 'required',
        'remarks'     => 'nullable|string',
        'action_type' => 'required|string', 
    ];

    // 2. Add Dynamic Rules based on action_type
    if ($request->action_type == 'intro_stage') {
        $rules['customer_name']    = 'required|string|max:255';
        $rules['contact_number']   = 'required|string|max:20';
        $rules['customer_type']    = 'required|string';
        $rules['building_stage']   = 'required|string';
        $rules['no_of_window']     = 'required|integer';
        $rules['lead_temperature'] = 'required|in:Hot,Warm,Cold';
        $rules['meeting_type']     = 'required|in:Site Visit,Office,Others';
    }
    elseif ($request->action_type == 'followup_meeting') {
        $rules['total_required_area_sqft'] = 'required|numeric|min:1';
        $rules['current_building_stage']   = 'required|string';
    }
    elseif ($request->action_type == 'quotation_pending_with_fabricator') {
        $rules['fabricator_id'] = 'required|exists:users,id';
        $rules['notes']         = 'nullable|string';
        $rules['priority']      = 'required|integer'; 

        $rules['measurements'] = 'required|array|min:1';
        $rules['measurements.*.product']     = 'required|string';
        $rules['measurements.*.width_val']   = 'required|numeric';
        $rules['measurements.*.width_unit']  = 'required|in:mm,ft,inch';
        $rules['measurements.*.height_val']  = 'required|numeric';
        $rules['measurements.*.height_unit'] = 'required|in:mm,ft,inch';
        $rules['measurements.*.qty']         = 'required|integer|min:1';
    }
    elseif ($request->action_type == 'quotationsent_followup') {
        $rules['follow_up_date'] = 'required|date|after_or_equal:today';
    }
    elseif ($request->action_type == 'won') {
        $rules['per_sq_ft_rate']             = 'required|numeric';
        $rules['expected_installation_date'] = 'required|date';
        $rules['advance_received']           = 'required|numeric';
        $rules['final_quotation_pdf']        = 'nullable|mimes:pdf|max:5120';
    }
    elseif ($request->action_type == 'lost') {
        $rules['lost_type']  = 'required|string';
        $rules['competitor'] = 'nullable|string';
    }
    elseif ($request->action_type == 'site_handover') {
        $rules['installed_date']      = 'required|date';
        $rules['handovered_date']     = 'required|date';
        $rules['final_site_photos']   = 'required|array|min:1';
        $rules['final_site_photos.*'] = 'image|mimes:jpeg,png,jpg|max:5120';
    }

    // 3. Execute Validation
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    try {
        DB::beginTransaction();

        // 4. Find the Visit and Lead
        $visit = LeadVisit::find($request->visit_id);
        $lead  = Lead::find($visit->lead_id);

        if (!$lead) {
            throw new \Exception("Lead not found for this visit.");
        }

        // --- STAGE PROTECTION LOGIC (No Go Back) ---
        // Using the logic from your snippet: won=5, handover=6, lost=7
        $finalStages = [5, 6, 7]; 
        if (in_array($lead->lead_stage, $finalStages)) {
            $allowedTransition = false;
            // Allow transition to handover (6) only if currently Won (5)
            if ($lead->lead_stage == 5 && $request->action_type == 'site_handover') {
                $allowedTransition = true;
            }
            if (!$allowedTransition) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Action Denied: This lead is already in "' . $lead->status . '" stage.'
                ], 403);
            }
        }
        // -------------------------------------------


        // 5. Process Logic Based on Action Type
        switch ($request->action_type) {

            case 'intro_stage':
                $lead->update([
                    'name'             => $request->customer_name,
                    'phone_number'     => $request->contact_number,
                    'customer_type'    => $request->customer_type,
                    'building_status'  => $request->building_stage,
                    'no_of_windows'    => $request->no_of_window,    
                    'lead_temperature' => $request->lead_temperature, 
                    'meeting_type'     => $request->meeting_type,     
                    'lead_stage'       => 1, 
                ]);
                break;

            case 'followup_meeting':
                $lead->update([
                    'total_required_area_sqft' => $request->total_required_area_sqft,
                    'building_status'          => $request->current_building_stage,
                    'lead_stage'               => 2, 
                ]);
                break;

            case 'quotation_pending_with_fabricator':
                
                $totalCalculatedSqft = 0;

                // A. Loop through measurements
                foreach ($request->measurements as $item) {
                    $wInFeet = $this->convertToFeet($item['width_val'], $item['width_unit']);
                    $hInFeet = $this->convertToFeet($item['height_val'], $item['height_unit']);
                    $calculatedSqft = $wInFeet * $hInFeet * $item['qty'];

                    $totalCalculatedSqft += $calculatedSqft;

                    MeasurementDetail::create([
                        'lead_id'     => $lead->id,
                        'user_id'     => $lead->user_id,
                        'product'     => $item['product'],
                        'design_code' => $item['design_code'] ?? null,
                        'area'        => $item['area'] ?? null,
                        'width_val'   => $item['width_val'],
                        'width_unit'  => $item['width_unit'],
                        'height_val'  => $item['height_val'],
                        'height_unit' => $item['height_unit'],
                        'qty'         => $item['qty'],
                        'color'       => $item['color'] ?? null,
                        'sqft'        => round($calculatedSqft, 2),
                        'notes'       => $item['notes'] ?? null,
                    ]);
                }

                // B. Create Fabricator Request
                FabricatorRequest::create([
                    'lead_id'       => $lead->id,
                    'fabricator_id' => $request->fabricator_id,
                    'approx_sqft'   => round($totalCalculatedSqft, 2),
                    'notes'         => $request->notes,
                ]);

                // C. Update Lead
                $lead->update([
                    'lead_stage' => 3, 
                    'priority'   => $request->priority
                ]); 
                break;

            case 'quotationsent_followup':
                $lead->update([
                    'follow_up_date' => $request->follow_up_date,
                ]);
                break;

            case 'won':
                $pdfPath = $lead->final_quotation_pdf;
                if ($request->hasFile('final_quotation_pdf')) {
                    $pdfPath = $request->file('final_quotation_pdf')->store('final_quotes', 'public');
                }
                $lead->update([
                    'lead_stage'                 => 5,
                    'rate_per_sqft'              => $request->per_sq_ft_rate,
                    'won_date'                   => Carbon::today(),
                    'expected_installation_date' => $request->expected_installation_date,
                    'advance_received'           => $request->advance_received,
                    'final_quotation_pdf'        => $pdfPath,
                    'status'                     => 'Won'
                ]);
                break;

            case 'lost':
                $lead->update([
                    'lead_stage' => 7,
                    'lost_type'  => $request->lost_type,
                    'competitor' => $request->competitor
                ]);
                break;

            case 'site_handover':
                if ($request->hasFile('final_site_photos')) {
                    foreach ($request->file('final_site_photos') as $photo) {
                        $path = $photo->store('handover_photos', 'public');
                        LeadHandoverPhoto::create([
                            'lead_id'    => $lead->id,
                            'photo_path' => $path
                        ]);
                    }
                }
                $lead->update([
                    'lead_stage'      => 6,
                    'installed_date'  => $request->installed_date,
                    'handovered_date' => $request->handovered_date,
                    'google_review'   => $request->google_review ?? null,
                    'status'          => '0' 
                ]);
                break;
        }

        // 6. Log Status
        LeadHelper::logStatus(
            $lead,
            $lead->building_status ?? 'Status Updated',
            $lead->lead_stage
        );

        // 7. Checkout the Visit
        $visit->update([
            'out_time'   => Carbon::now()->toTimeString(),
            'outlat'     => $request->outlat,
            'outlong'    => $request->outlong,
            'remarks'    => $request->remarks,
            'lead_stage' => $lead->lead_stage      // <--- ADDED: Storing the updated lead stage
        ]);

        DB::commit();

        return response()->json([
            'status'  => true,
            'message' => 'Check-out successful and Lead updated for ' . $request->action_type,
            'data'    => $visit
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
}


/**
     * Step 1: Create Unplanned Visit(s)
     * Allows multiple visits in one request array for bdo
     */

    public function storeUnplannedSchedule(Request $request)
    {
        // ============================================================
        // 1. FAIL-SAFE: Force JSON Decoding if Header is Missing
        // ============================================================
        // If $request->all() is empty but there is raw content, decode it manually.
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
            // Check if there is a PLANNED visit for today to copy allowance setting
            $plannedVisit = \App\Models\LeadVisit::where('user_id', $request->user_id)
                ->where('type', 'planned')
                ->whereDate('schedule_date', $today)
                ->first();

            $autoFoodAllowance = $plannedVisit ? $plannedVisit->food_allowance : '1';

            $createdVisits = [];

            // 4. CREATE VISITS LOOP
            foreach ($request->visits as $visitItem) {
                
                $visit = \App\Models\LeadVisit::create([
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
 * Get Unplanned Schedule List for a User
 */
public function getUnplannedScheduleList(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    $schedules = LeadVisit::where('user_id', $request->user_id)
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
 * Updates intime_time, visit_date, inlat, inlong, and image for an unplanned visit bdo
 */
public function unplannedCheckIn(Request $request)
{
    $validator = Validator::make($request->all(), [
        'visit_id' => 'required|exists:lead_visits,id', 
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
        $visit = LeadVisit::find($request->visit_id);

        // 2. Security Check
        if ($visit->type !== 'unplanned') {
            return response()->json([
                'status' => false,
                'message' => 'This record is not an unplanned visit.'
            ], 400);
        }

        // --- NEW CHECK: Prevent duplicate In-Time ---
        if (!empty($visit->intime_time)) {
            return response()->json([
                'status'  => false,
                'message' => 'Check-in already done for this visit.'
            ], 400);
        }

        // 4. Update the record
        $visit->update([
            'visit_date'  => Carbon::now()->format('Y-m-d'),
            'intime_time' => Carbon::now()->format('H:i:s'),
            'inlat'       => $request->inlat,
            'inlong'      => $request->inlong,
            'action'      => '1', // Setting to '1' (In-Progress)
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



public function storeFollowupMeeting(Request $request)
{
    // 1. Validate the specific fields required for this stage
    $validator = Validator::make($request->all(), [
        'lead_id'                  => 'required|exists:leads,id',
        'current_building_stage'   => 'required|string|max:255',
        'total_required_area_sqft' => 'required|numeric|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // 2. Find the lead
    $lead = Lead::find($request->lead_id);

    // 3. Update the lead details and move to stage 3
    $lead->update([
        'building_status'          => $request->current_building_stage,
        'total_required_area_sqft' => $request->total_required_area_sqft,
        'lead_stage'               => 3, // Changed to Follow-up stage
    ]);

    // 4. Log the status using your existing Helper
    LeadHelper::logStatus(
        $lead,
        $request->current_building_stage,
        3
    );

    return response()->json([
        'status'  => true,
        'message' => 'Follow-up meeting details updated and lead moved to Stage 3',
        'data'    => $lead
    ], 200);
}

public function storeMeasurements(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lead_id'      => 'required|exists:leads,id',
        'user_id'      => 'required|exists:users,id',
        'priority'     => 'required|integer', // <--- Added Validation
        'measurements' => 'required|array|min:1',
        'measurements.*.width_val'   => 'required|numeric',
        'measurements.*.width_unit'  => 'required|in:mm,ft,inch',
        'measurements.*.height_val'  => 'required|numeric',
        'measurements.*.height_unit' => 'required|in:mm,ft,inch',
        'measurements.*.qty'         => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    try {
        DB::beginTransaction();

        foreach ($request->measurements as $item) {
            $wInFeet = $this->convertToFeet($item['width_val'], $item['width_unit']);
            $hInFeet = $this->convertToFeet($item['height_val'], $item['height_unit']);
            $calculatedSqft = $wInFeet * $hInFeet * $item['qty'];

            MeasurementDetail::create([
                'lead_id'     => $request->lead_id,
                'user_id'     => $request->user_id,
                'product'     => $item['product'],
                'design_code' => $item['design_code'],
                'area'        => $item['area'],
                'width_val'   => $item['width_val'],
                'width_unit'  => $item['width_unit'],
                'height_val'  => $item['height_val'],
                'height_unit' => $item['height_unit'],
                'qty'         => $item['qty'],
                'color'       => $item['color'],
                'sqft'        => round($calculatedSqft, 2),
                'notes'       => $item['notes'] ?? null,
            ]);
        }

        // Update Lead Stage AND Priority
        Lead::where('id', $request->lead_id)->update([
            'lead_stage' => 4,
            'priority'   => $request->priority // <--- Saving the priority here
        ]);

        DB::commit();

        return response()->json(['status' => true, 'message' => 'Success'], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}
    /**
     * Helper to convert various units to Feet
     */
    private function convertToFeet($value, $unit) 
    {
        switch ($unit) {
            case 'inch': 
                return $value / 12;
            case 'mm':   
                return $value / 304.8;
            case 'ft':   
                return $value;
            default:     
                return $value;
        }
    }


public function getMeasurementsByLead(Request $request)
{
    // 1. Validate that the lead exists
    $validator = Validator::make($request->all(), [
        'lead_id' => 'required|exists:leads,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // 2. Fetch the Lead to get the priority
    $lead = Lead::find($request->lead_id);

    // 3. Fetch measurements
    $measurements = MeasurementDetail::where('lead_id', $request->lead_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // 4. Calculate grand total
    $totalLeadSqft = $measurements->sum('sqft');

    return response()->json([
        'status'          => true,
        'message'         => 'Measurements retrieved successfully',
        'count'           => $measurements->count(),
        'priority'        => $lead->priority, // <--- Added priority here
        'total_lead_sqft' => round($totalLeadSqft, 2),
        'data'            => $measurements,
    ], 200);
}


public function sendToFabricator(Request $request)
{
    // 1. Validate the request
    $validator = Validator::make($request->all(), [
        'lead_id'       => 'required|exists:leads,id',
        'fabricator_id' => 'required|exists:fabricator_requests,id',
        'approx_sqft'   => 'required|numeric|min:0.01',
        'notes'         => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    try {
        DB::beginTransaction();

        // 2. Store the fabricator request
        $fabRequest = FabricatorRequest::create([
            'lead_id'       => $request->lead_id,
            'fabricator_id' => $request->fabricator_id,
            'approx_sqft'   => $request->approx_sqft,
            'notes'         => $request->notes,
        ]);

        // 3. Update Lead Stage to 5
        $lead = Lead::find($request->lead_id);
        $lead->update(['lead_stage' => 5]);

        // 4. Log the status change
        LeadHelper::logStatus(
            $lead,
            $lead->building_status,
            5
        );

        DB::commit();

        return response()->json([
            'status'  => true,
            'message' => 'Request sent to fabricator successfully',
            'data'    => $fabRequest
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status'  => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

public function getLeadMeasurements(Request $request)
{
    // 1. Validate that the lead_id is provided and exists
    $validator = Validator::make($request->all(), [
        'lead_id' => 'required|exists:leads,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // 2. Fetch all measurement records for this lead
    $measurements = \App\Models\MeasurementDetail::where('lead_id', $request->lead_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // 3. Optional: Calculate the total Sq.ft for all items in this lead
    $totalSqft = $measurements->sum('sqft');

    return response()->json([
        'status'         => true,
        'message'        => 'Measurement details retrieved successfully',
        'total_items'    => $measurements->count(),
        'total_lead_sqft' => round($totalSqft, 2),
        'data'           => $measurements,
    ], 200);
}

public function getFabricatorAssignments(Request $request)
{
    // 1. Validate the fabricator ID
    $validator = Validator::make($request->all(), [
        'fabricator_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // 2. Fetch requests with related Lead, Lead Creator (User), and Measurement Details
    $assignments = \App\Models\FabricatorRequest::with([
            'lead:id,name,user_id', // Get lead name and the owner ID
            'lead.assignedUser:id,name' // Get the name of the salesperson (User)
        ])
        ->where('fabricator_id', $request->fabricator_id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($request) {
            // 3. Attach all measurements belonging to this lead
            $request->measurements = \App\Models\MeasurementDetail::where('lead_id', $request->lead_id)->get();
            return $request;
        });

    return response()->json([
        'status'  => true,
        'message' => 'Fabricator assignments retrieved successfully',
        'count'   => $assignments->count(),
        'data'    => $assignments,
    ], 200);
}

public function uploadFabricationDetails(Request $request)
{
    // 1. Initial Validation
    $validator = Validator::make($request->all(), [
        'lead_id'       => 'required|exists:leads,id',
        'fabricator_id' => 'required|exists:users,id',
        'rate_per_sqft' => 'required|numeric|min:0',
        'pdf_file'      => 'required|file|mimes:pdf|max:10240', 
    ]);

    if ($validator->fails()) {
        // This will now catch the "failed to upload" error early
        return response()->json([
            'status'  => false,
            'message' => 'Validation Error',
            'reason'  => $validator->errors()->first()
        ], 422);
    }

    try {
        DB::beginTransaction();

        $file = $request->file('pdf_file');

        // Check for PHP system errors during upload
        if (!$file->isValid()) {
            throw new \Exception("System Upload Error: " . $file->getErrorMessage());
        }

        $fileName = 'fab_' . $request->lead_id . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store the file and verify success
        $path = $file->storeAs('fabrication_docs', $fileName, 'public');

        if (!$path) {
            throw new \Exception("Failed to write file to disk. Check storage folder permissions.");
        }

        // Update Record
        $fabRequest = \App\Models\FabricatorRequest::where('lead_id', $request->lead_id)
            ->where('fabricator_id', $request->fabricator_id)
            ->firstOrFail();

        $fabRequest->update([
            'fabrication_pdf' => $path,
            'rate_per_sqft'   => $request->rate_per_sqft,
            'status'          => '1'
        ]);

        // Update Lead Stage to 4
        $lead = \App\Models\Lead::find($request->lead_id);
        $lead->update(['lead_stage' => 4]);

        \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 4);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Quotation uploaded successfully',
            'pdf_url' => asset('storage/' . $path)
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status'  => false,
            'message' => 'Upload Failed',
            'reason'  => $e->getMessage()
        ], 500);
    }
}


public function updateLeadFinalStatus(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lead_id' => 'required|exists:leads,id',
        'is_won'  => 'required|in:0,1', // 0 = Won, 1 = Lost
        
        // Required for Won (is_won = 0)
        'won_date'                   => 'required_if:is_won,0|date',
        'expected_installation_date' => 'required_if:is_won,0|date',
        'advance_received'           => 'required_if:is_won,0|numeric',
        'final_quotation_pdf'        => 'nullable|mimes:pdf|max:5120',
        
        // Required for Lost (is_won = 1)
        'lost_type'  => 'required_if:is_won,1|string',
        'competitor' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    try {
        DB::beginTransaction();
        $lead = Lead::findOrFail($request->lead_id);

        if ($request->is_won == 0) {
            // --- HANDLE WON (is_won = 0) ---
            $pdfPath = $lead->final_quotation_pdf;
            if ($request->hasFile('final_quotation_pdf')) {
                $pdfPath = $request->file('final_quotation_pdf')->store('final_quotes', 'public');
            }

            $lead->update([
                'lead_stage'                 => 6, // Stage: Won
                'won_date'                   => $request->won_date,
                'expected_installation_date' => $request->expected_installation_date,
                'advance_received'           => $request->advance_received,
                'final_quotation_pdf'        => $pdfPath,
                'status'                     => 'Won'
            ]);
        } else {
            // --- HANDLE LOST (is_won = 1) ---
            $lead->update([
                'lead_stage' => 7, // Stage: Lost
                'lost_type'  => $request->lost_type,
                'competitor' => $request->competitor
              
            ]);
        }

        // Log history using your helper
        LeadHelper::logStatus($lead, $lead->building_status, $lead->lead_stage);

        DB::commit();
        return response()->json([
            'status'  => true, 
            'message' => ($request->is_won == 0) ? 'Lead marked as Won' : 'Lead marked as Lost',
            'data'    => $lead
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}


public function completeSiteHandover(Request $request)
{
    // 1. Validate that at least one photo is provided
    $validator = Validator::make($request->all(), [
        'lead_id'           => 'required|exists:leads,id',
        'installed_date'    => 'required|date',
        'handovered_date'   => 'required|date',
        'google_review'     => 'nullable|string',
        // 'final_site_photos' is now required and must be an array of images
        'final_site_photos' => 'required|array|min:1',
        'final_site_photos.*' => 'image|mimes:jpeg,png,jpg|max:5120', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false, 
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        DB::beginTransaction();

        $lead = Lead::findOrFail($request->lead_id);

        // 2. Process and Store Multiple Photos
        $uploadedPhotos = [];
        if ($request->hasFile('final_site_photos')) {
            foreach ($request->file('final_site_photos') as $photo) {
                // Store file and get path
                $path = $photo->store('handover_photos', 'public');
                
                if (!$path) {
                    throw new \Exception("Failed to upload one or more images. Operation aborted.");
                }

                // Create record in your new LeadHandoverPhoto table
                \App\Models\LeadHandoverPhoto::create([
                    'lead_id'    => $lead->id,
                    'photo_path' => $path
                ]);
                
                $uploadedPhotos[] = asset('storage/' . $path);
            }
        }

        // 3. Update the main Lead record only if images were saved
        $lead->update([
            'lead_stage'       => 7, // Stage: Site Handovered
            'installed_date'   => $request->installed_date,
            'handovered_date'  => $request->handovered_date,
            'google_review'    => $request->google_review,
            'status'           => '0'
        ]);

        // 4. Log the stage change
        \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 7);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Site Handover completed successfully with ' . count($uploadedPhotos) . ' photos.',
            'photos' => $uploadedPhotos
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Handover failed: ' . $e->getMessage()
        ], 500);
    }
}


public function storeOrConvertToNewLead(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id'          => 'required|exists:users,id',
        'lead_id'          => 'nullable|exists:leads,id',
        'name'             => 'required|string|max:255',
        'phone_number'     => 'required|string|max:20',
        'customer_type'    => 'required|string|max:50',
        'city'             => 'required|string|max:255',
        'total_required_area_sqft' => 'required|numeric|min:1',
        'building_status'  => 'required|string|max:255',
        'type_of_building' => 'required|string|max:255',
        'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    if ($request->lead_id) {

        // Convert existing Site Identification
        $lead = Lead::find($request->lead_id);

        if ((int)$lead->lead_stage !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'Only Site Identification leads can be converted'
            ], 400);
        }

        $lead->update([
            'name'                     => $request->name,
            'phone_number'             => $request->phone_number,
            'customer_type'            => $request->customer_type,
            'city'                     => $request->city,
            'total_required_area_sqft' => $request->total_required_area_sqft,
            'building_status'          => $request->building_status,
            'type_of_building'         => $request->type_of_building,
            'lead_stage'               => 1,
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('lead_images', 'public');
            \App\Models\LeadImage::create([
                'lead_id'    => $lead->id,
                'lead_stage' => '1',
                'img_path'   => $imagePath,
                'action'     => '0'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Lead converted to New Lead successfully',
            'data' => $lead
        ], 200);
    }

    // Create new lead directly
    $lead = Lead::create([
        'user_id'          => $request->user_id,
        'name'             => $request->name,
        'phone_number'     => $request->phone_number,
        'customer_type'    => $request->customer_type,
        'city'             => $request->city,
        'total_required_area_sqft' => $request->total_required_area_sqft,
        'building_status'  => $request->building_status,
        'type_of_building' => $request->type_of_building,
        'lead_stage'       => 1,
        'lead_source'      => 'OWN',
        'status'           => 0,
        'created_by'       => $request->user_id,
    ]);

    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('lead_images', 'public');
        \App\Models\LeadImage::create([
            'lead_id'    => $lead->id,
            'lead_stage' => '1',
            'img_path'   => $imagePath,
            'action'     => '0'
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'New Lead created successfully',
        'data' => $lead
    ], 201);
}

public function addFollowUp(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'follow_up_date'  => 'required|date|after_or_equal:today',
        'building_status' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $lead = Lead::find($id);

    if (!$lead) {
        return response()->json([
            'status' => false,
            'message' => 'Lead not found'
        ], 404);
    }

    $oldStage = $lead->building_status;
    $oldStatus = $lead->lead_stage;

    $lead->update([
        'follow_up_date'  => $request->follow_up_date,
        'building_status' => $request->building_status ?? $lead->building_status,
        'lead_stage'      => 2,
    ]);

    LeadHelper::logStatus(
        $lead,
        $lead->building_status,
        2
    );

    return response()->json([
        'status' => true,
        'message' => 'Follow-up added successfully',
        'data' => $lead
    ], 200);
}



public function transferLead(Request $request)
{
    // 1. Validate the Request
    $validator = Validator::make($request->all(), [
        'lead_id'             => 'required|exists:leads,id',      // The ID of the main lead to find the linked DM lead
        'brand_id'            => 'required|exists:brands,id',     // The Brand Context
        'remarks'             => 'required|string',               // Transfer Remarks
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    // 2. Find the specific Digital Marketing Lead linked to this Main Lead
    $dmLead = DigitalMarketingLead::where('lead_id', $request->lead_id)->first();

    if (!$dmLead) {
        return response()->json(['status' => false, 'message' => 'Digital Marketing Lead record not found.'], 404);
    }

    // 3. Capture the current user before changing it (for history)
    // We check 'assigned_to' in the DM table. If null, we assume the current Auth user was holding it.
    $previousOwnerId = $dmLead->assigned_to;

    // 4. Update ONLY the Digital Marketing Lead Table
    $dmLead->update([
     
        // Record WHO is doing the transfer (The currently logged-in user)
        'transfered_by'                => $previousOwnerId,

        // Auto-fill the Date/Time
        'transfered_date'              => Carbon::now(),

        // Record who had it before
        'before_transfer_user'         => $previousOwnerId,

        // Store the Remarks from the request
        'transfter_remarks'            => $request->remarks,

        // Store the Brand ID from the request
        'transftered_lead_using_brand' => $request->brand_id,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Lead transferred successfully (Digital Marketing record updated).',
        'data'    => $dmLead
    ], 200);
}

}