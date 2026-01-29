<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelAllowanceMaster extends Model
{
    use HasFactory;

    protected $table = 'travel_allowance_masters';

    protected $fillable = [
        'vehicle_type',
        'amount',
        'action',
    ];

    public static function getVehicleTypes()
    {
        return [
            1 => 'Bike',
            2 => 'Car'
        ];
    }
}
