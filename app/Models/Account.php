<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';
    protected $fillable = [
        'name',
        'mobile_number',
        'address',
        'zone_id',
        'state_id',
        'district_id',
        'pincode',
        'account_type_id',
        'user_id',
        'action'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }
}
