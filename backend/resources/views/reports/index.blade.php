@extends('layouts.app')

@section('title', 'Reports & Analytics Center • HRMS')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-brand-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-lg border border-brand-800/40">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/10 text-brand-200 border border-white/10">
                        Admin Intelligence
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/20 text-emerald-300">
                        Real-Time Export Engine
                    </span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Reports & Analytics Hub</h1>
                <p class="text-xs text-indigo-200/80 mt-1 max-w-xl">
                    Generate and download CSV and Excel-ready operational reports across attendance logs, employee master records, statutory payroll, and leave trends.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-white/10 text-center">
                    <span class="text-lg font-bold font-mono block leading-tight">{{ $stats['total_employees'] }}</span>
                    <span class="text-[10px] uppercase font-semibold text-indigo-200">Total Staff</span>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-white/10 text-center">
                    <span class="text-lg font-bold font-mono block leading-tight">{{ $stats['total_attendances'] }}</span>
                    <span class="text-[10px] uppercase font-semibold text-indigo-200">Punches Logged</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Report Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 1. ATTENDANCE & TIMESHEET REPORT -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="calendar-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Attendance & Timesheet Report</h2>
                            <p class="text-xs text-slate-500">Punches, hours, geofence, grace quota, and field duty logs</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Live Tracking
                    </span>
                </div>

                <form method="GET" action="{{ route('reports.attendance.download') }}" class="space-y-3.5 pt-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">From Date *</label>
                            <input 
                                type="date" 
                                name="start_date" 
                                value="{{ date('Y-m-01') }}" 
                                required
                                class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">To Date *</label>
                            <input 
                                type="date" 
                                name="end_date" 
                                value="{{ date('Y-m-d') }}" 
                                required
                                class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Department</label>
                            <select name="department_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Status Filter</label>
                            <select name="status" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">All Statuses</option>
                                <option value="present">Present Only</option>
                                <option value="half_day">Half Day Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_field_visit" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                            <span>Field Duty / Outside Visits only (with selfies & GPS)</span>
                        </label>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export Attendance Report (.CSV)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. EMPLOYEE MASTER DIRECTORY REPORT -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Employee Master Report</h2>
                            <p class="text-xs text-slate-500">Complete staff profiles, statutory PF/ESIC, bank & emergency info</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $stats['active_employees'] }} Active
                    </span>
                </div>

                <form method="GET" action="{{ route('reports.employees.download') }}" class="space-y-3.5 pt-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Company Entity</label>
                            <select name="company_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">All Registered Companies</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Department</label>
                            <select name="department_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Employee Status</label>
                        <select name="status" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                            <option value="all">All (Active, Inactive, Resigned)</option>
                            <option value="active" selected>Active Employees Only</option>
                            <option value="inactive">Inactive / On Hold</option>
                        </select>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                        <p class="text-[11px] leading-relaxed">
                            Includes PAN, Aadhaar, UAN, PF Number, ESIC, Bank IFSC, joining dates, CTC breakdown, and contact channels.
                        </p>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export Employee Master (.CSV)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. PAYROLL & SALARY DISBURSAL REPORT -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                            <i data-lucide="indian-rupee" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Payroll & Disbursal Report</h2>
                            <p class="text-xs text-slate-500">Salaries, earnings breakdown, PF/ESI/PT deductions, take-home pay</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-brand-700 border border-indigo-200">
                        Statutory Compliance
                    </span>
                </div>

                <form method="GET" action="{{ route('reports.payroll.download') }}" class="space-y-3.5 pt-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Financial Year *</label>
                            <select name="year" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Month</label>
                            <select name="month" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">Full Financial Year (All Months)</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Company Entity</label>
                        <select name="company_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                            <option value="all">All Companies</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                        <p class="text-[11px] leading-relaxed">
                            Detailed line-item earnings: Basic, DA, HRA, Special Allowance, PF (Employer/Employee), ESIC, Professional Tax, and Net Disbursed.
                        </p>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export Payroll Disbursal (.CSV)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. LEAVE APPLICATIONS & ABSENTEEISM REPORT -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i data-lucide="calendar-off" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Leave Applications Report</h2>
                            <p class="text-xs text-slate-500">Approved, pending, and rejected leave requests across types</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        Leave Records
                    </span>
                </div>

                <form method="GET" action="{{ route('reports.leaves.download') }}" class="space-y-3.5 pt-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Calendar Year</label>
                            <select name="year" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1">Approval Status</label>
                            <select name="status" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                                <option value="all">All (Approved, Pending, Rejected)</option>
                                <option value="approved">Approved Leaves</option>
                                <option value="pending">Pending Approvals</option>
                                <option value="rejected">Rejected Applications</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                        <p class="text-[11px] leading-relaxed">
                            Tracks leaves by Paid Leave, Sick Leave, Casual Leave, Half-Days, reason logs, and approving manager notes.
                        </p>
                    </div>

                    <div class="pt-7">
                        <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export Leave Report (.CSV)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
