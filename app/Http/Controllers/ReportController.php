<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function companyId()
    {
        return Auth::user()->company_id;
    }

    private function getDateRange(string $period, $startDate, $endDate): array
    {
        return match($period) {
            'daily' => [Carbon::today(), Carbon::today()],
            'weekly' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'monthly' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'custom' => [
                Carbon::parse($startDate ?? today()),
                Carbon::parse($endDate ?? today()),
            ],
            default => [Carbon::today(), Carbon::today()],
        };
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$from, $to] = $this->getDateRange($period, $startDate, $endDate);

        $companyId = $this->companyId();
        $periodStart = $from->copy()->startOfDay();
        $periodEnd = $to->copy()->endOfDay();

        $baseQuery = fn() => Sale::where('company_id', $companyId)
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $returnsQuery = fn() => SaleReturn::where('company_id', $companyId)
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $totalSales = $baseQuery()->count();
        $totalGrossRevenue = (float) $baseQuery()->sum('total');
        $totalReturns = (float) $returnsQuery()->sum('total');
        $totalRevenue = $totalGrossRevenue - $totalReturns;
        $totalDiscount = $baseQuery()->sum('discount_amount');

        $paymentBreakdown = SalePayment::whereHas('sale', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$periodStart, $periodEnd]))
            ->select('payment_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(amount_in_tl) as total_tl'))
            ->groupBy('payment_type')
            ->get();

        $soldProducts = SaleItem::whereHas('sale', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$periodStart, $periodEnd]))
            ->select('product_name', DB::raw('SUM(quantity) as sold_qty'), DB::raw('SUM(total) as sold_revenue'))
            ->groupBy('product_name')
            ->get()
            ->keyBy('product_name');

        $returnedProducts = SaleReturnItem::whereHas('saleReturn', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$periodStart, $periodEnd]))
            ->select('product_name', DB::raw('SUM(quantity) as returned_qty'), DB::raw('SUM(total) as returned_revenue'))
            ->groupBy('product_name')
            ->get();

        $topProducts = $soldProducts->keys()
            ->merge($returnedProducts->keys())
            ->unique()
            ->map(function ($productName) use ($soldProducts, $returnedProducts) {
                $sold = $soldProducts->get($productName);
                $returned = $returnedProducts->get($productName);

                $soldQty = (float) ($sold->sold_qty ?? 0);
                $returnedQty = (float) ($returned->returned_qty ?? 0);
                $soldRevenue = (float) ($sold->sold_revenue ?? 0);
                $returnedRevenue = (float) ($returned->returned_revenue ?? 0);

                return (object) [
                    'product_name' => $productName,
                    'total_qty' => $soldQty - $returnedQty,
                    'total_revenue' => $soldRevenue - $returnedRevenue,
                    'returned_qty' => $returnedQty,
                    'returned_revenue' => $returnedRevenue,
                ];
            })
            ->filter(fn($product) => $product->total_qty > 0.0001 || $product->total_revenue > 0.009)
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        $salesByDate = $baseQuery()
            ->select(DB::raw("date(created_at) as date"), DB::raw('SUM(total) as gross_total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $returnsByDate = $returnsQuery()
            ->select(DB::raw("date(created_at) as date"), DB::raw('SUM(total) as return_total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartData = collect(CarbonPeriod::create($periodStart->toDateString(), $periodEnd->toDateString()))
            ->map(function ($date) use ($salesByDate, $returnsByDate) {
                $key = $date->format('Y-m-d');
                $sales = $salesByDate->get($key);
                $returns = $returnsByDate->get($key);

                $grossTotal = (float) ($sales->gross_total ?? 0);
                $returnTotal = (float) ($returns->return_total ?? 0);

                return (object) [
                    'date' => $key,
                    'gross_total' => $grossTotal,
                    'return_total' => $returnTotal,
                    'total' => $grossTotal - $returnTotal,
                    'count' => (int) ($sales->count ?? 0),
                ];
            })
            ->filter(fn($day) => $day->count > 0 || abs($day->total) > 0.009 || abs($day->return_total) > 0.009)
            ->values();

        $salesByHour = Sale::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->select(DB::raw("HOUR(created_at) as hour"), DB::raw('SUM(total) as gross_total'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $returnsByHour = SaleReturn::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->select(DB::raw("HOUR(created_at) as hour"), DB::raw('SUM(total) as return_total'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hourlyData = collect(range(0, 23))
            ->map(function ($hour) use ($salesByHour, $returnsByHour) {
                $sales = $salesByHour->get($hour);
                $returns = $returnsByHour->get($hour);

                $grossTotal = (float) ($sales->gross_total ?? 0);
                $returnTotal = (float) ($returns->return_total ?? 0);

                return (object) [
                    'hour' => $hour,
                    'gross_total' => $grossTotal,
                    'return_total' => $returnTotal,
                    'total' => $grossTotal - $returnTotal,
                    'count' => (int) ($sales->count ?? 0),
                ];
            })
            ->filter(fn($hour) => $hour->count > 0 || abs($hour->total) > 0.009 || abs($hour->return_total) > 0.009)
            ->values();

        return view('reports.index', compact(
            'totalSales', 'totalRevenue', 'totalDiscount', 'totalReturns',
            'paymentBreakdown', 'topProducts', 'chartData', 'hourlyData',
            'period', 'from', 'to'
        ));
    }
}
