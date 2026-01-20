<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRoleController extends Controller
{
    public function index()
    {
        // Load roles with their assigned permissions
        $roles = Role::with('permissions')->get();
        
        // Fetch all permissions from the database
        $permissions = Permission::all();
        
        // Group permissions by module (e.g., 'user', 'roles', 'executives')
        $groupedPermissions = $permissions->groupBy(function($item) {
            return explode('.', $item->name)[0];
        });

        return view('userroles.index', compact('roles', 'permissions', 'groupedPermissions'));
    }

    public function updatePermissions(Request $request)
    {
        // Find the specific role
        $role = Role::findById($request->role_id);
        
        // SyncPermissions automatically handles adding and removing links in role_has_permissions
        $role->syncPermissions($request->permissions ?? []);

        return response()->json(['success' => 'Permissions updated successfully!']);
    }
}