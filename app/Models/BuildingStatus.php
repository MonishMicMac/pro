<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * * @var string
     */
    protected $table = 'building_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'action',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'action' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Optional: Helper to check if status is active
     * Usage: $buildingStatus->isActive()
     */
    public function isActive(): bool
    {
        return $this->action === false; // 0 = Active based on your comment
    }
}
