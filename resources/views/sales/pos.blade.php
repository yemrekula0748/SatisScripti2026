<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Satış Ekranı - POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        body { overflow: hidden; }
        .pos-height { height: calc(100vh - 56px); }
        .cart-scroll { overflow-y: auto; max-height: calc(100vh - 420px); }
        input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; }
    </style>
</head>
<body class="bg-slate-100" x-data="posApp()">

{{-- Header --}}
<header class="h-14 bg-slate-800 flex items-center justify-between px-4 shadow-lg">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center">
            <i class="fas fa-cash-register text-white text-xs"></i>
        </div>
        <span class="text-white font-semibold text-sm">Satış Ekranı</span>
    </div>
    <div class="flex items-center gap-3 text-xs text-slate-400">
        <span>{{ auth()->user()->name }}</span>
        <span>|</span>
        <span>{{ auth()->user()->company->name ?? '' }}</span>
        <span>|</span>
        <span x-text="now"></span>
    </div>
</header>

<div class="flex pos-height">
    {{-- LEFT: Products --}}
    <div class="flex-1 flex flex-col bg-slate-100 min-w-0">
        {{-- Search --}}
        <div class="p-4 bg-white border-b border-slate-200 shadow-sm">
            <div class="relative">
                <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl"></i>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.300ms="searchProducts()"
                    @keydown.enter="handleEnter()"
                    x-ref="searchInput"
                    placeholder="Ürün adı veya barkod okutun / yazın..."
                    class="w-full bg-slate-50 border-2 border-indigo-300 focus:border-indigo-500 rounded-xl pl-14 pr-6 py-4 text-lg font-medium placeholder-slate-400 outline-none transition-all shadow-inner"
                    autocomplete="off"
                >
                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                    <span x-show="searchQuery" @click="searchQuery=''; searchResults=[]" class="cursor-pointer text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times"></i>
                    </span>
                    <span class="text-xs text-slate-400 bg-slate-200 px-2 py-1 rounded">Enter = ekle</span>
                </div>
            </div>
        </div>

        {{-- Search Results / Product Grid --}}
        <div class="flex-1 overflow-y-auto p-4">
            <div x-show="searchResults.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                <template x-for="product in searchResults" :key="product.id">
                    <div @click="addToCart(product)"
                        class="bg-white rounded-xl p-3 shadow-sm border border-slate-200 hover:border-indigo-400 hover:shadow-md cursor-pointer transition-all group">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center mb-2 group-hover:bg-indigo-100">
                            <i class="fas fa-box text-indigo-500 text-sm"></i>
                        </div>
                        <p class="text-xs font-semibold text-slate-800 leading-tight line-clamp-2 mb-1" x-text="product.name"></p>
                        <p class="text-xs text-slate-400 mb-2" x-text="product.barcode || '—'"></p>
                        <div class="flex items-center justify-between">
                            <span class="text-indigo-600 font-bold text-sm" x-text="formatPrice(product.sale_price) + ' ₺'"></span>
                            <span class="text-xs text-slate-400" x-text="product.stock + ' ' + product.unit"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="searchQuery && searchResults.length === 0 && !searching" class="flex flex-col items-center justify-center h-48 text-slate-400">
                <i class="fas fa-search text-4xl mb-3 opacity-30"></i>
                <p class="text-sm">Ürün bulunamadı</p>
            </div>

            <div x-show="!searchQuery" class="flex flex-col items-center justify-center h-48 text-slate-300">
                <i class="fas fa-barcode text-6xl mb-4 opacity-40"></i>
                <p class="text-base font-medium">Barkod okutun veya ürün adı yazın</p>
                <p class="text-sm mt-1">Arama kutusuna odaklanın</p>
            </div>
        </div>
    </div>

    {{-- RIGHT: Cart --}}
    <div class="w-96 bg-white border-l border-slate-200 flex flex-col shadow-xl">

        {{-- Cart Header --}}
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2">
                <i class="fas fa-shopping-cart text-slate-600"></i>
                <span class="font-semibold text-slate-800">Sepet</span>
                <span class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="cart.length"></span>
            </div>
            <div class="flex gap-2">
                <button @click="openCurrencyModal()" title="Kur Ayarla"
                    class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-700 hover:bg-yellow-200 rounded-lg transition-all text-xs">
                    <i class="fas fa-dollar-sign"></i>
                </button>
                <button @click="applyDiscount()" title="İndirim Tanımla"
                    class="w-8 h-8 flex items-center justify-center bg-orange-100 text-orange-700 hover:bg-orange-200 rounded-lg transition-all text-xs">
                    <i class="fas fa-percent"></i>
                </button>
            </div>
        </div>

        {{-- Customer Selector --}}
        <div class="px-4 py-2 border-b border-slate-100">
            <select x-model="selectedCustomerId" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Misafir Müşteri</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - '.$customer->phone : '' }}</option>
                @endforeach
            </select>
        </div>

        {{-- Cart Items --}}
        <div class="cart-scroll flex-1 px-4 py-2">
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-300">
                <i class="fas fa-shopping-basket text-4xl mb-3 opacity-40"></i>
                <p class="text-sm">Sepet boş</p>
            </div>

            <template x-for="(item, index) in cart" :key="index">
                <div class="flex items-start gap-2 py-2.5 border-b border-slate-50 group">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 leading-tight" x-text="item.name"></p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="formatPrice(item.price) + ' ₺ / ' + item.unit"></p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button @click="decreaseQty(index)"
                            class="w-7 h-7 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-bold transition-all">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number" x-model.number="item.qty" @change="updateQty(index, $event.target.value)"
                            class="w-12 text-center text-sm font-semibold border border-slate-200 rounded-lg py-1 outline-none focus:ring-2 focus:ring-indigo-300">
                        <button @click="increaseQty(index)"
                            class="w-7 h-7 flex items-center justify-center bg-indigo-100 hover:bg-indigo-200 text-indigo-600 rounded-lg text-sm font-bold transition-all">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="text-sm font-semibold text-slate-800 w-16 text-right" x-text="formatPrice(item.price * item.qty) + ' ₺'"></span>
                        <button @click="removeFromCart(index)"
                            class="w-6 h-6 flex items-center justify-center text-slate-300 hover:text-red-500 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Totals --}}
        <div class="border-t border-slate-200 px-5 py-3 space-y-1.5 bg-slate-50">
            <div class="flex justify-between text-sm text-slate-600">
                <span>Ara Toplam:</span>
                <span x-text="formatPrice(subtotal) + ' ₺'"></span>
            </div>
            <div x-show="discountPercent > 0" class="flex justify-between text-sm text-orange-600">
                <span>İndirim (<span x-text="discountPercent"></span>%):</span>
                <span>-<span x-text="formatPrice(discountAmount)"></span> ₺</span>
            </div>
            <div class="flex justify-between text-xl font-bold text-indigo-700 pt-1 border-t border-slate-200">
                <span>TOPLAM:</span>
                <span x-text="formatPrice(total) + ' ₺'"></span>
            </div>
        </div>

        {{-- Payment Section --}}
        <div class="px-4 py-3 border-t border-slate-200 space-y-2">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ödeme</p>

            <template x-for="(pType, pKey) in paymentTypes" :key="pKey">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-600 w-24 flex-shrink-0" x-text="pType.label"></label>
                    <input type="number" step="0.01" min="0"
                        x-model.number="payments[pKey]"
                        @input="updatePaymentTotal()"
                        class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-300 text-right"
                        placeholder="0,00">
                    <span x-show="pType.currency && payments[pKey] > 0"
                        class="text-xs text-slate-400 w-20 text-right flex-shrink-0"
                        x-text="formatPrice(payments[pKey] * (rates[pType.currency] || 1)) + ' ₺'"></span>
                </div>
            </template>

            <div class="flex justify-between text-xs pt-1 border-t border-slate-100">
                <span class="text-slate-500">Ödenen:</span>
                <span :class="paymentTotal >= total ? 'text-green-600 font-semibold' : 'text-red-500 font-semibold'" x-text="formatPrice(paymentTotal) + ' ₺'"></span>
            </div>
            <div x-show="paymentTotal > total" class="flex justify-between text-xs text-green-600">
                <span>Para Üstü:</span>
                <span x-text="formatPrice(paymentTotal - total) + ' ₺'"></span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="px-4 pb-4 space-y-2">
            <button @click="completeSale()"
                :disabled="cart.length === 0 || paymentTotal < total"
                :class="cart.length === 0 || paymentTotal < total ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-600'"
                class="w-full bg-green-500 text-white font-semibold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2 shadow-md shadow-green-200">
                <i class="fas fa-check-circle"></i> Satışı Tamamla
            </button>
            <button @click="clearCart()"
                class="w-full border-2 border-red-400 text-red-500 hover:bg-red-50 font-medium py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-trash-alt"></i> Sepeti Temizle
            </button>
        </div>
    </div>
</div>

{{-- Currency Modal --}}
<div x-show="showCurrencyModal" x-cloak
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="showCurrencyModal = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-exchange-alt text-yellow-500"></i> Kur Ayarla
            </h3>
            <button @click="showCurrencyModal = false" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-4">1 birim yabancı para = kaç Türk Lirası?</p>

            <template x-for="(info, key) in currencyInfo" :key="key">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-sm" :class="info.bg" x-text="key"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700" x-text="info.name"></p>
                        <p class="text-xs text-slate-400" x-text="'Öneri: ' + (liveRates[key] ? formatPrice(liveRates[key]) + ' ₺' : 'yükleniyor...')"></p>
                    </div>
                    <input type="number" step="0.01" min="0"
                        x-model.number="tempRates[key]"
                        class="w-28 border border-slate-200 rounded-lg px-3 py-2 text-sm text-right outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
            </template>
        </div>

        <div class="p-5 border-t border-slate-100 flex gap-3">
            <button @click="fillWithLiveRates()"
                class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium py-2.5 rounded-xl text-sm transition-all">
                <i class="fas fa-sync-alt mr-1.5"></i> Güncel Kurları Doldur
            </button>
            <button @click="saveCurrencyRates()"
                class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-white font-semibold py-2.5 rounded-xl text-sm transition-all">
                <i class="fas fa-save mr-1.5"></i> Kaydet
            </button>
        </div>
    </div>
</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchResults: [],
        searching: false,
        cart: [],
        selectedCustomerId: '',
        discountPercent: 0,
        showCurrencyModal: false,
        now: '',
        rates: @json($rates->map(fn($r) => (float)$r->rate)),
        tempRates: @json($rates->map(fn($r) => (float)$r->rate)),
        liveRates: {},
        payments: { TL: 0, EURO: 0, DOLAR: 0, RUBLE: 0, PAUND: 0, KREDI_KARTI: 0, BANKA_HAVALE: 0 },
        paymentTypes: {
            TL: { label: 'Türk Lirası', currency: null },
            KREDI_KARTI: { label: 'Kredi Kartı', currency: null },
            BANKA_HAVALE: { label: 'Banka Havale', currency: null },
            EURO: { label: 'Euro (€)', currency: 'EUR' },
            DOLAR: { label: 'Dolar ($)', currency: 'USD' },
            PAUND: { label: 'Pound (£)', currency: 'GBP' },
            RUBLE: { label: 'Ruble (₽)', currency: 'RUB' },
        },
        currencyInfo: {
            EUR: { name: 'Euro', bg: 'bg-blue-100 text-blue-700' },
            USD: { name: 'Dolar', bg: 'bg-green-100 text-green-700' },
            GBP: { name: 'Pound', bg: 'bg-purple-100 text-purple-700' },
            RUB: { name: 'Ruble', bg: 'bg-red-100 text-red-700' },
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        },
        get discountAmount() {
            return this.subtotal * (this.discountPercent / 100);
        },
        get total() {
            return this.subtotal - this.discountAmount;
        },
        get paymentTotal() {
            let sum = 0;
            for (const [key, amount] of Object.entries(this.payments)) {
                if (amount > 0) {
                    const type = this.paymentTypes[key];
                    const rate = type.currency ? (this.rates[type.currency] || 1) : 1;
                    sum += amount * rate;
                }
            }
            return sum;
        },

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        updateClock() {
            this.now = new Date().toLocaleTimeString('tr-TR');
        },

        formatPrice(val) {
            return parseFloat(val || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async searchProducts() {
            if (!this.searchQuery.trim()) {
                this.searchResults = [];
                return;
            }
            this.searching = true;
            try {
                const resp = await fetch(`/api/products/search?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                this.searchResults = await resp.json();

                // Auto-add if exact barcode match
                if (this.searchResults.length === 1 && this.searchResults[0].barcode === this.searchQuery.trim()) {
                    this.addToCart(this.searchResults[0]);
                    this.searchQuery = '';
                    this.searchResults = [];
                }
            } catch(e) {}
            this.searching = false;
        },

        handleEnter() {
            if (this.searchResults.length === 1) {
                this.addToCart(this.searchResults[0]);
                this.searchQuery = '';
                this.searchResults = [];
            } else if (this.searchResults.length > 1) {
                this.addToCart(this.searchResults[0]);
                this.searchQuery = '';
                this.searchResults = [];
            }
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    barcode: product.barcode,
                    price: parseFloat(product.sale_price),
                    qty: 1,
                    unit: product.unit,
                });
            }
            this.updatePaymentTotal();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.updatePaymentTotal();
        },

        increaseQty(index) {
            this.cart[index].qty++;
            this.updatePaymentTotal();
        },

        decreaseQty(index) {
            if (this.cart[index].qty > 1) {
                this.cart[index].qty--;
            } else {
                this.removeFromCart(index);
            }
            this.updatePaymentTotal();
        },

        updateQty(index, val) {
            const qty = parseFloat(val);
            if (qty > 0) {
                this.cart[index].qty = qty;
            } else {
                this.removeFromCart(index);
            }
            this.updatePaymentTotal();
        },

        updatePaymentTotal() {
            // Auto-fill TL if only TL and total < 0.01
            if (this.paymentTotal < 0.01 && this.total > 0) {
                this.payments.TL = Math.round(this.total * 100) / 100;
            }
        },

        async applyDiscount() {
            const { value } = await Swal.fire({
                title: 'İndirim Tanımla',
                html: '<p class="text-sm text-gray-500 mb-3">Yüzde kaç indirim uygulanacak?</p>',
                input: 'number',
                inputValue: this.discountPercent || '',
                inputAttributes: { min: 0, max: 100, step: 0.5, placeholder: 'Örn: 10' },
                showCancelButton: true,
                confirmButtonText: 'Uygula',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#f97316',
                inputValidator: (v) => {
                    if (!v || v < 0 || v > 100) return 'Geçerli bir değer girin (0-100)';
                }
            });
            if (value !== undefined) {
                this.discountPercent = parseFloat(value);
                this.updatePaymentTotal();
            }
        },

        openCurrencyModal() {
            this.showCurrencyModal = true;
            this.fetchLiveRates();
        },

        async fetchLiveRates() {
            try {
                const resp = await fetch('https://api.exchangerate-api.com/v4/latest/TRY');
                const data = await resp.json();
                // API returns rates per TRY, we need TRY per foreign = 1/rate
                const r = data.rates;
                this.liveRates = {
                    EUR: r.EUR ? Math.round((1/r.EUR) * 100) / 100 : null,
                    USD: r.USD ? Math.round((1/r.USD) * 100) / 100 : null,
                    GBP: r.GBP ? Math.round((1/r.GBP) * 100) / 100 : null,
                    RUB: r.RUB ? Math.round((1/r.RUB) * 100) / 100 : null,
                };
            } catch(e) {
                // silently fail, show saved rates
            }
        },

        fillWithLiveRates() {
            for (const key of ['EUR', 'USD', 'GBP', 'RUB']) {
                if (this.liveRates[key]) {
                    this.tempRates[key] = this.liveRates[key];
                }
            }
        },

        async saveCurrencyRates() {
            try {
                const resp = await fetch('/sales/currency-rates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ rates: this.tempRates }),
                });
                const data = await resp.json();
                if (data.success) {
                    // Update active rates
                    this.rates = { ...this.tempRates };
                    this.showCurrencyModal = false;
                    Swal.fire({ icon: 'success', title: 'Kurlar güncellendi', timer: 1500, showConfirmButton: false });
                }
            } catch(e) {}
        },

        clearCart() {
            if (this.cart.length === 0) return;
            Swal.fire({
                title: 'Sepeti temizle?',
                text: 'Tüm ürünler kaldırılacak.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, temizle',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#ef4444',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.cart = [];
                    this.discountPercent = 0;
                    this.payments = { TL: 0, EURO: 0, DOLAR: 0, RUBLE: 0, PAUND: 0, KREDI_KARTI: 0, BANKA_HAVALE: 0 };
                }
            });
        },

        async completeSale() {
            if (this.cart.length === 0) return;
            if (this.paymentTotal < this.total - 0.01) {
                Swal.fire({ icon: 'error', title: 'Yetersiz ödeme', text: 'Lütfen tüm tutarı ödeyin.' });
                return;
            }

            const activePayments = Object.entries(this.payments)
                .filter(([k, v]) => v > 0)
                .map(([key, amount]) => {
                    const type = this.paymentTypes[key];
                    const rate = type.currency ? (this.rates[type.currency] || 1) : 1;
                    return { payment_type: key, amount, exchange_rate: rate };
                });

            const payload = {
                customer_id: this.selectedCustomerId || null,
                discount_percent: this.discountPercent,
                items: this.cart.map(i => ({
                    product_id: i.id,
                    product_name: i.name,
                    product_barcode: i.barcode,
                    unit_price: i.price,
                    quantity: i.qty,
                    unit: i.unit,
                })),
                payments: activePayments,
            };

            try {
                const resp = await fetch('/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await resp.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Satış Tamamlandı!',
                        html: `<p class="text-2xl font-bold text-indigo-700 mt-2">${this.formatPrice(this.total)} ₺</p>`,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    this.cart = [];
                    this.discountPercent = 0;
                    this.selectedCustomerId = '';
                    this.payments = { TL: 0, EURO: 0, DOLAR: 0, RUBLE: 0, PAUND: 0, KREDI_KARTI: 0, BANKA_HAVALE: 0 };
                    this.searchResults = [];
                    this.searchQuery = '';
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Satış tamamlanamadı.' });
                }
            } catch(e) {
                Swal.fire({ icon: 'error', title: 'Bağlantı hatası', text: 'Lütfen tekrar deneyin.' });
            }
        },
    }
}
</script>
</body>
</html>
