<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FabricatorRequest extends Model
{
    protected $table = 'fabricator_requests';

    protected $fillable = [
        'lead_id',
        'fabricator_id',
        'approx_sqft',
        'notes',
        'status',
        'fabrication_pdf', // Added
        'rate_per_sqft',    // Added
        'total_quotation_amount',
        'total_value'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function fabricator()
    {
        return $this->belongsTo(Fabricator::class, 'fabricator_id');
    }
}
