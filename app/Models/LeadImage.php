<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'lead_stage',
        'img_path',
        'action',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
