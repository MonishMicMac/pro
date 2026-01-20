<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pincodes extends Model {
    protected $table = 'pincodes';
    protected $fillable = ['area_id', 'pincode', 'action'];
}