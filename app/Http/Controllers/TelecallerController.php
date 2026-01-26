<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DigitalMarketingLead;
use App\Models\User;
use App\Helpers\LeadHelper;
use Illuminate\Support\Facades\DB;

class TelecallerController extends Controller
{
    /**
     * Display the Telecaller Consolidated Report
     */
    public function index(Request $request)
    {
        // 1. Get all Lead Stages from Helper
        $stages = LeadHelper::getLeadStages();
        
        // Ensure specific overrides match your other controllers
        $stages[0] = 'New Lead';
        $stages[7] = 'RNR';

        // 2. Prepare the Query for Statistics
        $query = DigitalMarketingLead::query();

        // --- Apply Filters ---

        // Filter by Date Range (Created Date or Assigned Date? Usually Created Date for management reports)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by specific Telecaller if selected
        if ($request->filled('telecaller_id')) {
            $query->where('telecaller_id', $request->telecaller_id);
        } else {
            // Only show leads that HAVE a telecaller assigned
            $query->whereNotNull('telecaller_id');
        }

        // 3. Fetch Aggregate Data
        // Group by Telecaller and Stage to get counts
        $stats = $query->select('telecaller_id', 'stage', DB::raw('count(*) as total'))
            ->groupBy('telecaller_id', 'stage')
            ->get();

        // 4. Fetch Telecaller Names for Display
        // We only need users who actually appear in the stats or are marked as telecallers
        $telecallerIds = $stats->pluck('telecaller_id')->unique();
        $telecallers = User::whereIn('id', $telecallerIds)->pluck('name', 'id');
        
        // Also fetch ALL telecallers for the filter dropdown (Role ID 16)
        $allTelecallers = User::whereHas('roles', function($q) {
            $q->where('id', 16); 
        })->get();

        // 5. Structure Data for the View
        // Format: [ telecaller_id => [ 'name' => 'John', 'counts' => [ stage_id => count ] ] ]
        $reportData = [];

        foreach ($stats as $row) {
            $tId = $row->telecaller_id;
            
            if (!isset($reportData[$tId])) {
                $reportData[$tId] = [
                    'name'   => $telecallers[$tId] ?? 'Unknown User',
                    'counts' => [],
                    'total_assigned' => 0
                ];
            }

            $reportData[$tId]['counts'][$row->stage] = $row->total;
            $reportData[$tId]['total_assigned'] += $row->total;
        }

        return view('telecaller_report.index', compact('reportData', 'stages', 'allTelecallers'));
    }
}