<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zones';
    protected $fillable = ['name','action'];

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
