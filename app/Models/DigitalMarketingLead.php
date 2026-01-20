<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalMarketingLead extends Model
{
    // Explicitly define the table name
    protected $table = 'digital_marketing_leads';

    protected $fillable = [
        'id', 'date', 'name', 'phone_number', 'email', 'city', 'otp_status',
        'color_preference', 'source', 'campaign_name', 'ad_name',
        'keyword', 'campaign_id', 'ad_set_id', 'ad_id',
        'ad_set_name', 'form_id', 'referred_from', 'notes', 'enquiry_count',
        'stage','customer_type','colour','total_order_sqft','building_status','building_type',
        'remarks','assigned_to','zone','future_follow_up_date','potential_follow_up_date','future_follow_up_time','potential_follow_up_time','disqualified_reason','rnr_reason',
        'lead_id'
    ];

    public function assignedUser(): BelongsTo
    {
        // Assuming 'assigned_to' refers to the ID in the 'users' table
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function zoneDetails(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(BuildingStatus::class, 'building_status');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
