<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadVisit extends Model
{
    protected $table = 'lead_visits';

    protected $fillable = [
        'user_id', 
        'type',
        'lead_id', 
        'account_id', 
        'fabricator_id', 
        'visit_type', 
        'food_allowance', 
        'schedule_date', 
        'visit_date', 
        'intime_time', 
        'out_time', 
        'inlat', 
        'inlong', 
        'outlat', 
        'outlong', 
        'remarks', 
        'work_type', 
        'bdm_id', 
        'bdo_id', 
        'image', 
        'action','lead_stage'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function fabricator()
    {
        return $this->belongsTo(User::class, 'fabricator_id');
    }
}