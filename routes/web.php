<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // POS
    Route::get('/sales/pos', [SaleController::class, 'pos'])
        ->middleware('permission:sales.view')
        ->name('sales.pos');
    Route::post('/sales', [SaleController::class, 'store'])
        ->middleware('permission:sales.create')
        ->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])
        ->middleware('permission:sales.history')
        ->name('sales.history');
    Route::post('/sales/currency-rates', [SaleController::class, 'saveCurrencyRates'])
        ->middleware('permission:sales.view')
        ->name('sales.currency-rates.save');
    Route::get('/sales/currency-rates', [SaleController::class, 'getCurrencyRates'])
        ->middleware('permission:sales.view')
        ->name('sales.currency-rates.get');

    // Products
    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:products.view')
        ->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products.edit')
        ->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.delete')
        ->name('products.destroy');
    Route::get('/api/products/search', [ProductController::class, 'search'])
        ->middleware('permission:sales.view')
        ->name('products.search');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit')
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

    // Companies (super admin only)
    Route::get('/companies', [CompanyController::class, 'index'])
        ->middleware('permission:companies.view')
        ->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])
        ->middleware('permission:companies.create')
        ->name('companies.store');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])
        ->middleware('permission:companies.edit')
        ->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])
        ->middleware('permission:companies.delete')
        ->name('companies.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')
        ->name('reports.index');
});
