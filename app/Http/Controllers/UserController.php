<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $query = User::with('roles', 'permissions', 'company')
            ->where('is_super_admin', false);

        if (!$this->isSuperAdmin()) {
            $query->where('company_id', $this->companyId());
        }

        $users = $query->get();
        $permissions = Permission::where('name', 'not like', 'companies.%')->get();
        $roles = Role::where('name', '!=', 'super-admin')->get();
        $companies = $this->isSuperAdmin() ? Company::where('is_active', true)->orderBy('name')->get() : collect();

        return view('users.index', compact('users', 'permissions', 'roles', 'companies'));
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

        $user = User::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        if (!empty($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

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

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
        ];

        if ($this->isSuperAdmin()) {
            $updateData['company_id'] = $data['company_id'];
        }

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($user->company_id !== $this->companyId(), 403);
        }
        abort_if($user->id === Auth::id(), 403, 'Kendinizi silemezsiniz.');
        $user->delete();
        return back()->with('success', 'Kullanıcı silindi.');
    }
}
