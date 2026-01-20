<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ExpenseTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('expense-types.view');

        if ($request->ajax()) {
            $data = ExpenseType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.expense-types.index');
    }

    public function store(Request $request)
    {
        $this->authorize('expense-types.create');

        $request->validate([
            'name' => 'required|unique:expense_types,name,NULL,id,action,0'
        ]);

        ExpenseType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Expense Type added']);
    }

    public function edit(ExpenseType $expenseType)
    {
        $this->authorize('expense-types.edit');
        return response()->json($expenseType);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('expense-types.edit');

        $request->validate([
            'name' => 'required|unique:expense_types,name,' . $id . ',id,action,0'
        ]);

        ExpenseType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        $this->authorize('expense-types.delete');

        ExpenseType::where('id', $id)->update([
            'action' => '1'
        ]);

        return response()->json(['success' => 'Expense Type deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('expense-types.delete');

        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        ExpenseType::whereIn('id', $request->ids)
            ->update(['action' => '1']);

        return response()->json([
            'success' => 'Selected expense type deactivated'
        ]);
    }
}