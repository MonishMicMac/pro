<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'item_code',
        'brand_id',
        'property_type_id',
        'item_type_id',
        'category_id',
        'sub_category_id',
        'model_name',
        'length',
        'pieces_per_packet',
        'section_weight',
        'name',
        'tax_rate',
        'uom',
        'action'
    ];

    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function propertyType() {
        return $this->belongsTo(PropertyType::class);
    }

    public function itemType() {
        return $this->belongsTo(ItemType::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function subCategory() {
        return $this->belongsTo(SubCategory::class);
    }
}
