<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JointWorkRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    // ✅ Link 'bdo_id' to the User table
    public function bdo()
    {
        return $this->belongsTo(User::class, 'bdo_id');
    }

    // ✅ Link 'bdm_id' to the User table (Optional, but good practice)
    public function bdm()
    {
        return $this->belongsTo(User::class, 'bdm_id');
    }

    // ✅ Link 'visit_id' to the LeadVisit table
    // This allows you to use 'visit.lead', 'visit.account' in your controller
    public function visit()
    {
        return $this->belongsTo(LeadVisit::class, 'visit_id');
    }
}