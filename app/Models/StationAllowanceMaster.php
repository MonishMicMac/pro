<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationAllowanceMaster extends Model
{
    use HasFactory;

    protected $table = 'station_allowance_masters';

    protected $fillable = [
        'station_type',
        'amount',
        'action',
    ];

    public static function getStationTypes()
    {
        return [
            1 => 'Local',
            2 => 'Outstation'
        ];
    }
}
