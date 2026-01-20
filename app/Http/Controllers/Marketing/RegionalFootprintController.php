<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Fabricator;
use App\Models\Account;
use Illuminate\Http\Request;

class RegionalFootprintController extends Controller
{
    public function index()
    {
        return view('marketing.regional_footprint');
    }

    public function getData()
    {
        // 1. Leads
        $leads = Lead::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'latitude', 'longitude', 'lead_stage', 'city']);

        $leadMarkers = $leads->map(function ($lead) {
            $color = 'gray'; // Default: Unidentified (Stage 0)
            if ($lead->lead_stage == 4) {
                $color = 'purple'; // Quote Sent
            } elseif ($lead->lead_stage == 5 || $lead->lead_stage == 6) {
                $color = 'green'; // Converted/Won
            }

            return [
                'type' => 'lead',
                'name' => $lead->name,
                'lat' => (float)$lead->latitude,
                'lng' => (float)$lead->longitude,
                'color' => $color,
                'details' => "Lead: {$lead->name}<br>City: {$lead->city}"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $leadMarkers
        ]);
    }
}
