<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $isSuperAdmin = $user->is_super_admin;
        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $last7DaysStart = now()->subDays(6)->startOfDay();
        $previous7DaysStart = now()->subDays(13)->startOfDay();
        $previous7DaysEnd = now()->subDays(7)->endOfDay();

        $salesQuery = Sale::query()
            ->when(!$isSuperAdmin, fn($q) => $q->where('company_id', $companyId));

        $todaySales = (clone $salesQuery)
            ->whereDate('created_at', $today)
            ->count();

        $todayRevenue = (clone $salesQuery)
            ->whereDate('created_at', $today)
            ->sum('total');

        $productCount = Product::query()
            ->when(!$isSuperAdmin, fn($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->count();

        $customerCount = Customer::query()
            ->when(!$isSuperAdmin, fn($q) => $q->where('company_id', $companyId))
            ->count();

        $monthRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('total');

        $monthSalesCount = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $averageBasket = $todaySales > 0 ? $todayRevenue / $todaySales : 0;

        $last7DaysRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$last7DaysStart, now()->endOfDay()])
            ->sum('total');

        $previous7DaysRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$previous7DaysStart, $previous7DaysEnd])
            ->sum('total');

        $weeklyTrend = $previous7DaysRevenue > 0
            ? (($last7DaysRevenue - $previous7DaysRevenue) / $previous7DaysRevenue) * 100
            : null;

        $monthDiscountTotal = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('discount_amount');

        $monthCustomerReach = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->distinct()
            ->count('customer_name');

        $monthItemsSold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(!$isSuperAdmin, fn($q) => $q->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->sum('sale_items.quantity');

        $topProducts = SaleItem::query()
            ->select('sale_items.product_name')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_revenue')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(!$isSuperAdmin, fn($q) => $q->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($monthItemsSold) {
                $item->total_quantity = (float) $item->total_quantity;
                $item->total_revenue = (float) $item->total_revenue;
                $item->share = $monthItemsSold > 0
                    ? ($item->total_quantity / (float) $monthItemsSold) * 100
                    : 0;

                return $item;
            });

        $topCashiers = Sale::query()
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(sales.id) as sale_count')
            ->selectRaw('SUM(sales.total) as total_revenue')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->when(!$isSuperAdmin, fn($q) => $q->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->limit(3)
            ->get()
            ->map(function ($cashier) {
                $cashier->sale_count = (int) $cashier->sale_count;
                $cashier->total_revenue = (float) $cashier->total_revenue;

                return $cashier;
            });

        $paymentBreakdown = SalePayment::query()
            ->select('sale_payments.payment_type')
            ->selectRaw('SUM(sale_payments.amount_in_tl) as total_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->when(!$isSuperAdmin, fn($q) => $q->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_payments.payment_type')
            ->orderByDesc('total_amount')
            ->get();

        $monthPaymentVolume = $paymentBreakdown->sum(fn($payment) => (float) $payment->total_amount);

        $paymentBreakdown = $paymentBreakdown->map(function ($payment) use ($monthPaymentVolume) {
                $payment->total_amount = (float) $payment->total_amount;
                $payment->payment_count = (int) $payment->payment_count;
                $payment->label = SalePayment::paymentLabel($payment->payment_type);
                $payment->share = $monthPaymentVolume > 0
                    ? ($payment->total_amount / $monthPaymentVolume) * 100
                    : 0;

                return $payment;
            });

        return view('dashboard', compact(
            'todaySales',
            'todayRevenue',
            'productCount',
            'customerCount',
            'monthRevenue',
            'monthSalesCount',
            'averageBasket',
            'last7DaysRevenue',
            'previous7DaysRevenue',
            'weeklyTrend',
            'monthDiscountTotal',
            'monthCustomerReach',
            'topProducts',
            'topCashiers',
            'paymentBreakdown'
        ));
    }
}
