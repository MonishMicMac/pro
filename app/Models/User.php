<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    // ADD HasApiTokens TO THE LINE BELOW
    use HasApiTokens, HasRoles, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'brand_id', 'emp_code',
        'doj', 'designation', 'phone', 'zone_id', 'state_id',
        'district_id', 'city_id', 'area_id', 'pincode_id', 'otp'
    ];

    // Added 'otp' to hidden so it doesn't show up in API results for security
    protected $hidden = ['password', 'remember_token', 'otp'];

    /**
     * Cast attributes to handle JSON storage as arrays.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'brand_id' => 'array',
            'area_id' => 'array',
            'pincode_id' => 'array',
        ];
    }
}