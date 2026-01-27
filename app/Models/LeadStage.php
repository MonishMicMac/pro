<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStage extends Model
{
    use HasFactory;

    // Explicitly define the table name matching your SQL dump
    protected $table = 'lead_stages';

    protected $fillable = [
        'lead_id',
        'lead_stage',
        'img_path',
        'action' // '0' or '1' based on your schema
    ];

    // Optional: Relationship back to Lead 
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}