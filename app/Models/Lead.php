<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $table = 'leads';

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'email',
        'city',
        'site_owner_name',
        'site_owner_mobile_number',
        'deciding_authority',
        'deciding_authority_mobile_number',
        'site_area',
        'site_address',
        'latitude',
        'longitude',
        'order_type',
        'type_of_building',
        'building_status',
        'lead_priority',
        'total_required_area_sqft',
        'type_of_product',
        'order_give_date',
        'color_preference',
        'lead_stage',
        'customer_type',
        'assigned_by',
        'zone',
        'lead_source',
        'discovery_source',
        'expected_timeline',
        'created_by',
        'status',
        'follow_up_date',
        'final_quotation_pdf',
        'won_date',
        'expected_installation_date',
        'advance_received',
        'lost_type',
        'competitor',
        'installed_date',
        'handovered_date',
        'brand_id',
        'priority',
        'lead_temperature',
        'fabricator_id',
        'google_review',
        'total_quotation_amount',
        'total_value',
        'final_rate_per_sqft'
    ];

    protected $casts = [
        'order_give_date' => 'date',
        'total_required_area_sqft' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (optional, extend later)
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function measurements()
    {
        return $this->hasMany(MeasurementDetail::class, 'lead_id');
    }

    public function fabricatorRequests()
    {
        return $this->hasMany(FabricatorRequest::class, 'lead_id');
    }

    public function handoverPhotos()
    {
        return $this->hasMany(LeadHandoverPhoto::class, 'lead_id');
    }

    public function images()
    {
        return $this->hasMany(LeadImage::class, 'lead_id');
    }

    public function fabricator()
    {
        return $this->belongsTo(Fabricator::class, 'fabricator_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function zoneRelation()
    {
        return $this->belongsTo(Zone::class, 'zone'); // Assuming column is 'zone'
    }

    public function stateRelation()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function districtRelation()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function cityRelation()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function areaRelation()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function pincodeRelation()
    {
        return $this->belongsTo(Pincodes::class, 'pincode_id');
    }
}
