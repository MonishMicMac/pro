<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Fabricator;
use App\Models\Product; // Assuming you have a Product model


class FabricatorStockManagement extends Model
{
    use HasFactory;

    // 1. Define the table name explicitly if it doesn't follow Laravel's plural convention
    protected $table = 'fabricator_stock_management';

    // 2. Define the fillable fields based on your table columns
    protected $fillable = [
        'product_id',
        'fabricator_id',
        'opening_stock',
        'closing_stock',
        'current_stock',
        'updated_by',
        'action', // enum('0', '1')
    ];

    // 3. Define Relationships (Optional but recommended)

    /**
     * Get the fabricator (User) associated with this stock.
     */
    public function fabricator()
    {
        return $this->belongsTo(Fabricator::class, 'fabricator_id');
    }


    /**
     * Get the product associated with this stock.
     */
    public function product()
    {
        // Assuming 'product_id' links to a 'products' table
        return $this->belongsTo(Product::class, 'product_id');
    }
}