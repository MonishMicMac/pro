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
        'action',
        'vehicle_type',
        'lead_stage'
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

    public function bdo()
    {
    // This tells Laravel: "bdo_id" in this table belongs to a "User"
    return $this->belongsTo(User::class, 'bdo_id');
    }
    public function bdm()
    {
    // Assuming 'bdm_id' is the column in lead_visits table
    return $this->belongsTo(User::class, 'bdm_id'); 
    }
    /**
     * Relationship to the Joint Work Request table
     * Used to check if a Joint Work request is Pending, Approved, or Declined.
     */
    public function jointWorkRequest()
    {
        return $this->hasOne(JointWorkRequest::class, 'visit_id');
    }
}