<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttendance extends Model
{
    protected $table = 'users_attendances';

    protected $fillable = [
      'user_id', 'user_role', 'punch_in_time', 'punch_out_time', 
      'in_lat', 'in_long', 'out_lat', 'out_long', 
      'start_km', 'end_km', 'start_km_photo', 'end_km_photo', 
      'in_time_vehicle_type', 'out_time_vehicle_type', 'status','date'
    ];
}