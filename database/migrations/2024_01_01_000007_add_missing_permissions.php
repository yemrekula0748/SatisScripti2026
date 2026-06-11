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

        $perm = Permission::firstOrCreate(['name' => 'sales.history', 'guard_name' => 'web']);

        foreach (['super-admin', 'company-admin', 'cashier'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            if ($role && ! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (! Schema::hasTable($tableNames['permissions'] ?? 'permissions')) {
            return;
        }

        Permission::where('name', 'sales.history')->delete();
    }
};
