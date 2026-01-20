<?php

namespace App\Http\Controllers;

use App\Models\{User, Brand, Zone, State, District, City, Area, Pincodes};
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $brands = Brand::where('action', '0')->orderBy('name')->get();
        $zones = Zone::where('action', '0')->orderBy('name')->get();
        return view('users.index', compact('roles', 'brands', 'zones'));
    }

    public function getGeoData(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'states':    return State::where('zone_id', $id)->get();
            case 'districts': return District::where('state_id', $id)->get();
            case 'cities':    return City::where('district_id', $id)->get();
            case 'areas':     return Area::where('city_id', $id)->get();
            case 'pincodes':  
                // Handle multiple Area IDs passed from checkboxes
                $ids = is_array($id) ? $id : explode(',', $id);
                return Pincodes::whereIn('area_id', array_filter($ids))->get();
            default: return response()->json([]);
        }
    }

    public function getData()
    {
        // Only show users with action '0' (active)
        $data = User::with(['roles'])->where('action', '0')->select('users.*');

        return DataTables::of($data)
            ->addColumn('role_name', fn($user) => $user->roles->pluck('name')->first() ?: 'User')
            ->addColumn('brand_names', function($user) {
                $ids = $user->brand_id;
                if (!empty($ids) && is_array($ids)) {
                    return Brand::whereIn('id', $ids)->pluck('name')->implode(', ');
                }
                return '-';
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'emp_code' => 'required|unique:users,emp_code',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['action'] = '0'; 
        
        $user = User::create($data);
        $user->assignRole($request->role);
        return response()->json(['success' => true]);
    }

    // public function edit(User $user)
    // {
    //     return response()->json([
    //         'user' => $user,
    //         'role' => $user->roles->pluck('name')->first(),
    //         'brand_ids' => $user->brand_id ?? [],
    //         'area_ids' => $user->area_id ?? [],
    //         'pincode_ids' => $user->pincode_id ?? []
    //     ]);
    // }

    public function edit(User $user)
    {
        return response()->json([
            'user' => $user,
            'role' => $user->roles->pluck('name')->first(),
            // Ensure these are arrays of strings for easier JS matching
            'brand_ids' => array_map('strval', $user->brand_id ?? []),
            'area_ids' => array_map('strval', $user->area_id ?? []),
            'pincode_ids' => array_map('strval', $user->pincode_id ?? [])
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->except(['password', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        $user->syncRoles($request->role);
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        // Logical delete: set action to '1'
        User::whereIn('id', $request->ids)->update(['action' => '1']);
        return response()->json(['success' => true]);
    }
}