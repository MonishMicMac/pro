<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AccountTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // Permission check (assuming same pattern as accounts)
        $this->authorize('accounts.view');

        if ($request->ajax()) {
            $data = AccountType::where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('masters.account_types.index');
    }

    public function store(Request $request)
    {
        $this->authorize('accounts.create');

        $request->validate([
            'name' => 'required|unique:account_types,name,NULL,id,action,0'
        ]);

        AccountType::create([
            'name' => $request->name,
            'action' => '0'
        ]);

        return response()->json(['success' => 'Account type added']);
    }

    public function edit($id)
    {
        $this->authorize('accounts.edit');
        $accountType = AccountType::findOrFail($id);
        return response()->json($accountType);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('accounts.edit');

        $request->validate([
            'name' => 'required|unique:account_types,name,' . $id . ',id,action,0'
        ]);

        AccountType::where('id', $id)->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => 'Updated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('accounts.delete');

        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        AccountType::whereIn('id', $request->ids)->update(['action' => '1']);

        return response()->json(['success' => 'Selected types deactivated']);
    }
}
