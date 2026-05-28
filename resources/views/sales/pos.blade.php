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
    <div class="flex items-center gap-3 text-xs text-slate-400">
        <span>{{ auth()->user()->name }}</span>
        <span>|</span>
        <span>{{ auth()->user()->company->name ?? 'Demo Şirket' }}</span>
        <span>|</span>
        <span x-text="now" class="font-mono"></span>
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
                            @click="addToCart(product); closeDropdown()"
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

        {{-- Son Eklenen Ürünler (Sepet özeti sol tarafta) --}}
        <div class="flex-1 p-4 overflow-y-auto">
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full text-slate-300 select-none">
                <i class="fas fa-barcode text-7xl mb-4 opacity-30"></i>
                <p class="text-lg font-medium">Barkod okutun veya ürün adı yazın</p>
                <p class="text-sm mt-1 opacity-70">Ürünler otomatik sepete eklenecek</p>
            </div>

            <div x-show="cart.length > 0">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                    Sepete Eklenenler (<span x-text="cart.length"></span> ürün)
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-100 relative group">
                            <button @click="removeFromCart(index)"
                                class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mb-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                            </div>
                            <p class="text-xs font-semibold text-slate-800 leading-tight line-clamp-2 mb-1" x-text="item.name"></p>
                            <p class="text-xs text-indigo-600 font-bold" x-text="formatPrice(item.price * item.qty) + ' ₺'"></p>
                            <p class="text-xs text-slate-400" x-text="item.qty + ' ' + item.unit + ' × ' + formatPrice(item.price)"></p>
                        </div>
                    </template>
                </div>
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

        {{-- Müşteri --}}
        <div class="px-4 py-2.5 border-b border-slate-100">
            <select x-model="selectedCustomerId" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white text-slate-700 outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Misafir Müşteri</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' — '.$customer->phone : '' }}</option>
                @endforeach
            </select>
        </div>

        {{-- Sepet Kalemleri --}}
        <div class="cart-items flex-1 px-3 py-2">
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center py-10 text-slate-300">
                <i class="fas fa-shopping-basket text-3xl mb-2 opacity-40"></i>
                <p class="text-xs">Sepet boş</p>
            </div>
            <template x-for="(item, index) in cart" :key="index">
                <div class="flex items-center gap-2 py-2 border-b border-slate-50 last:border-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800 leading-tight truncate" x-text="item.name"></p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="formatPrice(item.price) + ' ₺'"></p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button @click="decreaseQty(index)"
                            class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-xs font-bold">
                            <i class="fas fa-minus" style="font-size:9px"></i>
                        </button>
                        <input type="number" x-model.number="item.qty" @change="updateQty(index, $event.target.value)"
                            class="w-10 text-center text-xs font-bold border border-slate-200 rounded py-1 outline-none focus:ring-1 focus:ring-indigo-300">
                        <button @click="increaseQty(index)"
                            class="w-6 h-6 flex items-center justify-center bg-indigo-100 hover:bg-indigo-200 text-indigo-600 rounded text-xs font-bold">
                            <i class="fas fa-plus" style="font-size:9px"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-xs font-bold text-slate-800 w-14 text-right" x-text="formatPrice(item.price * item.qty) + ' ₺'"></span>
                        <button @click="removeFromCart(index)" class="w-5 h-5 flex items-center justify-center text-slate-300 hover:text-red-500">
                            <i class="fas fa-times" style="font-size:10px"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Toplamlar --}}
        <div class="border-t border-slate-200 px-4 py-3 space-y-1.5 bg-slate-50">
            <div class="flex justify-between text-sm text-slate-600">
                <span>Ara Toplam:</span>
                <span x-text="formatPrice(subtotal) + ' ₺'"></span>
            </div>
            <div x-show="discountPercent > 0" class="flex justify-between text-sm text-orange-600 font-medium">
                <span>İndirim (<span x-text="discountPercent"></span>%):</span>
                <span>−<span x-text="formatPrice(discountAmount)"></span> ₺</span>
            </div>
            <div class="flex justify-between text-lg font-bold text-indigo-700 pt-1 border-t border-slate-200">
                <span>TOPLAM:</span>
                <span x-text="formatPrice(total) + ' ₺'"></span>
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
                <span :class="paymentTotal >= total && total > 0 ? 'text-green-600 font-bold' : 'text-red-500 font-bold'"
                      x-text="formatPrice(paymentTotal) + ' ₺'"></span>
            </div>
            <div x-show="paymentTotal > total && total > 0" class="flex justify-between text-xs text-green-600 font-medium">
                <span>Para Üstü:</span>
                <span x-text="formatPrice(paymentTotal - total) + ' ₺'"></span>
            </div>
        </div>

        {{-- Butonlar --}}
        <div class="px-4 pb-4 pt-2 space-y-2">
            <button @click="completeSale()"
                :disabled="cart.length === 0 || paymentTotal < total - 0.01"
                :class="cart.length === 0 || paymentTotal < total - 0.01 ? 'opacity-40 cursor-not-allowed bg-green-500' : 'bg-green-500 hover:bg-green-600 shadow-md shadow-green-200'"
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
                this.showDropdown = false;
                return;
            }
            try {
                const resp = await fetch(`/api/products/search?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                this.searchResults = await resp.json();
                this.showDropdown = true;
                this.highlighted = this.searchResults.length === 1 ? 0 : -1;

                // Tek sonuç ve barkod tam eşleşmesi → otomatik ekle
                if (this.searchResults.length === 1 && this.searchResults[0].barcode === this.searchQuery.trim()) {
                    this.addToCart(this.searchResults[0]);
                    this.closeDropdown();
                }
            } catch(e) {}
        },

        closeDropdown() {
            this.showDropdown = false;
            this.highlighted = -1;
        },

        highlightNext() {
            if (this.searchResults.length === 0) return;
            this.highlighted = Math.min(this.highlighted + 1, this.searchResults.length - 1);
        },

        highlightPrev() {
            this.highlighted = Math.max(this.highlighted - 1, 0);
        },

        handleEnter() {
            if (this.highlighted >= 0 && this.searchResults[this.highlighted]) {
                this.addToCart(this.searchResults[this.highlighted]);
            } else if (this.searchResults.length > 0) {
                this.addToCart(this.searchResults[0]);
            }
            this.closeDropdown();
            this.searchQuery = '';
            this.searchResults = [];
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
            this.autoFillPayment();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.autoFillPayment();
        },

        increaseQty(index) { this.cart[index].qty++; this.autoFillPayment(); },

        decreaseQty(index) {
            if (this.cart[index].qty > 1) { this.cart[index].qty--; }
            else { this.removeFromCart(index); return; }
            this.autoFillPayment();
        },

        updateQty(index, val) {
            const qty = parseFloat(val);
            if (qty > 0) { this.cart[index].qty = qty; }
            else { this.removeFromCart(index); return; }
            this.autoFillPayment();
        },

        autoFillPayment() {
            // Eğer sadece TL ödeme varsa otomatik doldur
            const otherPayments = Object.entries(this.payments).filter(([k,v]) => k !== 'TL' && v > 0);
            if (otherPayments.length === 0) {
                this.payments.TL = Math.round(this.total * 100) / 100;
            }
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
                this.autoFillPayment();
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ rates: this.tempRates }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.rates = { ...this.tempRates };
                    this.showCurrencyModal = false;
                    this.autoFillPayment();
                    Swal.fire({ icon: 'success', title: 'Kurlar kaydedildi!', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Kaydedilemedi.' });
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
            if (this.paymentTotal < this.total - 0.01) {
                Swal.fire({ icon: 'error', title: 'Yetersiz ödeme', text: `Eksik: ${this.formatPrice(this.total - this.paymentTotal)} ₺` });
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await resp.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Satış Tamamlandı!',
                        html: `<p class="text-3xl font-bold text-indigo-700 mt-1">${this.formatPrice(this.total)} ₺</p>`,
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
