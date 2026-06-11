<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
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

        $cashier = Role::findByName('cashier', 'web');
        $cashier?->revokePermissionTo('sales.history');

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

        $cashier = Role::findByName('cashier', 'web');
        $cashier?->givePermissionTo('sales.history');
    }
};
