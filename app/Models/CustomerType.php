<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_types';

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
        'action' => 'integer', // Cast to int since it stores 0 or 1
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active customer types.
     * Usage: CustomerType::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where('action', 0);
    }

    /**
     * Get the status label.
     * Usage: $customerType->status_label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->action === 0 ? 'Active' : 'Inactive';
    }
}
