<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ayarlar - POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100" x-data="settingsApp()">

{{-- Header --}}
<header class="h-14 bg-slate-800 flex items-center justify-between px-5 shadow-lg">
    <div class="flex items-center gap-3">
        <a href="{{ route('sales.pos') }}" class="text-slate-400 hover:text-white transition-colors text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Geri (POS)
        </a>
        <span class="text-slate-600">|</span>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-yellow-500 rounded flex items-center justify-center">
                <i class="fas fa-sliders-h text-white text-xs"></i>
            </div>
            <span class="text-white font-semibold text-sm">Ayarlar</span>
        </div>
    </div>
    <div class="flex items-center gap-3 text-xs text-slate-400">
        <span>{{ auth()->user()->name }}</span>
        <span>|</span>
        <span>{{ auth()->user()->company->name ?? 'Demo Şirket' }}</span>
    </div>
</header>

<div class="max-w-4xl mx-auto p-6">
    {{-- Başarı Mesajı --}}
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600 text-lg"></i>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Ayarlar Kartı --}}
    <div class="bg-white rounded-2xl shadow-lg p-8 border border-slate-100">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3 mb-2">
                <i class="fas fa-cog text-yellow-500"></i> POS Ayarları
            </h1>
            <p class="text-sm text-slate-500">{{ $company->name }} şirketi için ayarları yapılandırın</p>
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Müşteri Alanı Göster/Gizle --}}
            <div class="border border-slate-200 rounded-xl p-6 bg-slate-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" 
                                    name="show_customer_field" 
                                    value="1"
                                    {{ $company->show_customer_field ? 'checked' : '' }}
                                    @change="showCustomerField = $el.checked"
                                    class="w-5 h-5 rounded border-2 border-slate-300 focus:ring-2 focus:ring-yellow-400 cursor-pointer accent-yellow-500">
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-slate-800 group-hover:text-slate-900">
                                    POS Ekranında Müşteri Alanını Göster
                                </p>
                                <p class="text-sm text-slate-600 mt-1">
                                    <span x-show="showCustomerField" class="text-green-600">
                                        <i class="fas fa-check mr-1"></i>Müşteri alanı görünecek (varsayılan: Misafir Müşteri)
                                    </span>
                                    <span x-show="!showCustomerField" class="text-orange-600">
                                        <i class="fas fa-times mr-1"></i>Müşteri alanı gizlenecek (sadece misafir müşteri)
                                    </span>
                                </p>
                                <p class="text-xs text-slate-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Sadece misafir müşterilere satış yapıyorsanız bu alanı kapatın.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Bilgi Kutusu --}}
            <div class="border border-blue-200 rounded-xl p-4 bg-blue-50">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                    <strong>Bilgi:</strong> Bu ayarlar POS ekranında müşteri seçimi alanının görünürlüğünü kontrol eder. 
                    Kapatıldığında müşteri alanı gizlenir ve satışlar otomatik olarak "Misafir Müşteri" olarak kaydedilir.
                </p>
            </div>

            {{-- Butonlar --}}
            <div class="flex gap-3 pt-6 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2 shadow-md shadow-yellow-200">
                    <i class="fas fa-save"></i> Ayarları Kaydet
                </button>
                <a href="{{ route('sales.pos') }}" class="px-6 bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-times"></i> İptal
                </a>
            </div>
        </form>
    </div>

    {{-- Ek Bilgi --}}
    <div class="mt-8 p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">
            <i class="fas fa-question-circle text-slate-400 mr-2"></i> Sık Sorulan Sorular
        </h3>
        <ul class="space-y-3 text-sm text-slate-600">
            <li>
                <strong>Müşteri alanı kapalıysa ne olur?</strong><br>
                Müşteri alanı POS ekranında görünmez ve tüm satışlar "Misafir Müşteri" olarak kaydedilir.
            </li>
            <li>
                <strong>Müşteri alanı açıksa ne olur?</strong><br>
                Müşteri alanı POS ekranında görünür ve kasiyer müşteri seçebilir veya "Misafir Müşteri" seçili bırakabilir.
            </li>
        </ul>
    </div>
</div>

<script>
function settingsApp() {
    return {
        showCustomerField: {{ $company->show_customer_field ? 'true' : 'false' }},
    }
}
</script>

</body>
</html>
