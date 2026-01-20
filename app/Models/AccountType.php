<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $table = 'account_types';
    protected $fillable = ['name', 'action'];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }
}
