<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdmCall extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    // Relationship: The BDM who made the call
    public function bdm()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: The Client (Lead, Account, or Fabricator)
    public function callable()
    {
        return $this->morphTo();
    }
}