@extends('layouts.app')

@section('title', 'Satış Geçmişi')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Satış Geçmişi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Tüm tamamlanan satışlar</p>
        </div>
        <a href="{{ route('sales.pos') }}" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all">
            <i class="fas fa-cash-register"></i> Satış Ekranı
        </a>
    </div>

    {{-- Filtreler --}}
    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-slate-500 mb-1">Müşteri Ara</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Müşteri adı..."
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all">
                <i class="fas fa-search mr-1"></i> Filtrele
            </button>
            <a href="{{ route('sales.history') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-200 transition-all">
                Temizle
            </a>
        </div>
    </form>

    {{-- Tablo --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">#</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">Tarih / Saat</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">Müşteri</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">Kasiyer</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500">Tutar</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">Ödeme Tipi</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500">Detay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($sales as $sale)
                <tr class="hover:bg-slate-50 transition-colors" x-data="{ open: false }">
                    <td class="px-4 py-3 text-slate-400 text-xs font-mono">{{ $sale->id }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $sale->created_at->format('d.m.Y') }}</p>
                        <p class="text-xs text-slate-400">{{ $sale->created_at->format('H:i:s') }}</p>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $sale->customer_name }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $sale->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <p class="font-bold text-indigo-700">{{ number_format($sale->total, 2, ',', '.') }} ₺</p>
                        @if($sale->discount_percent > 0)
                        <p class="text-xs text-orange-500">%{{ number_format($sale->discount_percent, 0) }} indirim</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($sale->payments as $pay)
                            @php
                                $colors = [
                                    'TL' => 'bg-blue-100 text-blue-700',
                                    'EURO' => 'bg-yellow-100 text-yellow-700',
                                    'DOLAR' => 'bg-green-100 text-green-700',
                                    'RUBLE' => 'bg-red-100 text-red-700',
                                    'PAUND' => 'bg-purple-100 text-purple-700',
                                    'KREDI_KARTI' => 'bg-pink-100 text-pink-700',
                                    'BANKA_HAVALE' => 'bg-slate-100 text-slate-700',
                                ];
                                $cls = $colors[$pay->payment_type] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
                                {{ \App\Models\SalePayment::paymentLabel($pay->payment_type) }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button @click="open = !open"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 rounded-lg text-xs font-medium transition-all">
                            <i class="fas fa-chevron-down text-xs" :class="open && 'rotate-180'" style="transition:transform 0.2s"></i>
                            Detay
                        </button>
                    </td>
                </tr>

                {{-- Detay Satırı --}}
                <tr x-show="open" x-cloak class="bg-slate-50">
                    <td colspan="7" class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- Ürünler --}}
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                    <i class="fas fa-box mr-1"></i> Ürünler ({{ $sale->items->count() }} kalem)
                                </p>
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-slate-400">
                                            <th class="text-left pb-1">Ürün</th>
                                            <th class="text-right pb-1">Birim Fiyat</th>
                                            <th class="text-right pb-1">Miktar</th>
                                            <th class="text-right pb-1">Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($sale->items as $item)
                                        <tr>
                                            <td class="py-1 font-medium text-slate-700">
                                                {{ $item->product_name }}
                                                @if($item->product_barcode)
                                                <span class="text-slate-400 font-normal">({{ $item->product_barcode }})</span>
                                                @endif
                                            </td>
                                            <td class="py-1 text-right text-slate-600">{{ number_format($item->unit_price, 2, ',', '.') }} ₺</td>
                                            <td class="py-1 text-right text-slate-600">{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->unit }}</td>
                                            <td class="py-1 text-right font-semibold text-slate-800">{{ number_format($item->total, 2, ',', '.') }} ₺</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t border-slate-200">
                                        @if($sale->discount_percent > 0)
                                        <tr>
                                            <td colspan="3" class="pt-1 text-orange-600">İndirim (%{{ number_format($sale->discount_percent, 2, ',', '.') }})</td>
                                            <td class="pt-1 text-right text-orange-600 font-medium">−{{ number_format($sale->discount_amount, 2, ',', '.') }} ₺</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td colspan="3" class="pt-1 font-bold text-slate-700">TOPLAM</td>
                                            <td class="pt-1 text-right font-bold text-indigo-700">{{ number_format($sale->total, 2, ',', '.') }} ₺</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Ödemeler --}}
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                    <i class="fas fa-credit-card mr-1"></i> Ödemeler
                                </p>
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-slate-400">
                                            <th class="text-left pb-1">Ödeme Tipi</th>
                                            <th class="text-right pb-1">Tutar</th>
                                            <th class="text-right pb-1">Kur</th>
                                            <th class="text-right pb-1">TL Karşılığı</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($sale->payments as $pay)
                                        <tr>
                                            <td class="py-1 font-medium text-slate-700">
                                                {{ \App\Models\SalePayment::paymentLabel($pay->payment_type) }}
                                            </td>
                                            <td class="py-1 text-right text-slate-600">
                                                {{ number_format($pay->amount, 2, ',', '.') }}
                                                @if(!in_array($pay->payment_type, ['TL','KREDI_KARTI','BANKA_HAVALE']))
                                                    @php $symbols = ['EURO'=>'€','DOLAR'=>'$','PAUND'=>'£','RUBLE'=>'₽']; @endphp
                                                    {{ $symbols[$pay->payment_type] ?? '' }}
                                                @else ₺ @endif
                                            </td>
                                            <td class="py-1 text-right text-slate-400">
                                                @if($pay->exchange_rate != 1)
                                                    {{ number_format($pay->exchange_rate, 4, ',', '.') }} ₺
                                                @else — @endif
                                            </td>
                                            <td class="py-1 text-right font-semibold text-slate-800">{{ number_format($pay->amount_in_tl, 2, ',', '.') }} ₺</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t border-slate-200">
                                        @php
                                            $totalPaid = $sale->payments->sum('amount_in_tl');
                                            $change = $totalPaid - $sale->total;
                                        @endphp
                                        <tr>
                                            <td colspan="3" class="pt-1 font-semibold text-slate-700">Ödenen Toplam (TL)</td>
                                            <td class="pt-1 text-right font-bold text-slate-800">{{ number_format($totalPaid, 2, ',', '.') }} ₺</td>
                                        </tr>
                                        @if($change > 0.009)
                                        <tr>
                                            <td colspan="3" class="pt-1 font-semibold text-green-600">Para Üstü</td>
                                            <td class="pt-1 text-right font-bold text-green-600">{{ number_format($change, 2, ',', '.') }} ₺</td>
                                        </tr>
                                        @endif
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        <i class="fas fa-receipt text-3xl mb-2 opacity-30 block"></i>
                        Satış kaydı bulunamadı.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($sales->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
