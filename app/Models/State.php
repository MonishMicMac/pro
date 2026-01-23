<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'action',
        'zone_id'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    // Status is '0' or '1', no specific cast needed automatically unless we want integer, 
    // but enum usually comes as string. We can leave it or just remove the boolean cast.
    protected $casts = [
        // 'status' => 'string',
    ];
}
