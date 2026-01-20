<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadVisit extends Model
{
    protected $table = 'lead_visits';

    protected $fillable = [
        'lead_id', 'user_id', 'intime_time', 'out_time', 'inlat', 'inlong', 
        'outlat', 'outlong', 'remarks', 'image', 'action'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}