<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'expense_type',
        'other_expense_name',
        'expense_amount',
        'expense_date',
        'action',
        'image',
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense type.
     */
    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type');
    }
}
