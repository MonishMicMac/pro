<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Brand;

class DigitalMarketingLead extends Model
{
    // Explicitly define the table name
    protected $table = 'digital_marketing_leads';

    protected $fillable = [
        'id',
        'date',
        'name',
        'phone_number',
        'email',
        'city',
        'otp_status',
        'color_preference',
        'source',
        'campaign_name',
        'ad_name',
        'keyword',
        'campaign_id',
        'ad_set_id',
        'ad_id',
        'ad_set_name',
        'form_id',
        'referred_from',
        'notes',
        'enquiry_count',
        'stage',
        'customer_type',
        'colour',
        'total_order_sqft',
        'building_status',
        'building_type',
        'remarks',
        'assigned_to',
        'assigned_at',
        'zone',
        'future_follow_up_date',
        'potential_follow_up_date',
        'future_follow_up_time',
        'potential_follow_up_time',
        'disqualified_reason',
        'rnr_reason',
        'lead_id',
        'crossed_lead_id',
        'transfered_by',
        'transfered_date',
        'before_transfer_user',
        'transfter_remarks',
        'transftered_lead_using_brand',
        'telecaller_id',
        'updated_by','is_cross_selling'
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


    public function transferer(): BelongsTo
{
    return $this->belongsTo(User::class, 'transfered_by');
}
    public function telecaller()
    {
        return $this->belongsTo(User::class, 'telecaller_id');
    }

    public function targetBrand()
    {
        return $this->belongsTo(Brand::class, 'transftered_lead_using_brand');
    }
}
