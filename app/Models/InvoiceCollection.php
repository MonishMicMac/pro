<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCollection extends Model
{
    protected $fillable = [
        'cust_id',
        'invoice_no',
        'invoice_date',
        'invoice_amount',
        'due_date',
        'collected_date',
        'collected_amount',
        'due_amount',
        'overdue_days'
    ];
}
