<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'lead_status_histories';

    protected $fillable = [
       'lead_id',
    'building_stage',
    'lead_status',
    ];

    /**
     * Each history record belongs to one lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
