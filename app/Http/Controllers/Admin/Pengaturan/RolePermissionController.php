<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions')->withCount('users')->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $limit = $request->input('limit', 5);
        $roles = $query->paginate($limit)->withQueryString();

        // Get all permissions and group them by module
        $allPermissions = Permission::orderBy('name')->get();

        $groupedPermissions = [];
        foreach ($allPermissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = count($parts) > 1 ? $parts[0] : 'general';

            if (! isset($groupedPermissions[$module])) {
                $groupedPermissions[$module] = [];
            }

            $groupedPermissions[$module][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => count($parts) > 1 ? $parts[1] : $permission->name,
            ];
        }

        return Inertia::render('Admin/Pengaturan/RolePermission/Index', [
            'roles' => $roles,
            'filters' => $request->only(['search']),
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            if ($request->filled('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            activity()
                ->useLog('Role')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => ['name' => $role->name, 'permissions' => $request->permissions],
                ])
                ->log('Menambahkan role baru: '.$role->name);
        });

        return back()->with('success', 'Role berhasil ditambahkan.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $oldData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        DB::transaction(function () use ($request, $role, $oldData) {
            $role->update(['name' => $request->name]);

            $permissions = $request->input('permissions', []);
            $role->syncPermissions($permissions);

            activity()
                ->useLog('Role')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => ['name' => $role->name, 'permissions' => $permissions],
                ])
                ->log('Mengubah role: '.$role->name);
        });

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroyRole(Role $role, Request $request)
    {
        if (strtolower($role->name) === 'super admin' || strtolower($role->name) === 'pendaftar') {
            return back()->with('error', 'Role Super Admin dan Pendaftar tidak boleh dihapus.');
        }

        $oldData = [
            'id' => $role->id,
            'name' => $role->name,
        ];

        DB::transaction(function () use ($role, $request, $oldData) {
            $role->delete();

            activity()
                ->useLog('Role')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                ])
                ->log('Menghapus role: '.$oldData['name']);
        });

        return back()->with('success', 'Role berhasil dihapus.');
    }
}
