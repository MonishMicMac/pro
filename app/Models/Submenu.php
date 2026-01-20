<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submenu extends Model
{
    // Important: Tell Laravel to use your manual table name
    protected $table = 'submenu';

    public function mainmenu()
    {
        return $this->belongsTo(MainMenu::class, 'mainmenu_id');
    }
}