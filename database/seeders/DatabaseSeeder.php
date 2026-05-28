<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'sales.view', 'sales.create',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'reports.view',
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $companyAdminRole = Role::firstOrCreate(['name' => 'company-admin']);
        $companyAdminRole->syncPermissions(array_values(array_filter($permissions, fn($p) => !str_starts_with($p, 'companies.'))));

        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierRole->syncPermissions(['dashboard.view', 'sales.view', 'sales.create', 'products.view', 'customers.view']);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'Süper Admin',
                'password' => bcrypt('password'),
                'is_super_admin' => true,
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super-admin');

        $company = Company::firstOrCreate(
            ['name' => 'Demo Şirket'],
            [
                'address' => 'İstanbul, Türkiye',
                'phone' => '0212 000 00 00',
                'email' => 'info@demo.com',
                'tax_number' => '1234567890',
                'is_active' => true,
            ]
        );

        $companyAdmin = User::firstOrCreate(
            ['email' => 'company@pos.com'],
            [
                'company_id' => $company->id,
                'name' => 'Şirket Yöneticisi',
                'password' => bcrypt('password'),
                'is_super_admin' => false,
                'is_active' => true,
            ]
        );
        $companyAdmin->assignRole('company-admin');

        $cashier = User::firstOrCreate(
            ['email' => 'kasiyer@pos.com'],
            [
                'company_id' => $company->id,
                'name' => 'Demo Kasiyer',
                'password' => bcrypt('password'),
                'is_super_admin' => false,
                'is_active' => true,
            ]
        );
        $cashier->assignRole('cashier');

        $products = [
            ['barcode' => '8690000001', 'name' => '1THALIA PERA DAY FOR WOMAN SOAP', 'sale_price' => 108.00, 'stock' => 50, 'unit' => 'adet'],
            ['barcode' => '8690000002', 'name' => '24K GOLD FOIL MASK MOYAM', 'sale_price' => 216.00, 'stock' => 30, 'unit' => 'adet'],
            ['barcode' => '8690000003', 'name' => 'AFTICYL SPREY', 'sale_price' => 540.00, 'stock' => 25, 'unit' => 'adet'],
            ['barcode' => '8690000004', 'name' => 'TEST ÜRÜN A', 'sale_price' => 75.50, 'stock' => 100, 'unit' => 'adet'],
            ['barcode' => '8690000005', 'name' => 'TEST ÜRÜN B', 'sale_price' => 199.90, 'stock' => 60, 'unit' => 'kg'],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['company_id' => $company->id, 'barcode' => $product['barcode']],
                array_merge($product, ['company_id' => $company->id])
            );
        }

        $customers = [
            ['name' => 'Ahmet Yılmaz', 'phone' => '0532 111 22 33', 'email' => 'ahmet@test.com'],
            ['name' => 'Ayşe Kaya', 'phone' => '0533 444 55 66', 'email' => 'ayse@test.com'],
            ['name' => 'Mehmet Demir', 'phone' => '0535 777 88 99', 'email' => null],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                ['company_id' => $company->id, 'name' => $customer['name']],
                array_merge($customer, ['company_id' => $company->id])
            );
        }

        foreach (['EUR' => 38.50, 'USD' => 35.20, 'GBP' => 44.80, 'RUB' => 0.39] as $currency => $rate) {
            CurrencyRate::firstOrCreate(
                ['company_id' => $company->id, 'currency' => $currency],
                ['rate' => $rate]
            );
        }
    }
}
