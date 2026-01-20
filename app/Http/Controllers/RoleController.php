<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('roles.view');

        if ($request->ajax()) {
            // Using Role model from Spatie
            $data = Role::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('roles.index');
    }

    public function getData(Request $request)
    {
    $this->authorize('roles.view');
    
    $data = Role::query();
    return DataTables::of($data)
        ->addIndexColumn()
        ->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('roles.create');

        // Validation returns 422 automatically for AJAX
        $request->validate([
            'name' => 'required|unique:roles,name'
        ]);

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return response()->json(['message' => 'Role created successfully.']);
    }

    public function edit($id)
    {
        $this->authorize('roles.edit');
        $role = Role::findOrFail($id);
        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('roles.edit');
        
        $role = Role::findOrFail($id);
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id
        ]);

        $role->update(['name' => $request->name]);

        return response()->json(['message' => 'Role updated successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('roles.delete');
        
        if ($request->has('ids') && !empty($request->ids)) {
            Role::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => 'Roles deleted successfully.']);
        }

        return response()->json(['message' => 'No roles selected.'], 400);
    }
}