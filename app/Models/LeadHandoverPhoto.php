<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadHandoverPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'photo_path'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
