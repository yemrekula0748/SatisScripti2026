<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — Pos Sistemi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #f8fafc inset !important;
            -webkit-text-fill-color: #1e293b !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex">

    {{-- Sol panel --}}
    <div class="hidden lg:flex w-1/2 bg-slate-800 flex-col justify-between p-12">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-cash-register text-white text-sm"></i>
            </div>
            <span class="text-white font-bold text-lg tracking-tight">Pos Sistemi</span>
        </div>
        <div>
            <blockquote class="text-slate-300 text-xl font-light leading-relaxed mb-6">
                "Satış süreçlerinizi hızlandırın,<br>stoklarınızı anlık takip edin."
            </blockquote>
            <div class="flex gap-6">
                <div>
                    <p class="text-white text-2xl font-bold">Hızlı</p>
                    <p class="text-slate-400 text-sm">Barkod okuyucu desteği</p>
                </div>
                <div class="w-px bg-slate-700"></div>
                <div>
                    <p class="text-white text-2xl font-bold">Güvenli</p>
                    <p class="text-slate-400 text-sm">Rol tabanlı yetki sistemi</p>
                </div>
                <div class="w-px bg-slate-700"></div>
                <div>
                    <p class="text-white text-2xl font-bold">Çoklu</p>
                    <p class="text-slate-400 text-sm">Şirket yönetimi</p>
                </div>
            </div>
        </div>
        <p class="text-slate-600 text-xs">&copy; {{ date('Y') }} Pos Sistemi. Tüm hakları saklıdır.</p>
    </div>

    {{-- Sağ panel (form) --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-sm">

            {{-- Mobil logo --}}
            <div class="flex items-center gap-3 mb-10 lg:hidden">
                <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cash-register text-white text-sm"></i>
                </div>
                <span class="text-slate-800 font-bold text-lg">Pos Sistemi</span>
            </div>

            <h2 class="text-2xl font-bold text-slate-800 mb-1">Hoş geldiniz</h2>
            <p class="text-slate-400 text-sm mb-8">Devam etmek için giriş yapın.</p>

            @if($errors->any())
            <div class="flex items-start gap-2.5 mb-6 p-3.5 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
                <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">E-posta Adresi</label>
                    <div class="relative">
                        <i class="fas fa-at absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                            class="w-full border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white"
                            placeholder="kullanici@sirket.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Şifre</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="password" name="password" required autocomplete="current-password"
                            class="w-full border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-600">Beni hatırla</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg transition-all text-sm flex items-center justify-center gap-2 mt-2">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Giriş Yap
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">Yetkisiz erişim yasaktır. Tüm işlemler kayıt altındadır.</p>
            </div>
        </div>
    </div>

</body>
</html>
