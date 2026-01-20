<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Zone;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('accounts.view');

        if ($request->ajax()) {
            $data = Account::with(['zone', 'state', 'district', 'accountType'])->where('action', '0')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_name', function($row) {
                    return $row->accountType->name ?? '-';
                })
                ->addColumn('location', function($row) {
                    $loc = [];
                    if ($row->district) $loc[] = $row->district->district_name;
                    if ($row->state) $loc[] = $row->state->name;
                    return implode(', ', $loc) ?: '-';
                })
                ->make(true);
        }

        $zones = Zone::where('action', '0')->get();
        $accountTypes = AccountType::where('action', '0')->get();

        return view('masters.accounts.index', compact('zones', 'accountTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('accounts.create');

        $request->validate([
            'name' => 'required',
            'mobile_number' => 'required',
            'zone_id' => 'required',
            'state_id' => 'required',
            'district_id' => 'required',
            'account_type_id' => 'required',
        ]);

        Account::create($request->all() + ['action' => '0']);

        return response()->json(['success' => 'Account added']);
    }

    public function edit(Account $account)
    {
        $this->authorize('accounts.edit');
        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('accounts.edit');

        $request->validate([
            'name' => 'required',
            'mobile_number' => 'required',
            'zone_id' => 'required',
            'state_id' => 'required',
            'district_id' => 'required',
            'account_type_id' => 'required',
        ]);

        Account::where('id', $id)->update($request->except(['_token', '_method', 'id']));

        return response()->json(['success' => 'Updated']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('accounts.delete');

        if (!$request->has('ids') || !is_array($request->ids)) {
            return response()->json(['error' => 'No records selected'], 422);
        }

        Account::whereIn('id', $request->ids)->update(['action' => '1']);

        return response()->json(['success' => 'Selected accounts deactivated']);
    }
}