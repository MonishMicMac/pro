<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainMenu extends Model
{
    // Important: Tell Laravel to use your manual table name
    protected $table = 'mainmenu';

    public function submenus() 
    {
        // Use the Submenu class and the foreign key from your SQL table
        return $this->hasMany(Submenu::class, 'mainmenu_id')->orderBy('position');
    }
}