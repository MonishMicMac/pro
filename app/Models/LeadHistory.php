<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadHistory extends Model
{
    protected $fillable = [
        'lead_id', 'updated_by', 'stage', 'customer_type', 'colour',
        'total_order_sqft', 'building_status', 'building_type',
        'assigned_to', 'zone', 'remarks','future_follow_up_date','potential_follow_up_date','future_follow_up_time','potential_follow_up_time','disqualified_reason','rnr_reason'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
