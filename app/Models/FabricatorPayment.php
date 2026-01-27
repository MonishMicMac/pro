<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricatorPayment extends Model
{
    protected $fillable = [
        'cust_id',
        'payment_mode',
        'ref_no',
        'amount',
        'payment_date'
    ];
}
