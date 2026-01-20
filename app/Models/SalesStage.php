<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesStage extends Model
{
    use HasFactory;

    protected $table = 'sales_stages';
    protected $fillable = ['name','action'];
}
