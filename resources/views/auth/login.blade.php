<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Satış POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-indigo-900/50">
                <i class="fas fa-cash-register text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Satış POS</h1>
            <p class="text-slate-400 text-sm mt-1">Hesabınıza giriş yapın</p>
        </div>

        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/10 shadow-2xl">
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-500/20 border border-red-500/30 text-red-200 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">E-posta</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-lg pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="ornek@email.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Şifre</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="password" name="password" required
                            class="w-full bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-lg pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="••••••••">
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-white/20 bg-white/10 text-indigo-500">
                    <span class="text-sm text-slate-300">Beni hatırla</span>
                </label>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 rounded-lg transition-all shadow-lg shadow-indigo-900/40 text-sm">
                    <i class="fas fa-sign-in-alt mr-2"></i> Giriş Yap
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-white/10 text-xs text-slate-500 text-center space-y-1">
                <p>Demo: admin@pos.com / password</p>
                <p>Şirket: company@pos.com / password</p>
            </div>
        </div>
    </div>
</body>
</html>
