<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMapping extends Model
{
    protected $fillable = ['md_id', 'vp_id', 'ism_id', 'zsm_id', 'bdm_id', 'bdo_id', 'action'];

    // Relationships to the User model
    public function md() { return $this->belongsTo(User::class, 'md_id'); }
    public function vp() { return $this->belongsTo(User::class, 'vp_id'); }
    public function ism() { return $this->belongsTo(User::class, 'ism_id'); }
    public function zsm() { return $this->belongsTo(User::class, 'zsm_id'); }
    public function bdm() { return $this->belongsTo(User::class, 'bdm_id'); }
    public function bdo() { return $this->belongsTo(User::class, 'bdo_id'); }

    // Accessor for multiple ZSMs
    public function getZsmsAttribute()
    {
        if (empty($this->zsm_id)) return collect([]);
        return User::whereIn('id', explode(',', $this->zsm_id))->get(['id', 'name']);
    }
}