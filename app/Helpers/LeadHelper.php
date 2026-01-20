<?php

namespace App\Helpers;

use App\Models\Lead;
use App\Models\LeadStatusHistory;

class LeadHelper
{
    public static function logStatus(Lead $lead, $newBuildingStage, $newLeadStatus)
    {
        LeadStatusHistory::create([
            'lead_id'        => $lead->id,
            'building_stage' => $newBuildingStage,
            'lead_status'    => $newLeadStatus,
        ]);
    }

    public static function getBuildingTypes()
    {
        return [
            0 => 'Residential',
            1 => 'Commercial'
        ];
    }

    // New method for Lead Stages
    public static function getLeadStages()
    {
        return [
            0 => 'RNR',
            1 => 'Call Back',
            2 => 'Disqualified',
            3 => 'No Requirement',
            4 => 'Prospect',
            5 => 'Future Follow up',
            6 => 'Potential Follow up'
        ];
    }
}
