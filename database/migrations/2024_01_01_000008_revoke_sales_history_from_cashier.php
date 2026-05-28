<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $cashier = Role::findByName('cashier', 'web');
        $cashier?->revokePermissionTo('sales.history');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $cashier = Role::findByName('cashier', 'web');
        $cashier?->givePermissionTo('sales.history');
    }
};
