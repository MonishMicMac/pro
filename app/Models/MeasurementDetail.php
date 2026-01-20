<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementDetail extends Model
{
    protected $table = 'measurement_details';

    protected $fillable = [
        'lead_id', 'user_id', 'product', 'design_code', 'area', 
        'width_val', 'height_val', 'qty', 'color', 'sqft', 'notes', 'is_sent_to_quote','width_unit','height_unit'
    ];
}