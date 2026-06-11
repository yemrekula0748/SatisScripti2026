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
        html, body { height: 100%; overflow: hidden; }
        .pos-body { height: calc(100vh - 56px); }
        .cart-items { overflow-y: auto; max-height: calc(100vh - 430px); }
    </style>
</head>
<body class="bg-slate-100" x-data="posApp()">

{{-- Header --}}
<header class="h-14 bg-slate-800 flex items-center justify-between px-5 shadow-lg flex-shrink-0">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white transition-colors text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Geri
        </a>
        <span class="text-slate-600">|</span>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-indigo-600 rounded flex items-center justify-center">
                <i class="fas fa-cash-register text-white text-xs"></i>
            </div>
            <span class="text-white font-semibold text-sm">Satış Ekranı</span>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('settings.index') }}" title="Ayarlar"
            class="text-slate-400 hover:text-slate-300 transition-colors">
            <i class="fas fa-sliders-h"></i>
        </a>
        <div class="flex items-center gap-3 text-xs text-slate-400">
            <span>{{ auth()->user()->name }}</span>
            <span>|</span>
            <span>{{ auth()->user()->company->name ?? 'Demo Şirket' }}</span>
            <span>|</span>
            <span x-text="now" class="font-mono"></span>
        </div>
    </div>
</header>

<div class="pos-body flex">

    {{-- LEFT: Arama + Son eklenenler --}}
    <div class="flex-1 flex flex-col min-w-0 bg-slate-50">

        {{-- Arama Kutusu --}}
        <div class="p-4 bg-white shadow-sm border-b border-slate-200">
            <div class="relative">
                <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400 text-xl pointer-events-none"></i>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.250ms="searchProducts()"
                    @keydown.enter.prevent="handleEnter()"
                    @keydown.escape="closeDropdown()"
                    @keydown.arrow-down.prevent="highlightNext()"
                    @keydown.arrow-up.prevent="highlightPrev()"
                    x-ref="searchInput"
                    placeholder="Ürün adı yazın veya barkod okutun..."
                    class="w-full border-2 border-indigo-300 focus:border-indigo-500 rounded-xl pl-14 pr-10 py-4 text-lg font-medium outline-none transition-all bg-white shadow-inner"
                    autocomplete="off"
                >
                <button x-show="searchQuery" @click="searchQuery=''; searchResults=[]; showDropdown=false; $refs.searchInput.focus()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>

                {{-- Dropdown Sonuçlar --}}
                <div x-show="showDropdown && searchResults.length > 0" x-cloak
                    @click.away="closeDropdown()"
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 overflow-hidden max-h-72 overflow-y-auto">
                    <template x-for="(product, index) in searchResults" :key="product.id">
                        <div
                            @click="selectProduct(product)"
                            @mouseenter="highlighted = index"
                            :class="highlighted === index ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'border-l-4 border-transparent hover:bg-slate-50'"
                            class="flex items-center justify-between px-4 py-3 cursor-pointer transition-all border-b border-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-box text-indigo-500 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800" x-text="product.name"></p>
                                    <p class="text-xs text-slate-400" x-text="product.barcode ? ('Barkod: ' + product.barcode) : 'Barkod yok'"></p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-sm font-bold text-indigo-600" x-text="formatPrice(product.sale_price) + ' ₺'"></p>
                                <p class="text-xs text-slate-400" x-text="'Stok: ' + product.stock + ' ' + product.unit"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Sonuç yok --}}
                <div x-show="showDropdown && searchResults.length === 0 && searchQuery.length > 0" x-cloak
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-50 px-4 py-5 text-center text-slate-400 text-sm">
                    <i class="fas fa-search mb-2 opacity-40 text-lg"></i>
                    <p>"<span x-text="searchQuery"></span>" için ürün bulunamadı</p>
                </div>
            </div>
        </div>

        {{-- Sepet Tablosu --}}
        <div class="flex-1 overflow-y-auto">
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full text-slate-300 select-none">
                <i class="fas fa-barcode text-7xl mb-4 opacity-30"></i>
                <p class="text-lg font-medium">Barkod okutun veya ürün adı yazın</p>
                <p class="text-sm mt-1 opacity-70">Ürünler otomatik sepete eklenecek</p>
            </div>

            <div x-show="cart.length > 0" class="h-full flex flex-col">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-slate-100 sticky top-0 z-10">
                        <tr>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 w-8">#</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Ürün Adı</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500 w-24">Birim Fiyat</th>
                            <th class="text-center px-3 py-2 text-xs font-semibold text-slate-500 w-32">Miktar</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500 w-24">Toplam</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in cart" :key="index">
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2 text-xs text-slate-400 font-medium" x-text="index + 1"></td>
                                <td class="px-3 py-2">
                                    <p class="text-sm font-semibold text-slate-800 leading-tight" x-text="item.name"></p>
                                    <p class="text-xs text-slate-400" x-text="item.unit"></p>
                                </td>
                                <td class="px-3 py-2 text-right text-sm text-slate-600 whitespace-nowrap" x-text="formatPrice(item.price) + ' ₺'"></td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="item.qty > 0.001 ? item.qty = Math.round((item.qty - 1) * 1000) / 1000 : removeFromCart(index)"
                                            class="w-7 h-7 flex items-center justify-center bg-slate-200 hover:bg-red-100 hover:text-red-600 rounded-lg text-slate-600 font-bold text-sm transition-colors">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" x-model.number="item.qty" @change="item.qty <= 0 && removeFromCart(index)" min="0.001" step="0.001"
                                            class="w-14 text-center text-sm font-semibold border border-slate-200 rounded-lg py-1 outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                                        <button @click="item.qty = Math.round((item.qty + 1) * 1000) / 1000"
                                            class="w-7 h-7 flex items-center justify-center bg-slate-200 hover:bg-green-100 hover:text-green-600 rounded-lg text-slate-600 font-bold text-sm transition-colors">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-indigo-600 whitespace-nowrap" x-text="formatPrice(item.price * item.qty) + ' ₺'"></td>
                                <td class="px-3 py-2 text-center">
                                    <button @click="removeFromCart(index)"
                                        class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- RIGHT: Sepet --}}
    <div class="w-96 bg-white border-l border-slate-200 flex flex-col shadow-xl flex-shrink-0">

        {{-- Sepet Başlık --}}
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-shopping-cart text-slate-500 text-sm"></i>
                <span class="font-semibold text-slate-700 text-sm">Sepet</span>
                <span class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="cart.length"></span>
            </div>
            <div class="flex gap-1.5">
                <button @click="openCurrencyModal()" title="Kur Ayarla"
                    class="px-2.5 py-1.5 bg-yellow-100 text-yellow-700 hover:bg-yellow-200 rounded-lg text-xs font-medium transition-all flex items-center gap-1">
                    <i class="fas fa-dollar-sign text-xs"></i> Kur
                </button>
                <button @click="applyDiscount()" title="İndirim"
                    class="px-2.5 py-1.5 bg-orange-100 text-orange-700 hover:bg-orange-200 rounded-lg text-xs font-medium transition-all flex items-center gap-1">
                    <i class="fas fa-percent text-xs"></i> İndirim
                </button>
            </div>
        </div>



        {{-- Ürün sayısı özeti --}}
        <div class="px-4 py-2 border-b border-slate-100 bg-slate-50">
            <p class="text-xs text-slate-500">
                <span x-show="cart.length === 0">Sepet boş</span>
                <span x-show="cart.length > 0"><span class="font-bold text-indigo-700" x-text="cart.length"></span> çeşit ürün</span>
            </p>
        </div>

        {{-- Toplamlar --}}
        <div class="border-t border-slate-200 px-4 py-3 space-y-2 bg-slate-50">
            <div x-show="discountPercent > 0" class="flex justify-between text-sm text-orange-600 font-medium">
                <span>İndirim (<span x-text="discountPercent"></span>%):</span>
                <span>−<span x-text="formatPrice(discountAmount)"></span> ₺</span>
            </div>
            <div class="flex justify-between text-lg font-bold text-indigo-700 pt-1 border-t border-slate-200">
                <span>TOPLAM:</span>
                <span x-text="formatPrice(total) + ' ₺'"></span>
            </div>
            
            {{-- Para Birimlerinde Toplamlar --}}
            <div class="pt-2 border-t border-slate-200 space-y-1">
                <p class="text-xs font-semibold text-slate-500 uppercase">Para Birimlerinde:</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white rounded p-2 border border-slate-100">
                        <p class="text-slate-500">Euro (€)</p>
                        <p class="font-bold text-slate-800" x-text="rates['EUR'] ? formatPrice(total / rates['EUR']) : '—'"></p>
                    </div>
                    <div class="bg-white rounded p-2 border border-slate-100">
                        <p class="text-slate-500">Dolar ($)</p>
                        <p class="font-bold text-slate-800" x-text="rates['USD'] ? formatPrice(total / rates['USD']) : '—'"></p>
                    </div>
                    <div class="bg-white rounded p-2 border border-slate-100">
                        <p class="text-slate-500">Pound (£)</p>
                        <p class="font-bold text-slate-800" x-text="rates['GBP'] ? formatPrice(total / rates['GBP']) : '—'"></p>
                    </div>
                    <div class="bg-white rounded p-2 border border-slate-100">
                        <p class="text-slate-500">Ruble (₽)</p>
                        <p class="font-bold text-slate-800" x-text="rates['RUB'] ? formatPrice(total / rates['RUB']) : '—'"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ödeme --}}
        <div class="px-4 py-2 border-t border-slate-200 space-y-1.5">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ödeme Tipi</p>
            <template x-for="(pType, pKey) in paymentTypes" :key="pKey">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-600 w-24 flex-shrink-0" x-text="pType.label"></label>
                    <input type="number" step="0.01" min="0"
                        x-model.number="payments[pKey]"
                        class="flex-1 border border-slate-200 rounded-lg px-2 py-1 text-xs outline-none focus:ring-2 focus:ring-indigo-300 text-right"
                        placeholder="0.00">
                    <span x-show="pType.currency && payments[pKey] > 0"
                        class="text-xs text-slate-400 w-16 text-right flex-shrink-0"
                        x-text="'≈' + formatPrice(payments[pKey] * (rates[pType.currency] || 1)) + '₺'"></span>
                </div>
            </template>
            <div class="flex justify-between text-xs pt-1 border-t border-slate-100">
                <span class="text-slate-500">Ödenen:</span>
                <span :class="hasPaymentShortage ? 'text-amber-600 font-bold' : 'text-green-600 font-bold'"
                      x-text="formatPrice(paymentTotal) + ' ₺'"></span>
            </div>
            <div x-show="hasPaymentShortage" class="flex justify-between text-xs text-amber-600 font-medium">
                <span>Eksik Tahsilat:</span>
                <span x-text="formatPrice(paymentShortage) + ' ₺'"></span>
            </div>
            <div x-show="hasPaymentChange" class="flex justify-between text-xs text-green-600 font-medium">
                <span>Para Üstü:</span>
                <span x-text="formatPrice(paymentChange) + ' ₺'"></span>
            </div>
        </div>

        {{-- Butonlar --}}
        <div class="px-4 pb-4 pt-2 space-y-2">
            <button @click="completeSale()"
                :disabled="cart.length === 0"
                :class="cart.length === 0 ? 'opacity-40 cursor-not-allowed bg-green-500' : hasPaymentShortage ? 'bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-200' : 'bg-green-500 hover:bg-green-600 shadow-md shadow-green-200'"
                class="w-full text-white font-semibold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-check-circle"></i> Satışı Tamamla
            </button>
            <button @click="clearCart()"
                class="w-full border-2 border-red-300 text-red-500 hover:bg-red-50 font-medium py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-trash-alt"></i> Sepeti Temizle
            </button>
        </div>
    </div>
</div>

{{-- Kur Modalı --}}
<div x-show="showCurrencyModal" x-cloak
    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="showCurrencyModal = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-exchange-alt text-yellow-500"></i> Döviz Kurları
            </h3>
            <button @click="showCurrencyModal = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-500 bg-blue-50 rounded-lg p-2.5">
                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                1 birim yabancı para kaç Türk Lirası eder?
            </p>
            <template x-for="(info, key) in currencyInfo" :key="key">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-sm"
                         :class="info.bg" x-text="key"></div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-700" x-text="info.name"></p>
                        <p class="text-xs text-slate-400">
                            Öneri: <span x-text="liveRates[key] ? formatPrice(liveRates[key]) + ' ₺' : 'yükleniyor...'" class="font-medium"></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="number" step="0.01" min="0"
                            x-model.number="tempRates[key]"
                            class="w-24 border border-slate-200 rounded-lg px-3 py-2 text-sm text-right outline-none focus:ring-2 focus:ring-yellow-400 font-semibold">
                        <span class="text-xs text-slate-400">₺</span>
                    </div>
                </div>
            </template>
        </div>
        <div class="p-4 border-t border-slate-100 flex gap-2">
            <button @click="fillWithLiveRates()"
                :disabled="Object.keys(liveRates).length === 0"
                class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium py-2.5 rounded-xl text-sm transition-all disabled:opacity-40">
                <i class="fas fa-sync-alt mr-1"></i> Güncel Kurları Doldur
            </button>
            <button @click="saveCurrencyRates()"
                class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-2.5 rounded-xl text-sm transition-all shadow-md shadow-yellow-200">
                <i class="fas fa-save mr-1"></i> Kaydet & Uygula
            </button>
        </div>
    </div>
</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchResults: [],
        showDropdown: false,
        highlighted: -1,
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
            TL:          { label: 'Türk Lirası',  currency: null },
            KREDI_KARTI: { label: 'Kredi Kartı',  currency: null },
            BANKA_HAVALE:{ label: 'Banka Havale', currency: null },
            EURO:        { label: 'Euro (€)',      currency: 'EUR' },
            DOLAR:       { label: 'Dolar ($)',     currency: 'USD' },
            PAUND:       { label: 'Pound (£)',     currency: 'GBP' },
            RUBLE:       { label: 'Ruble (₽)',     currency: 'RUB' },
        },
        currencyInfo: {
            EUR: { name: 'Euro',   bg: 'bg-blue-100 text-blue-700'   },
            USD: { name: 'Dolar',  bg: 'bg-green-100 text-green-700' },
            GBP: { name: 'Pound',  bg: 'bg-purple-100 text-purple-700'},
            RUB: { name: 'Ruble',  bg: 'bg-red-100 text-red-700'     },
        },
        searchRequestId: 0,
        scannerBuffer: '',
        scannerStartedAt: 0,
        scannerLastKeyAt: 0,
        scannerMaxGapMs: 80,
        scannerMaxDurationMs: 1500,
        scannerMinLength: 6,
        scannerSourceTarget: null,
        scannerSourceValue: null,
        globalScannerHandler: null,

        get subtotal() { return this.cart.reduce((s, i) => s + i.price * i.qty, 0); },
        get discountAmount() { return this.subtotal * (this.discountPercent / 100); },
        get total() { return this.subtotal - this.discountAmount; },
        get paymentTotal() {
            let sum = 0;
            for (const [key, amount] of Object.entries(this.payments)) {
                if (amount > 0) {
                    const type = this.paymentTypes[key];
                    const rate = type.currency ? (this.rates[type.currency] || 1) : 1;
                    sum += amount * rate;
                }
            }
            return Math.round(sum * 100) / 100;
        },
        get paymentShortage() {
            return Math.max(Math.round((this.total - this.paymentTotal) * 100) / 100, 0);
        },
        get paymentChange() {
            return Math.max(Math.round((this.paymentTotal - this.total) * 100) / 100, 0);
        },
        get hasPaymentShortage() {
            return this.total > 0 && this.paymentShortage > 0.009;
        },
        get hasPaymentChange() {
            return this.total > 0 && this.paymentChange > 0.009;
        },

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.$nextTick(() => this.$refs.searchInput?.focus());
            this.globalScannerHandler = this.handleGlobalScannerKeydown.bind(this);
            window.addEventListener('keydown', this.globalScannerHandler, true);
        },

        updateClock() {
            this.now = new Date().toLocaleTimeString('tr-TR');
        },

        formatPrice(val) {
            return parseFloat(val || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        normalizeQuery(value = '') {
            return String(value ?? '').trim();
        },

        async searchProducts(options = {}) {
            const {
                query = this.searchQuery,
                autoSelectExact = false,
            } = options;

            const normalizedQuery = this.normalizeQuery(query);

            if (!normalizedQuery) {
                this.searchResults = [];
                this.showDropdown = false;
                return { results: [], exactMatch: null };
            }

            this.searchQuery = normalizedQuery;
            const requestId = ++this.searchRequestId;

            try {
                const resp = await fetch(`/api/products/search?q=${encodeURIComponent(normalizedQuery)}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const results = await resp.json();

                if (requestId !== this.searchRequestId) {
                    return { results: [], exactMatch: null, stale: true };
                }

                this.searchResults = results;
                this.showDropdown = true;
                this.highlighted = this.searchResults.length === 1 ? 0 : -1;

                const exactMatch = this.searchResults.find(product =>
                    this.normalizeQuery(product.barcode) === normalizedQuery
                ) ?? null;

                if (autoSelectExact && exactMatch) {
                    this.selectProduct(exactMatch);
                }

                return { results: this.searchResults, exactMatch };
            } catch(e) {
                return { results: [], exactMatch: null, error: e };
            }
        },

        closeDropdown() {
            this.showDropdown = false;
            this.highlighted = -1;
        },

        resetSearchState(options = {}) {
            const { keepFocus = false } = options;
            this.searchQuery = '';
            this.searchResults = [];
            this.closeDropdown();

            if (keepFocus) {
                this.$nextTick(() => this.$refs.searchInput?.focus());
            }
        },

        highlightNext() {
            if (this.searchResults.length === 0) return;
            this.highlighted = Math.min(this.highlighted + 1, this.searchResults.length - 1);
        },

        highlightPrev() {
            this.highlighted = Math.max(this.highlighted - 1, 0);
        },

        async handleEnter() {
            const normalizedQuery = this.normalizeQuery(this.searchQuery);
            if (!normalizedQuery) return;

            if (this.searchResults.length === 0) {
                const { exactMatch, results } = await this.searchProducts({
                    query: normalizedQuery,
                    autoSelectExact: true,
                });

                if (exactMatch) {
                    return;
                }

                if (!results.length) {
                    return;
                }
            }

            if (this.highlighted >= 0 && this.searchResults[this.highlighted]) {
                this.selectProduct(this.searchResults[this.highlighted]);
            } else if (this.searchResults.length > 0) {
                this.selectProduct(this.searchResults[0]);
            }
        },

        isPrintableScannerKey(event) {
            return event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey;
        },

        resetScannerBuffer() {
            this.scannerBuffer = '';
            this.scannerStartedAt = 0;
            this.scannerLastKeyAt = 0;
            this.scannerSourceTarget = null;
            this.scannerSourceValue = null;
        },

        isScannerSequence(now = Date.now()) {
            if (!this.scannerBuffer) return false;

            return this.scannerBuffer.length >= this.scannerMinLength
                && (now - this.scannerStartedAt) <= this.scannerMaxDurationMs;
        },

        isEditableElement(element) {
            if (!element || element === this.$refs.searchInput) return false;

            const tagName = element.tagName;
            return element.isContentEditable || tagName === 'INPUT' || tagName === 'TEXTAREA';
        },

        restoreScannerSourceTarget() {
            if (!this.isEditableElement(this.scannerSourceTarget)) {
                return;
            }

            if ('value' in this.scannerSourceTarget) {
                this.scannerSourceTarget.value = this.scannerSourceValue ?? '';
                this.scannerSourceTarget.dispatchEvent(new Event('input', { bubbles: true }));
            } else if (this.scannerSourceTarget.isContentEditable) {
                this.scannerSourceTarget.textContent = this.scannerSourceValue ?? '';
            }
        },

        async processScannerInput(scannedValue) {
            const normalizedQuery = this.normalizeQuery(scannedValue);
            if (!normalizedQuery) return;

            this.searchQuery = normalizedQuery;
            this.$nextTick(() => this.$refs.searchInput?.focus());

            const { exactMatch, results } = await this.searchProducts({
                query: normalizedQuery,
                autoSelectExact: true,
            });

            if (exactMatch) {
                return;
            }

            if (results.length === 1) {
                this.selectProduct(results[0]);
            }
        },

        handleGlobalScannerKeydown(event) {
            if (this.showCurrencyModal) {
                this.resetScannerBuffer();
                return;
            }

            if (event.target === this.$refs.searchInput) {
                return;
            }

            const now = Date.now();

            if (this.isPrintableScannerKey(event)) {
                const isContinuation = !this.scannerBuffer || (now - this.scannerLastKeyAt) <= this.scannerMaxGapMs;

                if (!isContinuation) {
                    this.scannerBuffer = '';
                    this.scannerStartedAt = now;
                    this.scannerSourceTarget = null;
                    this.scannerSourceValue = null;
                }

                if (!this.scannerBuffer) {
                    this.scannerStartedAt = now;
                    this.scannerSourceTarget = event.target;
                    this.scannerSourceValue = this.isEditableElement(event.target)
                        ? ('value' in event.target ? event.target.value : event.target.textContent)
                        : null;
                }

                this.scannerBuffer += event.key;
                this.scannerLastKeyAt = now;
                return;
            }

            if ((event.key === 'Enter' || event.key === 'Tab') && this.isScannerSequence(now)) {
                const scannedValue = this.scannerBuffer;
                this.restoreScannerSourceTarget();
                this.resetScannerBuffer();
                event.preventDefault();
                event.stopPropagation();
                this.processScannerInput(scannedValue);
                return;
            }

            if (!['Shift', 'Control', 'Alt', 'Meta'].includes(event.key)) {
                this.resetScannerBuffer();
            }
        },

        selectProduct(product) {
            this.addToCart(product);
            this.resetSearchState({ keepFocus: true });
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
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        increaseQty(index) { this.cart[index].qty++; },

        decreaseQty(index) {
            if (this.cart[index].qty > 1) { this.cart[index].qty--; }
            else { this.removeFromCart(index); return; }
        },

        updateQty(index, val) {
            const qty = parseFloat(val);
            if (qty > 0) { this.cart[index].qty = qty; }
            else { this.removeFromCart(index); return; }
        },

        autoFillPayment() {
            // Artık otomatik doldurma yapılmıyor - kasiyer manuel girecek
        },

        async applyDiscount() {
            const { value } = await Swal.fire({
                title: 'İndirim Tanımla',
                input: 'number',
                inputValue: this.discountPercent || '',
                inputAttributes: { min: 0, max: 100, step: 0.5, placeholder: 'Örn: 10' },
                inputLabel: 'Yüzde kaç indirim?',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Uygula',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#f97316',
                inputValidator: v => (!v || v < 0 || v > 100) ? '0-100 arası bir değer girin' : null
            });
            if (value !== undefined) {
                this.discountPercent = parseFloat(value);
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
                const r = data.rates;
                this.liveRates = {
                    EUR: r.EUR ? parseFloat((1/r.EUR).toFixed(4)) : null,
                    USD: r.USD ? parseFloat((1/r.USD).toFixed(4)) : null,
                    GBP: r.GBP ? parseFloat((1/r.GBP).toFixed(4)) : null,
                    RUB: r.RUB ? parseFloat((1/r.RUB).toFixed(4)) : null,
                };
            } catch(e) {}
        },

        fillWithLiveRates() {
            for (const key of ['EUR','USD','GBP','RUB']) {
                if (this.liveRates[key]) this.tempRates[key] = this.liveRates[key];
            }
        },

        async saveCurrencyRates() {
            try {
                const resp = await fetch('/sales/currency-rates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ rates: this.tempRates }),
                });
                const text = await resp.text();
                let data;
                try { data = JSON.parse(text); }
                catch(e) {
                    console.error('Sunucu yanıtı:', text.substring(0, 300));
                    Swal.fire({ icon: 'error', title: 'Sunucu Hatası', text: 'HTTP ' + resp.status + ' — Konsolu kontrol edin.' });
                    return;
                }
                if (data.success) {
                    this.rates = { ...this.tempRates };
                    this.showCurrencyModal = false;
                    Swal.fire({ icon: 'success', title: 'Kurlar kaydedildi!', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: data.message || JSON.stringify(data.errors) || 'Kaydedilemedi.' });
                }
            } catch(e) {
                Swal.fire({ icon: 'error', title: 'Bağlantı hatası', text: e.message });
            }
        },

        clearCart() {
            if (!this.cart.length) return;
            Swal.fire({
                title: 'Sepeti temizle?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, temizle',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#ef4444',
            }).then(r => {
                if (r.isConfirmed) {
                    this.cart = [];
                    this.discountPercent = 0;
                    this.payments = { TL: 0, EURO: 0, DOLAR: 0, RUBLE: 0, PAUND: 0, KREDI_KARTI: 0, BANKA_HAVALE: 0 };
                }
            });
        },

        async completeSale() {
            if (!this.cart.length) return;

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
                    product_id: i.id, product_name: i.name, product_barcode: i.barcode,
                    unit_price: i.price, quantity: i.qty, unit: i.unit,
                })),
                payments: activePayments,
            };

            try {
                const resp = await fetch('/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await resp.json();

                if (data.success) {
                    const totalPaid = this.paymentTotal;
                    const change = this.paymentChange;
                    const shortage = this.paymentShortage;
                    const paymentLabels = {
                        TL: 'Türk Lirası', EURO: 'Euro €', DOLAR: 'Dolar $',
                        RUBLE: 'Ruble ₽', PAUND: 'Pound £',
                        KREDI_KARTI: 'Kredi Kartı', BANKA_HAVALE: 'Banka Havale',
                    };
                    const payRows = activePayments.length
                        ? activePayments.map(p => {
                            const label = paymentLabels[p.payment_type] || p.payment_type;
                            const inTl = p.amount * p.exchange_rate;
                            if (p.exchange_rate === 1) {
                                return `<tr><td class="text-left text-slate-600 py-0.5">${label}</td><td class="text-right font-semibold pl-4">${this.formatPrice(p.amount)} ₺</td></tr>`;
                            }

                            return `<tr><td class="text-left text-slate-600 py-0.5">${label}</td><td class="text-right font-semibold pl-4">${this.formatPrice(p.amount)} (≈${this.formatPrice(inTl)} ₺)</td></tr>`;
                        }).join('')
                        : '<tr><td class="text-left text-slate-400 py-0.5">Ödeme</td><td class="text-right text-slate-500 pl-4">Kayıt girilmedi</td></tr>';
                    const totalPaidRow = `<tr class="border-t border-slate-200"><td class="text-left text-slate-600 font-semibold pt-1">Ödenen Toplam</td><td class="text-right font-bold text-slate-800 pl-4 pt-1">${this.formatPrice(totalPaid)} ₺</td></tr>`;
                    const shortageRow = shortage > 0.009 ? `<tr><td class="text-left text-amber-600 font-semibold pt-1">Eksik Tahsilat</td><td class="text-right font-bold text-amber-600 pl-4 pt-1">${this.formatPrice(shortage)} ₺</td></tr>` : '';
                    const changeRow = change > 0.009 ? `<tr><td class="text-left text-green-600 font-semibold pt-1">Para Üstü</td><td class="text-right font-bold text-green-600 pl-4 pt-1">${this.formatPrice(change)} ₺</td></tr>` : '';
                    await Swal.fire({
                        icon: 'success',
                        title: 'Satış Tamamlandı!',
                        html: `
                            <p class="text-2xl font-bold text-indigo-700 mb-3">${this.formatPrice(this.total)} ₺</p>
                            <table class="w-full text-sm">${payRows}${totalPaidRow}${shortageRow}${changeRow}</table>
                        `,
                        confirmButtonText: '<i class="fas fa-check"></i> Tamam',
                        confirmButtonColor: '#4f46e5',
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
