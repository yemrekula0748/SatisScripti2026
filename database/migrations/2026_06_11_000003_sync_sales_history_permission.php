<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (
            ! Schema::hasTable($tableNames['permissions'] ?? 'permissions')
            || ! Schema::hasTable($tableNames['roles'] ?? 'roles')
        ) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'sales.history',
            'guard_name' => 'web',
        ]);

        foreach (['super-admin', 'company-admin'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $cashier = Role::query()
            ->where('name', 'cashier')
            ->where('guard_name', 'web')
            ->first();

        if ($cashier && $cashier->hasPermissionTo($permission)) {
            $cashier->revokePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (
            ! Schema::hasTable($tableNames['permissions'] ?? 'permissions')
            || ! Schema::hasTable($tableNames['roles'] ?? 'roles')
        ) {
            return;
        }

        $permission = Permission::query()
            ->where('name', 'sales.history')
            ->where('guard_name', 'web')
            ->first();

        $cashier = Role::query()
            ->where('name', 'cashier')
            ->where('guard_name', 'web')
            ->first();

        if ($permission && $cashier && ! $cashier->hasPermissionTo($permission)) {
            $cashier->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
