<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    /**
     * Display the expenses page.
     */
    public function index()
    {
        $users = User::orderBy('name')->where('action', '0')->get(['id', 'name']);
        return view('expenses.index', compact('users'));
    }

    /**
     * Data handler for DataTables.
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = Expense::with(['user:id,name', 'expenseType:id,name'])
                ->select('expenses.*');

            // Filters
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('expense_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('expense_date', '<=', $request->to_date);
            }
          
            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y h:i A');
                })
                ->editColumn('expense_date', function ($row) {
                    return date('d-m-Y', strtotime($row->expense_date));
                })
                ->editColumn('user_name', function ($row) {
                    return $row->user ? $row->user->name : 'Unknown';
                })
                ->editColumn('expense_type_name', function ($row) {
                     $type = $row->expenseType ? $row->expenseType->name : '-';
                     if ($type === 'Others' || $type === 'Other' || !$row->expenseType) {
                         return $type . ($row->other_expense_name ? ' (' . $row->other_expense_name . ')' : '');
                     }
                     return $type;
                })
                ->editColumn('action', function($row) {
                     return $row->action == '0' 
                        ? '<span class="inline-flex px-2 py-1 bg-green-50 text-green-600 rounded-lg text-[10px] font-black uppercase tracking-wider">Active</span>'
                        : '<span class="inline-flex px-2 py-1 bg-red-50 text-red-600 rounded-lg text-[10px] font-black uppercase tracking-wider">Inactive</span>';
                })
                ->addColumn('proof', function($row) {
                    if ($row->image) {
                        $url = asset('storage/' . $row->image);
                        return '<a href="'.$url.'" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                    <span class="material-symbols-outlined text-[18px]">image</span>
                                </a>';
                    }
                    return '<span class="text-slate-300 text-[20px] material-symbols-outlined">image_not_supported</span>';
                })
                ->addColumn('actions', function ($row) {
                    // Start hidden
                    return ''; 
                })
                ->rawColumns(['action', 'proof'])
                ->make(true);
        }
    }
}
