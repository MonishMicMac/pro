<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fabricator;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';
    protected $fillable = ['name', 'action'];

    public function fabricators()
    {
        return $this->belongsToMany(
            Fabricator::class,
            'fabricator_brand',
            'brand_id',
            'fabricator_id'
        );
    }
}
