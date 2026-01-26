<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use App\Models\Area;
use App\Models\Pincodes;
use App\Models\Brand;
use App\Models\Zone;
use \App\Models\User;
use \App\Models\FabricatorRequest;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Fabricator extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;

    protected $guard_name = 'fabricator';
    protected $fillable = [
        'shop_name',
        'cust_id',
        'password',
        'email',

        // LOCATION
        'zone_id',
        'state_id',
        'district_id',
        'city_id',
        'area_id',
        'pincode_id',
        'address',
        'shipping_address',
        'billing_address',

        // BUSINESS
        'division',
        'category',
        'segment',
        'sub_segment',
        'gst',

        // BANK
        'bank_name',
        'ifsc_code',
        'account_number',
        'branch',
        'payment_credit_terms',
        'credit_limit',
        'credit_days',

  'net',
        // CONTACT
        'contact_person',
        'sales_person_id',
        'contact_type',
        'email',
        'mobile',

        'action',
        'status',
        'remarks',
        'is_existing',
        'shop_image',
        'latitude',
        'longitude',
        'created_by',
        'noHashPassword'
    ];


    protected $hidden = ['password'];



    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function pincode()
    {
        return $this->belongsTo(Pincodes::class);
    }
    public function brands()
    {
        return $this->belongsToMany(
            Brand::class,
            'fabricator_brand',
            'fabricator_id',
            'brand_id'
        );
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests()
    {
        return $this->hasMany(FabricatorRequest::class);
    }
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }
}
