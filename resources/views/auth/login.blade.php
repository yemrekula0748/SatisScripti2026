<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — Satış POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        .bg-pattern {
            background-color: #0f172a;
            background-image: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(99,102,241,0.25) 0%, transparent 70%);
        }
        .glass {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px rgba(30,41,59,0.9) inset !important;
            -webkit-text-fill-color: #f1f5f9 !important;
            caret-color: #f1f5f9;
        }
    </style>
</head>
<body class="min-h-screen bg-pattern flex items-center justify-center p-4">

    {{-- Sol dekoratif çizgiler --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-800/10 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-sm relative z-10">

        {{-- Logo + Başlık --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl shadow-2xl shadow-indigo-900/60 mb-5 ring-1 ring-indigo-500/50">
                <i class="fas fa-cash-register text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Satış POS</h1>
            <p class="text-slate-400 text-sm mt-1.5 tracking-wide">Yönetim Paneli</p>
        </div>

        {{-- Form Kartı --}}
        <div class="glass rounded-2xl border border-white/8 shadow-2xl overflow-hidden">

            <div class="px-8 pt-8 pb-2">
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-5">Hesap Girişi</p>

                @if($errors->any())
                <div class="flex items-start gap-2.5 mb-5 p-3.5 bg-red-500/15 border border-red-500/25 rounded-xl text-red-300 text-sm">
                    <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">E-posta Adresi</label>
                        <div class="relative">
                            <i class="fas fa-at absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                class="w-full bg-slate-800/60 border border-slate-700/60 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/50 transition-all"
                                placeholder="kullanici@sirket.com">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Şifre</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                            <input type="password" name="password" required autocomplete="current-password"
                                class="w-full bg-slate-800/60 border border-slate-700/60 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/50 transition-all"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                            <span class="text-sm text-slate-400">Beni hatırla</span>
                        </label>
                    </div>

                    <div class="pt-2 pb-2">
                        <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition-all duration-150 shadow-lg shadow-indigo-900/50 text-sm tracking-wide flex items-center justify-center gap-2">
                            <i class="fas fa-arrow-right-to-bracket text-sm"></i>
                            Giriş Yap
                        </button>
                    </div>
                </form>
            </div>

            <div class="px-8 py-4 bg-slate-900/40 border-t border-white/5 text-center">
                <p class="text-xs text-slate-600">Yetkisiz erişim yasaktır. Tüm işlemler kayıt altındadır.</p>
            </div>
        </div>

        <p class="text-center text-xs text-slate-700 mt-6">
            &copy; {{ date('Y') }} Satış POS — Tüm hakları saklıdır.
        </p>
    </div>

</body>
</html>
