@extends('layouts.app')

@section('title', 'Superadmin Dashboard')

@section('content')
<div class="space-y-4">

    <!-- Welcome Hero Banner -->
    <div class="relative bg-gradient-to-r from-brand-900 via-brand-700 to-indigo-700 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-brand-900/15 overflow-hidden border border-white/10">
        <!-- Ambient background glows -->
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-400/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -bottom-16 w-56 h-56 bg-brand-400/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-center space-x-4 sm:space-x-5">
                <div class="relative shrink-0">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl ring-4 ring-white/20 object-cover shadow-md bg-white">
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-400 border-2 border-brand-900 rounded-full" title="Online & Active"></span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="px-3 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/15 text-white backdrop-blur-md border border-white/20">
                            {{ in_array($user->role, ['superadmin', 'admin']) ? ($user->role === 'superadmin' ? 'Super Administrator' : 'Administrator') : 'Employee Portal' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/25 text-emerald-200 border border-emerald-400/30 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Active Session
                        </span>
                        @if($employee && $employee->company)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/10 text-indigo-100 border border-white/15">
                                {{ $employee->company->name }}
                            </span>
                        @endif
                    </div>
                    @php
                        $hour = (int) \Carbon\Carbon::now()->format('H');
                        if ($hour < 12) {
                            $greeting = 'Good morning';
                        } elseif ($hour < 17) {
                            $greeting = 'Good afternoon';
                        } else {
                            $greeting = 'Good evening';
                        }
                    @endphp
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $greeting }}, {{ $user->name }}!</h1>
                    <p class="text-sm text-indigo-100/90 mt-1 font-medium flex items-center gap-2 flex-wrap">
                        <span>{{ $employee?->designation?->name ?: ($user->designation ?: 'Senior Executive') }}</span>
                        <span class="text-indigo-300">•</span>
                        <span>{{ $employee?->department?->name ?: ($user->department ?: 'Information Technology') }}</span>
                    </p>
                </div>
            </div>

            <!-- Profile Upgrade Quick Callouts -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('settings.profile') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-brand-700 hover:bg-brand-50 font-bold text-xs shadow-sm hover:shadow-md transition-all duration-200">
                    <i data-lucide="user-cog" class="w-4 h-4 text-brand-600"></i>
                    <span>Profile Settings</span>
                </a>
                @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs backdrop-blur-md border border-white/20 transition-all duration-200">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>+ Onboard</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Banner Sub-features -->
        <div class="mt-5 pt-4 border-t border-white/15 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs text-indigo-100/90 font-medium">
            <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10 truncate">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-300 shrink-0"></i>
                <span class="truncate">{{ $user->email }}</span>
            </div>
            <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                <i data-lucide="phone" class="w-3.5 h-3.5 text-indigo-300 shrink-0"></i>
                <span>{{ $user->mobile_number ? '+91 ' . $user->mobile_number : ($employee?->phone ? '+91 ' . $employee->phone : 'Contact Registered') }}</span>
            </div>
            <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-indigo-300 shrink-0"></i>
                <span>{{ $user->city ? $user->city . ($user->state ? ', ' . $user->state : '') : ($employee?->location?->city ?: ($employee?->location?->name ?: 'Guwahati, Assam')) }}</span>
            </div>
            <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-300 shrink-0"></i>
                <span>FY {{ date('Y') }}-{{ date('y', strtotime('+1 year')) }} ({{ 'Q' . ceil(date('n') / 3) }})</span>
            </div>
        </div>
    </div>

    <!-- Attendance Punch In / Punch Out Interactive Card -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/90 shadow-sm relative">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            
            <!-- Left: Shift Info & Status -->
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center shadow-2xs">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900 tracking-tight">Today's Attendance Punch Station</h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-ping"></span>
                                Live Station
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            @if($employee)
                                {{ $employee->shift?->name ?: 'General Day Shift' }} 
                                ({{ $employee->shift ? substr($employee->shift->start_time, 0, 5) . ' - ' . substr($employee->shift->end_time, 0, 5) : '09:30 - 18:30' }})
                                • Grace Period: {{ $employee->shift?->grace_period_minutes ?? 15 }} mins
                            @else
                                System Administrator Session • Auto Geofence Active (200m)
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-0.5 text-xs">
                    <!-- Location Mode Badge -->
                    @if($employee && $employee->location_type === 'random')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 font-semibold shadow-2xs">
                            <i data-lucide="compass" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Random Location (Device GPS)</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold shadow-2xs">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Fixed: {{ $employee?->location?->name ?: 'Main Office' }} (200m Radius)</span>
                        </span>
                    @endif

                    <!-- Grace Days Quota Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 text-brand-700 border border-brand-200 font-medium shadow-2xs">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-brand-600"></i>
                        <span>Monthly Grace Used: <strong>{{ $monthlyGraceUsed }}/5 days</strong></span>
                    </span>

                    <!-- Live GPS Indicator -->
                    <span id="gpsStatusBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 text-slate-600 font-mono text-xs border border-slate-200/80">
                        <i data-lucide="navigation" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span id="gpsStatusText">Detecting device GPS...</span>
                    </span>
                </div>
            </div>

            <!-- Right: Punch Actions & Current Log Times -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                
                <!-- Punch In Block -->
                <div class="p-3.5 rounded-2xl bg-slate-50/90 border border-slate-200 min-w-[160px] text-center shadow-2xs">
                    <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 block mb-1.5">
                        {{ $todayAttendance && $todayAttendance->is_field_visit ? 'Field Visit' : 'Punch In' }}
                    </span>
                    @if($todayAttendance && $todayAttendance->punch_in_at)
                        <span class="text-lg font-black text-emerald-700 font-mono block">
                            {{ $todayAttendance->punch_in_at->format('h:i A') }}
                        </span>
                        <div class="mt-1 flex flex-col items-center gap-1">
                            <span class="text-xs text-slate-600 font-medium">
                                Status: <strong class="{{ $todayAttendance->status === 'half_day' ? 'text-rose-600' : 'text-emerald-600' }}">{{ ucfirst($todayAttendance->status) }}</strong>
                            </span>
                            @if($todayAttendance->is_field_visit)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i data-lucide="briefcase" class="w-3 h-3"></i>
                                    Field Visit
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col gap-2">
                            <button 
                                type="button" 
                                id="btnPunchIn"
                                onclick="submitPunch('in')" 
                                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs shadow-sm hover:shadow transition-all flex items-center justify-center gap-1.5"
                            >
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Mark Punch In</span>
                            </button>

                            <button 
                                type="button" 
                                id="btnFieldVisit"
                                onclick="openFieldVisitModal()" 
                                class="w-full py-2 px-3 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold text-xs shadow-2xs transition-all flex items-center justify-center gap-1.5"
                            >
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-amber-600"></i>
                                <span>Mark Field Visit</span>
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1.5 font-medium">Allowed once / day</span>
                    @endif
                </div>

                <!-- Punch Out Block -->
                <div class="p-3.5 rounded-2xl bg-slate-50/90 border border-slate-200 min-w-[160px] text-center shadow-2xs">
                    <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Punch Out</span>
                    @if($todayAttendance && $todayAttendance->punch_out_at)
                        <span class="text-lg font-black text-slate-800 font-mono block">
                            {{ $todayAttendance->punch_out_at->format('h:i A') }}
                        </span>
                        <span class="text-xs text-slate-500 block mt-0.5 font-medium">
                            Total: <strong class="text-slate-800">{{ $todayAttendance->working_hours }} hrs</strong>
                        </span>
                        @if($todayAttendance->is_regularized)
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md mt-1 inline-block">
                                Regularized ({{ ucfirst($todayAttendance->status) }})
                            </span>
                        @elseif($todayAttendance->is_early_exit)
                            <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md mt-1 inline-block">
                                Early Exit (-{{ $todayAttendance->early_minutes }}m)
                            </span>
                        @endif
                        <button 
                            type="button" 
                            id="btnPunchOutAgain"
                            onclick="submitPunch('out')" 
                            class="mt-2 w-full py-1.5 px-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-brand-700 font-bold text-xs border border-brand-200 transition-colors flex items-center justify-center gap-1"
                        >
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            <span>Punch Out Again</span>
                        </button>
                    @elseif($todayAttendance && $todayAttendance->punch_in_at)
                        <button 
                            type="button" 
                            id="btnPunchOut"
                            onclick="submitPunch('out')" 
                            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white font-bold text-xs shadow-sm hover:shadow transition-all flex items-center justify-center gap-1.5"
                        >
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Mark Punch Out</span>
                        </button>
                        <span class="text-[10px] text-slate-400 block mt-1.5 font-medium">Available till 12:00 PM</span>
                    @else
                        <div class="py-4 flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4 mb-1 text-slate-300"></i>
                            <span class="text-xs font-semibold">Punch In First</span>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        <!-- Hidden Punch Form for Web Submissions -->
        <form id="attendancePunchForm" method="POST" action="" class="hidden">
            @csrf
            <input type="hidden" name="latitude" id="punchFormLat">
            <input type="hidden" name="longitude" id="punchFormLng">
        </form>
    </div>

    <!-- Instructions / Rules Banner -->
    <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-3 sm:p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 text-indigo-900">
            <div class="w-7 h-7 rounded-lg bg-brand-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
                <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900">Attendance Rules & Grace Policy</h4>
                <p class="text-[11px] text-slate-600 mt-0.2">
                    • <strong>Fixed Office:</strong> Within 200m radius of office. • <strong>Daily Limit:</strong> Punch In once/day; Punch Out allowed multiple times till 12:00 PM. • <strong>Grace:</strong> 5 grace days per month. Arrivals after grace period or quota expiry are marked as <strong>Half Day</strong>.
                </p>
            </div>
        </div>
        @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
            <a href="{{ route('attendance.index') }}" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg whitespace-nowrap shadow-2xs transition-colors shrink-0">
                View Records &rarr;
            </a>
        @endif
    </div>

    @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
        <!-- PRODUCTION ADMIN KPI METRIC ROW -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4.5">
            <!-- 1. Total Active Workforce -->
            <div class="stat-card-gradient bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Workforce</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xs">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900 tracking-tight">{{ $stats['active_employees'] }}</span>
                    <span class="text-xs text-slate-400 font-semibold">/ {{ $stats['total_users'] }} registered</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">{{ $stats['companies_count'] }} Entities • {{ $stats['departments_count'] }} Depts</span>
                    <a href="{{ route('employees.index') }}" class="text-brand-600 hover:text-brand-800 font-bold inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                        <span>Directory</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- 2. Today's Live Attendance -->
            <div class="stat-card-gradient bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today's Attendance</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xs">
                        <i data-lucide="check-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-emerald-700 tracking-tight">{{ $stats['today_punched'] }}</span>
                    <span class="text-xs text-emerald-700 font-bold px-2 py-0.5 rounded-full bg-emerald-100/70 border border-emerald-200/80">{{ $stats['attendance_rate'] }}</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">
                        @if($stats['today_field_visits'] > 0)
                            <strong class="text-amber-700 font-bold">{{ $stats['today_field_visits'] }}</strong> Field Duty
                        @else
                            {{ $stats['today_late'] }} Late arrivals
                        @endif
                    </span>
                    <a href="{{ route('attendance.index') }}" class="text-emerald-700 hover:text-emerald-800 font-bold inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                        <span>Live Logs</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- 3. Pending Approvals (Leaves & Resignations) -->
            <div class="stat-card-gradient bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Action</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xs">
                        <i data-lucide="clock-alert" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-amber-700 tracking-tight">{{ $stats['pending_leaves'] + $stats['pending_resignations'] }}</span>
                    <span class="text-xs text-amber-700 font-bold px-2 py-0.5 rounded-full bg-amber-100/70 border border-amber-200/80">Action Needed</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">{{ $stats['pending_leaves'] }} Leaves • {{ $stats['pending_resignations'] }} Exits</span>
                    <a href="{{ route('leave.index') }}" class="text-amber-700 hover:text-amber-800 font-bold inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                        <span>Review</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- 4. Payroll Cycle (Real INR Processed) -->
            <div class="stat-card-gradient bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Payroll Disbursed</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xs">
                        <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900 tracking-tight font-mono">{{ $stats['payroll_amount'] }}</span>
                    <span class="text-xs text-slate-500 font-bold bg-slate-100 px-2 py-0.5 rounded-md">{{ date('M Y') }}</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">{{ $stats['payroll_status'] }}</span>
                    <a href="{{ route('payroll.index') }}" class="text-brand-600 hover:text-brand-800 font-bold inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                        <span>Payroll</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- PRODUCTION LIVE OPERATIONAL OVERVIEW & DATA TABLES -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: Today's Live Attendance Feed & Field Visits -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-100 shadow-2xs">
                            <i data-lucide="activity" class="w-4.5 h-4.5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900">Today's Live Attendance Stream</h3>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>
                            <p class="text-xs text-slate-500">Real-time clock-ins & field visits for {{ date('d M, Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('attendance.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition-colors inline-flex items-center gap-1 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg border border-brand-200/60">
                        <span>All Records</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b border-slate-200/80 tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-3">Mode</th>
                                <th class="py-3 px-3">Clock-In</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-4 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentAttendances as $att)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <img src="{{ $att->employee->user?->avatar_url }}" alt="{{ $att->employee->full_name }}" class="w-8 h-8 rounded-lg object-cover ring-1 ring-slate-200 shadow-2xs">
                                            <div>
                                                <span class="font-bold text-slate-900 block leading-tight">{{ $att->employee->full_name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ $att->employee->employee_id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att->is_field_visit)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">
                                                <i data-lucide="briefcase" class="w-3 h-3 text-amber-700"></i>
                                                <span>Field Duty</span>
                                            </span>
                                        @elseif($att->punch_in_location_type === 'random')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                                                <i data-lucide="compass" class="w-3 h-3 text-slate-500"></i>
                                                <span>GPS</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded-md border border-brand-200">
                                                <i data-lucide="map-pin" class="w-3 h-3 text-brand-500"></i>
                                                <span>Office (200m)</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-slate-800">
                                        {{ $att->punch_in_at ? $att->punch_in_at->format('h:i A') : '—' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att->status === 'half_day')
                                            <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">Half Day</span>
                                        @elseif($att->status === 'present')
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Present</span>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full">{{ ucfirst($att->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($att->is_field_visit && $att->field_visit_selfie)
                                            <a href="{{ route('attendance.index') }}" class="text-[11px] font-bold text-brand-600 hover:text-brand-800">Selfie &rarr;</a>
                                        @else
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $att->punch_out_at ? 'Out: ' . $att->punch_out_at->format('h:i A') : 'Active' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">
                                        <i data-lucide="clock" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                                        <p class="text-xs">No attendance punches recorded yet for today.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: System Operations & Pending Leaves Queue -->
            <div class="space-y-6">
                <!-- Pending Leaves Queue -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5">
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shadow-2xs">
                                <i data-lucide="calendar-off" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Pending Leave Requests</h4>
                                <p class="text-[10.5px] text-slate-400">Applications awaiting approval</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 text-[10.5px] font-black rounded-full bg-purple-100 text-purple-800 border border-purple-200/80">
                            {{ $stats['pending_leaves'] }}
                        </span>
                    </div>

                    <div class="mt-3.5 divide-y divide-slate-100">
                        @forelse($recentLeaveRequests as $lr)
                            <div class="py-2.5 flex items-center justify-between gap-2">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block leading-tight">{{ $lr->employee->full_name }}</span>
                                    <span class="text-[10.5px] text-slate-500 font-medium">
                                        {{ $lr->leaveType?->name ?: 'Leave' }} • {{ $lr->days_count }} day{{ $lr->days_count > 1 ? 's' : '' }} ({{ $lr->start_date->format('d M') }})
                                    </span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $lr->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($lr->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800 border border-amber-200') }}">
                                    {{ ucfirst($lr->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400">
                                <i data-lucide="calendar-check" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                                <p class="text-xs font-medium">No pending leave applications.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-3.5 pt-3 border-t border-slate-100">
                        <a href="{{ route('leave.index') }}" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100/90 text-slate-700 text-xs font-bold rounded-xl border border-slate-200/90 flex items-center justify-center gap-1.5 transition-colors shadow-2xs">
                            <span>Manage Leave Applications</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-slate-400"></i>
                        </a>
                    </div>
                </div>

                <!-- System Infrastructure Status -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 pb-3.5 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center border border-brand-100 shadow-2xs">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">System Infrastructure</h4>
                            <p class="text-[10.5px] text-slate-400">Enterprise deployment status</p>
                        </div>
                    </div>

                    <div class="mt-3.5 space-y-2.5">
                        <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100/90">
                            <span class="text-slate-700 font-semibold flex items-center gap-2">
                                <i data-lucide="building" class="w-3.5 h-3.5 text-brand-600"></i>
                                Legal Entities
                            </span>
                            <span class="font-extrabold text-slate-900 bg-white px-2 py-0.5 rounded-md border border-slate-200/80">{{ $stats['companies_count'] }} Active</span>
                        </div>

                        <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100/90">
                            <span class="text-slate-700 font-semibold flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i>
                                Office Geofences
                            </span>
                            <span class="font-extrabold text-slate-900 bg-white px-2 py-0.5 rounded-md border border-slate-200/80">{{ $stats['locations_count'] }} Active (200m)</span>
                        </div>

                        <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100/90">
                            <span class="text-slate-700 font-semibold flex items-center gap-2">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                                Standard Shifts
                            </span>
                            <span class="font-extrabold text-slate-900 bg-white px-2 py-0.5 rounded-md border border-slate-200/80">{{ $stats['shifts_count'] }} Active</span>
                        </div>
                    </div>

                    <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Need changes?</span>
                        <a href="{{ route('settings.profile') }}" class="text-brand-600 hover:text-brand-800 font-bold inline-flex items-center gap-1">
                            <span>Admin Settings</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    @endif

</div>

<!-- Field Visit Modal -->
<div id="fieldVisitModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200">
        <!-- Modal Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <i data-lucide="map-pin" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm">Mark Field Visit Attendance</h3>
                    <p class="text-[11px] text-amber-100">For outdoor client meetings, on-site duties & field work</p>
                </div>
            </div>
            <button type="button" onclick="closeFieldVisitModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="fieldVisitForm" action="{{ route('attendance.punch-in') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="is_field_visit" value="1">
            <input type="hidden" name="latitude" id="fvLat">
            <input type="hidden" name="longitude" id="fvLng">
            <input type="hidden" name="selfie_base64" id="fvSelfieBase64">

            <!-- Live GPS Status -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div id="fvGpsIcon" class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-700 block">Current Field Coordinates</span>
                        <span id="fvGpsCoords" class="text-xs font-mono text-slate-500">Detecting high-precision GPS...</span>
                    </div>
                </div>
                <button type="button" onclick="refreshFieldLocation()" class="text-xs text-brand-600 hover:text-brand-800 font-semibold flex items-center gap-1">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    Retry
                </button>
            </div>

            <!-- Selfie Capture Section -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>Selfie Photo <span class="text-rose-500">*</span></span>
                    <span class="text-[10px] font-normal text-slate-400">Live Camera or File</span>
                </label>

                <!-- Camera Preview / Image Capture Container -->
                <div class="relative rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 overflow-hidden flex flex-col items-center justify-center min-h-[200px]">
                    <video id="cameraVideo" autoplay playsinline class="w-full max-h-56 object-cover hidden rounded-lg"></video>
                    <canvas id="cameraCanvas" class="hidden"></canvas>
                    <img id="selfiePreview" src="" alt="Captured Selfie" class="w-full max-h-56 object-cover hidden rounded-lg">

                    <!-- Camera Placeholder / Start Button -->
                    <div id="cameraPlaceholder" class="p-4 text-center">
                        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 mx-auto flex items-center justify-center mb-2">
                            <i data-lucide="camera" class="w-6 h-6"></i>
                        </div>
                        <p class="text-xs font-semibold text-slate-700">Take Live Selfie or Upload Photo</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Clear selfie required for field verification</p>
                        <div class="mt-3 flex items-center justify-center gap-2">
                            <button type="button" onclick="startCamera()" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition-colors flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="video" class="w-3.5 h-3.5"></i>
                                Open Camera
                            </button>
                            <label class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs cursor-pointer transition-colors flex items-center gap-1.5">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                Browse File
                                <input type="file" id="selfieFileInput" name="selfie" accept="image/*" capture="user" class="hidden" onchange="handleSelfieFile(event)">
                            </label>
                        </div>
                    </div>

                    <!-- In-camera overlay controls -->
                    <div id="cameraControls" class="absolute bottom-3 inset-x-0 flex items-center justify-center gap-3 hidden z-10">
                        <button type="button" onclick="captureSelfie()" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1.5 transition-transform active:scale-95">
                            <i data-lucide="aperture" class="w-4 h-4"></i>
                            Capture Photo
                        </button>
                        <button type="button" onclick="stopCamera()" class="px-3 py-1.5 bg-slate-900/80 hover:bg-slate-900 text-white text-xs font-medium rounded-full backdrop-blur-xs">
                            Cancel
                        </button>
                    </div>

                    <!-- Retake button if photo captured -->
                    <div id="retakeControls" class="absolute bottom-3 right-3 hidden z-10">
                        <button type="button" onclick="retakePhoto()" class="px-3 py-1 bg-slate-900/80 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg backdrop-blur-xs flex items-center gap-1">
                            <i data-lucide="refresh-ccw" class="w-3 h-3"></i>
                            Retake
                        </button>
                    </div>
                </div>
            </div>

            <!-- Remarks Textarea -->
            <div>
                <label for="fvRemarks" class="block text-xs font-bold text-slate-700 mb-1">
                    Field Duty Remarks / Purpose <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    id="fvRemarks" 
                    name="remarks" 
                    rows="2" 
                    required 
                    placeholder="e.g. Client visit at DLF CyberCity, vendor inspection, outdoor meeting..." 
                    class="w-full p-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all placeholder:text-slate-400"
                ></textarea>
            </div>

            <!-- Form Actions -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeFieldVisitModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitFieldVisit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    Submit Field Visit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let userCoords = { lat: null, lng: null };
let cameraStream = null;

function detectDeviceLocation() {
    const badge = document.getElementById('gpsStatusBadge');
    const text = document.getElementById('gpsStatusText');

    if (!navigator.geolocation) {
        if (text) text.innerText = 'GPS unsupported on browser';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            userCoords.lat = position.coords.latitude;
            userCoords.lng = position.coords.longitude;
            if (text) text.innerText = `GPS Active (${userCoords.lat.toFixed(4)}, ${userCoords.lng.toFixed(4)})`;
            if (badge) {
                badge.classList.remove('bg-slate-100', 'text-slate-600');
                badge.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-200');
            }
        },
        function(error) {
            if (text) text.innerText = 'GPS: ' + error.message;
            if (badge) {
                badge.classList.remove('bg-slate-100', 'text-slate-600');
                badge.classList.add('bg-amber-50', 'text-amber-700', 'border', 'border-amber-200');
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function refreshFieldLocation() {
    const coordsElem = document.getElementById('fvGpsCoords');
    const iconElem = document.getElementById('fvGpsIcon');
    coordsElem.innerText = 'Acquiring GPS coordinates...';

    if (!navigator.geolocation) {
        coordsElem.innerText = 'Geolocation not supported by device/browser';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            userCoords.lat = position.coords.latitude;
            userCoords.lng = position.coords.longitude;
            document.getElementById('fvLat').value = userCoords.lat;
            document.getElementById('fvLng').value = userCoords.lng;
            coordsElem.innerText = `Lat: ${userCoords.lat.toFixed(6)}, Lng: ${userCoords.lng.toFixed(6)}`;
            if (iconElem) {
                iconElem.classList.remove('bg-amber-100', 'text-amber-700');
                iconElem.classList.add('bg-emerald-100', 'text-emerald-700');
            }
        },
        function(error) {
            coordsElem.innerText = 'Failed to acquire location: ' + error.message;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function openFieldVisitModal() {
    document.getElementById('fieldVisitModal').classList.remove('hidden');
    refreshFieldLocation();
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function closeFieldVisitModal() {
    stopCamera();
    document.getElementById('fieldVisitModal').classList.add('hidden');
}

async function startCamera() {
    try {
        const video = document.getElementById('cameraVideo');
        const placeholder = document.getElementById('cameraPlaceholder');
        const controls = document.getElementById('cameraControls');
        const preview = document.getElementById('selfiePreview');
        const retake = document.getElementById('retakeControls');

        preview.classList.add('hidden');
        retake.classList.add('hidden');

        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        });

        video.srcObject = cameraStream;
        video.classList.remove('hidden');
        placeholder.classList.add('hidden');
        controls.classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    } catch (err) {
        alert('Camera access denied or unavailable: ' + err.message + '. Please use the Browse File option instead.');
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    const video = document.getElementById('cameraVideo');
    const controls = document.getElementById('cameraControls');
    if (video) video.classList.add('hidden');
    if (controls) controls.classList.add('hidden');
}

function captureSelfie() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const preview = document.getElementById('selfiePreview');
    const placeholder = document.getElementById('cameraPlaceholder');
    const controls = document.getElementById('cameraControls');
    const retake = document.getElementById('retakeControls');
    const base64Input = document.getElementById('fvSelfieBase64');

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    base64Input.value = dataUrl;
    preview.src = dataUrl;
    preview.classList.remove('hidden');

    stopCamera();
    placeholder.classList.add('hidden');
    controls.classList.add('hidden');
    retake.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}

function handleSelfieFile(event) {
    const file = event.target.files[0];
    if (!file) return;

    const preview = document.getElementById('selfiePreview');
    const placeholder = document.getElementById('cameraPlaceholder');
    const retake = document.getElementById('retakeControls');
    const base64Input = document.getElementById('fvSelfieBase64');

    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        base64Input.value = e.target.result;
        placeholder.classList.add('hidden');
        retake.classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    };
    reader.readAsDataURL(file);
}

function retakePhoto() {
    const preview = document.getElementById('selfiePreview');
    const placeholder = document.getElementById('cameraPlaceholder');
    const retake = document.getElementById('retakeControls');
    const base64Input = document.getElementById('fvSelfieBase64');
    const fileInput = document.getElementById('selfieFileInput');

    preview.classList.add('hidden');
    retake.classList.add('hidden');
    base64Input.value = '';
    if (fileInput) fileInput.value = '';
    placeholder.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}

document.getElementById('fieldVisitForm')?.addEventListener('submit', function(e) {
    const lat = document.getElementById('fvLat').value;
    const lng = document.getElementById('fvLng').value;
    const selfieBase64 = document.getElementById('fvSelfieBase64').value;
    const fileInput = document.getElementById('selfieFileInput');
    const remarks = document.getElementById('fvRemarks').value.trim();

    if (!lat || !lng) {
        e.preventDefault();
        alert('GPS Coordinates could not be detected. Please ensure GPS is enabled and click "Retry".');
        refreshFieldLocation();
        return;
    }

    const hasSelfie = (selfieBase64 && selfieBase64.length > 50) || (fileInput && fileInput.files && fileInput.files.length > 0);
    if (!hasSelfie) {
        e.preventDefault();
        alert('Selfie photo is required to mark a field visit.');
        return;
    }

    if (!remarks) {
        e.preventDefault();
        alert('Please specify field duty remarks/purpose.');
        return;
    }
});

function submitPunch(type) {
    const form = document.getElementById('attendancePunchForm');
    const formLat = document.getElementById('punchFormLat');
    const formLng = document.getElementById('punchFormLng');

    if (type === 'in') {
        form.action = "{{ route('attendance.punch-in') }}";
    } else {
        form.action = "{{ route('attendance.punch-out') }}";
    }

    // Set coordinates if already available
    if (userCoords.lat && userCoords.lng) {
        formLat.value = userCoords.lat;
        formLng.value = userCoords.lng;
        form.submit();
        return;
    }

    // Otherwise detect now
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                formLat.value = position.coords.latitude;
                formLng.value = position.coords.longitude;
                form.submit();
            },
            function(error) {
                if (confirm('Location access could not be retrieved automatically (' + error.message + '). Proceed anyway? (Fixed office employees require coordinates)')) {
                    form.submit();
                }
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    } else {
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    detectDeviceLocation();
});
</script>
@endsection
