<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdoTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_month',
        'target_new_calls',
        'target_quotations',
        'target_followups',
        'target_conversion_sqft',
        'target_sales_value',
        'assigned_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignor()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
