<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadVisitBdm extends Model
{
    protected $table = 'lead_visits_bdm';

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
        'intime', 
        'outtime', 
        'inlat', 
        'inlong', 
        'outlat', 
        'outlong', 
        'remarks', 
        'work_type', 
        'bdm_id', 
        'bdo_id', 
        'image', 
        'vehicle_type', 
        'action'
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
        return $this->belongsTo(Fabricator::class, 'fabricator_id');
    }

    public function bdm()
    {
        return $this->belongsTo(User::class, 'bdm_id');
    }

    public function bdo()
    {
        return $this->belongsTo(User::class, 'bdo_id');
    }
}