<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FabricatorProjection extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabricator_id',
        'user_id',
        'projection_month',
        'sale_projection_tonnage',
        'fabricator_collection',
        'action'
    ];

    public function fabricator()
    {
        return $this->belongsTo(Fabricator::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
