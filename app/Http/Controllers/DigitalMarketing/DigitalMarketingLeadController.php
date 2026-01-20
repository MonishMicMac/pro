<?php

namespace App\Http\Controllers\DigitalMarketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DigitalMarketingLead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Zone;
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

        $buildingTypes = \App\Helpers\LeadHelper::getBuildingTypes();
        $leadStages    = \App\Helpers\LeadHelper::getLeadStages();

        if ($request->ajax()) {
            $query = DigitalMarketingLead::with(['assignedUser', 'zoneDetails', 'status', 'type', 'lead']);

            // Filter by Stage
            if ($request->filled('stage')) {
                $query->where('stage', $request->stage);
            }

            // Filter by Future Follow Up Date
            if ($request->filled('future_date')) {
                $query->whereDate('future_follow_up_date', $request->future_date);
            }

            // Filter by Potential Follow Up Date
            if ($request->filled('potential_date')) {
                $query->whereDate('potential_follow_up_date', $request->potential_date);
            }

            $data = $query->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowId(function($row) {
                    return $row->id; // Explicitly returning the value
                })
                ->editColumn('date', function ($row) {
                    return $row->date ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '-';
                })
                ->editColumn('future_follow_up_date', function ($row) {
                    if(!$row->future_follow_up_date) return '-';
                    $time = $row->future_follow_up_time ? ' ' . \Carbon\Carbon::parse($row->future_follow_up_time)->format('h:i A') : '';
                    return \Carbon\Carbon::parse($row->future_follow_up_date)->format('d-m-Y') . $time;
                })
                ->editColumn('potential_follow_up_date', function ($row) {
                    if(!$row->potential_follow_up_date) return '-';
                    $time = $row->potential_follow_up_time ? ' ' . \Carbon\Carbon::parse($row->potential_follow_up_time)->format('h:i A') : '';
                    return \Carbon\Carbon::parse($row->potential_follow_up_date)->format('d-m-Y') . $time;
                })
                ->editColumn('stage', function($row) use ($leadStages) {
                    return $leadStages[$row->stage] ?? '-';
                })
                ->editColumn('building_type', function($row) use ($buildingTypes) {
                    return $buildingTypes[$row->building_type] ?? '-';
                })
                ->rawColumns(['otp_status'])
                ->make(true);
        }

        $users = User::all();
        $zones = Zone::where('action', '0')->get();
        $buildingStatuses = BuildingStatus::where('action', 0)->get();
        $customerTypes = CustomerType::where('action', 0)->get();

        return view('marketing.leads.index', compact(
            'users',
            'zones',
            'buildingTypes',
            'leadStages',
            'buildingStatuses',
            'customerTypes'
        ));

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
        $lead->update($request->all());
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
            'rnr_reason'               => $lead->rnr_reason,
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

    public function history($id)
    {
        $lead = DigitalMarketingLead::findOrFail($id);
        $history = LeadHistory::with('user')
            ->where('lead_id', $id)
            ->latest()
            ->get();

        // Fetch helper data for ID-to-Name translation
        $buildingTypes = \App\Helpers\LeadHelper::getBuildingTypes();
        $leadStages    = \App\Helpers\LeadHelper::getLeadStages();
        $zones         = Zone::pluck('name', 'id');
        $buildingStatuses = BuildingStatus::pluck('name', 'id');
        $customerTypes = CustomerType::pluck('name', 'id');
        $users         = User::pluck('name', 'id');

        return view('marketing.leads.history', compact(
            'lead', 'history', 'buildingTypes', 'leadStages',
            'zones', 'buildingStatuses', 'customerTypes', 'users'
        ));
    }
}
