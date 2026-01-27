<?php

namespace App\Http\Controllers\DigitalMarketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DigitalMarketingLead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Zone;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\BuildingStatus;
use App\Models\CustomerType;
use App\Models\LeadHistory;
use Illuminate\Support\Facades\Auth;

class DigitalMarketingLeadController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('leads.view');
        $authUser = Auth::user();

        // --- 1. PREPARE DROPDOWNS (With Security Logic) ---
        
        // Zones: If user has zone_id, only show that. Else show all.
        $zones = Zone::where('action', '0')->orderBy('name')
            ->when($authUser->zone_id, fn($q) => $q->where('id', $authUser->zone_id))
            ->get();

        // States: If user has state_id, pre-load only that. 
        // If user has zone_id but no state_id, pre-load states for that zone.
        $states = collect();
        if ($authUser->state_id) {
            $states = State::where('id', $authUser->state_id)->get();
        } elseif ($authUser->zone_id) {
            $states = State::where('zone_id', $authUser->zone_id)->orderBy('name')->get();
        }

        // Districts: Similar logic
        $districts = collect();
        if ($authUser->district_id) {
            $districts = District::where('id', $authUser->district_id)->get();
        } elseif ($authUser->state_id) {
            $districts = District::where('state_id', $authUser->state_id)->orderBy('district_name')->get();
        }

        // Cities: Similar logic
        $cities = collect();
        if ($authUser->city_id) {
             $cities = City::where('id', $authUser->city_id)->get();
        } elseif ($authUser->district_id) {
             $cities = City::where('district_id', $authUser->district_id)->orderBy('city_name')->get();
        }

        $buildingTypes = \App\Helpers\LeadHelper::getBuildingTypes();
        $leadStages    = \App\Helpers\LeadHelper::getLeadStages();
        $leadStages[0] = 'New Lead';
        $leadStages[7] = 'RNR';

        // --- 2. AJAX DATATABLE REQUEST ---
        if ($request->ajax()) {
            
            // Start Query & Join with Users table to filter by Assigned User's Location
            $query = DigitalMarketingLead::with(['assignedUser', 'zoneDetails', 'status', 'type', 'lead', 'transferer'])
                ->leftJoin('users', 'digital_marketing_leads.assigned_to', '=', 'users.id')
                ->select('digital_marketing_leads.*');

            // --- A. APPLY SECURITY FILTERS (Based on Logged In User) ---
            if ($authUser->zone_id) {
                $query->where('users.zone_id', $authUser->zone_id);
            }
            if ($authUser->state_id) {
                $query->where('users.state_id', $authUser->state_id);
            }
            if ($authUser->district_id) {
                $query->where('users.district_id', $authUser->district_id);
            }

            // --- B. APPLY MANUAL FILTERS (Dropdowns) ---
            // Only apply if the user isn't already restricted by their profile
            if (!$authUser->zone_id && $request->filled('zone_id')) {
                $query->where('users.zone_id', $request->zone_id);
            }
            if (!$authUser->state_id && $request->filled('state_id')) {
                $query->where('users.state_id', $request->state_id);
            }
            if (!$authUser->district_id && $request->filled('district_id')) {
                $query->where('users.district_id', $request->district_id);
            }
            if ($request->filled('city_id')) {
                $query->where('users.city_id', $request->city_id);
            }

            // --- C. EXISTING FILTERS ---
            if ($request->filled('stage')) {
                $query->where('digital_marketing_leads.stage', $request->stage);
            }
            if ($request->filled('future_date')) {
                $query->whereDate('digital_marketing_leads.future_follow_up_date', $request->future_date);
            }
            if ($request->filled('potential_date')) {
                $query->whereDate('digital_marketing_leads.potential_follow_up_date', $request->potential_date);
            }

            $data = $query->latest('digital_marketing_leads.created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowId(fn ($row) => $row->id)
                ->editColumn('date', fn ($row) => $row->date ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '-')
                ->editColumn('future_follow_up_date', function ($row) {
                    if (!$row->future_follow_up_date) return '-';
                    $time = $row->future_follow_up_time ? ' ' . \Carbon\Carbon::parse($row->future_follow_up_time)->format('h:i A') : '';
                    return \Carbon\Carbon::parse($row->future_follow_up_date)->format('d-m-Y') . $time;
                })
                ->editColumn('potential_follow_up_date', function ($row) {
                    if (!$row->potential_follow_up_date) return '-';
                    $time = $row->potential_follow_up_time ? ' ' . \Carbon\Carbon::parse($row->potential_follow_up_time)->format('h:i A') : '';
                    return \Carbon\Carbon::parse($row->potential_follow_up_date)->format('d-m-Y') . $time;
                })
                ->editColumn('stage', fn ($row) => $leadStages[$row->stage] ?? '-')
                ->editColumn('building_type', fn ($row) => $buildingTypes[$row->building_type] ?? '-')
                ->rawColumns(['otp_status', 'name', 'lead_id']) 
                ->make(true);
        }

        $users = User::orderBy('name')->get(); // You might want to filter this based on hierarchy too
        $buildingStatuses = BuildingStatus::where('action', 0)->get();
        $customerTypes = CustomerType::where('action', 0)->get();

        return view('marketing.leads.index', compact(
            'users', 'zones', 'states', 'districts', 'cities',
            'buildingTypes', 'leadStages', 'buildingStatuses', 'customerTypes'
        ));
    }

    /**
     * AJAX: Get Cascading Location Data
     */
    public function getLocationData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'zone':
                return response()->json([
                    'states' => State::where('zone_id', $id)->orderBy('name')->get(['id', 'name']),
                ]);
            case 'state':
                return District::where('state_id', (string) $id)->orderBy('district_name')->get(['id', 'district_name as name']);
            case 'district':
                return City::where('district_id', $id)->orderBy('city_name')->get(['id', 'city_name as name']);
            default:
                return response()->json([]);
        }
    }

    public function store(Request $request)
    {
        $this->authorize('leads.create');
        // Validation for operational fields
        $request->validate([
            'total_order_sqft' => 'nullable|numeric',
        ]);

        DigitalMarketingLead::create($request->all());
        return response()->json(['success' => 'Lead Created Successfully']);
    }

    public function edit($id)
    {
        $this->authorize('leads.edit');
        $lead = DigitalMarketingLead::findOrFail($id);
        return response()->json($lead);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('leads.edit');
        $lead = DigitalMarketingLead::findOrFail($id);

        // Update only the fields provided in the request
        // Update only the fields provided in the request
        $data = $request->all();
        $data['updated_by'] = Auth::id(); // ✅ logged in user

        // Clear reasons if not relevant to the new stage
        if (isset($data['stage']) && $data['stage'] != 2) { // 2 = Disqualified
            $data['disqualified_reason'] = null;
        }
        if (isset($data['stage']) && $data['stage'] != 7) { // 7 = RNR
            $data['rnr_reason'] = null;
        }

        // Check for redundant RNR reason (Optimization: Don't show in history if unchanged)
        $historyRnrReason = $data['rnr_reason'] ?? $lead->rnr_reason;
        if ($lead->stage == 7 && isset($data['stage']) && $data['stage'] == 7 && $lead->rnr_reason == $historyRnrReason) {
             $historyRnrReason = null;
        }
        // Ensure proper null handling for cleared reasons
        if(isset($data['stage']) && $data['stage'] != 7) $historyRnrReason = null;


        $lead->update($data);
        LeadHistory::create([
            'lead_id'           => $lead->id,
            'updated_by'        => Auth::id(),
            'stage'             => $lead->stage,
            'customer_type'     => $lead->customer_type,
            'colour'            => $lead->colour,
            'total_order_sqft'  => $lead->total_order_sqft,
            'building_status'   => $lead->building_status,
            'building_type'     => $lead->building_type,
            'assigned_to'       => $lead->assigned_to,
            'zone'              => $lead->zone,
            'remarks'           => $lead->remarks,
            'future_follow_up_date'  => $lead->future_follow_up_date,
            'future_follow_up_time'  => $lead->future_follow_up_time,
            'potential_follow_up_date' => $lead->potential_follow_up_date,
            'potential_follow_up_time' => $lead->potential_follow_up_time,
            'disqualified_reason'      => $lead->disqualified_reason,
            'rnr_reason'               => $historyRnrReason,
        ]);
        if ($request->filled('assigned_to')) {

            // Use updateOrCreate to prevent duplicate lead entries if the button is clicked twice
            $mainLead = \App\Models\Lead::updateOrCreate(
                ['phone_number' => $lead->phone_number], // Unique identifier
                [
                    'user_id'           => $lead->assigned_to,
                    'name'              => $lead->name,
                    'email'             => $lead->email,
                    'city'              => $lead->city,
                    'type_of_building'  => $lead->building_type, // Map '0' or '1'
                    'building_status'   => $lead->building_status,
                    'color_preference'  => $lead->colour,
                    'zone'              => $lead->zone,
                    'customer_type'     => $lead->customer_type,
                    'lead_source'       => 'HO', // Head Office / Digital Marketing source
                    'lead_stage'        => 0,    // Default to 'site identification'
                    'created_by'        => Auth::id(),
                    // Map the follow up date if available
                    'follow_up_date'    => $lead->future_follow_up_date ?? $lead->potential_follow_up_date,
                ]
            );

            // Link the Digital Lead to the Main Lead
            $lead->update(['lead_id' => $mainLead->id]);
        }
        return response()->json(['success' => true, 'message' => 'Lead updated successfully']);
    }

    public function destroy($id)
    {
        $this->authorize('leads.delete');
        DigitalMarketingLead::findOrFail($id)->delete();
        return response()->json(['success' => 'Lead Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('leads.delete');
        DigitalMarketingLead::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => 'Bulk deletion successful']);
    }

    public function history(Request $request, $id)
    {
        $lead = DigitalMarketingLead::findOrFail($id);
        
        $query = LeadHistory::with('user')
            ->where('lead_id', $id);

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->latest()->get();

        // Fetch helper data for ID-to-Name translation
        $buildingTypes = \App\Helpers\LeadHelper::getBuildingTypes();
        $leadStages    = \App\Helpers\LeadHelper::getLeadStages();
        $leadStages[7] = 'RNR'; // ID 7 fix
        $zones         = Zone::pluck('name', 'id');
        $buildingStatuses = BuildingStatus::pluck('name', 'id');
        $customerTypes = CustomerType::pluck('name', 'id');
        $users         = User::pluck('name', 'id');

        return view('marketing.leads.history', compact(
            'lead',
            'history',
            'buildingTypes',
            'leadStages',
            'zones',
            'buildingStatuses',
            'customerTypes',
            'users'
        ));
    }
}
