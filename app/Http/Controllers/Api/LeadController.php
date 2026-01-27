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
use App\Models\DigitalMarketingLead;
use App\Helpers\FileUploadHelper;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function storeSiteIdentification(Request $request)
    {

       
        // 1. Validate the Request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'city' => 'required|string|max:255',
            'site_owner_name' => 'nullable|string|max:255',
            'site_owner_mobile_number' => 'nullable|string|max:255',
            'deciding_authority' => 'nullable|string|max:255',
            'deciding_authority_mobile_number' => 'nullable|string|max:255',
            'deciding_authority_type' => 'nullable|string|max:255',
            'site_area' => 'required|string|max:255',
            'site_address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'type_of_building' => 'required|string|max:255',
            'building_status' => 'required|string|max:255',
            'order_type' => 'nullable|string|max:255',

            // CHANGED: Validate 'images' as an array
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // Validate EACH image inside the array
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Create the Lead Record
        $lead = Lead::create([
            'user_id' => $request->user_id,
            'city' => $request->city,
            'site_area' => $request->site_area,
            'name' => $request->name,
            'site_owner_name' => $request->site_owner_name,
            'phone_number' => $request->site_owner_mobile_number,
            'site_owner_mobile_number' => $request->site_owner_mobile_number,
            'site_address' => $request->site_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'type_of_building' => $request->type_of_building,
            'building_status' => $request->building_status,
            'deciding_authority' => $request->deciding_authority,
            'deciding_authority_mobile_number' => $request->deciding_authority_mobile_number,
            'deciding_authority_type' => $request->deciding_authority_type,
            'order_type' => $request->order_type,
            'lead_stage' => 0,
            'lead_source' => 'OWN',
            'status' => '0',
            'created_by' => $request->user_id,
        ]);

        $uploadedFiles = [];

        // 3. Handle Multiple Image Uploads
        if ($request->hasFile('images')) {

            // Loop through each file in the 'images' array
            foreach ($request->file('images') as $image) {

                $path = $image->store('lead_images', 'public');

                if ($path) {
                    $fullUrl = asset('storage/' . $path);

                    // Save to Database
                    \App\Models\LeadImage::create([
                        'lead_id' => $lead->id,
                        'lead_stage' => '0',
                        'img_path' => $fullUrl,
                        'action' => '0'
                    ]);

                    // Add to response array
                    $uploadedFiles[] = [
                        'url' => $fullUrl,
                        'name' => basename($path)
                    ];
                }
            }
        }

        // 4. Return Success Response
        return response()->json([
            'status' => true,
            'message' => 'Site identification created successfully',
            'data' => [
                'id' => $lead->id,
                'lead_stage' => $lead->lead_stage,
                'created_at' => $lead->created_at,
                'uploaded_images' => $uploadedFiles, // Return list of all images
            ],
        ], 200);
    }



    // // Add this to your existing LeadController

    // public function getLeadsByUser(Request $request)
    // {
    //     // 1. Validate the input
    //     $validator = Validator::make($request->all(), [
    //         'user_id'   => 'required|exists:users,id',
    //         'from_date' => 'nullable|date',
    //         'to_date'   => 'nullable|date|after_or_equal:from_date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }

    //     // 2. Start the query
    //     $query = Lead::where('user_id', $request->user_id);

    //     // 3. Apply optional date filters (using created_at)
    //     if ($request->filled('from_date')) {
    //         $query->whereDate('created_at', '>=', $request->from_date);
    //     }

    //     if ($request->filled('to_date')) {
    //         $query->whereDate('created_at', '<=', $request->to_date);
    //     }

    //     // 4. Get the results (ordered by newest first)
    //     $leads = $query->orderBy('created_at', 'desc')->get();

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Leads retrieved successfully',
    //         'count'   => $leads->count(),
    //         'data'    => $leads,
    //     ], 200);
    // }

    public function getLeadsByUser(Request $request)
    {
        // 1. Validate the input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // 2. Fetch and Map the data
        // If lead_source or priority are relationships, add ->with(['relationName']) here
        $leads = Lead::where('user_id', $request->user_id)
            // Apply Date Filters if present
            ->when($request->filled('from_date'), function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->to_date);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'user_id' => $lead->user_id,
                    'name' => $lead->name,

                    // Maps DB column 'mobile_number' (if that's what you use) to 'phone_number'
                    'phone_number' => $lead->mobile_number ?? $lead->phone_number,

                    'email' => $lead->email,
                    'city' => $lead->city,
                    'priority' => $lead->priority,

                    // If this is a relationship ID in DB, change to: $lead->sourceRelation?->name
                    'lead_source' => $lead->lead_source,

                    'lead_stage' => $lead->lead_stage,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Leads retrieved successfully',
            'count' => $leads->count(),
            'data' => $leads,
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
            'user_id' => 'required|exists:users,id',
            'food_allowance' => 'required|in:1,2,3,4',
            'schedule_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . $today,
                'before_or_equal:' . $maxDate
            ],
            'visits' => 'required|array|min:1',
            'remarks' => 'nullable',
            'visits.*.visit_type' => 'required|in:1,2,3',
        ], [
            'schedule_date.before_or_equal' => 'You can only schedule visits up to 25 days in advance.',
            'schedule_date.after_or_equal' => 'The schedule date cannot be in the past.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $createdVisits = [];

            // Loop through each visit in the JSON array
            foreach ($request->visits as $index => $visitItem) {

                $visit = LeadVisit::create([
                    'user_id' => $request->user_id,
                    'type' => 'planned',

                    // Mapping IDs based on visit_type
                    'account_id' => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
                    'lead_id' => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
                    'fabricator_id' => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,

                    'visit_type' => $visitItem['visit_type'],

                    'bdo_id' => $request->user_id,

                    // Shared values from the top level
                    'food_allowance' => $request->food_allowance,
                    'schedule_date' => $request->schedule_date,
                    'remarks' => $request->remarks,


                    'action' => '0',
                ]);

                $createdVisits[] = $visit;
            }

            return response()->json([
                'status' => true,
                'message' => count($createdVisits) . ' visits scheduled successfully',
                'data' => $createdVisits
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get Today's Schedule List (Strictly Formatted)
     */
    public function getScheduleList(Request $request)
    {
        // 1. Validate
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Define today's date
        $today = Carbon::today()->toDateString();

        // 3. Fetch records with relationships
        $schedules = LeadVisit::with(['account', 'fabricator', 'lead'])
            ->where('user_id', $request->user_id)
            ->whereDate('schedule_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Initialize Response Structure
        $response = [
            'leads' => [],
            'accounts' => [],
            'fabricators' => []
        ];

        // 5. Loop and Format Data
        foreach ($schedules as $visit) {

            // Common Fields for all types
            $commonData = [
                'id' => $visit->id,         // Visit ID
                'user_id' => $visit->user_id,
                'visit_type' => $visit->visit_type,
                'intime' => $visit->intime_time, // Or $visit->intime depending on your DB column
                'outtime' => $visit->out_time,
            ];

            // --- TYPE 1: ACCOUNTS ---
            if ($visit->visit_type == '1') {
                $accountData = $commonData + [
                    'account_id' => $visit->account_id,
                    'account_name' => $visit->account ? $visit->account->name : null,
                    'address' => $visit->account ? $visit->account->address : null,
                    'mobile_number' => $visit->account ? $visit->account->mobile_number : null, // Mapped from Account table
                ];
                $response['accounts'][] = $accountData;
            }

            // --- TYPE 2: LEADS ---
            elseif ($visit->visit_type == '2') {
                $leadData = $commonData + [
                    'lead_id' => $visit->lead_id,
                    'lead_name' => $visit->lead ? $visit->lead->name : null,
                    'address' => $visit->lead ? ($visit->lead->site_address ?? $visit->lead->address) : null,
                    'mobile_number' => $visit->lead ? $visit->lead->phone_number : null, // Mapped from Lead table
                    'lead_stage' => $visit->lead ? $visit->lead->lead_stage : null,
                    'lead_source' => $visit->lead ? $visit->lead->lead_source : null, // 'HO' or 'Own' from DB
                ];
                $response['leads'][] = $leadData;
            }

            // --- TYPE 3: FABRICATORS ---
            elseif ($visit->visit_type == '3') {
                $fabricatorData = $commonData + [
                    'fabricator_id' => $visit->fabricator_id,
                    'fabricator_name' => $visit->fabricator ? ($visit->fabricator->shop_name ?? $visit->fabricator->name) : null,
                    'address' => $visit->fabricator ? $visit->fabricator->address : null,
                    'mobile_number' => $visit->fabricator ? $visit->fabricator->mobile : null, // Mapped from Fabricator table
                ];
                $response['fabricators'][] = $fabricatorData;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Today schedule list retrieved successfully',
            'count' => $schedules->count(),
            'data' => $response
        ], 200);
    }

    /**
     * Check-in to an existing schedule
     * Updates intime_time, visit_date, inlat, inlong, and image for bdo
     */
    // public function leadCheckIn(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'visit_id' => 'required|exists:lead_visits,id', // Use the primary ID of the table
    //         'inlat' => 'required',
    //         'inlong' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first()
    //         ], 422);
    //     }

    //     try {
    //         // 1. Find the existing schedule record
    //         $visit = LeadVisit::find($request->visit_id);

    //         // --- RESTRICTION LOGIC STARTS HERE ---

    //         // A. Prevent checking in twice to the SAME visit
    //         if (!empty($visit->intime_time)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'You have already checked in for this visit at ' . $visit->intime_time
    //             ], 403);
    //         }

    //         // B. Check if the user has ANY other active visit (Checked In but NOT Checked Out)
    //         // We assume 'user_id' exists on the lead_visits table. 
    //         // If it is on the 'leads' table, use $visit->lead->user_id
    //         $userId = $visit->user_id;

    //         $ongoingVisit = LeadVisit::where('user_id', $userId)
    //             ->whereNotNull('intime_time') // They have checked in...
    //             ->whereNull('out_time')       // ...but haven't checked out yet
    //             ->where('id', '!=', $visit->id) // Exclude the current row just in case
    //             ->first();

    //         if ($ongoingVisit) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Restriction: You have an ongoing visit (ID: ' . $ongoingVisit->id . ') that is not completed. Please check out from there first.'
    //             ], 403);
    //         }
    //         // --- RESTRICTION LOGIC ENDS HERE ---

    //         // 3. Update the existing record
    //         $visit->update([
    //             'visit_date' => Carbon::now()->format('Y-m-d'), // Stores only date
    //             'intime_time' => Carbon::now()->format('H:i:s'), // Stores only time
    //             'inlat' => $request->inlat,
    //             'inlong' => $request->inlong,
    //             'action' => '0', // As per your requirement to keep/update action to '0'
    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Check-in successful',
    //             'data' => $visit
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function leadCheckIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id' => 'required|exists:lead_visits,id',
            'inlat' => 'required',
            'inlong' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $visit = LeadVisit::find($request->visit_id);

            // --- RESTRICTION LOGIC STARTS HERE ---

            // A. Prevent checking in twice to the SAME visit
            if (!empty($visit->intime_time)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already checked in for this visit at ' . $visit->intime_time
                ], 403);
            }

            // B. Check if user has an active visit TODAY (Checked In, Not Checked Out, Today's Date)
            $userId = $visit->user_id;
            $todayDate = \Carbon\Carbon::now()->format('Y-m-d'); // Get today's date

            $ongoingVisit = LeadVisit::where('user_id', $userId)
                ->where('visit_date', $todayDate) // <--- ADDED: Only look for visits on today's date
                ->whereNotNull('intime_time')     
                ->whereNull('out_time')           
                ->where('id', '!=', $visit->id)   
                ->first();

            if ($ongoingVisit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Restriction: You have an ongoing visit today (ID: ' . $ongoingVisit->id . '). Please check out from there first.'
                ], 403);
            }
            // --- RESTRICTION LOGIC ENDS HERE ---

            // 3. Update the existing record
            $visit->update([
                'visit_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                'intime_time' => \Carbon\Carbon::now()->format('H:i:s'),
                'inlat' => $request->inlat,
                'inlong' => $request->inlong,
                'action' => '0',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Check-in successful',
                'data' => $visit
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    //  public function leadCheckOut(Request $request)
    //     {
    //         // 1. Base Validation Rules
    //         $rules = [
    //             'visit_id'    => 'required|exists:lead_visits,id',
    //             'outlat'      => 'required',
    //             'outlong'     => 'required',
    //             'remarks'     => 'nullable|string',
    //             'action_type' => 'required|string', 
    //         ];

    //         // 2. Add Dynamic Rules based on action_type
    //         if ($request->action_type == 'intro_stage') {
    //             $rules['customer_name']    = 'required|string|max:255';
    //             $rules['contact_number']   = 'required|string|max:20';
    //             $rules['customer_type']    = 'required|string';
    //             $rules['building_stage']   = 'required|string';
    //             $rules['no_of_window']     = 'required|integer';
    //             $rules['lead_temperature'] = 'required|in:Hot,Warm,Cold';
    //             $rules['meeting_type']     = 'required|in:Site Visit,Office,Others';
    //         }
    //         elseif ($request->action_type == 'followup_meeting') {
    //             $rules['total_required_area_sqft'] = 'required|numeric|min:1';
    //             $rules['current_building_stage']   = 'required|string';
    //             $rules['brand_id']                 = 'required|integer'; 
    //         }
    //         elseif ($request->action_type == 'cross_selling') {
    //             $rules['brand_id'] = 'required|integer'; 
    //         }
    //         elseif ($request->action_type == 'quotation_pending_with_fabricator') {
    //             $rules['fabricator_id'] = 'required|exists:users,id';
    //             $rules['notes']         = 'nullable|string';
    //             $rules['priority']      = 'required|integer'; 
    //             $rules['measurements'] = 'required|array|min:1';
    //             $rules['measurements.*.product']     = 'required|string';
    //             $rules['measurements.*.width_val']   = 'required|numeric';
    //             $rules['measurements.*.width_unit']  = 'required|in:mm,ft,inch';
    //             $rules['measurements.*.height_val']  = 'required|numeric';
    //             $rules['measurements.*.height_unit'] = 'required|in:mm,ft,inch';
    //             $rules['measurements.*.qty']         = 'required|integer|min:1';
    //         }
    //         elseif ($request->action_type == 'quotationsent_followup') {
    //             $rules['follow_up_date'] = 'required|date|after_or_equal:today';
    //         }
    //         elseif ($request->action_type == 'won') {

    //             $rules['expected_installation_date'] = 'required|date';
    //             $rules['advance_received']           = 'required|numeric';

    //         }
    //         elseif ($request->action_type == 'lost') {
    //             $rules['lost_type']  = 'required|string';
    //             $rules['competitor'] = 'nullable|string';
    //         }
    //         elseif ($request->action_type == 'site_handover') {
    //             $rules['installed_date']      = 'required|date';
    //             $rules['handovered_date']     = 'required|date';
    //             $rules['final_site_photos']   = 'required|array|min:1';
    //             $rules['final_site_photos.*'] = 'image|mimes:jpeg,png,jpg|max:5120';
    //         }

    //         // 3. Execute Validation
    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    //         }

    //         try {
    //             DB::beginTransaction();

    //             // 4. Find the Visit and Lead
    //             $visit = LeadVisit::find($request->visit_id);

    //             // You cannot check out if you never checked in
    //             if (empty($visit->intime_time)) {
    //                 return response()->json([
    //                     'status'  => false,
    //                     'message' => 'You cannot check out because you have not checked in yet.'
    //                 ], 403);
    //             }

    //             // You cannot check out if already checked out
    //             if (!empty($visit->out_time)) {
    //                 return response()->json([
    //                     'status'  => false,
    //                     'message' => 'You have already checked out of this visit.'
    //                 ], 403);
    //             }
    //             // --------------------

    //             // Safety Check: If visit is invalid, stop here (prevents 500 error on next line)
    //             if (!$visit) {
    //                 return response()->json(['status' => false, 'message' => 'Visit ID not found'], 404);
    //             }

    //             $lead = Lead::find($visit->lead_id);

    //             if (!$lead) {
    //                 throw new \Exception("Lead not found for this visit.");
    //             }

    //             // --- STAGE PROTECTION LOGIC ---
    //             // CAUTION: If the lead is already "Won" or "Lost", this block will 
    //             // PREVENT the cross_selling code from running.
    //             $finalStages = [5, 6, 7]; 
    //             if (in_array($lead->lead_stage, $finalStages)) {
    //                 $allowedTransition = false;
    //                 if ($lead->lead_stage == 5 && $request->action_type == 'site_handover') {
    //                     $allowedTransition = true;
    //                 }

    //                 // Allow cross-selling even if lead is closed? If yes, uncomment line below:
    //                 // if ($request->action_type == 'cross_selling') $allowedTransition = true;

    //                 if (!$allowedTransition) {
    //                     return response()->json([
    //                         'status'  => false,
    //                         'message' => 'Action Denied: This lead is already in "' . ($lead->status ?? 'Closed') . '" stage.'
    //                     ], 403);
    //                 }
    //             }
    //             // ------------------------------

    //             // 5. Process Logic Based on Action Type
    //             switch ($request->action_type) {

    //                 case 'intro_stage':
    //                     $lead->update([
    //                         'name'             => $request->customer_name,
    //                         'phone_number'     => $request->contact_number,
    //                         'customer_type'    => $request->customer_type,
    //                         'building_status'  => $request->building_stage,
    //                         'no_of_windows'    => $request->no_of_window,    
    //                         'lead_temperature' => $request->lead_temperature, 
    //                         'meeting_type'     => $request->meeting_type,     
    //                         'lead_stage'       => 1, 
    //                     ]);
    //                     break;

    //                 case 'followup_meeting':
    //                     $lead->update([
    //                         'total_required_area_sqft' => $request->total_required_area_sqft,
    //                         'building_status'          => $request->current_building_stage,
    //                         'lead_stage'               => 2,
    //                         'brand_id'                 => $request->brand_id, 
    //                     ]);
    //                     break;

    //                 case 'cross_selling':
    //                     // Find the existing Digital Marketing Lead by lead_id
    //                     $digitalLead = DigitalMarketingLead::where('lead_id', $lead->id)->first();



    //                     if ($digitalLead) {
    //                         $digitalLead->update([
    //                             'stage'                        => 0,
    //                             'transfered_by'                => $lead->user_id, // Ensure lead table has user_id
    //                             'transfered_date'              => Carbon::now(),
    //                             'transfter_remarks'            => $request->remarks,
    //                             'transftered_lead_using_brand' => $request->brand_id
    //                         ]);
    //                     } else {
    //                         // Optional: Log if no digital lead was found to update
    //                         // Log::warning("Cross selling attempted on Lead {$lead->id} but no DigitalLead record found.");
    //                     }
    //                     break;

    //                 case 'quotation_pending_with_fabricator':
    //                     $totalCalculatedSqft = 0;
    //                     foreach ($request->measurements as $item) {
    //                         // Note: Ensure convertToFeet function exists in this class
    //                         $wInFeet = $this->convertToFeet($item['width_val'], $item['width_unit']);
    //                         $hInFeet = $this->convertToFeet($item['height_val'], $item['height_unit']);
    //                         $calculatedSqft = $wInFeet * $hInFeet * $item['qty'];
    //                         $totalCalculatedSqft += $calculatedSqft;

    //                         MeasurementDetail::create([
    //                             'lead_id'     => $lead->id,
    //                             'user_id'     => $lead->user_id,
    //                             'product'     => $item['product'],
    //                             'design_code' => $item['design_code'] ?? null,
    //                             'area'        => $item['area'] ?? null,
    //                             'width_val'   => $item['width_val'],
    //                             'width_unit'  => $item['width_unit'],
    //                             'height_val'  => $item['height_val'],
    //                             'height_unit' => $item['height_unit'],
    //                             'qty'         => $item['qty'],
    //                             'color'       => $item['color'] ?? null,
    //                             'sqft'        => round($calculatedSqft, 2),
    //                             'notes'       => $item['notes'] ?? null,
    //                         ]);
    //                     }
    //                     FabricatorRequest::create([
    //                         'lead_id'       => $lead->id,
    //                         'fabricator_id' => $request->fabricator_id,
    //                         'approx_sqft'   => round($totalCalculatedSqft, 2),
    //                         'notes'         => $request->notes,
    //                     ]);
    //                     $lead->update([
    //                         'lead_stage' => 3, 
    //                         'priority'   => $request->priority
    //                     ]); 
    //                     break;

    //                 case 'quotationsent_followup':
    //                     $lead->update(['follow_up_date' => $request->follow_up_date]);
    //                     break;

    //                 case 'won':
    //                     $pdfPath = $lead->final_quotation_pdf;
    //                     if ($request->hasFile('final_quotation_pdf')) {
    //                         $pdfPath = $request->file('final_quotation_pdf')->store('final_quotes', 'public');
    //                     }
    //                     $lead->update([
    //                         'lead_stage'                 => 5,
    //                         'won_date'                   => Carbon::today(),
    //                         'expected_installation_date' => $request->expected_installation_date,
    //                         'advance_received'           => $request->advance_received

    //                     ]);
    //                     break;

    //                 case 'lost':
    //                     $lead->update([
    //                         'lead_stage' => 7,
    //                         'lost_type'  => $request->lost_type,
    //                         'competitor' => $request->competitor
    //                     ]);
    //                     break;

    //                 case 'site_handover':
    //                     if ($request->hasFile('final_site_photos')) {
    //                         foreach ($request->file('final_site_photos') as $photo) {
    //                             LeadHandoverPhoto::create([
    //                                 'lead_id'    => $lead->id,
    //                                 'photo_path' => $photo->store('handover_photos', 'public')
    //                             ]);
    //                         }
    //                     }
    //                     $lead->update([
    //                         'lead_stage'      => 6,
    //                         'installed_date'  => $request->installed_date,
    //                         'handovered_date' => $request->handovered_date,
    //                         'google_review'   => $request->google_review ?? null,
    //                         'status'          => '0' 
    //                     ]);
    //                     break;
    //             }

    //             // 6. Log Status
    //             // Check if Helper exists, otherwise comment this out
    //             if (class_exists('App\Helpers\LeadHelper')) {
    //                 LeadHelper::logStatus(
    //                     $lead,
    //                     $lead->building_status ?? 'Status Updated',
    //                     $lead->lead_stage
    //                 );
    //             }

    //             // 7. Checkout the Visit
    //             $visit->update([
    //                 'out_time'   => Carbon::now()->toTimeString(),
    //                 'outlat'     => $request->outlat,
    //                 'outlong'    => $request->outlong,
    //                 'remarks'    => $request->remarks,
    //                 'lead_stage' => $lead->lead_stage
    //             ]);

    //             DB::commit();

    //             return response()->json([
    //                 'status'  => true,
    //                 'message' => 'Check-out successful and Lead updated for ' . $request->action_type,
    //                 'data'    => $visit
    //             ], 200);

    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             // Return detailed error for debugging
    //             return response()->json([
    //                 'status' => false, 
    //                 'message' => 'Error: ' . $e->getMessage(),
    //                 'line' => $e->getLine(),
    //                 'file' => $e->getFile()
    //             ], 500);
    //         }
    //     }

public function leadCheckOut(Request $request)
{
    Log::info('lead checkout', $request->all());

    // 1. Fetch Visit Early
    $visit = LeadVisit::find($request->visit_id);

    if (!$visit) {
        return response()->json(['status' => false, 'message' => 'Visit ID not found'], 404);
    }

    // 2. Determine Logic based on Visit Type
    // If Visit Type is 2, Action Type is REQUIRED. 
    // If Visit Type is 1 or 3 (No Lead), Action Type is NULLABLE (and ignored).
    $isLeadVisit = ($visit->visit_type == 2);
    $actionRule  = $isLeadVisit ? 'required' : 'nullable';

    // 3. Base Validation Rules
    $rules = [
        'visit_id'    => 'required|exists:lead_visits,id',
        'outlat'      => 'required',
        'outlong'     => 'required',
        'remarks'     => 'nullable|string',
        'action_type' => "$actionRule|string",
        'work_type'   => 'required|in:Individual,Joint Work',
        'bdm_id'      => 'required_if:work_type,Joint Work|nullable|exists:users,id',
    ];

    // 4. Dynamic Rules: ONLY apply if it is a Lead Visit (Type 2) AND action_type is present
    if ($isLeadVisit && $request->filled('action_type')) {
        // ... (Keep existing validation logic for lead actions) ...
        if ($request->action_type == 'Intro Meeting') {
            $rules['customer_name']    = 'required|string|max:255';
            $rules['contact_number']   = 'required|string|max:20';
            $rules['customer_type']    = 'required|string';
            $rules['building_stage']   = 'required|string';
            $rules['no_of_window']     = 'required|integer';
            $rules['lead_temperature'] = 'required|in:Hot,Warm,Cold';
            $rules['meeting_type']     = 'nullable|in:Site Visit,Office,Others';
        } elseif ($request->action_type == 'followup_meeting') {
            $rules['total_required_area_sqft'] = 'required|numeric|min:1';
            $rules['current_building_stage']   = 'required|string';
            $rules['brand_id']                 = 'required|integer';
        } elseif ($request->action_type == 'cross_selling') {
            $rules['brand_id'] = 'required|integer';
        } elseif ($request->action_type == 'quotation_pending_with_fabricator') {
            $rules['fabricator_id']              = 'required|exists:users,id';
            $rules['notes']                      = 'nullable|string';
            $rules['priority']                   = 'required|integer';
            $rules['measurements']               = 'required|array|min:1';
            $rules['measurements.*.product']     = 'required|string';
            $rules['measurements.*.width_val']   = 'required|numeric';
            $rules['measurements.*.width_unit']  = 'required|in:mm,ft,inch';
            $rules['measurements.*.height_val']  = 'required|numeric';
            $rules['measurements.*.height_unit'] = 'required|in:mm,ft,inch';
            $rules['measurements.*.qty']         = 'required|integer|min:1';
        } elseif ($request->action_type == 'quotationsent_followup') {
            $rules['follow_up_date'] = 'required|date|after_or_equal:today';
        } elseif ($request->action_type == 'won') {
            $rules['expected_installation_date'] = 'required|date';
            $rules['advance_received']           = 'required|numeric';
        } elseif ($request->action_type == 'lost') {
            $rules['lost_type']  = 'required|string';
            $rules['competitor'] = 'nullable|string';
        } elseif ($request->action_type == 'site_handover') {
            $rules['installed_date']      = 'required|date';
            $rules['handovered_date']     = 'required|date';
            $rules['final_site_photos']   = 'required|array|min:1';
            $rules['final_site_photos.*'] = 'image|mimes:jpeg,png,jpg|max:5120';
        }
    }

    // 5. Execute Validation
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    try {
        DB::beginTransaction();

        // Check In/Out Integrity Checks
        if (empty($visit->intime_time)) {
            return response()->json(['status' => false, 'message' => 'You cannot check out because you have not checked in yet.'], 403);
        }
        if (!empty($visit->out_time)) {
            return response()->json(['status' => false, 'message' => 'You have already checked out of this visit.'], 403);
        }

        $lead = null;

        // =========================================================
        // ONLY PERFORM LEAD OPERATIONS IF VISIT TYPE IS 2
        // =========================================================
        if ($isLeadVisit) { 
            $lead = Lead::find($visit->lead_id);

            if (!$lead) {
                throw new \Exception("Lead not found for this visit.");
            }

            // --- STAGE PROTECTION ---
            $finalStages = [5, 6, 7];
            if (in_array($lead->lead_stage, $finalStages)) {
                $allowedTransition = false;
                if ($lead->lead_stage == 5 && $request->action_type == 'site_handover') {
                    $allowedTransition = true;
                }
                if (!$allowedTransition) {
                    return response()->json(['status' => false, 'message' => 'Action Denied: Lead is already closed.'], 403);
                }
            }

            // --- PROCESS ACTIONS ---
            if ($request->filled('action_type')) {
                switch ($request->action_type) {
                    case 'Intro Meeting':
                        $lead->update([
                            'name' => $request->customer_name,
                            'phone_number' => $request->contact_number,
                            'customer_type' => $request->customer_type,
                            'building_status' => $request->building_stage,
                            'no_of_windows' => $request->no_of_window,
                            'lead_temperature' => $request->lead_temperature,
                            'meeting_type' => $request->meeting_type,
                            'lead_stage' => 1,
                        ]);
                        break;
                    case 'followup_meeting':
                        $lead->update([
                            'total_required_area_sqft' => $request->total_required_area_sqft,
                            'building_status' => $request->current_building_stage,
                            'lead_stage' => 2,
                            'brand_id' => $request->brand_id,
                        ]);
                        break;
                    case 'cross_selling':
                        $digitalLead = DigitalMarketingLead::where('lead_id', $lead->id)->first();
                        if ($digitalLead) {
                            $digitalLead->update([
                                'stage' => 0,
                                'transfered_by' => $lead->user_id,
                                'transfered_date' => Carbon::now(),
                                'transfter_remarks' => $request->remarks,
                                'transftered_lead_using_brand' => $request->brand_id
                            ]);
                        }
                        break;
                    case 'quotation_pending_with_fabricator':
                        $totalCalculatedSqft = 0;
                        foreach ($request->measurements as $item) {
                            $wInFeet = $this->convertToFeet($item['width_val'], $item['width_unit']);
                            $hInFeet = $this->convertToFeet($item['height_val'], $item['height_unit']);
                            $calculatedSqft = $wInFeet * $hInFeet * $item['qty'];
                            $totalCalculatedSqft += $calculatedSqft;
        
                            MeasurementDetail::create([
                                'lead_id' => $lead->id,
                                'user_id' => $lead->user_id,
                                'product' => $item['product'],
                                'design_code' => $item['design_code'] ?? null,
                                'area' => $item['area'] ?? null,
                                'width_val' => $item['width_val'],
                                'width_unit' => $item['width_unit'],
                                'height_val' => $item['height_val'],
                                'height_unit' => $item['height_unit'],
                                'qty' => $item['qty'],
                                'color' => $item['color'] ?? null,
                                'sqft' => round($calculatedSqft, 2),
                                'notes' => $item['notes'] ?? null,
                            ]);
                        }
                        FabricatorRequest::create([
                            'lead_id' => $lead->id,
                            'fabricator_id' => $request->fabricator_id,
                            'approx_sqft' => round($totalCalculatedSqft, 2),
                            'notes' => $request->notes,
                        ]);
                        $lead->update([
                            'lead_stage' => 3,
                            'priority' => $request->priority
                        ]);
                        break;
                    case 'quotationsent_followup':
                        $lead->update(['follow_up_date' => $request->follow_up_date]);
                        break;
                    case 'won':
                        $pdfPath = $lead->final_quotation_pdf;
                        if ($request->hasFile('final_quotation_pdf')) {
                            $pdfPath = $request->file('final_quotation_pdf')->store('final_quotes', 'public');
                        }
                        $lead->update([
                            'lead_stage' => 5,
                            'won_date' => Carbon::today(),
                            'expected_installation_date' => $request->expected_installation_date,
                            'advance_received' => $request->advance_received,
                            'final_quotation_pdf' => $pdfPath
                        ]);
                        break;
                    case 'lost':
                        $lead->update([
                            'lead_stage' => 7,
                            'lost_type' => $request->lost_type,
                            'competitor' => $request->competitor
                        ]);
                        break;
                    case 'site_handover':
                        if ($request->hasFile('final_site_photos')) {
                            foreach ($request->file('final_site_photos') as $photo) {
                                LeadHandoverPhoto::create([
                                    'lead_id' => $lead->id,
                                    'photo_path' => $photo->store('handover_photos', 'public')
                                ]);
                            }
                        }
                        $lead->update([
                            'lead_stage' => 6,
                            'installed_date' => $request->installed_date,
                            'handovered_date' => $request->handovered_date,
                            'google_review' => $request->google_review ?? null,
                            'status' => '0'
                        ]);
                        break;
                }
            }

            // Log Status (Lead Helper)
            if (class_exists('App\Helpers\LeadHelper')) {
                LeadHelper::logStatus(
                    $lead,
                    $lead->building_status ?? 'Status Updated',
                    $lead->lead_stage
                );
            }
        } // End of $isLeadVisit check

        // 9. Checkout the Visit (Happens for ALL types)
        $visit->update([
            'out_time'   => Carbon::now()->toTimeString(),
            'outlat'     => $request->outlat,
            'outlong'    => $request->outlong,
            'remarks'    => $request->remarks,
            'work_type'  => $request->work_type,
            'bdm_id'     => ($request->work_type === 'Joint Work') ? $request->bdm_id : null,
            // Only update lead_stage in visit table if we actually have a lead
            'lead_stage' => $lead ? $lead->lead_stage : $visit->lead_stage,
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Check-out successful' . ($request->filled('action_type') && $isLeadVisit ? ' and Lead updated.' : ''),
            'data' => $visit
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}


    /**
     * Step 1: Create Unplanned Visit(s)
     * Allows multiple visits in one request array for bdo
     */

    // public function storeUnplannedSchedule(Request $request)
    // {
    //     // ============================================================
    //     // 1. FAIL-SAFE: Force JSON Decoding if Header is Missing
    //     // ============================================================
    //     // If $request->all() is empty but there is raw content, decode it manually.
    //     if (empty($request->all()) && !empty($request->getContent())) {
    //         $data = json_decode($request->getContent(), true);
    //         if (is_array($data)) {
    //             $request->merge($data);
    //         }
    //     }

    //     // ============================================================
    //     // 2. VALIDATION
    //     // ============================================================
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //         'visits' => 'required|array|min:1',

    //         'visits.*.visit_type' => 'required|in:1,2,3',

    //         // Dynamic requirements
    //         'visits.*.account_id' => 'nullable|required_if:visits.*.visit_type,1',
    //         'visits.*.lead_id' => 'nullable|required_if:visits.*.visit_type,2',
    //         'visits.*.fabricator_id' => 'nullable|required_if:visits.*.visit_type,3',

    //         'visits.*.bdm_id' => 'nullable|string',
    //         'visits.*.bdo_id' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()->first()
    //         ], 422);
    //     }

    //     try {
    //         $today = Carbon::today()->toDateString();

    //         // 3. AUTO-CALCULATE FOOD ALLOWANCE
    //         // Check if there is a PLANNED visit for today to copy allowance setting
    //         $plannedVisit = \App\Models\LeadVisit::where('user_id', $request->user_id)
    //             ->where('type', 'planned')
    //             ->whereDate('schedule_date', $today)
    //             ->first();

    //         $autoFoodAllowance = $plannedVisit ? $plannedVisit->food_allowance : '1';

    //         $createdVisits = [];

    //         // 4. CREATE VISITS LOOP
    //         foreach ($request->visits as $visitItem) {

    //             $visit = \App\Models\LeadVisit::create([
    //                 'user_id' => $request->user_id,
    //                 'type' => 'unplanned',
    //                 'schedule_date' => $today,
    //                 'food_allowance' => $autoFoodAllowance,
    //                 'action' => '0',

    //                 // Data from JSON array
    //                 'visit_type' => $visitItem['visit_type'],
    //                 'work_type' => $visitItem['work_type'] ?? 'Individual',

    //                 // Conditional IDs
    //                 'account_id' => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
    //                 'lead_id' => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
    //                 'fabricator_id' => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,

    //                 // Manager IDs
    //                 'bdm_id' => $visitItem['bdm_id'] ?? $request->user_id,
    //                 'bdo_id' => $visitItem['bdo_id'] ?? null,
    //             ]);

    //             $createdVisits[] = $visit;
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => count($createdVisits) . ' unplanned visit(s) created successfully.',
    //             'data' => $createdVisits
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function storeUnplannedSchedule(Request $request)
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
            'remarks' => 'nullable',

            // Visit Items Validation
            'visits.*.visit_type' => 'required|in:1,2,3',
            
            // Dynamic requirements based on type
            'visits.*.account_id'    => 'nullable|required_if:visits.*.visit_type,1',
            'visits.*.lead_id'       => 'nullable|required_if:visits.*.visit_type,2',
            'visits.*.fabricator_id' => 'nullable|required_if:visits.*.visit_type,3',
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
            // Check if there is already a PLANNED visit for today to copy the allowance setting
            $plannedVisit = \App\Models\LeadVisit::where('user_id', $request->user_id)
                ->where('type', 'planned')
                ->whereDate('schedule_date', $today)
                ->first();

            // Use existing allowance if found, otherwise default to '1' (Local Station)
            $autoFoodAllowance = $plannedVisit ? $plannedVisit->food_allowance : '1';

            $createdVisits = [];

            // 4. CREATE VISITS LOOP
            foreach ($request->visits as $visitItem) {

                $visit = \App\Models\LeadVisit::create([
                    'user_id'       => $request->user_id,
                    'type'          => 'unplanned',
                    'schedule_date' => $today,
                    'food_allowance'=> $autoFoodAllowance,
                    'action'        => '0', // Default pending
                    'remarks'       => $request->remarks, // Shared remarks

                    // IDs
                    'bdo_id'        => $request->user_id, // Same as storeSchedule
                    'visit_type'    => $visitItem['visit_type'],

                    // Conditional IDs based on type
                    'account_id'    => ($visitItem['visit_type'] == 1) ? ($visitItem['account_id'] ?? null) : null,
                    'lead_id'       => ($visitItem['visit_type'] == 2) ? ($visitItem['lead_id'] ?? null) : null,
                    'fabricator_id' => ($visitItem['visit_type'] == 3) ? ($visitItem['fabricator_id'] ?? null) : null,
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

    // /**
//  * Get Unplanned Schedule List for a User
//  */
// public function getUnplannedScheduleList(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'user_id' => 'required|exists:users,id',
//     ]);

    //     if ($validator->fails()) {
//         return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
//     }

    //     $schedules = LeadVisit::where('user_id', $request->user_id)
//         ->where('type', 'unplanned')
//         ->orderBy('created_at', 'desc')
//         ->get();

    //     return response()->json([
//         'status'  => true,
//         'message' => 'Unplanned schedule list retrieved successfully',
//         'count'   => $schedules->count(),
//         'data'    => $schedules
//     ], 200);
// }

    /**
     * Get Unplanned Schedule List for a User
     */
    public function getUnplannedScheduleList(Request $request)
    {
        // 1. Validate
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 2. Fetch records with relationships (Added 'with')
        // We filter by 'type' => 'unplanned' specific to this method
        $schedules = LeadVisit::with(['account', 'fabricator', 'lead'])
            ->where('user_id', $request->user_id)
            ->where('type', 'unplanned')
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Initialize Response Structure (Same as planned list)
        $response = [
            'leads' => [],
            'accounts' => [],
            'fabricators' => []
        ];

        // 4. Loop and Format Data
        foreach ($schedules as $visit) {

            // Common Fields
            $commonData = [
                'id' => $visit->id,
                'user_id' => $visit->user_id,
                'visit_type' => $visit->visit_type,
                'intime' => $visit->intime_time,
                'outtime' => $visit->out_time,
            ];

            // --- TYPE 1: ACCOUNTS ---
            if ($visit->visit_type == '1') {
                $accountData = $commonData + [
                    'account_id' => $visit->account_id,
                    'account_name' => $visit->account ? $visit->account->name : null,
                    'address' => $visit->account ? $visit->account->address : null,
                    'mobile_number' => $visit->account ? $visit->account->mobile_number : null,
                ];
                $response['accounts'][] = $accountData;
            }

            // --- TYPE 2: LEADS ---
            elseif ($visit->visit_type == '2') {
                $leadData = $commonData + [
                    'lead_id' => $visit->lead_id,
                    'lead_name' => $visit->lead ? $visit->lead->name : null,
                    'address' => $visit->lead ? ($visit->lead->site_address ?? $visit->lead->address) : null,
                    'mobile_number' => $visit->lead ? $visit->lead->phone_number : null,
                    'lead_stage' => $visit->lead ? $visit->lead->lead_stage : null,
                    'lead_source' => $visit->lead ? $visit->lead->lead_source : null,
                ];
                $response['leads'][] = $leadData;
            }

            // --- TYPE 3: FABRICATORS ---
            elseif ($visit->visit_type == '3') {
                $fabricatorData = $commonData + [
                    'fabricator_id' => $visit->fabricator_id,
                    'fabricator_name' => $visit->fabricator ? ($visit->fabricator->shop_name ?? $visit->fabricator->name) : null,
                    'address' => $visit->fabricator ? $visit->fabricator->address : null,
                    'mobile_number' => $visit->fabricator ? $visit->fabricator->mobile : null,
                ];
                $response['fabricators'][] = $fabricatorData;
            }
        }

        // 5. Return Response
        return response()->json([
            'status' => true,
            'message' => 'Unplanned schedule list retrieved successfully',
            'count' => $schedules->count(),
            'data' => $response
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
            'inlat' => 'required',
            'inlong' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 1. Find the existing unplanned record
            $visit = LeadVisit::find($request->visit_id);

            // 2. Security Check (Must be unplanned)
            if ($visit->type !== 'unplanned') {
                return response()->json([
                    'status' => false,
                    'message' => 'This record is not an unplanned visit.'
                ], 400);
            }

            // 3. Prevent duplicate In-Time (Already checked in)
            if (!empty($visit->intime_time)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Check-in already done for this visit.'
                ], 400);
            }

            // --- RESTRICTION LOGIC START ---
            // Check if user has ANY other active visit (Checked In but NOT Checked Out)
            // We look for any visit for this user where intime is set, outtime is null, and ID is different
            $userId = $visit->user_id;

            $ongoingVisit = LeadVisit::where('user_id', $userId)
                ->whereNotNull('intime_time') // Checked in...
                ->whereNull('out_time')       // ...but not checked out
                ->where('id', '!=', $visit->id)
                ->first();

            if ($ongoingVisit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Restriction: You have an ongoing visit (ID: ' . $ongoingVisit->id . ') that is not completed. Please check out from there first.'
                ], 403);
            }
            // --- RESTRICTION LOGIC END ---

            // 4. Update the record
            $visit->update([
                'visit_date' => Carbon::now()->format('Y-m-d'),
                'intime_time' => Carbon::now()->format('H:i:s'),
                'inlat' => $request->inlat,
                'inlong' => $request->inlong,
                'action' => '1', // Setting to '1' (In-Progress)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Unplanned Check-in successful',
                'data' => $visit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }



    public function storeFollowupMeeting(Request $request)
    {
        // 1. Validate the specific fields required for this stage
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'current_building_stage' => 'required|string|max:255',
            'total_required_area_sqft' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Find the lead
        $lead = Lead::find($request->lead_id);

        // 3. Update the lead details and move to stage 3
        $lead->update([
            'building_status' => $request->current_building_stage,
            'total_required_area_sqft' => $request->total_required_area_sqft,
            'lead_stage' => 3, // Changed to Follow-up stage
        ]);

        // 4. Log the status using your existing Helper
        LeadHelper::logStatus(
            $lead,
            $request->current_building_stage,
            3
        );

        return response()->json([
            'status' => true,
            'message' => 'Follow-up meeting details updated and lead moved to Stage 3',
            'data' => $lead
        ], 200);
    }

    public function storeMeasurements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'user_id' => 'required|exists:users,id',
            'priority' => 'required|integer', // <--- Added Validation
            'measurements' => 'required|array|min:1',
            'measurements.*.width_val' => 'required|numeric',
            'measurements.*.width_unit' => 'required|in:mm,ft,inch',
            'measurements.*.height_val' => 'required|numeric',
            'measurements.*.height_unit' => 'required|in:mm,ft,inch',
            'measurements.*.qty' => 'required|integer|min:1',
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
                    'lead_id' => $request->lead_id,
                    'user_id' => $request->user_id,
                    'product' => $item['product'],
                    'design_code' => $item['design_code'],
                    'area' => $item['area'],
                    'width_val' => $item['width_val'],
                    'width_unit' => $item['width_unit'],
                    'height_val' => $item['height_val'],
                    'height_unit' => $item['height_unit'],
                    'qty' => $item['qty'],
                    'color' => $item['color'],
                    'sqft' => round($calculatedSqft, 2),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update Lead Stage AND Priority
            Lead::where('id', $request->lead_id)->update([
                'lead_stage' => 4,
                'priority' => $request->priority // <--- Saving the priority here
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
                'status' => false,
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
            'status' => true,
            'message' => 'Measurements retrieved successfully',
            'count' => $measurements->count(),
            'priority' => $lead->priority, // <--- Added priority here
            'total_lead_sqft' => round($totalLeadSqft, 2),
            'data' => $measurements,
        ], 200);
    }


    public function sendToFabricator(Request $request)
    {
        // 1. Validate the request
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'fabricator_id' => 'required|exists:fabricator_requests,id',
            'approx_sqft' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 2. Store the fabricator request
            $fabRequest = FabricatorRequest::create([
                'lead_id' => $request->lead_id,
                'fabricator_id' => $request->fabricator_id,
                'approx_sqft' => $request->approx_sqft,
                'notes' => $request->notes,
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
                'status' => true,
                'message' => 'Request sent to fabricator successfully',
                'data' => $fabRequest
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
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
                'status' => false,
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
            'status' => true,
            'message' => 'Measurement details retrieved successfully',
            'total_items' => $measurements->count(),
            'total_lead_sqft' => round($totalSqft, 2),
            'data' => $measurements,
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
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Fetch requests with related Lead, Lead Creator (User), and Measurement Details
        $assignments = \App\Models\FabricatorRequest::with([
            'lead.assignedUser:id,name'
        ])
            ->where('fabricator_id', $request->fabricator_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'lead_id'     => $item->lead_id,
                    'approx_sqft' => $item->approx_sqft,
                    'notes'       => $item->notes,
                    'user_id'     => optional($item->lead->assignedUser)->id,
                    'bdo'         => optional($item->lead->assignedUser)->name,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Fabricator assignments retrieved successfully',
            'count' => $assignments->count(),
            'data' => $assignments,
        ], 200);
    }

    public function getFabricatorAssignmentsDetails(Request $request)
    {
        // 1. Validate input
        $validator = Validator::make($request->all(), [
            'fabricator_id' => 'required|exists:users,id',
            'fabricator_request_id' => 'required|exists:fabricator_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Fetch single fabricator request
        $assignment = FabricatorRequest::where('id', $request->fabricator_request_id)
            ->where('fabricator_id', $request->fabricator_id)
            ->first();

        if (!$assignment) {
            return response()->json([
                'status' => false,
                'message' => 'Fabricator request not found',
            ], 404);
        }

        // 🔥 FORCE eager load (this fixes the issue)
        $assignment->load([
            'lead:id,name,user_id',
            'lead.assignedUser:id,name'
        ]);


        if (!$assignment) {
            return response()->json([
                'status' => false,
                'message' => 'Fabricator request not found',
            ], 404);
        }

        // 3. Fetch measurements for the lead
        $measurements = \App\Models\MeasurementDetail::where('lead_id', $assignment->lead_id)
            ->get();

        // 4. Final response structure
        $data = [
            'id'          => $assignment->id,
            'lead_id'     => $assignment->lead_id,
            'approx_sqft' => $assignment->approx_sqft,
            'notes'       => $assignment->notes,
            'pdf_sent_status' => $assignment->status,
            'fabrication_pdf' => $assignment->fabrication_pdf,
            'rate_per_sqft' => $assignment->rate_per_sqft,
            'total_value' => $assignment->total_value,
            'total_quotation_amount' => $assignment->total_quotation_amount,
            'user_id'     => optional($assignment->lead->assignedUser)->id,
            'bdo'         => optional($assignment->lead->assignedUser)->name,
            'measurements' => $measurements,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Fabricator assignment details retrieved successfully',
            'data' => $data,
        ], 200);
    }
    public function uploadFabricationDetails(Request $request)
    {
        // 1. Initial Validation
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'fabricator_id' => 'required|exists:users,id',
            'rate_per_sqft' => 'required|numeric|min:0',
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
            'total_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'reason' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $lead = \App\Models\Lead::find($request->lead_id);

            // --- CHECK: Is this the second time? ---
            // If the lead is ALREADY at stage 4, it means a quotation was uploaded previously.
            $isSecondUpload = ($lead->lead_stage == 4);

            $file = $request->file('pdf_file');

            if (!$file->isValid()) {
                throw new \Exception("System Upload Error: " . $file->getErrorMessage());
            }

            // Unique filename
            $fileName = 'fab_' . $request->lead_id . '_' . time() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('fabrication_docs', $fileName, 'public');

            if (!$path) {
                throw new \Exception("Failed to write file to disk.");
            }

            // 2. Update Fabricator Request Record (Always update the specific request)
            $fabRequest = \App\Models\FabricatorRequest::where('lead_id', $request->lead_id)
                ->where('fabricator_id', $request->fabricator_id)
                ->firstOrFail();

            $fabRequest->update([
                'fabrication_pdf' => $path,
                'rate_per_sqft' => $request->rate_per_sqft,
                'status' => '1',
                'total_value' => $request->total_value,
            ]);

            // 3. Prepare Lead Updates
            $leadUpdates = [
                'lead_stage' => 4, // Ensure it is stage 4
                'total_value' => $request->total_value,
            ];

            // --- CONDITIONAL UPDATE ---
            // Only update 'final_quotation_pdf' if this is the second upload (Stage was already 4)
            if ($isSecondUpload) {
                $leadUpdates['final_quotation_pdf'] = $path;
                $fabRequest->update([
                    'status' => '2',
                ]);
            }

            $lead->update($leadUpdates);

            // Log status only if it wasn't already 4 (optional, depending on if you want duplicate logs)
            if (!$isSecondUpload) {
                \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 4);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $isSecondUpload
                    ? 'Final Quotation updated successfully'
                    : 'Initial Quotation uploaded successfully',
                'pdf_url' => asset('storage/' . $path),
                'is_final' => $isSecondUpload // flag for frontend to know
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Upload Failed',
                'reason' => $e->getMessage()
            ], 500);
        }
    }

    // public function uploadFabricationDetails(Request $request)
    // {
    //     // 1. Initial Validation
    //     $validator = Validator::make($request->all(), [
    //         'lead_id' => 'required|exists:leads,id',
    //         'fabricator_id' => 'required|exists:users,id',
    //         'rate_per_sqft' => 'required|numeric|min:0',
    //         'pdf_file' => 'required|file|mimes:pdf|max:10240',
    //         'total_value' => 'required|numeric|min:0',
    //     ]);

    //     if ($validator->fails()) {
    //         // This will now catch the "failed to upload" error early
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation Error',
    //             'reason' => $validator->errors()->first()
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $file = $request->file('pdf_file');

    //         // Check for PHP system errors during upload
    //         if (!$file->isValid()) {
    //             throw new \Exception("System Upload Error: " . $file->getErrorMessage());
    //         }

    //         $fileName = 'fab_' . $request->lead_id . '_' . time() . '.' . $file->getClientOriginalExtension();

    //         // Store the file and verify success
    //         $path = $file->storeAs('fabrication_docs', $fileName, 'public');

    //         if (!$path) {
    //             throw new \Exception("Failed to write file to disk. Check storage folder permissions.");
    //         }

    //         // Update Record
    //         $fabRequest = \App\Models\FabricatorRequest::where('lead_id', $request->lead_id)
    //             ->where('fabricator_id', $request->fabricator_id)
    //             ->firstOrFail();

    //         $fabRequest->update([
    //             'fabrication_pdf' => $path,
    //             'rate_per_sqft' => $request->rate_per_sqft,
    //             'status' => '1',
    //             'total_value' => $request->total_value,
    //         ]);

    //         // Update Lead Stage to 4
    //         $lead = \App\Models\Lead::find($request->lead_id);
    //         $lead->update(['lead_stage' => 4]);
    //         $lead->update(['total_value' => $request->total_value,]);

    //         \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 4);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Quotation uploaded successfully',
    //             'pdf_url' => asset('storage/' . $path)
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Upload Failed',
    //             'reason' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function updateLeadFinalStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'is_won' => 'required|in:0,1', // 0 = Won, 1 = Lost

            // Required for Won (is_won = 0)
            'won_date' => 'required_if:is_won,0|date',
            'expected_installation_date' => 'required_if:is_won,0|date',
            'advance_received' => 'required_if:is_won,0|numeric',
            'final_quotation_pdf' => 'nullable|mimes:pdf|max:5120',

            // Required for Lost (is_won = 1)
            'lost_type' => 'required_if:is_won,1|string',
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
                    'lead_stage' => 6, // Stage: Won
                    'won_date' => $request->won_date,
                    'expected_installation_date' => $request->expected_installation_date,
                    'advance_received' => $request->advance_received,
                    'final_quotation_pdf' => $pdfPath,
                    'status' => 'Won'
                ]);
            } else {
                // --- HANDLE LOST (is_won = 1) ---
                $lead->update([
                    'lead_stage' => 7, // Stage: Lost
                    'lost_type' => $request->lost_type,
                    'competitor' => $request->competitor

                ]);
            }

            // Log history using your helper
            LeadHelper::logStatus($lead, $lead->building_status, $lead->lead_stage);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => ($request->is_won == 0) ? 'Lead marked as Won' : 'Lead marked as Lost',
                'data' => $lead
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
            'lead_id' => 'required|exists:leads,id',
            'installed_date' => 'required|date',
            'handovered_date' => 'required|date',
            'google_review' => 'nullable|string',
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
                        'lead_id' => $lead->id,
                        'photo_path' => $path
                    ]);

                    $uploadedPhotos[] = asset('storage/' . $path);
                }
            }

            // 3. Update the main Lead record only if images were saved
            $lead->update([
                'lead_stage' => 7, // Stage: Site Handovered
                'installed_date' => $request->installed_date,
                'handovered_date' => $request->handovered_date,
                'google_review' => $request->google_review,
                'status' => '0'
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
            'user_id' => 'required|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'customer_type' => 'required|string|max:50',
            'city' => 'required|string|max:255',
            'total_required_area_sqft' => 'required|numeric|min:1',
            'building_status' => 'required|string|max:255',
            'type_of_building' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
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

            if ((int) $lead->lead_stage !== 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only Site Identification leads can be converted'
                ], 400);
            }

            $lead->update([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'customer_type' => $request->customer_type,
                'city' => $request->city,
                'total_required_area_sqft' => $request->total_required_area_sqft,
                'building_status' => $request->building_status,
                'type_of_building' => $request->type_of_building,
                'lead_stage' => 1,
            ]);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('lead_images', 'public');
                \App\Models\LeadImage::create([
                    'lead_id' => $lead->id,
                    'lead_stage' => '1',
                    'img_path' => $imagePath,
                    'action' => '0'
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
            'user_id' => $request->user_id,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'customer_type' => $request->customer_type,
            'city' => $request->city,
            'total_required_area_sqft' => $request->total_required_area_sqft,
            'building_status' => $request->building_status,
            'type_of_building' => $request->type_of_building,
            'lead_stage' => 0,
            'lead_source' => 'OWN',
            'status' => 0,
            'created_by' => $request->user_id,
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('lead_images', 'public');
            \App\Models\LeadImage::create([
                'lead_id' => $lead->id,
                'lead_stage' => '0',
                'img_path' => $imagePath,
                'action' => '0'
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
            'follow_up_date' => 'required|date|after_or_equal:today',
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
            'follow_up_date' => $request->follow_up_date,
            'building_status' => $request->building_status ?? $lead->building_status,
            'lead_stage' => 2,
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
            'lead_id' => 'required|exists:leads,id',      // The ID of the main lead to find the linked DM lead
            'brand_id' => 'required|exists:brands,id',     // The Brand Context
            'remarks' => 'required|string',               // Transfer Remarks
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
            'transfered_by' => $previousOwnerId,

            // Auto-fill the Date/Time
            'transfered_date' => Carbon::now(),

            // Record who had it before
            'before_transfer_user' => $previousOwnerId,

            // Store the Remarks from the request
            'transfter_remarks' => $request->remarks,

            // Store the Brand ID from the request
            'transftered_lead_using_brand' => $request->brand_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Lead transferred successfully (Digital Marketing record updated).',
            'data' => $dmLead
        ], 200);
    }


    public function getLeadDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $lead = Lead::find($request->lead_id);

        // 1. Define the Stage Mapping
        $stages = [
            0 => 'Site Identification',
            1 => 'Intro Stage',
            2 => 'Follow-up Meeting',
            3 => 'Quotation Pending',
            4 => 'Quotation Sent',
            5 => 'Won',
            6 => 'Site Handover',
            7 => 'Lost',
            // Add other stages if you have them
        ];

        // 2. Append the text format to the lead object
        // usage of '??' handles cases where the stage ID might not be in the array
        $lead->lead_stage_text = $stages[$lead->lead_stage] ?? 'Unknown Stage';

        return response()->json([
            'status' => true,
            'message' => 'Lead details fetched successfully',
            'data' => $lead
        ], 200);
    }
    /**
     * Get BDO Tour Plan Calendar (Monthly View)
     * Uses LeadVisit model
     */
    public function getBdoTourPlanCalendar(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric|min:2024|max:2030',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // 2. Define Date Range
        $year = $request->year;
        $month = $request->month;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // 3. Fetch Existing Plans from LeadVisit DB
        $dbPlans = LeadVisit::where('user_id', $request->user_id)
            ->whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('created_at', 'desc')
            ->get()
            // --- FIX: Normalize keys to Y-m-d to prevent mismatch with timestamps ---
            ->mapWithKeys(function ($item) {
                // Extracts just the date part (first 10 chars) 
                return [substr($item->schedule_date, 0, 10) => $item];
            });

        // 4. Generate Calendar Data
        $calendar = [];
        $currentDate = $startDate->copy();

        $statusMap = [
            '0' => ['label' => 'Not Planned', 'color' => '#808080'],
            '1' => ['label' => 'Local Station', 'color' => '#28a745'],
            '2' => ['label' => 'Out Station', 'color' => '#007bff'],
            '3' => ['label' => 'Meeting', 'color' => '#ffc107'],
            '4' => ['label' => 'Leave', 'color' => '#dc3545'],
        ];

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');

            // Check if date exists in our fetched list
            if ($dbPlans->has($dateString)) {
                $record = $dbPlans->get($dateString);
                $statusId = (string) $record->food_allowance;
            } else {
                $statusId = '0'; // Default to Not Planned
            }

            $calendar[] = [
                'date' => $dateString,
                'day' => $currentDate->format('l'),
                'status' => $statusId,
                'label' => $statusMap[$statusId]['label'] ?? 'Unknown',
                'color' => $statusMap[$statusId]['color'] ?? '#000000',
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'status' => true,
            'message' => 'BDO Tour plan calendar retrieved successfully',
            'data' => $calendar
        ], 200);
    }

    // /**
    //  * View BDO Tour Plan for a Specific Date
    //  * Segregates data into Joint Work and Individual Work
    //  * Uses LeadVisit model
    //  */
    // public function viewBdoTourPlan(Request $request)
    // {
    //     // 1. Validate Inputs
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //         'date' => 'required|date_format:Y-m-d',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    //     }

    //     // 2. Fetch Visits from LeadVisit
    //     // Using LIKE query to match date string regardless of time component
    //     $visits = LeadVisit::with(['lead', 'account', 'fabricator'])
    //         ->where('user_id', $request->user_id)
    //         ->where('schedule_date', 'like', $request->date . '%')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     // 3. Initialize Structure
    //     $jointWork = [];
    //     $individualWork = [];

    //     $statusMap = [
    //         '0' => 'Not Planned',
    //         '1' => 'Local Station',
    //         '2' => 'Out Station',
    //         '3' => 'Meeting',
    //         '4' => 'Leave',
    //     ];

    //     // Determine Overall Day Status
    //     $dayStatusId = $visits->isNotEmpty() ? $visits->first()->food_allowance : '0';
    //     $dayStatusLabel = $statusMap[$dayStatusId] ?? 'Unknown';

    //     // 4. Loop and Segregate
    //     foreach ($visits as $row) {

    //         // Resolve Name
    //         $name = null;
    //         if ($row->visit_type == '1') { // Account
    //             $name = $row->account ? $row->account->name : 'Unknown Account';
    //         } elseif ($row->visit_type == '2') { // Lead
    //             $name = $row->lead ? $row->lead->name : 'Unknown Lead';
    //         } elseif ($row->visit_type == '3') { // Fabricator
    //             $name = $row->fabricator ? $row->fabricator->shop_name : 'Unknown Fabricator';
    //         }

    //         // Build Data Object
    //         $data = [
    //             'visit_id' => $row->id,
    //             'visit_type_id' => $row->visit_type,
    //             'visit_type' => match ($row->visit_type) {
    //                 '1' => 'Account',
    //                 '2' => 'Lead',
    //                 '3' => 'Fabricator',
    //                 default => 'Unknown'
    //             },
    //             'name' => $name,

    //             // IDs
    //             'lead_id' => $row->lead_id,
    //             'account_id' => $row->account_id,
    //             'fabricator_id' => $row->fabricator_id,

    //             // Details
    //             'work_type' => $row->work_type,
    //             'remarks' => $row->remarks,
    //             'status' => ($row->action == '2') ? 'Visited' : 'Pending',
    //             'schedule_date' => $row->schedule_date,
    //             'visit_date' => $row->visit_date,
    //             'intime' => $row->intime_time,
    //             'outtime' => $row->out_time,
    //         ];

    //         // Segregate
    //         if ($row->work_type === 'Joint Work') {
    //             $jointWork[] = $data;
    //         } else {
    //             $individualWork[] = $data;
    //         }
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'BDO Tour plan details retrieved successfully',
    //         'data' => [
    //             'date' => $request->date,
    //             'day_status' => $dayStatusLabel,
    //             'total_visits' => $visits->count(),
    //             'joint_work' => $jointWork,
    //             'individual_work' => $individualWork,
    //         ]
    //     ], 200);
    // }

    /**
     * View BDO Tour Plan for a Specific Date
     * Segregates data into Joint Work and Individual Work
     * Uses LeadVisit model
     */
    public function viewBdoTourPlan(Request $request)
    {
        // 1. Validate Inputs
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        // 2. Fetch Visits from LeadVisit
        // Using LIKE query to match date string regardless of time component
        $visits = LeadVisit::with(['lead', 'account', 'fabricator'])
            ->where('user_id', $request->user_id)
            ->where('schedule_date', 'like', $request->date . '%')
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Initialize Structure
        $jointWork      = [];
        $individualWork = [];

        $statusMap = [
            '0' => 'Not Planned',
            '1' => 'Local Station',
            '2' => 'Out Station',
            '3' => 'Meeting',
            '4' => 'Leave',
        ];

        // Determine Overall Day Status
        $dayStatusId    = $visits->isNotEmpty() ? $visits->first()->food_allowance : '0';
        $dayStatusLabel = $statusMap[$dayStatusId] ?? 'Unknown';

        // 4. Loop and Segregate
        foreach ($visits as $row) {

            // Initialize variables
            $name    = null;
            $address = null;
            $mobile  = null;

            // --- Resolve Name, Address, and Mobile based on Type ---
            if ($row->visit_type == '1') { // Account
                $name    = $row->account ? $row->account->name : 'Unknown Account';
                $address = $row->account ? $row->account->address : null;
                $mobile  = $row->account ? $row->account->mobile_number : null;
            } 
            elseif ($row->visit_type == '2') { // Lead
                $name    = $row->lead ? $row->lead->name : 'Unknown Lead';
                // Check site_address first, fallback to regular address
                $address = $row->lead ? ($row->lead->site_address ?? $row->lead->address) : null;
                $mobile  = $row->lead ? $row->lead->phone_number : null;
            } 
            elseif ($row->visit_type == '3') { // Fabricator
                $name    = $row->fabricator ? $row->fabricator->shop_name : 'Unknown Fabricator';
                $address = $row->fabricator ? $row->fabricator->address : null;
                $mobile  = $row->fabricator ? $row->fabricator->mobile : null;
            }

            // Build Data Object
            $data = [
                'visit_id'      => $row->id,
                'visit_type_id' => $row->visit_type,
                'visit_type'    => match ($row->visit_type) {
                    '1'     => 'Account',
                    '2'     => 'Lead',
                    '3'     => 'Fabricator',
                    default => 'Unknown'
                },
                'name'          => $name,
                'mobile'        => $mobile,  // <--- Added Mobile
                'address'       => $address, // <--- Added Address

                // IDs
                'lead_id'       => $row->lead_id,
                'account_id'    => $row->account_id,
                'fabricator_id' => $row->fabricator_id,

                // Details
                'work_type'     => $row->work_type,
                'remarks'       => $row->remarks,
                'status'        => ($row->action == '2') ? 'Visited' : 'Pending',
                'schedule_date' => $row->schedule_date,
                'visit_date'    => $row->visit_date,
                'intime'        => $row->intime_time,
                'outtime'       => $row->out_time,
            ];

            // Segregate
            if ($row->work_type === 'Joint Work') {
                $jointWork[] = $data;
            } else {
                $individualWork[] = $data;
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'BDO Tour plan details retrieved successfully',
            'data'    => [
                'date'            => $request->date,
                'day_status'      => $dayStatusLabel,
                'total_visits'    => $visits->count(),
                'joint_work'      => $jointWork,
                'individual_work' => $individualWork,
            ]
        ], 200);
    }
}
