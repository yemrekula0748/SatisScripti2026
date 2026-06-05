@extends('layouts.app')

@section('title', 'Satış Geçmişi')

@section('content')
<div class="p-6 space-y-5" x-data="salesHistoryApp()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Satış Geçmişi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Tüm tamamlanan satışlar</p>
        </div>
        <a href="{{ route('sales.pos') }}" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all">
            <i class="fas fa-cash-register"></i> Satış Ekranı
        </a>
    </div>

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
                    <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500">İşlem</th>
                </tr>
            </thead>
            @forelse($sales as $sale)
            @php
                $returnedTotal = (float) $sale->returns->sum('total');
                $netTotal = max((float) $sale->total - $returnedTotal, 0);
                $hasRefundableItems = $sale->items->contains(function ($item) {
                    return ((float) $item->quantity - (float) $item->returnItems->sum('quantity')) > 0.0001;
                });
                $saleData = [
                    'id' => $sale->id,
                    'customer_name' => $sale->customer_name,
                    'discount_percent' => (float) $sale->discount_percent,
                    'total' => (float) $sale->total,
                    'items' => $sale->items->map(function ($item) {
                        return [
                            'sale_item_id' => $item->id,
                            'product_name' => $item->product_name,
                            'product_barcode' => $item->product_barcode,
                            'unit' => $item->unit,
                            'unit_price' => (float) $item->unit_price,
                            'quantity' => (float) $item->quantity,
                            'refunded_quantity' => (float) $item->returnItems->sum('quantity'),
                        ];
                    })->values(),
                ];
            @endphp
            <tbody x-data="{ open: false }" class="border-b border-slate-100">
                <tr class="hover:bg-slate-50 transition-colors">
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
                        @if($returnedTotal > 0)
                        <p class="text-xs text-rose-500 mt-1">İade: −{{ number_format($returnedTotal, 2, ',', '.') }} ₺</p>
                        <p class="text-xs text-slate-400">Net: {{ number_format($netTotal, 2, ',', '.') }} ₺</p>
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
                    <td class="px-4 py-3">
                        <div class="flex flex-col items-center gap-2">
                            <button @click="open = !open"
                                class="w-full px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 rounded-lg text-xs font-medium transition-all">
                                <i class="fas fa-chevron-down text-xs" :class="{ 'rotate-180': open }" style="transition:transform 0.2s"></i>
                                Detay
                            </button>
                            <button
                                @click="openRefundModal(@js($saleData))"
                                @disabled(!$hasRefundableItems)
                                class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $hasRefundableItems ? 'bg-rose-50 hover:bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}">
                                <i class="fas fa-rotate-left text-xs mr-1"></i>
                                {{ $hasRefundableItems ? 'İade' : 'Tam İade' }}
                            </button>
                        </div>
                    </td>
                </tr>

                <tr x-show="open" x-cloak class="bg-slate-50">
                    <td colspan="7" class="px-6 py-4">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                            @php
                                                $refundedQuantity = (float) $item->returnItems->sum('quantity');
                                                $remainingQuantity = max((float) $item->quantity - $refundedQuantity, 0);
                                            @endphp
                                            <tr>
                                                <td class="py-1 font-medium text-slate-700">
                                                    {{ $item->product_name }}
                                                    @if($item->product_barcode)
                                                    <span class="text-slate-400 font-normal">({{ $item->product_barcode }})</span>
                                                    @endif
                                                    @if($refundedQuantity > 0)
                                                    <p class="text-[11px] text-rose-500 mt-1">
                                                        İade: {{ number_format($refundedQuantity, 3, ',', '.') }} {{ $item->unit }}
                                                        | Kalan: {{ number_format($remainingQuantity, 3, ',', '.') }} {{ $item->unit }}
                                                    </p>
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

                            <div class="rounded-xl border border-rose-100 bg-white overflow-hidden">
                                <div class="px-4 py-3 border-b border-rose-100 bg-rose-50/70 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">
                                            <i class="fas fa-rotate-left mr-1"></i> İade Bilgileri
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">Bu satışa bağlı yapılan iadeler burada görünür.</p>
                                    </div>
                                    @if($returnedTotal > 0)
                                    <div class="text-right">
                                        <p class="text-xs text-slate-400">Toplam iade</p>
                                        <p class="text-sm font-bold text-rose-600">{{ number_format($returnedTotal, 2, ',', '.') }} ₺</p>
                                    </div>
                                    @endif
                                </div>

                                <div class="p-4">
                                    @forelse($sale->returns as $return)
                                    <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-4 {{ !$loop->last ? 'mb-4' : '' }}">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="font-semibold text-slate-800">İade #{{ $return->id }}</p>
                                                <p class="text-xs text-slate-500 mt-1">
                                                    {{ $return->created_at->format('d.m.Y H:i:s') }}
                                                    · {{ $return->user?->name ?? 'Bilinmiyor' }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-rose-600">{{ number_format($return->total, 2, ',', '.') }} ₺</p>
                                                @if($return->discount_amount > 0)
                                                <p class="text-xs text-slate-400">
                                                    Brüt: {{ number_format($return->subtotal, 2, ',', '.') }} ₺
                                                    | İndirim: −{{ number_format($return->discount_amount, 2, ',', '.') }} ₺
                                                </p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-3 overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-slate-400">
                                                        <th class="text-left pb-1">Ürün</th>
                                                        <th class="text-right pb-1">Birim Fiyat</th>
                                                        <th class="text-right pb-1">İade Miktarı</th>
                                                        <th class="text-right pb-1">Ara Toplam</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-rose-100">
                                                    @foreach($return->items as $returnItem)
                                                    <tr>
                                                        <td class="py-1 font-medium text-slate-700">
                                                            {{ $returnItem->product_name }}
                                                            @if($returnItem->product_barcode)
                                                            <span class="text-slate-400 font-normal">({{ $returnItem->product_barcode }})</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-1 text-right text-slate-600">{{ number_format($returnItem->unit_price, 2, ',', '.') }} ₺</td>
                                                        <td class="py-1 text-right text-slate-600">{{ number_format($returnItem->quantity, 3, ',', '.') }} {{ $returnItem->unit }}</td>
                                                        <td class="py-1 text-right font-semibold text-slate-800">{{ number_format($returnItem->total, 2, ',', '.') }} ₺</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="py-8 text-center text-slate-400">
                                        <i class="fas fa-ban text-2xl mb-2 opacity-30 block"></i>
                                        Bu satış için henüz iade yapılmadı.
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
            @empty
            <tbody>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        <i class="fas fa-receipt text-3xl mb-2 opacity-30 block"></i>
                        Satış kaydı bulunamadı.
                    </td>
                </tr>
            </tbody>
            @endforelse
        </table>

        @if($sales->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    <div x-show="showRefundModal" x-cloak
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeRefundModal()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Satış İadesi</h3>
                    <p class="text-sm text-slate-500 mt-1" x-text="activeSale ? `Satış #${activeSale.id} · ${activeSale.customer_name || 'Misafir Müşteri'}` : ''"></p>
                </div>
                <button @click="closeRefundModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <div class="p-5 overflow-y-auto max-h-[calc(90vh-170px)]">
                <div class="rounded-xl bg-amber-50 border border-amber-100 text-amber-700 text-sm px-4 py-3 mb-4">
                    İade edilen miktarlar stoklara geri eklenecek. Her ürün için sadece kalan iade edilebilir adet seçilebilir.
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500">Ürün</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500">Satılan</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500">İade Edilen</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500">Kalan</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500">İade Miktarı</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="item in refundItems" :key="item.sale_item_id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-800" x-text="item.product_name"></p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            <span x-show="item.product_barcode" x-text="item.product_barcode"></span>
                                            <span x-show="!item.product_barcode">Barkod yok</span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1" x-text="formatPrice(item.unit_price) + ' ₺ / ' + item.unit"></p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="formatQty(item.original_quantity) + ' ' + item.unit"></td>
                                    <td class="px-4 py-3 text-right text-rose-500" x-text="formatQty(item.refunded_quantity) + ' ' + item.unit"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800" x-text="formatQty(item.remaining_quantity) + ' ' + item.unit"></td>
                                    <td class="px-4 py-3 text-right">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            :max="item.remaining_quantity"
                                            x-model.number="item.refund_quantity"
                                            @change="normalizeRefundQuantity(item)"
                                            :disabled="item.remaining_quantity <= 0 || refundLoading"
                                            class="w-28 border border-slate-200 rounded-lg px-3 py-2 text-sm text-right outline-none focus:ring-2 focus:ring-rose-300 disabled:bg-slate-100 disabled:text-slate-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-sm">
                        <div>
                            <p class="font-medium text-slate-700">Seçilen iade toplamı</p>
                            <p class="text-xs text-slate-400 mt-1" x-show="activeSale && activeSale.discount_percent > 0" x-text="`Satıştaki %${formatQty(activeSale.discount_percent)} indirim iade toplamına da uygulanacak.`"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Ara toplam: <span class="font-medium text-slate-600" x-text="formatPrice(refundSubtotal) + ' ₺'"></span></p>
                            <p class="text-xs text-slate-400" x-show="refundDiscountAmount > 0">İndirim: −<span class="font-medium text-slate-600" x-text="formatPrice(refundDiscountAmount) + ' ₺'"></span></p>
                            <p class="text-lg font-bold text-rose-600 mt-1" x-text="formatPrice(refundTotal) + ' ₺'"></p>
                        </div>
                    </div>
                </div>

                <div x-show="!hasRefundableItems" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Bu satıştaki tüm ürünler zaten tamamen iade edilmiş.
                </div>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-white">
                <button @click="closeRefundModal()" :disabled="refundLoading"
                    class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-200 transition-all disabled:opacity-50">
                    Vazgeç
                </button>
                <button @click="saveRefund()" :disabled="refundLoading || !hasRefundableItems"
                    class="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-sm font-medium hover:bg-rose-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!refundLoading"><i class="fas fa-check-circle mr-1"></i> İadeyi Kaydet</span>
                    <span x-show="refundLoading"><i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function salesHistoryApp() {
    return {
        showRefundModal: false,
        refundLoading: false,
        activeSale: null,
        refundItems: [],

        get refundSubtotal() {
            return this.refundItems.reduce((sum, item) => {
                const qty = Number(item.refund_quantity || 0);
                return sum + (qty * Number(item.unit_price || 0));
            }, 0);
        },

        get refundDiscountAmount() {
            const discountPercent = Number(this.activeSale?.discount_percent || 0);
            return this.refundSubtotal * (discountPercent / 100);
        },

        get refundTotal() {
            return this.refundSubtotal - this.refundDiscountAmount;
        },

        get hasRefundableItems() {
            return this.refundItems.some(item => Number(item.remaining_quantity || 0) > 0.0001);
        },

        roundQty(value) {
            return Math.round(Number(value || 0) * 1000) / 1000;
        },

        formatPrice(value) {
            return Number(value || 0).toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        formatQty(value) {
            return Number(value || 0).toLocaleString('tr-TR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3,
            });
        },

        openRefundModal(sale) {
            this.activeSale = sale;
            this.refundItems = (sale.items || []).map(item => {
                const originalQuantity = this.roundQty(item.quantity);
                const refundedQuantity = this.roundQty(item.refunded_quantity);
                const remainingQuantity = this.roundQty(Math.max(originalQuantity - refundedQuantity, 0));

                return {
                    sale_item_id: item.sale_item_id,
                    product_name: item.product_name,
                    product_barcode: item.product_barcode,
                    unit: item.unit,
                    unit_price: Number(item.unit_price || 0),
                    original_quantity: originalQuantity,
                    refunded_quantity: refundedQuantity,
                    remaining_quantity: remainingQuantity,
                    refund_quantity: 0,
                };
            });
            this.showRefundModal = true;
        },

        closeRefundModal(force = false) {
            if (this.refundLoading && !force) return;

            this.showRefundModal = false;
            this.activeSale = null;
            this.refundItems = [];
        },

        normalizeRefundQuantity(item) {
            let qty = this.roundQty(item.refund_quantity);
            if (!Number.isFinite(qty) || qty < 0) {
                qty = 0;
            }

            const maxQty = this.roundQty(item.remaining_quantity);
            if (qty > maxQty) {
                qty = maxQty;
            }

            item.refund_quantity = qty;
        },

        async saveRefund() {
            const selectedItems = this.refundItems
                .map(item => {
                    this.normalizeRefundQuantity(item);
                    return item;
                })
                .map(item => ({
                    sale_item_id: item.sale_item_id,
                    quantity: this.roundQty(item.refund_quantity),
                    product_name: item.product_name,
                }))
                .filter(item => item.quantity > 0);

            if (!selectedItems.length) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'İade seçilmedi',
                    text: 'Lütfen iade edilecek en az bir ürün ve miktar seçin.',
                });
                return;
            }

            const confirmResult = await Swal.fire({
                icon: 'warning',
                title: 'İadeyi onaylıyor musunuz?',
                text: 'Seçilen ürünler stoklara geri eklenecek ve satışa iade kaydı düşülecek.',
                showCancelButton: true,
                confirmButtonText: 'Evet, iade et',
                cancelButtonText: 'Vazgeç',
                confirmButtonColor: '#e11d48',
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            this.refundLoading = true;

            try {
                const response = await fetch(`/sales/${this.activeSale.id}/refund`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        items: selectedItems.map(item => ({
                            sale_item_id: item.sale_item_id,
                            quantity: item.quantity,
                        })),
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'İade kaydedilemedi.');
                }

                this.closeRefundModal(true);

                await Swal.fire({
                    icon: 'success',
                    title: 'İade tamamlandı',
                    text: `Toplam ${this.formatPrice(data.refund_total)} ₺ iade kaydedildi.`,
                    confirmButtonColor: '#4f46e5',
                });

                window.location.reload();
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'İade kaydedilemedi',
                    text: error.message || 'Lütfen tekrar deneyin.',
                });
            } finally {
                this.refundLoading = false;
            }
        },
    };
}
</script>
@endpush
