@extends('layouts.app')
@section('title', 'Raporlar')
@section('page-title', 'Raporlar')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-5">
    <form class="flex flex-wrap items-center gap-3">
        <div class="flex gap-1">
            @foreach(['daily' => 'Bugün', 'weekly' => 'Bu Hafta', 'monthly' => 'Bu Ay', 'custom' => 'Özel'] as $key => $label)
            <a href="{{ route('reports.index', array_merge(request()->except('period','page'), ['period' => $key])) }}"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all {{ $period === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        @if($period === 'custom')
        <div class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
            <span class="text-slate-400 text-sm">—</span>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
            <input type="hidden" name="period" value="custom">
            <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium">Uygula</button>
        </div>
        @endif
    </form>

    <div class="mt-3 text-xs text-slate-400">
        Dönem: {{ $from->format('d.m.Y') }} — {{ $to->format('d.m.Y') }}
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <p class="text-sm text-slate-500">Toplam Satış</p>
        <p class="text-3xl font-bold text-slate-800 mt-1">{{ $totalSales }}</p>
        <p class="text-xs text-slate-400 mt-1">adet işlem</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <p class="text-sm text-slate-500">Net Ciro</p>
        <p class="text-3xl font-bold text-indigo-700 mt-1">{{ number_format($totalRevenue, 2, ',', '.') }} ₺</p>
        <p class="text-xs text-slate-400 mt-1">satışlar eksi iadeler</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <p class="text-sm text-slate-500">Toplam İade</p>
        <p class="text-3xl font-bold text-rose-600 mt-1">{{ number_format($totalReturns, 2, ',', '.') }} ₺</p>
        <p class="text-xs text-slate-400 mt-1">dönem içinde yapılan iade</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <p class="text-sm text-slate-500">Toplam İndirim</p>
        <p class="text-3xl font-bold text-orange-600 mt-1">{{ number_format($totalDiscount, 2, ',', '.') }} ₺</p>
        <p class="text-xs text-slate-400 mt-1">verilen indirim</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fas fa-credit-card text-indigo-500"></i> Ödeme Tiplerine Göre
            </h3>
            <p class="text-xs text-slate-400 mt-1">Bu tablo brüt tahsilatı gösterir; iadeler net ciro kartında düşülür.</p>
        </div>
        @if($paymentBreakdown->isEmpty())
        <div class="p-8 text-center text-slate-400 text-sm">Bu dönemde satış yok</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-2.5 text-slate-500 text-xs font-medium">Ödeme Tipi</th>
                    <th class="text-right px-4 py-2.5 text-slate-500 text-xs font-medium">Miktar</th>
                    <th class="text-right px-4 py-2.5 text-slate-500 text-xs font-medium">TL Karşılığı</th>
                    <th class="text-right px-4 py-2.5 text-slate-500 text-xs font-medium">Pay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($paymentBreakdown as $p)
                @php
                    $share = $paymentBreakdown->sum('total_tl') > 0 ? ($p->total_tl / $paymentBreakdown->sum('total_tl') * 100) : 0;
                    $labels = [
                        'TL' => ['Türk Lirası', 'bg-blue-100 text-blue-700'],
                        'EURO' => ['Euro', 'bg-indigo-100 text-indigo-700'],
                        'DOLAR' => ['Dolar', 'bg-green-100 text-green-700'],
                        'RUBLE' => ['Ruble', 'bg-red-100 text-red-700'],
                        'PAUND' => ['Pound', 'bg-purple-100 text-purple-700'],
                        'KREDI_KARTI' => ['Kredi Kartı', 'bg-orange-100 text-orange-700'],
                        'BANKA_HAVALE' => ['Banka Havale', 'bg-teal-100 text-teal-700'],
                    ];
                    $info = $labels[$p->payment_type] ?? [$p->payment_type, 'bg-slate-100 text-slate-700'];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $info[1] }}">{{ $info[0] }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($p->total_amount, 2, ',', '.') }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-slate-800">{{ number_format($p->total_tl, 2, ',', '.') }} ₺</td>
                    <td class="px-4 py-2.5 text-right text-slate-500 text-xs">%{{ number_format($share, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200">
                <tr>
                    <td class="px-4 py-2.5 font-semibold text-slate-700">TOPLAM</td>
                    <td></td>
                    <td class="px-4 py-2.5 text-right font-bold text-indigo-700">{{ number_format($paymentBreakdown->sum('total_tl'), 2, ',', '.') }} ₺</td>
                    <td class="px-4 py-2.5 text-right text-xs text-slate-500">%100</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-500"></i> Net Ürün Performansı
            </h3>
            <p class="text-xs text-slate-400 mt-1">İadeler düşüldükten sonraki net satış görünümü.</p>
        </div>
        @if($topProducts->isEmpty())
        <div class="p-8 text-center text-slate-400 text-sm">Bu dönemde satış yok</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-2.5 text-slate-500 text-xs font-medium">#</th>
                    <th class="text-left px-4 py-2.5 text-slate-500 text-xs font-medium">Ürün</th>
                    <th class="text-right px-4 py-2.5 text-slate-500 text-xs font-medium">Net Adet</th>
                    <th class="text-right px-4 py-2.5 text-slate-500 text-xs font-medium">Net Ciro</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($topProducts as $i => $prod)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2.5 text-slate-400 font-medium">{{ $i + 1 }}</td>
                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ $prod->product_name }}</td>
                    <td class="px-4 py-2.5 text-right text-slate-600">{{ number_format($prod->total_qty, 2, ',', '.') }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-indigo-700">{{ number_format($prod->total_revenue, 2, ',', '.') }} ₺</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@if($chartData->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 mb-5">
    <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
        <i class="fas fa-chart-area text-indigo-500"></i> Günlük Net Ciro Grafiği
    </h3>
    @php $maxTotal = max($chartData->max(fn($day) => abs($day->total)) ?: 1, 1); @endphp
    <div class="flex items-end gap-2 h-32">
        @foreach($chartData as $day)
        @php
            $height = max(4, (abs($day->total) / $maxTotal) * 100);
            $barClass = $day->total < 0 ? 'bg-rose-500 hover:bg-rose-400' : 'bg-indigo-500 hover:bg-indigo-400';
        @endphp
        <div class="flex-1 flex flex-col items-center gap-1 group">
            <div class="w-full {{ $barClass }} rounded-t transition-all cursor-pointer relative"
                style="height: {{ $height }}%"
                title="{{ $day->date }}: Net {{ number_format($day->total, 2, ',', '.') }} ₺ | İade {{ number_format($day->return_total, 2, ',', '.') }} ₺ | {{ $day->count }} satış">
            </div>
            <span class="text-xs text-slate-400 truncate w-full text-center">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($hourlyData->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
    <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
        <i class="fas fa-clock text-green-500"></i> Bugün Saatlik Net Ciro Dağılımı
    </h3>
    @php $maxHour = max($hourlyData->max(fn($hour) => abs($hour->total)) ?: 1, 1); @endphp
    <div class="flex items-end gap-1 h-24">
        @foreach(range(0, 23) as $h)
        @php
            $hourData = $hourlyData->firstWhere('hour', $h);
            $hTotal = $hourData?->total ?? 0;
            $hHeight = max(2, (abs($hTotal) / $maxHour) * 100);
            $hourClass = $hTotal > 0 ? 'bg-green-400 hover:bg-green-300' : ($hTotal < 0 ? 'bg-rose-400 hover:bg-rose-300' : 'bg-slate-100');
        @endphp
        <div class="flex-1 flex flex-col items-center gap-1 group">
            <div class="w-full {{ $hourClass }} rounded-t transition-all"
                style="height: {{ $hHeight }}%"
                title="{{ $h }}:00 - Net {{ number_format($hTotal, 2, ',', '.') }} ₺ | İade {{ number_format($hourData?->return_total ?? 0, 2, ',', '.') }} ₺"></div>
            @if($h % 4 === 0)
            <span class="text-xs text-slate-400">{{ $h }}h</span>
            @else
            <span class="text-xs text-transparent">-</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
