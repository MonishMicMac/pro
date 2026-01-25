<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceResponse extends Model
{
    use HasFactory;

    protected $table = 'invoice_responses';

    protected $fillable = [
        'invoice_no',
        'request',
    ];

    protected $casts = [
        'request' => 'array', // JSON ? PHP array
    ];

    /**
     * Relationship: response belongs to an invoice
     */
    public function invoice()
    {
        return $this->belongsTo(FabricatorInvoice::class, 'invoice_no', 'invoice_no');
    }
}
