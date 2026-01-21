<?php

namespace App\Http\Controllers;

use App\Models\Fabricator;
use App\Models\FabricatorProjection;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class FabricatorProjectionController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();
        $zones = \App\Models\Zone::orderBy('name')->where('action', '0')
            ->when($authUser->zone_id, function($q) use ($authUser) {
                return $q->where('id', $authUser->zone_id);
            })->get();
            
        $fabricators = Fabricator::where('action', '0')->orderBy('shop_name')->get();
        // Fallback for users if needed, though we will populate via JS
        $users = User::role(['BDM', 'BDO', 'ZSM'])->orderBy('name')->get();
        
        return view('fabricator_projections.index', compact('fabricators', 'users', 'zones'));
    }

    public function getData(Request $request)
    {
        $query = FabricatorProjection::with(['fabricator', 'user'])
            ->select('fabricator_projections.*')
            ->join('users', 'fabricator_projections.user_id', '=', 'users.id');

        // Hierarchical Filtering
        if ($request->filled('zone_id')) {
            $query->where('users.zone_id', $request->zone_id);
        }
        
        if ($request->filled('zsm_id')) {
            $zsmId = $request->zsm_id;
            $mappingIds = \App\Models\UserMapping::where('zsm_id', $zsmId)->get(['bdm_id', 'bdo_id']);
            $subIds = $mappingIds->pluck('bdm_id')->merge($mappingIds->pluck('bdo_id'))->unique()->filter()->toArray();
            $query->whereIn('fabricator_projections.user_id', array_merge([$zsmId], $subIds));
        }
        
        if ($request->filled('manager_id')) {
            $managerId = $request->manager_id;
            $subIds = \App\Models\UserMapping::where('bdm_id', $managerId)->pluck('bdo_id')->unique()->filter()->toArray();
            $query->whereIn('fabricator_projections.user_id', array_merge([$managerId], $subIds));
        }

        if ($request->filled('fabricator_id')) {
            $query->where('fabricator_id', $request->fabricator_id);
        }
        
        if ($request->filled('user_id')) {
            $query->where('fabricator_projections.user_id', $request->user_id);
        }
        
        if ($request->filled('month')) {
            $query->where('projection_month', $request->month);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('fabricator_name', function($row) {
                return $row->fabricator->shop_name ?? 'N/A';
            })
            ->addColumn('user_name', function($row) {
                return $row->user->name ?? 'N/A';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="flex gap-2">';
                $btn .= '<button onclick="editProjection('.$row->id.')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>';
                $btn .= '<button onclick="deleteProjection('.$row->id.')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fabricator_id' => 'required|exists:fabricators,id',
            'projection_month' => 'required',
            'sale_projection_tonnage' => 'required|numeric',
            'fabricator_collection' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::id();

        FabricatorProjection::create($data);

        return response()->json(['success' => 'Projection saved successfully.']);
    }

    public function edit($id)
    {
        $projection = FabricatorProjection::findOrFail($id);
        return response()->json($projection);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fabricator_id' => 'required|exists:fabricators,id',
            'projection_month' => 'required',
            'sale_projection_tonnage' => 'required|numeric',
            'fabricator_collection' => 'required|numeric',
        ]);

        $projection = FabricatorProjection::findOrFail($id);
        $data = $request->all();
        // Keep original user_id or allow update if admin? User asked for session id.
        $data['user_id'] = Auth::id(); 
        
        $projection->update($data);

        return response()->json(['success' => 'Projection updated successfully.']);
    }

    public function destroy($id)
    {
        $projection = FabricatorProjection::findOrFail($id);
        $projection->delete();
        return response()->json(['success' => 'Projection deleted successfully.']);
    }
}
