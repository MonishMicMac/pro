<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Fabricator;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\FabricatorRequest;
use Illuminate\Support\Facades\Auth;


class FabricatorProfileController extends Controller
{

    public function profile(Request $request, $fabricator_id)
    {
        /*---------------------------------
    DEFAULT DATE (CURRENT MONTH)
    ---------------------------------*/
        $from = $request->from_date
            ?? now()->startOfMonth()->format('Y-m-d');

        $to = $request->to_date
            ?? now()->endOfMonth()->format('Y-m-d');

        /*---------------------------------
    OPTIONAL FILTERS
    ---------------------------------*/
        $wonFilter  = $request->won_filter ?? 'won'; // won | all
        $siteFilter = $request->site_filter ?? 'all'; // completed | pending | all


        /*---------------------------------
    1. GET QUOTED LEAD IDS
    ---------------------------------*/
        $quotedLeadIds = FabricatorRequest::where('fabricator_id', $fabricator_id)
            ->whereBetween('created_at', [$from, $to])
            ->pluck('lead_id');

        $quoteCount = $quotedLeadIds->unique()->count();

        $quoteSqft = Lead::whereIn('id', $quotedLeadIds)
            ->sum('total_required_area_sqft');


        /*---------------------------------
    2. WON SUMMARY (FROM QUOTED LEADS)
    ---------------------------------*/
        $wonLeads = Lead::whereIn('id', $quotedLeadIds)
            ->whereIn('lead_stage', [5, 6])
            ->get();

        $wonCount = $wonLeads->count();
        $wonSqft  = $wonLeads->sum('total_required_area_sqft');


        /*---------------------------------
3. QUOTED LEADS LIST (FILTERED)
---------------------------------*/

        $quoteQuery = Lead::whereIn('id', $quotedLeadIds)
            ->where('fabricator_id', $fabricator_id);

        if ($wonFilter == 'won') {
            $quoteQuery->whereIn('lead_stage', [5, 6]); // only won
        }

        $quoteLeadsList = $quoteQuery
            ->with('assignedUser:id,name')
            ->select('id', 'name', 'total_required_area_sqft', 'user_id', 'installed_date', 'lead_stage')
            ->get()
            ->map(function ($lead, $index) {
                return [
                    's_no'      => $index + 1,
                    'lead_id'   => $lead->id,
                    'lead_name' => $lead->name,
                    'sqft'      => $lead->total_required_area_sqft,
                    'bdo_name'  => $lead->assignedUser->name ?? null,
                    'installed_date' => $lead->installed_date,
                    'status' => in_array($lead->lead_stage, [5, 6]) ? 'Won' : 'Not Won'
                ];
            });

        /*---------------------------------
    4. INSTALLATION SUMMARY
    ---------------------------------*/
        $completedLeads = Lead::where('fabricator_id', $fabricator_id)
            ->where('lead_stage', 6)
            ->whereBetween('installed_date', [$from, $to])
            ->get();

        $pendingLeads = Lead::where('fabricator_id', $fabricator_id)
            ->where('lead_stage', 5)
            ->whereBetween('installed_date', [$from, $to])
            ->get();

        $completedCount = $completedLeads->count();
        $completedSqft  = $completedLeads->sum('total_required_area_sqft');

        $pendingCount = $pendingLeads->count();
        $pendingSqft  = $pendingLeads->sum('total_required_area_sqft');


        /*---------------------------------
    5. COMBINED INSTALLATION LIST
    ---------------------------------*/
        $siteQuery = Lead::with('assignedUser:id,name')
            ->where('fabricator_id', $fabricator_id)
            ->whereBetween('installed_date', [$from, $to]);

        if ($siteFilter == 'completed') {
            $siteQuery->where('lead_stage', 6);
        } elseif ($siteFilter == 'pending') {
            $siteQuery->where('lead_stage', 5);
        } else {
            $siteQuery->whereIn('lead_stage', [5, 6]);
        }

        $installationList = $siteQuery
            ->select('id', 'name', 'total_required_area_sqft', 'user_id', 'installed_date', 'lead_stage')
            ->get()
            ->map(function ($lead, $index) {
                return [
                    's_no' => $index + 1,
                    'lead_name' => $lead->name,
                    'sqft' => $lead->total_required_area_sqft,
                    'bdo_name' => $lead->assignedUser->name ?? null,
                    'installation_date' => $lead->installed_date,
                    'status' => $lead->lead_stage == 6 ? 'Completed' : 'Pending'
                ];
            });


        /*---------------------------------
    RESPONSE
    ---------------------------------*/
        return response()->json([
            'status' => true,
            'filters' => [
                'from_date' => $from,
                'to_date'   => $to,
                'won_filter' => $wonFilter,
                'site_filter' => $siteFilter
            ],
            'data' => [

                'quotes' => [
                    'count' => $quoteCount,
                    'sqft'  => $quoteSqft
                ],

                'won_summary' => [
                    'won_count' => $wonCount,
                    'sqft'      => $wonSqft
                ],

                'quote_leads' => $quoteLeadsList,


                'installation_summary' => [
                    'completed' => [
                        'count' => $completedCount,
                        'sqft'  => $completedSqft
                    ],
                    'pending' => [
                        'count' => $pendingCount,
                        'sqft'  => $pendingSqft
                    ]
                ],

                'installation_list' => $installationList
            ]
        ]);
    }
}
