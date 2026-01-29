<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role; 

class StationAllowanceMaster extends Model
{
    use HasFactory;

    protected $table = 'station_allowance_masters';

    protected $fillable = [
        'station_type',
        'amount',
        'action',
        'role_id'
    ];

    public static function getStationTypes()
    {
        return [
            1 => 'Local',
            2 => 'Outstation'
        ];
    }

     public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
