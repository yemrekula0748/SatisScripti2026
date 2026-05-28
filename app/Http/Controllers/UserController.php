<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function companyId()
    {
        return Auth::user()->company_id;
    }

    public function index()
    {
        $users = User::where('company_id', $this->companyId())
            ->with('roles', 'permissions')
            ->get();

        $permissions = Permission::where('name', 'not like', 'companies.%')->get();
        $roles = Role::where('name', '!=', 'super-admin')->get();

        return view('users.index', compact('users', 'permissions', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
        ]);

        $user = User::create([
            'company_id' => $this->companyId(),
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
        abort_if($user->company_id !== $this->companyId(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
        ];

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
        abort_if($user->company_id !== $this->companyId(), 403);
        abort_if($user->id === Auth::id(), 403, 'Kendinizi silemezsiniz.');
        $user->delete();
        return back()->with('success', 'Kullanıcı silindi.');
    }
}
