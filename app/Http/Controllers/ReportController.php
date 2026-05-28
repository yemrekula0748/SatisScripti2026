<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Carbon\Carbon;
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

        $baseQuery = fn() => Sale::where('company_id', $companyId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $totalSales = $baseQuery()->count();
        $totalRevenue = $baseQuery()->sum('total');
        $totalDiscount = $baseQuery()->sum('discount_amount');

        $paymentBreakdown = SalePayment::whereHas('sale', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->select('payment_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(amount_in_tl) as total_tl'))
            ->groupBy('payment_type')
            ->get();

        $topProducts = SaleItem::whereHas('sale', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->groupBy('product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $chartData = $baseQuery()
            ->select(DB::raw("date(created_at) as date"), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $hourlyData = Sale::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->select(DB::raw("strftime('%H', created_at) as hour"), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return view('reports.index', compact(
            'totalSales', 'totalRevenue', 'totalDiscount',
            'paymentBreakdown', 'topProducts', 'chartData', 'hourlyData',
            'period', 'from', 'to'
        ));
    }
}
