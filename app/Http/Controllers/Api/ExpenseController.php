<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Store a new expense.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'            => 'required|exists:users,id',
            'expense_type'       => 'required|exists:expense_types,id',
            'other_expense_name' => 'nullable|string|max:255',
            'expense_amount'     => 'required|numeric|min:0',
            'expense_date'       => 'required|date',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('expenses', 'public');
            }

            $expense = Expense::create([
                'user_id'            => $request->user_id,
                'expense_type'       => $request->expense_type,
                'other_expense_name' => $request->other_expense_name,
                'expense_amount'     => $request->expense_amount,
                'expense_date'       => $request->expense_date,
                'image'              => $imagePath,
                'action'             => '0', // Active
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Expense added successfully',
                'data'    => $expense,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to add expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expenses for a user.
     * Optional: Filter by specific date or date range.
     */
    public function getExpenses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $query = Expense::where('user_id', $request->user_id)
            ->with(['expenseType:id,name']); // Eager load type name

        if ($request->filled('date')) {
            $query->whereDate('expense_date', $request->date);
        }

        // Order by newest first
        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('created_at', 'desc')->get();

        $totalAmount = $expenses->sum('expense_amount');

        return response()->json([
            'status'       => true,
            'message'      => 'Expenses retrieved successfully',
            'count'        => $expenses->count(),
            'total_amount' => $totalAmount,
            'data'         => $expenses,
        ], 200);
    }
}
