@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $canViewDashboardMetrics = $canViewDashboardMetrics ?? true;
    $maskedValue = '**';
    $formatCurrency = fn ($value) => $canViewDashboardMetrics ? number_format((float) $value, 2, ',', '.') . ' ₺' : $maskedValue;
    $formatNumber = fn ($value, $decimals = 0) => $canViewDashboardMetrics ? number_format((float) $value, $decimals, ',', '.') : $maskedValue;
    $formatPercent = fn ($value, $decimals = 1) => $canViewDashboardMetrics ? '%' . number_format((float) $value, $decimals, ',', '.') : $maskedValue;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugunku Satis</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $formatNumber($todaySales) }}</p>
                <p class="text-xs text-slate-400 mt-2">Gun icinde tamamlanan fis adedi</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-indigo-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugunku Net Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $formatCurrency($todayRevenue) }}</p>
                <p class="text-xs text-slate-400 mt-2">Satislar eksi bugun yapilan iadeler</p>
                @if($canViewDashboardMetrics && $todayReturnTotal > 0)
                <p class="text-xs text-rose-500 mt-1">Bugunku iade: -{{ number_format($todayReturnTotal, 2, ',', '.') }} ₺</p>
                @elseif(!$canViewDashboardMetrics)
                <p class="text-xs text-slate-400 mt-1">Dashboard yetkisi kapali oldugu icin sayisal veriler maskeleniyor.</p>
                @endif
            </div>
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet text-emerald-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Ortalama Sepet</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $formatCurrency($averageBasket) }}</p>
                <p class="text-xs text-slate-400 mt-2">Satis basina ortalama net tutar</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-basket-shopping text-amber-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Aylik Net Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $formatCurrency($monthRevenue) }}</p>
                <p class="text-xs text-slate-400 mt-2">Bu ay satislar eksi iadeler</p>
                @if($canViewDashboardMetrics && $monthReturnTotal > 0)
                <p class="text-xs text-rose-500 mt-1">Aylik iade: -{{ number_format($monthReturnTotal, 2, ',', '.') }} ₺</p>
                @endif
            </div>
            <div class="w-12 h-12 bg-fuchsia-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-fuchsia-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800">Satis Nabzi</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ayki satis ritmini tek bakista takip edin.</p>
            </div>
            @can('sales.view')
            <a href="{{ route('sales.pos') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                <i class="fas fa-cash-register text-xs"></i>
                Yeni satis
            </a>
            @endcan
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Bu Ay</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $formatNumber($monthSalesCount) }}</p>
                <p class="text-sm text-slate-500 mt-1">toplam satis</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">7 Gun</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $formatCurrency($last7DaysRevenue) }}</p>
                <p class="text-sm text-slate-500 mt-1">son 7 gun net cirosu</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Musteri</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $formatNumber($monthCustomerReach) }}</p>
                <p class="text-sm text-slate-500 mt-1">bu ay ulasilan musteri</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Indirim</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $formatCurrency($monthDiscountTotal) }}</p>
                <p class="text-sm text-slate-500 mt-1">bu ay verilen indirim</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 text-white shadow-sm">
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-indigo-200/80">Haftalik Ivme</p>
                    <h2 class="text-xl font-semibold mt-2">Satis temposu nasil gidiyor?</h2>
                </div>
                <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                    <i class="fas fa-chart-column text-indigo-200"></i>
                </div>
            </div>

            <div class="mt-8">
                @if(!$canViewDashboardMetrics)
                    <p class="text-4xl font-bold">**</p>
                    <p class="text-sm text-slate-300 mt-3">Dashboard yetkisi kapali oldugu icin sayisal veriler maskeleniyor.</p>
                @elseif(!is_null($weeklyTrend))
                    @php
                        $isPositiveTrend = $weeklyTrend >= 0;
                    @endphp
                    <div class="flex items-end gap-3">
                        <p class="text-4xl font-bold">{{ $isPositiveTrend ? '+' : '' }}{{ number_format($weeklyTrend, 1, ',', '.') }}%</p>
                        <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium {{ $isPositiveTrend ? 'bg-emerald-400/15 text-emerald-200' : 'bg-rose-400/15 text-rose-200' }}">
                            <i class="fas {{ $isPositiveTrend ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            {{ $isPositiveTrend ? 'yukari yonlu' : 'dikkat gerekiyor' }}
                        </span>
                    </div>
                @else
                    <p class="text-4xl font-bold">Yeni</p>
                    <p class="text-sm text-slate-300 mt-3">Karsilastirma icin onceki hafta verisi olusmaya basladi.</p>
                @endif

                <p class="text-sm text-slate-300 mt-4">Son 7 gun: {{ $formatCurrency($last7DaysRevenue) }}</p>
                <p class="text-sm text-slate-400 mt-1">Onceki 7 gun: {{ $formatCurrency($previous7DaysRevenue) }}</p>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-xs text-slate-300">Aktif urun</p>
                    <p class="text-xl font-semibold mt-1">{{ $formatNumber($productCount) }}</p>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-xs text-slate-300">Kayitli musteri</p>
                    <p class="text-xl font-semibold mt-1">{{ $formatNumber($customerCount) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 2xl:grid-cols-5 gap-5">
    <div class="2xl:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Net Urun Performansi</h2>
            <p class="text-sm text-slate-500 mt-1">Bu ay iadeler dusuldukten sonra one cikan urunler.</p>
        </div>

        <div class="p-5">
            @if(!$canViewDashboardMetrics)
                @for($i = 1; $i <= 3; $i++)
                    <div class="py-4 {{ $i < 3 ? 'border-b border-slate-100' : '' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-sm font-semibold">
                                        {{ $i }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-500 truncate">Gizli urun verisi</p>
                                        <p class="text-sm text-slate-400 mt-0.5">Net ciro: **</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-slate-500">**</p>
                                <p class="text-sm text-slate-400">net satis miktari</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                                <span>Aylik net satis payi</span>
                                <span>**</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-slate-200" style="width: {{ 55 - (($i - 1) * 12) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            @else
                @forelse($topProducts as $product)
                    @php
                        $barWidth = $product->share > 0 ? min(max($product->share, 8), 100) : 0;
                    @endphp
                    <div class="py-4 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm font-semibold">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-800 truncate">{{ $product->product_name }}</p>
                                        <p class="text-sm text-slate-500 mt-0.5">Net ciro: {{ number_format($product->total_revenue, 2, ',', '.') }} ₺</p>
                                        @if(($product->returned_quantity ?? 0) > 0)
                                        <p class="text-xs text-rose-500 mt-1">Iade dusumu: {{ number_format($product->returned_quantity, 2, ',', '.') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-slate-800">{{ number_format($product->total_quantity, 2, ',', '.') }}</p>
                                <p class="text-sm text-slate-400">net satis miktari</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                                <span>Aylik net satis payi</span>
                                <span>%{{ number_format($product->share, 1, ',', '.') }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-400" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-400">
                        <i class="fas fa-box-open text-3xl opacity-30 block mb-3"></i>
                        Bu ay icin urun hareketi henuz olusmadi.
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    <div class="2xl:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Odeme Dagilimi</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ay musteriler hangi odeme yontemlerini tercih etti?</p>
                @if($canViewDashboardMetrics)
                <p class="text-xs text-slate-400 mt-1">Ayni ay icindeki iadeler, ilgili satisin odeme dagilimina oranlanarak netlenir.</p>
                @endif
            </div>
            <div class="p-5 space-y-4">
                @if(!$canViewDashboardMetrics)
                    @for($i = 1; $i <= 3; $i++)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div>
                                    <p class="font-medium text-slate-500">Gizli odeme kanali</p>
                                    <p class="text-xs text-slate-400">** islem</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-slate-500">**</p>
                                    <p class="text-xs text-slate-400">**</p>
                                </div>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-slate-200" style="width: {{ 62 - (($i - 1) * 14) }}%"></div>
                            </div>
                        </div>
                    @endfor
                @else
                    @forelse($paymentBreakdown as $payment)
                        @php
                            $colorMap = [
                                'TL' => 'from-blue-500 to-cyan-400',
                                'EURO' => 'from-yellow-500 to-amber-400',
                                'DOLAR' => 'from-emerald-500 to-lime-400',
                                'RUBLE' => 'from-rose-500 to-orange-400',
                                'PAUND' => 'from-violet-500 to-fuchsia-400',
                                'KREDI_KARTI' => 'from-pink-500 to-rose-400',
                                'BANKA_HAVALE' => 'from-slate-500 to-slate-400',
                            ];
                            $barColor = $colorMap[$payment->payment_type] ?? 'from-slate-500 to-slate-400';
                            $barWidth = $payment->share > 0 ? min(max($payment->share, 6), 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $payment->label }}</p>
                                    <p class="text-xs text-slate-400">{{ $payment->payment_count }} islem</p>
                                    @if(($payment->return_total ?? 0) > 0)
                                    <p class="text-xs text-rose-500 mt-1">Iade dusumu: -{{ number_format($payment->return_total, 2, ',', '.') }} ₺</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-slate-800">{{ number_format($payment->total_amount, 2, ',', '.') }} ₺</p>
                                    <p class="text-xs text-slate-400">%{{ number_format($payment->share, 1, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400">
                            <i class="fas fa-credit-card text-3xl opacity-30 block mb-3"></i>
                            Henuz odeme verisi olusmadi.
                        </div>
                    @endforelse
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Ekip Performansi</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ay iadeler dusuldukten sonra net katkisi en yuksek kasiyerler.</p>
            </div>
            <div class="p-5 space-y-4">
                @if(!$canViewDashboardMetrics)
                    @for($i = 1; $i <= 3; $i++)
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-semibold">
                                    {{ $i }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-500 truncate">Gizli kullanici</p>
                                    <p class="text-sm text-slate-400">** satis</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-slate-500">**</p>
                                <p class="text-xs text-slate-400">aylik net katki</p>
                            </div>
                        </div>
                    @endfor
                @else
                    @forelse($topCashiers as $cashier)
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold">
                                    {{ $loop->iteration }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-800 truncate">{{ $cashier->name }}</p>
                                    <p class="text-sm text-slate-400">{{ $cashier->sale_count }} satis</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-slate-800">{{ number_format($cashier->total_revenue, 2, ',', '.') }} ₺</p>
                                <p class="text-xs text-slate-400">aylik net katki</p>
                                @if(($cashier->return_total ?? 0) > 0)
                                <p class="text-xs text-rose-500 mt-1">Iade: -{{ number_format($cashier->return_total, 2, ',', '.') }} ₺</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400">
                            <i class="fas fa-users text-3xl opacity-30 block mb-3"></i>
                            Kasiyer performansi icin yeterli veri yok.
                        </div>
                    @endforelse
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
