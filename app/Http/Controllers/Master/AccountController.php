<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Zone;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;


class AccountController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('accounts.view');

        if ($request->ajax()) {
            $query = Account::with(['zone', 'state', 'district', 'accountType', 'user'])->where('action', '0');

            // Apply ZSM restriction
            if (auth()->user()->hasRole('ZSM')) {
                // Find all users mapped under this ZSM
                $mappings = \App\Models\UserMapping::where('zsm_id', auth()->id())->get();
                
                // Get relevant user IDs: The ZSM themselves, their BDMs, and their BDOs
                $userIds = $mappings->pluck('bdo_id')
                    ->merge($mappings->pluck('bdm_id'))
                    ->push(auth()->id()) // Include the ZSM
                    ->unique()
                    ->filter() // Remove nulls
                    ->values()
                    ->toArray();

                $query->whereIn('user_id', $userIds);
            }

            // Apply Filters
            if ($request->zone_id) {
                $query->where('zone_id', $request->zone_id);
            }

            if ($request->state_id) {
                $query->where('state_id', $request->state_id);
            }

            if ($request->manager_id) {
                // Accounts created by this manager OR their mapped BDOs
               // Logic: Find all BDOs mapped to this manager in UserMapping
                 $bdoIds = \App\Models\UserMapping::where('bdm_id', $request->manager_id)->pluck('bdo_id')->toArray();
                 $relevantUserIds = array_merge([$request->manager_id], $bdoIds);
                 $query->whereIn('user_id', $relevantUserIds);
            }

            if ($request->bdo_id) {
                $query->where('user_id', $request->bdo_id);
            }


            $data = $query->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_name', function ($row) {
                    return $row->accountType->name ?? '-';
                })
                ->addColumn('location', function ($row) {
                    $loc = [];
                    if ($row->district) $loc[] = $row->district->district_name;
                    if ($row->state) $loc[] = $row->state->name;
                    return implode(', ', $loc) ?: '-';
                })
                ->addColumn('created_by', function ($row) {
                    return $row->user ? $row->user->name : '-';
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

        Account::create($request->all() + ['action' => '0', 'user_id' => auth()->id()]);

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
