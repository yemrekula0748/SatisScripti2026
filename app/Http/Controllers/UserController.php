<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function isSuperAdmin(): bool
    {
        return Auth::user()->is_super_admin;
    }

    private function companyId(): ?int
    {
        return Auth::user()->company_id;
    }

    public function index()
    {
        $query = User::with('roles.permissions', 'permissions', 'company')
            ->where('is_super_admin', false);

        if (!$this->isSuperAdmin()) {
            $query->where('company_id', $this->companyId());
        }

        $users = $query->get();
        $permissions = Permission::where('name', 'not like', 'companies.%')->get();
        $roles = Role::with('permissions')->where('name', '!=', 'super-admin')->get();
        $companies = $this->isSuperAdmin() ? Company::where('is_active', true)->orderBy('name')->get() : collect();
        $rolePermissionMatrix = $roles->mapWithKeys(fn($role) => [$role->name => $role->permissions->pluck('name')->values()]);

        return view('users.index', compact('users', 'permissions', 'roles', 'companies', 'rolePermissionMatrix'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
        ];

        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $data = $request->validate($rules);

        $companyId = $this->isSuperAdmin() ? $data['company_id'] : $this->companyId();
        $editablePermissionNames = Permission::where('name', 'not like', 'companies.%')->pluck('name')->all();
        $role = Role::findByName($data['role'], 'web')->load('permissions');
        $selectedPermissions = collect($request->input('permissions', []))
            ->filter(fn($permission) => in_array($permission, $editablePermissionNames, true))
            ->values()
            ->all();

        if (empty($selectedPermissions)) {
            $selectedPermissions = $role->permissions
                ->pluck('name')
                ->filter(fn($permission) => in_array($permission, $editablePermissionNames, true))
                ->values()
                ->all();
        }

        $user = User::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_active' => true,
            'blocked_permissions' => array_values(array_diff($editablePermissionNames, $selectedPermissions)),
        ]);

        $user->assignRole($data['role']);
        $user->syncPermissions($selectedPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Kullanıcı eklendi.');
    }

    public function update(Request $request, User $user)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($user->company_id !== $this->companyId(), 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ];

        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $data = $request->validate($rules);
        $editablePermissionNames = Permission::where('name', 'not like', 'companies.%')->pluck('name')->all();
        $role = Role::findByName($data['role'], 'web')->load('permissions');
        $selectedPermissions = collect($request->input('permissions', []))
            ->filter(fn($permission) => in_array($permission, $editablePermissionNames, true))
            ->values()
            ->all();

        if (empty($selectedPermissions)) {
            $selectedPermissions = $role->permissions
                ->pluck('name')
                ->filter(fn($permission) => in_array($permission, $editablePermissionNames, true))
                ->values()
                ->all();
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
            'blocked_permissions' => array_values(array_diff($editablePermissionNames, $selectedPermissions)),
        ];

        if ($this->isSuperAdmin()) {
            $updateData['company_id'] = $data['company_id'];
        }

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$data['role']]);
        $user->syncPermissions($selectedPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($user->company_id !== $this->companyId(), 403);
        }
        abort_if($user->id === Auth::id(), 403, 'Kendinizi silemezsiniz.');
        $user->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', 'Kullanıcı silindi.');
    }
}
