<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Superadmin Login - HRMS Enterprise India</title>

    <!-- Google Fonts: Google Sans (Open Sans / Plus Jakarta Sans fallbacks) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Google Sans"', '"Plus Jakarta Sans"', '"Open Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                        google: ['"Google Sans"', '"Plus Jakarta Sans"', '"Open Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Google Sans', 'Plus Jakarta Sans', 'Open Sans', ui-sans-serif, system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 via-slate-100 to-indigo-50/30 flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl shadow-indigo-500/5 border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[600px]">
        
        <!-- Left Side: Clean Professional Brand & Human Resource Management System Image -->
        <div class="lg:col-span-5 bg-gradient-to-br from-brand-900 via-brand-800 to-slate-950 p-8 sm:p-10 flex flex-col justify-between text-white relative overflow-hidden">
            <!-- Decorative Subtle Background Glow -->
            <div class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-brand-500/20 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-64 h-64 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-6">
                <!-- Brand Title -->
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-semibold text-indigo-200 mb-4">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>Enterprise Portal</span>
                    </div>
                    <h1 class="font-extrabold text-2xl sm:text-3xl tracking-tight leading-tight text-white">
                        Human Resource Management System
                    </h1>
                </div>

                <!-- Suitable High Quality HR & Workforce Illustration Image -->
                <div class="relative rounded-2xl overflow-hidden border border-white/15 shadow-2xl shadow-black/40 group">
                    <img 
                        src="{{ asset('images/hrms-login.jpg') }}" 
                        alt="Human Resource Management System" 
                        class="w-full h-64 sm:h-72 object-cover object-center group-hover:scale-105 transition-transform duration-500"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="relative z-10 pt-6 mt-6 border-t border-white/10 flex items-center justify-between text-xs text-indigo-200/80">
                <span>Enterprise Suite</span>
                <span>Secure SSL Encryption</span>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center bg-white">
            <div class="max-w-md w-full mx-auto">

                <!-- Header Title -->
                <div class="mb-8">
                    <h3 class="text-2xl font-bold text-slate-900 tracking-tight">Sign in to your account</h3>
                    <p class="text-sm text-slate-500 mt-1.5">Enter your Mobile Number (+91) or Email address to access the dashboard</p>
                </div>

                <!-- Error Alert -->
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p class="font-medium">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-center gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0"></i>
                        <p class="font-medium">{{ session('info') }}</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                    @csrf

                    <!-- Identifier Input (Email or Indian Mobile) -->
                    <div>
                        <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Mobile Number or Email
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="user-check" class="w-5 h-5"></i>
                            </div>
                            <input 
                                id="login" 
                                name="login" 
                                type="text" 
                                autocomplete="username" 
                                required 
                                value="{{ old('login') }}" 
                                placeholder="e.g. 9876543210 or user@company.com" 
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>
                        <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                            <span class="font-semibold text-slate-700">Supported:</span> 10-digit Indian Mobile or registered Email
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Password
                            </label>
                        </div>
                        <div class="relative rounded-xl shadow-xs">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="key-round" class="w-5 h-5"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password" 
                                required 
                                value="" 
                                placeholder="Enter your password" 
                                class="w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                            <button 
                                type="button" 
                                id="togglePasswordBtn" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            >
                                <i id="passwordEyeIcon" data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Device -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                value="1" 
                                checked 
                                class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer"
                            >
                            <span class="text-xs font-medium text-slate-600 select-none">Keep me signed in</span>
                        </label>
                        <span class="text-xs text-slate-400 font-medium">Enterprise SSL Protected</span>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-500/25 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2"
                    >
                        <span>Login</span>
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                    </button>
                </form>

            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();

        // Password Toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('passwordEyeIcon');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                lucide.createIcons();
            });
        }
    </script>
</body>
</html>
