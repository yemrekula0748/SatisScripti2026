<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $isSuperAdmin = $user->is_super_admin;
        $canViewDashboardMetrics = $user->can('dashboard.view');

        if (! $canViewDashboardMetrics) {
            return view('dashboard', [
                'canViewDashboardMetrics' => false,
                'todaySales' => 0,
                'todayRevenue' => 0,
                'todayReturnTotal' => 0,
                'productCount' => 0,
                'customerCount' => 0,
                'monthRevenue' => 0,
                'monthReturnTotal' => 0,
                'monthSalesCount' => 0,
                'averageBasket' => 0,
                'last7DaysRevenue' => 0,
                'previous7DaysRevenue' => 0,
                'weeklyTrend' => null,
                'monthDiscountTotal' => 0,
                'monthCustomerReach' => 0,
                'topProducts' => collect(),
                'topCashiers' => collect(),
                'paymentBreakdown' => collect(),
            ]);
        }

        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $last7DaysStart = now()->subDays(6)->startOfDay();
        $previous7DaysStart = now()->subDays(13)->startOfDay();
        $previous7DaysEnd = now()->subDays(7)->endOfDay();

        $salesQuery = Sale::query()
            ->when(! $isSuperAdmin, fn ($query) => $query->where('company_id', $companyId));

        $returnsQuery = SaleReturn::query()
            ->when(! $isSuperAdmin, fn ($query) => $query->where('company_id', $companyId));

        $todaySales = (clone $salesQuery)
            ->whereDate('created_at', $today)
            ->count();

        $todayGrossRevenue = (clone $salesQuery)
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayReturnTotal = (clone $returnsQuery)
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayRevenue = $todayGrossRevenue - $todayReturnTotal;

        $productCount = Product::query()
            ->when(! $isSuperAdmin, fn ($query) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->count();

        $customerCount = Customer::query()
            ->when(! $isSuperAdmin, fn ($query) => $query->where('company_id', $companyId))
            ->count();

        $monthGrossRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('total');

        $monthReturnTotal = (clone $returnsQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('total');

        $monthRevenue = $monthGrossRevenue - $monthReturnTotal;

        $monthSalesCount = (clone $salesQuery)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $averageBasket = $todaySales > 0 ? $todayRevenue / $todaySales : 0;

        $last7DaysGrossRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$last7DaysStart, now()->endOfDay()])
            ->sum('total');

        $last7DaysReturnTotal = (clone $returnsQuery)
            ->whereBetween('created_at', [$last7DaysStart, now()->endOfDay()])
            ->sum('total');

        $last7DaysRevenue = $last7DaysGrossRevenue - $last7DaysReturnTotal;

        $previous7DaysGrossRevenue = (clone $salesQuery)
            ->whereBetween('created_at', [$previous7DaysStart, $previous7DaysEnd])
            ->sum('total');

        $previous7DaysReturnTotal = (clone $returnsQuery)
            ->whereBetween('created_at', [$previous7DaysStart, $previous7DaysEnd])
            ->sum('total');

        $previous7DaysRevenue = $previous7DaysGrossRevenue - $previous7DaysReturnTotal;

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

        $monthItemsSold = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->sum('sale_items.quantity');

        $monthReturnedItems = (float) SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sale_returns.company_id', $companyId))
            ->whereBetween('sale_returns.created_at', [$monthStart, $monthEnd])
            ->sum('sale_return_items.quantity');

        $monthNetItemsSold = max($monthItemsSold - $monthReturnedItems, 0);

        $soldProducts = SaleItem::query()
            ->select('sale_items.product_name')
            ->selectRaw('SUM(sale_items.quantity) as sold_quantity')
            ->selectRaw('SUM(sale_items.total) as sold_revenue')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_items.product_name')
            ->get()
            ->keyBy('product_name');

        $returnedProducts = SaleReturnItem::query()
            ->select('sale_return_items.product_name')
            ->selectRaw('SUM(sale_return_items.quantity) as returned_quantity')
            ->selectRaw('SUM(sale_return_items.total) as returned_revenue')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sale_returns.company_id', $companyId))
            ->whereBetween('sale_returns.created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_return_items.product_name')
            ->get()
            ->keyBy('product_name');

        $topProducts = $soldProducts->keys()
            ->merge($returnedProducts->keys())
            ->unique()
            ->map(function ($productName) use ($soldProducts, $returnedProducts, $monthNetItemsSold) {
                $sold = $soldProducts->get($productName);
                $returned = $returnedProducts->get($productName);

                $soldQuantity = (float) ($sold->sold_quantity ?? 0);
                $returnedQuantity = (float) ($returned->returned_quantity ?? 0);
                $soldRevenue = (float) ($sold->sold_revenue ?? 0);
                $returnedRevenue = (float) ($returned->returned_revenue ?? 0);
                $netQuantity = $soldQuantity - $returnedQuantity;
                $netRevenue = $soldRevenue - $returnedRevenue;

                return (object) [
                    'product_name' => $productName,
                    'total_quantity' => $netQuantity,
                    'total_revenue' => $netRevenue,
                    'returned_quantity' => $returnedQuantity,
                    'returned_revenue' => $returnedRevenue,
                    'share' => $monthNetItemsSold > 0
                        ? ($netQuantity / $monthNetItemsSold) * 100
                        : 0,
                ];
            })
            ->filter(fn ($item) => $item->total_quantity > 0.0001 || $item->total_revenue > 0.009)
            ->sortByDesc('total_quantity')
            ->take(5)
            ->values();

        $grossCashiers = Sale::query()
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(sales.id) as sale_count')
            ->selectRaw('SUM(sales.total) as gross_revenue')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');

        $returnedCashiers = SaleReturn::query()
            ->select('users.id', 'users.name')
            ->selectRaw('SUM(sale_returns.total) as return_total')
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sale_returns.company_id', $companyId))
            ->whereBetween('sale_returns.created_at', [$monthStart, $monthEnd])
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');

        $topCashiers = $grossCashiers->keys()
            ->merge($returnedCashiers->keys())
            ->unique()
            ->map(function ($cashierId) use ($grossCashiers, $returnedCashiers) {
                $gross = $grossCashiers->get($cashierId);
                $returned = $returnedCashiers->get($cashierId);

                $grossRevenue = (float) ($gross->gross_revenue ?? 0);
                $returnTotal = (float) ($returned->return_total ?? 0);

                return (object) [
                    'id' => (int) $cashierId,
                    'name' => $gross->name ?? $returned->name ?? 'Bilinmiyor',
                    'sale_count' => (int) ($gross->sale_count ?? 0),
                    'total_revenue' => $grossRevenue - $returnTotal,
                    'return_total' => $returnTotal,
                ];
            })
            ->filter(fn ($cashier) => $cashier->sale_count > 0 || $cashier->return_total > 0.009)
            ->sortByDesc('total_revenue')
            ->take(3)
            ->values();

        $paymentBreakdown = $this->buildPaymentBreakdown($isSuperAdmin, $companyId, $monthStart, $monthEnd);

        return view('dashboard', compact(
            'canViewDashboardMetrics',
            'todaySales',
            'todayRevenue',
            'todayReturnTotal',
            'productCount',
            'customerCount',
            'monthRevenue',
            'monthReturnTotal',
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

    private function buildPaymentBreakdown(bool $isSuperAdmin, ?int $companyId, $monthStart, $monthEnd): Collection
    {
        $grossPayments = SalePayment::query()
            ->select('sale_payments.payment_type')
            ->selectRaw('SUM(sale_payments.amount_in_tl) as gross_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('sales.company_id', $companyId))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_payments.payment_type')
            ->get()
            ->keyBy('payment_type');

        $returnAllocations = SaleReturn::query()
            ->with([
                'sale.payments' => fn ($query) => $query->select('id', 'sale_id', 'payment_type', 'amount_in_tl'),
            ])
            ->when(! $isSuperAdmin, fn ($query) => $query->where('company_id', $companyId))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get()
            ->reduce(function (array $carry, SaleReturn $saleReturn) {
                $payments = $saleReturn->sale?->payments ?? collect();
                $paymentBase = (float) $payments->sum(fn ($payment) => (float) $payment->amount_in_tl);

                if ($paymentBase <= 0) {
                    return $carry;
                }

                foreach ($payments as $payment) {
                    $paymentType = $payment->payment_type;
                    $paymentAmount = (float) $payment->amount_in_tl;
                    $allocatedReturn = ((float) $saleReturn->total * $paymentAmount) / $paymentBase;

                    $carry[$paymentType] = ($carry[$paymentType] ?? 0) + $allocatedReturn;
                }

                return $carry;
            }, []);

        $paymentBreakdown = $grossPayments->keys()
            ->merge(array_keys($returnAllocations))
            ->unique()
            ->map(function ($paymentType) use ($grossPayments, $returnAllocations) {
                $grossPayment = $grossPayments->get($paymentType);
                $grossAmount = (float) ($grossPayment->gross_amount ?? 0);
                $returnTotal = (float) ($returnAllocations[$paymentType] ?? 0);

                return (object) [
                    'payment_type' => $paymentType,
                    'label' => SalePayment::paymentLabel($paymentType),
                    'payment_count' => (int) ($grossPayment->payment_count ?? 0),
                    'gross_amount' => $grossAmount,
                    'return_total' => $returnTotal,
                    'total_amount' => $grossAmount - $returnTotal,
                ];
            })
            ->filter(fn ($payment) => abs($payment->gross_amount) > 0.009 || abs($payment->return_total) > 0.009 || $payment->payment_count > 0)
            ->sortByDesc('total_amount')
            ->values();

        $monthPaymentVolume = $paymentBreakdown->sum(fn ($payment) => max($payment->total_amount, 0));

        return $paymentBreakdown->map(function ($payment) use ($monthPaymentVolume) {
            $payment->share = $monthPaymentVolume > 0 && $payment->total_amount > 0
                ? ($payment->total_amount / $monthPaymentVolume) * 100
                : 0;

            return $payment;
        });
    }
}
