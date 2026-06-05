@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugünkü Satış</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $todaySales }}</p>
                <p class="text-xs text-slate-400 mt-2">Gün içinde tamamlanan fiş adedi</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-indigo-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugünkü Net Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($todayRevenue, 2, ',', '.') }} ₺</p>
                <p class="text-xs text-slate-400 mt-2">Satışlar eksi bugün yapılan iadeler</p>
                @if($todayReturnTotal > 0)
                <p class="text-xs text-rose-500 mt-1">Bugünkü iade: −{{ number_format($todayReturnTotal, 2, ',', '.') }} ₺</p>
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
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($averageBasket, 2, ',', '.') }} ₺</p>
                <p class="text-xs text-slate-400 mt-2">Satış başına ortalama tutar</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-basket-shopping text-amber-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Aylık Net Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($monthRevenue, 2, ',', '.') }} ₺</p>
                <p class="text-xs text-slate-400 mt-2">Bu ay satışlar eksi iadeler</p>
                @if($monthReturnTotal > 0)
                <p class="text-xs text-rose-500 mt-1">Aylık iade: −{{ number_format($monthReturnTotal, 2, ',', '.') }} ₺</p>
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
                <h2 class="font-semibold text-slate-800">Satış Nabzı</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ayki satış ritmini tek bakışta takip edin.</p>
            </div>
            @can('sales.view')
            <a href="{{ route('sales.pos') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                <i class="fas fa-cash-register text-xs"></i>
                Yeni satış
            </a>
            @endcan
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Bu Ay</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $monthSalesCount }}</p>
                <p class="text-sm text-slate-500 mt-1">toplam satış</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">7 Gün</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ number_format($last7DaysRevenue, 2, ',', '.') }} ₺</p>
                <p class="text-sm text-slate-500 mt-1">son 7 gün net cirosu</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Müşteri</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $monthCustomerReach }}</p>
                <p class="text-sm text-slate-500 mt-1">bu ay ulaşılan müşteri</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">İndirim</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ number_format($monthDiscountTotal, 2, ',', '.') }} ₺</p>
                <p class="text-sm text-slate-500 mt-1">bu ay verilen indirim</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 text-white shadow-sm">
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-indigo-200/80">Haftalık İvme</p>
                    <h2 class="text-xl font-semibold mt-2">Satış temposu nasıl gidiyor?</h2>
                </div>
                <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                    <i class="fas fa-chart-column text-indigo-200"></i>
                </div>
            </div>

            <div class="mt-8">
                @if(!is_null($weeklyTrend))
                    @php
                        $isPositiveTrend = $weeklyTrend >= 0;
                    @endphp
                    <div class="flex items-end gap-3">
                        <p class="text-4xl font-bold">{{ $isPositiveTrend ? '+' : '' }}{{ number_format($weeklyTrend, 1, ',', '.') }}%</p>
                        <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium {{ $isPositiveTrend ? 'bg-emerald-400/15 text-emerald-200' : 'bg-rose-400/15 text-rose-200' }}">
                            <i class="fas {{ $isPositiveTrend ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            {{ $isPositiveTrend ? 'yukarı yönlü' : 'dikkat gerekiyor' }}
                        </span>
                    </div>
                @else
                    <p class="text-4xl font-bold">Yeni</p>
                    <p class="text-sm text-slate-300 mt-3">Karşılaştırma için önceki hafta verisi oluşmaya başladı.</p>
                @endif

                <p class="text-sm text-slate-300 mt-4">Son 7 gün: {{ number_format($last7DaysRevenue, 2, ',', '.') }} ₺</p>
                <p class="text-sm text-slate-400 mt-1">Önceki 7 gün: {{ number_format($previous7DaysRevenue, 2, ',', '.') }} ₺</p>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-xs text-slate-300">Aktif ürün</p>
                    <p class="text-xl font-semibold mt-1">{{ $productCount }}</p>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-xs text-slate-300">Kayıtlı müşteri</p>
                    <p class="text-xl font-semibold mt-1">{{ $customerCount }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 2xl:grid-cols-5 gap-5">
    <div class="2xl:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">En Çok Giden Ürünler</h2>
            <p class="text-sm text-slate-500 mt-1">Bu ay satışta öne çıkan ürünler ve ciro katkıları.</p>
        </div>

        <div class="p-5">
            @forelse($topProducts as $product)
                @php
                    $barWidth = $product->share > 0 ? max($product->share, 8) : 0;
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
                                    <p class="text-sm text-slate-500 mt-0.5">Toplam ciro: {{ number_format($product->total_revenue, 2, ',', '.') }} ₺</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-semibold text-slate-800">{{ number_format($product->total_quantity, 2, ',', '.') }}</p>
                            <p class="text-sm text-slate-400">satılan miktar</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                            <span>Aylık satış payı</span>
                            <span>%{{ number_format($product->share, 1, ',', '.') }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-400" style="width: {{ min($barWidth, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400">
                    <i class="fas fa-box-open text-3xl opacity-30 block mb-3"></i>
                    Bu ay için ürün hareketi henüz oluşmadı.
                </div>
            @endforelse
        </div>
    </div>

    <div class="2xl:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Ödeme Dağılımı</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ay müşteriler hangi ödeme yöntemlerini tercih etti?</p>
            </div>
            <div class="p-5 space-y-4">
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
                    @endphp
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <div>
                                <p class="font-medium text-slate-800">{{ $payment->label }}</p>
                                <p class="text-xs text-slate-400">{{ $payment->payment_count }} işlem</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-slate-800">{{ number_format($payment->total_amount, 2, ',', '.') }} ₺</p>
                                <p class="text-xs text-slate-400">%{{ number_format($payment->share, 1, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r {{ $barColor }}" style="width: {{ min(max($payment->share, 6), 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <i class="fas fa-credit-card text-3xl opacity-30 block mb-3"></i>
                        Henüz ödeme verisi oluşmadı.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Ekip Performansı</h2>
                <p class="text-sm text-slate-500 mt-1">Bu ay ciroya en çok katkı veren kasiyerler.</p>
            </div>
            <div class="p-5 space-y-4">
                @forelse($topCashiers as $cashier)
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold">
                                {{ $loop->iteration }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800 truncate">{{ $cashier->name }}</p>
                                <p class="text-sm text-slate-400">{{ $cashier->sale_count }} satış</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-semibold text-slate-800">{{ number_format($cashier->total_revenue, 2, ',', '.') }} ₺</p>
                            <p class="text-xs text-slate-400">aylık katkı</p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <i class="fas fa-users text-3xl opacity-30 block mb-3"></i>
                        Kasiyer performansı için yeterli veri yok.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
