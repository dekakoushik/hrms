<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HRMS Portal') - Enterprise HRMS India</title>

    <!-- Google Fonts: Inter & Plus Jakarta Sans for crisp modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        heading: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
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
                    },
                    boxShadow: {
                        'premium': '0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03)',
                        'glow': '0 0 20px -5px rgba(79, 70, 229, 0.35)',
                        'card': '0 1px 3px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04)',
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons (deferred for non-blocking page render) -->
    <script src="https://unpkg.com/lucide@latest" defer></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', Inter, system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        /* Glassmorphism and UI polish */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .stat-card-gradient {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -6px rgba(79, 70, 229, 0.08), 0 4px 8px -4px rgba(15, 23, 42, 0.04);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="h-full flex flex-col antialiased text-slate-800 bg-slate-50 font-sans selection:bg-brand-500 selection:text-white">

    <!-- Main Container -->
    <div class="min-h-full flex flex-col">

        <!-- Top Navigation Bar -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/90 sticky top-0 z-40 shadow-xs">
            <div class="px-3 sm:px-5 lg:px-6">
                <div class="flex items-center justify-between h-14">
                    
                    <!-- Left: Mobile menu button & Brand -->
                    <div class="flex items-center space-x-2.5 sm:space-x-3 shrink-0">
                        <button id="mobileMenuBtn" type="button" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 focus:outline-none transition-colors">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>

                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 group">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-brand-700 via-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-sm shadow-brand-500/25 group-hover:scale-105 transition-transform duration-200">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1.5 leading-none">
                                    <span class="font-bold text-slate-900 text-sm tracking-tight group-hover:text-brand-600 transition-colors">
                                        HRMS Enterprise
                                    </span>
                                    <span class="text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/90 px-1.5 py-0.5 rounded tracking-wide uppercase">
                                        India
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 font-medium leading-tight mt-0.5">Workforce Suite</span>
                            </div>
                        </a>
                    </div>

                    <!-- Center: Global Company Filter & Indian Region Indicator -->
                    <div class="hidden md:flex items-center gap-2 sm:gap-2.5">
                        <!-- GLOBAL COMPANY SELECTOR (ALL OR PARTICULAR) -->
                        <div class="relative">
                            <form id="globalCompanyFilterForm" method="POST" action="{{ route('companies.switch') }}" class="flex items-center m-0">
                                @csrf
                                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50/80 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 transition-all">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-brand-600 shrink-0"></i>
                                    <span class="text-slate-400 font-normal hidden lg:inline">Entity:</span>
                                    <select 
                                        name="company_id" 
                                        onchange="document.getElementById('globalCompanyFilterForm').submit()" 
                                        class="bg-transparent border-none text-xs font-medium text-slate-800 focus:outline-none focus:ring-0 cursor-pointer pr-3 py-0"
                                    >
                                        <option value="all" {{ empty($activeCompanyId) ? 'selected' : '' }}>
                                            🏢 All Companies (Combined)
                                        </option>
                                        @foreach ($globalCompanies as $gComp)
                                            <option value="{{ $gComp->id }}" {{ $activeCompanyId == $gComp->id ? 'selected' : '' }}>
                                                {{ $gComp->name }} ({{ $gComp->code ?: 'Unit' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>

                        <!-- Center: Indian Region Indicator & Time -->
                        <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 bg-slate-50/80 border border-slate-200/90 rounded-lg text-xs text-slate-600">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="font-medium">India (IST)</span>
                            <span class="text-slate-300">|</span>
                            <span id="liveIndianTime" class="font-semibold text-slate-800 font-mono text-xs"></span>
                        </div>
                    </div>

                    <!-- Right: Search, Notifications & User Settings Dropdown -->
                    <div class="flex items-center space-x-1.5 sm:space-x-2">
                        <!-- Quick Link to Dashboard -->
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-slate-700 hover:text-brand-600 hover:bg-slate-50 rounded-lg border border-slate-200/80 transition-colors">
                            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span class="hidden sm:inline">Dashboard</span>
                        </a>

                        <!-- NOTIFICATION CENTER BELL DROPDOWN -->
                        <div class="relative" id="notificationMenuWrapper">
                            <button id="notificationDropdownBtn" type="button" class="relative p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200 focus:outline-none">
                                <i data-lucide="bell" class="w-4 h-4"></i>
                                <span id="notificationBadge" class="hidden absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                            </button>

                            <!-- Notification Popup Menu -->
                            <div id="notificationDropdownMenu" class="hidden absolute right-0 mt-1.5 w-80 sm:w-88 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50 transition-all transform opacity-0 scale-95 duration-150">
                                <div class="px-3.5 py-2 border-b border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-semibold text-slate-900 text-xs">Notification Centre</span>
                                        <span id="notificationCountBadge" class="text-[9.5px] bg-rose-50 text-rose-600 font-bold px-1.5 py-0.2 rounded-full border border-rose-200">0 new</span>
                                    </div>
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-[10.5px] font-medium text-brand-600 hover:text-brand-700 hover:underline">
                                            Mark all read
                                        </button>
                                    </form>
                                </div>

                                <div id="notificationList" class="max-h-72 overflow-y-auto divide-y divide-slate-50">
                                    <div class="p-4 text-center text-slate-400 text-xs">Loading notifications...</div>
                                </div>

                                <div class="p-1.5 border-t border-slate-100 bg-slate-50/50 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-[11.5px] font-semibold text-brand-600 hover:text-brand-700 block py-0.5">
                                        View All Alerts & Logs &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- USER & SETTINGS DROPDOWN -->
                        <div class="relative" id="userMenuWrapper">
                            <button id="userDropdownBtn" type="button" class="flex items-center gap-2 p-1.5 pl-2 rounded-xl hover:bg-slate-100/80 transition-all focus:outline-none border border-slate-200/80 shadow-2xs bg-white">
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-7 h-7 rounded-lg object-cover ring-1 ring-brand-500/20 shadow-2xs">
                                <div class="hidden sm:flex flex-col text-left">
                                    <span class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</span>
                                    <span class="text-[9.5px] font-extrabold text-brand-600 uppercase tracking-wider leading-none mt-0.5">
                                        {{ Auth::user()->role === 'superadmin' ? 'Super Admin' : ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 ml-0.5"></i>
                            </button>

                            <!-- DROPDOWN MENU -->
                            <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 transition-all transform opacity-0 scale-95 duration-150">
                                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 rounded-t-2xl">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Signed in as</p>
                                    <p class="text-sm font-bold text-slate-900 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                    <div class="mt-2 flex items-center gap-1 text-[11px] text-slate-600 bg-white border border-slate-200 px-2 py-0.5 rounded-md inline-block">
                                        <i data-lucide="phone" class="w-3 h-3 text-slate-400 inline"></i>
                                        +91 {{ Auth::user()->mobile_number ?: 'Not Set' }}
                                    </div>
                                </div>

                                <div class="py-1">
                                    <!-- SETTINGS SECTION -->
                                    <div class="px-4 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Settings</div>
                                    
                                    <!-- PROFILE UNDER SETTINGS DROPDOWN -->
                                    <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600 hover:bg-brand-50/60 transition-colors">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-brand-600 flex items-center justify-center">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span>Profile</span>
                                            <span class="block text-[11px] font-normal text-slate-500">Edit details & security</span>
                                        </div>
                                    </a>

                                    <!-- SHIFT MANAGER UNDER SETTINGS -->
                                    <a href="{{ route('settings.shifts') }}" class="flex items-center gap-3 px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600 hover:bg-brand-50/60 transition-colors">
                                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span>Shift Manager</span>
                                            <span class="block text-[11px] font-normal text-slate-500">Timings & grace period</span>
                                        </div>
                                    </a>

                                    <!-- SMTP CONFIGURATION UNDER SETTINGS -->
                                    <a href="{{ route('settings.smtp') }}" class="flex items-center gap-3 px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600 hover:bg-brand-50/60 transition-colors">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                            <i data-lucide="mail-check" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span>SMTP Config</span>
                                            <span class="block text-[11px] font-normal text-slate-500">Email server gateway</span>
                                        </div>
                                    </a>

                                    <!-- LOCATIONS / GEOFENCE UNDER SETTINGS -->
                                    <a href="{{ route('settings.locations') }}" class="flex items-center gap-3 px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600 hover:bg-brand-50/60 transition-colors">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-brand-600 flex items-center justify-center">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span>Location Manager</span>
                                            <span class="block text-[11px] font-normal text-slate-500">Fixed geofence & GPS</span>
                                        </div>
                                    </a>
                                </div>

                                <div class="border-t border-slate-100 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-colors text-left font-medium">
                                            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                            </div>
                                            <span>Sign out</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>

        <!-- Body with Sidebar and Main Content -->
        <div class="flex-1 flex overflow-hidden">

            <!-- Collapsible Desktop & Mobile Drawer Sidebar -->
            <aside id="sidebarNav" class="fixed inset-y-0 left-0 z-30 w-60 bg-white border-r border-slate-200/90 transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200 ease-in-out flex flex-col justify-between">
                <div class="p-3.5 space-y-5 overflow-y-auto">

                    <!-- Mobile Close Button (visible only in mobile drawer) -->
                    <div class="lg:hidden flex items-center justify-between pb-2.5 border-b border-slate-200">
                        <span class="font-bold text-slate-800 text-xs">Navigation Menu</span>
                        <button id="closeSidebarBtn" class="p-1 rounded-md text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Navigation Groups -->
                    <div>
                        <p class="px-2.5 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Core HRMS</p>
                        <nav class="space-y-1">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                <span>Dashboard</span>
                            </a>

                            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                                <a href="{{ route('companies.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('companies*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i data-lucide="building-2" class="w-4 h-4 {{ request()->routeIs('companies*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Organizations</span>
                                </a>
                                <a href="{{ route('employees.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('employees*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('employees*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Employees</span>
                                </a>
                                <a href="{{ route('attendance.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('attendance*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i data-lucide="calendar-check" class="w-4 h-4 {{ request()->routeIs('attendance*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Attendance</span>
                                </a>
                            @endif

                            <!-- Contact Directory (Accessible to Everyone) -->
                            <a href="{{ route('contacts.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('contacts*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <i data-lucide="contact" class="w-4 h-4 {{ request()->routeIs('contacts*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                <span>Contact Directory</span>
                            </a>

                            <!-- Reports Module (Admin / SuperAdmin) -->
                            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                                <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('reports*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4 {{ request()->routeIs('reports*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Reports Center</span>
                                </a>
                            @endif

                            <a href="{{ route('leave.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('leave*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="calendar-off" class="w-4 h-4 {{ request()->routeIs('leave*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Leave Manager</span>
                                </div>
                            </a>
                            <a href="{{ route('payroll.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('payroll*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="indian-rupee" class="w-4 h-4 {{ request()->routeIs('payroll*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>{{ in_array(auth()->user()->role, ['superadmin', 'admin']) ? 'Payroll (India)' : 'My Payslips' }}</span>
                                </div>
                            </a>

                            <!-- HR & Policies (All users view/download, Admins upload/create folders) -->
                            <a href="{{ route('policies.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('policies*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <i data-lucide="book-open-check" class="w-4 h-4 {{ request()->routeIs('policies*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                <span>HR & Policies</span>
                            </a>

                            <!-- Exit / Resignation Option -->
                            <a href="{{ route('resignations.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('resignations*') ? 'bg-brand-50 text-brand-700 shadow-2xs border border-brand-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="user-minus" class="w-4 h-4 {{ request()->routeIs('resignations*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>{{ in_array(auth()->user()->role, ['superadmin', 'admin']) ? 'Exit & Resignations' : 'Resignation / Exit' }}</span>
                                </div>
                            </a>
                        </nav>
                    </div>

                    @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                        <!-- Settings / System Configuration (Admin Only) -->
                        <div>
                            <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">System Configuration</p>
                            <nav class="space-y-0.5">
                                <a href="{{ route('settings.profile') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.profile') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} transition-colors">
                                    <i data-lucide="user" class="w-4 h-4 {{ request()->routeIs('settings.profile') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Superadmin Profile</span>
                                </a>
                                <a href="{{ route('settings.shifts') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.shifts*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} transition-colors">
                                    <i data-lucide="clock" class="w-4 h-4 {{ request()->routeIs('settings.shifts*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Shift Manager</span>
                                </a>
                                <a href="{{ route('settings.smtp') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.smtp*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} transition-colors">
                                    <i data-lucide="mail-check" class="w-4 h-4 {{ request()->routeIs('settings.smtp*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>SMTP Configuration</span>
                                </a>
                                <a href="{{ route('settings.locations') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.locations*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} transition-colors">
                                    <i data-lucide="map-pin" class="w-4 h-4 {{ request()->routeIs('settings.locations*') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Location Manager</span>
                                </a>
                            </nav>
                        </div>
                    @else
                        <!-- Employee Self-Service Settings -->
                        <div>
                            <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">My Account</p>
                            <nav class="space-y-0.5">
                                <a href="{{ route('settings.profile') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.profile') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} transition-colors">
                                    <i data-lucide="user" class="w-4 h-4 {{ request()->routeIs('settings.profile') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>My Profile</span>
                                </a>
                            </nav>
                        </div>
                    @endif

                </div>

                <!-- Bottom User Card -->
                <div class="p-3 border-t border-slate-200 bg-slate-50/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 min-w-0">
                            <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-7 h-7 rounded-md object-cover ring-1 ring-slate-200">
                            <div class="truncate">
                                <p class="text-xs font-semibold text-slate-900 truncate leading-tight">{{ Auth::user()->name }}</p>
                                <p class="text-[9.5px] text-slate-500 truncate leading-tight">{{ Auth::user()->designation ?: 'Super Admin' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('settings.profile') }}" title="Edit Profile" class="text-slate-400 hover:text-brand-600 p-1">
                            <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Backdrop for Mobile Sidebar -->
            <div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm z-20 hidden lg:hidden"></div>

            <!-- Main Page Content Area -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-7 py-5">
                
                <!-- Alerts / Flashes -->
                @if (session('success'))
                    <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 flex items-center justify-between shadow-sm">
                        <div class="flex items-center space-x-3 text-emerald-800">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            </div>
                            <span class="text-sm font-semibold">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-6 rounded-2xl bg-indigo-50 border border-indigo-200 p-4 flex items-center justify-between shadow-sm">
                        <div class="flex items-center space-x-3 text-indigo-800">
                            <i data-lucide="info" class="w-5 h-5 text-indigo-600"></i>
                            <span class="text-sm font-semibold">{{ session('info') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-indigo-500 hover:text-indigo-700">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 shadow-sm">
                        <div class="flex items-start space-x-3 text-rose-800">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 shrink-0 mt-0.5">
                                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold">Please correct the following:</h4>
                                <ul class="mt-1 list-disc list-inside text-xs space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')

            </main>

        </div>

    </div>

    <!-- Scripts for Dropdown & Interactivity -->
    <script>
        // Initialize Lucide Icons
        function initIcons() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initIcons);
        } else {
            initIcons();
        }
        window.addEventListener('load', initIcons);

        // Live Indian Time Indicator
        function updateIndianTime() {
            const timeElem = document.getElementById('liveIndianTime');
            if (timeElem) {
                const now = new Date();
                const options = {
                    timeZone: 'Asia/Kolkata',
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    day: 'numeric',
                    month: 'short'
                };
                timeElem.textContent = now.toLocaleString('en-IN', options);
            }
        }
        updateIndianTime();
        setInterval(updateIndianTime, 1000);

        // User Dropdown Logic
        const dropdownBtn = document.getElementById('userDropdownBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');

        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = dropdownMenu.classList.contains('hidden');
                if (isHidden) {
                    dropdownMenu.classList.remove('hidden');
                    setTimeout(() => {
                        dropdownMenu.classList.remove('opacity-0', 'scale-95');
                        dropdownMenu.classList.add('opacity-100', 'scale-100');
                    }, 10);
                } else {
                    dropdownMenu.classList.add('opacity-0', 'scale-95');
                    dropdownMenu.classList.remove('opacity-100', 'scale-100');
                    setTimeout(() => dropdownMenu.classList.add('hidden'), 150);
                }
            });

            document.addEventListener('click', (e) => {
                if (!dropdownMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                    dropdownMenu.classList.add('opacity-0', 'scale-95');
                    dropdownMenu.classList.remove('opacity-100', 'scale-100');
                    setTimeout(() => dropdownMenu.classList.add('hidden'), 150);
                }
            });
        }

        // Notification Center Dropdown & AJAX Fetch
        const notifBtn = document.getElementById('notificationDropdownBtn');
        const notifMenu = document.getElementById('notificationDropdownMenu');
        const notifBadge = document.getElementById('notificationBadge');
        const notifCountBadge = document.getElementById('notificationCountBadge');
        const notifList = document.getElementById('notificationList');

        function fetchNotifications() {
            fetch('{{ route("notifications.dropdown") }}')
                .then(res => res.json())
                .then(data => {
                    if (data.unread_count > 0) {
                        if (notifBadge) notifBadge.classList.remove('hidden');
                        if (notifCountBadge) {
                            notifCountBadge.textContent = `${data.unread_count} new`;
                            notifCountBadge.classList.remove('hidden');
                        }
                    } else {
                        if (notifBadge) notifBadge.classList.add('hidden');
                        if (notifCountBadge) notifCountBadge.textContent = '0 new';
                    }

                    if (notifList && data.notifications) {
                        if (data.notifications.length === 0) {
                            notifList.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs">No notifications yet.</div>';
                        } else {
                            notifList.innerHTML = data.notifications.map(n => `
                                <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 ${n.is_read ? 'opacity-70' : 'bg-slate-50/40'}">
                                    <div class="w-8 h-8 rounded-lg bg-${n.color}-50 text-${n.color}-600 border border-${n.color}-200 flex items-center justify-center shrink-0 mt-0.5">
                                        <i data-lucide="${n.icon || 'bell'}" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <p class="text-xs font-bold text-slate-900 truncate">${n.title}</p>
                                            <span class="text-[10px] text-slate-400 whitespace-nowrap">${n.time_ago}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 line-clamp-2 mt-0.5 leading-snug">${n.message}</p>
                                        ${n.link_url && n.link_url !== '#' ? `<a href="${n.link_url}" class="text-[11px] font-semibold text-brand-600 hover:underline mt-1 inline-block">View details &rarr;</a>` : ''}
                                    </div>
                                </div>
                            `).join('');
                            if (window.lucide) lucide.createIcons();
                        }
                    }
                })
                .catch(err => console.log('Notification fetch error', err));
        }

        if (notifBtn && notifMenu) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = notifMenu.classList.contains('hidden');
                if (isHidden) {
                    notifMenu.classList.remove('hidden');
                    setTimeout(() => {
                        notifMenu.classList.remove('opacity-0', 'scale-95');
                        notifMenu.classList.add('opacity-100', 'scale-100');
                    }, 10);
                    fetchNotifications();
                } else {
                    notifMenu.classList.add('opacity-0', 'scale-95');
                    notifMenu.classList.remove('opacity-100', 'scale-100');
                    setTimeout(() => notifMenu.classList.add('hidden'), 150);
                }
            });

            document.addEventListener('click', (e) => {
                if (!notifMenu.contains(e.target) && !notifBtn.contains(e.target)) {
                    notifMenu.classList.add('opacity-0', 'scale-95');
                    notifMenu.classList.remove('opacity-100', 'scale-100');
                    setTimeout(() => notifMenu.classList.add('hidden'), 150);
                }
            });

            // Initial check and periodic polling every 45s
            fetchNotifications();
            setInterval(fetchNotifications, 45000);
        }

        // Mobile Sidebar Drawer Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarNav = document.getElementById('sidebarNav');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');

        function openSidebar() {
            sidebarNav.classList.remove('-translate-x-full');
            sidebarBackdrop.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebarNav.classList.add('-translate-x-full');
            sidebarBackdrop.classList.add('hidden');
        }

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);
    </script>

    @stack('scripts')
</body>
</html>
