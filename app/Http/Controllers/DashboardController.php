<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $todaySales = Sale::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->count();

        $todayRevenue = Sale::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->sum('total');

        $productCount = Product::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->count();

        $customerCount = Customer::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->count();

        $monthRevenue = Sale::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $recentSales = Sale::with(['user', 'customer', 'payments'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'todaySales', 'todayRevenue', 'productCount',
            'customerCount', 'monthRevenue', 'recentSales'
        ));
    }
}
