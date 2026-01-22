<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Models\LeadVisitBdm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeadVisitBdmController extends Controller
{
    public function fieldActivity()
    {
        $authUser = Auth::user();
        
        // Leads for selection
        $leads = Lead::whereNotIn('lead_stage', [5, 6, 7])->orderBy('name')->get();
        
        // Accounts list
        $accounts = \App\Models\Account::where('action', '0')->orderBy('name')->get();
        
        // Fabricators list
        $fabricators = \App\Models\Fabricator::where('status', '1')->orderBy('shop_name')->get();

        // Users for BDM/BDO selection
        $users = User::where('action', '0')->orderBy('name')->get();

        // Today's scheduled visits (For BDMs this might be different, but using Leads for now as requested/contextual)
        $scheduledVisits = LeadVisitBdm::where('user_id', $authUser->id)
            ->whereDate('schedule_date', Carbon::today())
            ->where('action', 'Pending')
            ->with(['lead', 'account', 'fabricator'])
            ->get();

        // Any currently active visit
        $activeVisit = LeadVisitBdm::where('user_id', $authUser->id)
            ->where('action', 'In-Progress')
            ->with(['lead', 'account', 'fabricator'])
            ->first();

        return view('bdm_visits.on_field', compact('leads', 'scheduledVisits', 'activeVisit', 'accounts', 'fabricators', 'users'));
    }

    public function report()
    {
        $users = User::orderBy('name')->where('action', '0')->get();
        return view('bdm_visits.report', compact('users'));
    }

    public function reportData(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::today()->toDateString();
        $toDate = $request->to_date ?? Carbon::today()->toDateString();
        $userId = $request->user_id;

        $query = LeadVisitBdm::with(['lead', 'account.district', 'fabricator.city', 'user', 'bdm', 'bdo'])
            ->whereBetween('visit_date', [$fromDate, $toDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return DataTables::of($query)
            ->addColumn('target_name', function($row) {
                if ($row->lead_id) return $row->lead->name ?? '-';
                if ($row->account_id) return $row->account->name ?? '-';
                if ($row->fabricator_id) return $row->fabricator->shop_name ?? '-';
                return '-';
            })
            ->addColumn('visit_type_label', function($row) {
                return [1 => 'Account', 2 => 'Leads', 3 => 'Fabricator'][$row->visit_type] ?? '-';
            })
            ->addColumn('food_label', function($row) {
                return [1 => 'Local Station', 2 => 'Outstation'][$row->food_allowance] ?? '-';
            })
            ->addColumn('work_type_label', function($row) {
                $label = $row->work_type;
                if ($row->work_type == 'Joint Work') {
                    $names = [];
                    if ($row->bdm) $names[] = $row->bdm->name . ' (BDM)';
                    if ($row->bdo) $names[] = $row->bdo->name . ' (BDO)';
                    if (!empty($names)) $label .= ' (' . implode(', ', $names) . ')';
                }
                return $label;
            })
            ->editColumn('visit_date', function($row) {
                return Carbon::parse($row->visit_date)->format('Y-m-d');
            })
            ->editColumn('intime_time', function($row) {
                return $row->intime_time ? Carbon::parse($row->intime_time)->format('h:i A') : '-';
            })
            ->editColumn('out_time', function($row) {
                return $row->out_time ? Carbon::parse($row->out_time)->format('h:i A') : '-';
            })
            ->make(true);
    }
}
