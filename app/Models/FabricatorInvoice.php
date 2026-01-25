<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricatorInvoice extends Model
{
    use HasFactory;

    protected $table = 'fabricator_invoices';

    protected $fillable = [
        'fabricator_id',
        'invoice_type',
        'invoice_no',
        'invoice_date',
        'amount',
        'category',
        'qty',
        'original_invoice_no',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount'       => 'decimal:2',
        'qty'          => 'integer',
    ];

    /**
     * Relationship: one invoice can have many responses
     */
    public function responses()
    {
        return $this->hasMany(InvoiceResponse::class, 'invoice_no', 'invoice_no');
    }
}
