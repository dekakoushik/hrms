@extends('layouts.app')

@section('title', 'Payroll Manager - India Statutory & Salary Processing')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <span>Core HRMS</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-700">Payroll (India)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Payroll & Salary Management</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Cycle: 1st – Last of Month
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Automated salary computation with statutory PF/ESIC and 30-day leave salary deduction rule.</p>
        </div>

        @if($isAdmin)
            <div class="flex items-center gap-2.5">
                <!-- Process Monthly Payroll Button -->
                <button 
                    type="button" 
                    onclick="openProcessModal()"
                    class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 hover:shadow-lg transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="calculator" class="w-4 h-4"></i>
                    <span>Run Monthly Payroll</span>
                </button>
            </div>
        @endif
    </div>

    <!-- Rule Explanation Card (Highlighted for HR Transparency) -->
    <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-950 text-white shadow-xl border border-slate-800">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400 shrink-0">
                    <i data-lucide="info" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Active India Salary & Leave Deduction Rule</h3>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        • <strong>Salary Cycle:</strong> 1st of every month to the last calendar day of the month.<br>
                        • <strong>Leave Salary Deduction:</strong> <code class="px-1.5 py-0.5 rounded bg-white/10 text-emerald-300 font-mono text-[11px]">(TakeHome / 30) × Deductible Days</code>.<br>
                        • <strong>Half Day Formula:</strong> Each half day (or late departure/arrival marked as half day) is computed as <strong>0.5 day</strong> deduction.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 border-t lg:border-t-0 lg:border-l border-white/10 pt-3 lg:pt-0 lg:pl-6 shrink-0 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Active Employees</span>
                    <span class="text-xl font-black text-white font-mono">{{ $activeEmployeesCount }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Current Cycle</span>
                    <span class="text-sm font-bold text-emerald-400 font-mono">{{ date('01 M') }} – {{ date('t M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 1: All Monthly Payroll Runs (Admin / Superadmin) -->
    @if($isAdmin)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-700">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Monthly Payroll Batches</h2>
                        <p class="text-xs text-slate-500">Processed cycles and total bank disbursements</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-slate-400">{{ $payrollRuns->total() }} Cycles</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3 px-4">Cycle Period</th>
                            <th class="py-3 px-4">Entity</th>
                            <th class="py-3 px-4">Employees</th>
                            <th class="py-3 px-4">Total Gross</th>
                            <th class="py-3 px-4">Leave & Stat. Deductions</th>
                            <th class="py-3 px-4">Net Payout (INR)</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($payrollRuns as $run)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <span class="font-extrabold text-slate-900 block font-mono">{{ $run->cycle_period }}</span>
                                    <span class="text-[10px] text-slate-400">Processed {{ $run->processed_at?->diffForHumans() }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800">{{ $run->company?->name ?: 'All Companies' }}</span>
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                    {{ $run->employee_count }} staff
                                </td>

                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                    ₹{{ number_format($run->total_gross, 2) }}
                                </td>

                                <td class="py-3.5 px-4 font-mono font-semibold text-rose-600">
                                    -₹{{ number_format($run->total_deductions, 2) }}
                                </td>

                                <td class="py-3.5 px-4 font-mono font-black text-emerald-700 text-sm">
                                    ₹{{ number_format($run->total_net_payout, 2) }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($run->status === 'paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disbursed / Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-brand-700 border border-brand-200">
                                            Processed
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a 
                                        href="{{ route('payroll.show', $run->id) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-700 border border-slate-200 transition-colors"
                                    >
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View Payslips</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="calculator" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                    <p class="text-sm font-semibold">No payroll runs found.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Click "Run Monthly Payroll" above to generate the current month's salary batch.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payrollRuns->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $payrollRuns->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- SECTION 2: Employee's Personal Payslips (If logged in employee) -->
    @if($employee)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">My Payslips ({{ $employee->full_name }})</h2>
                        <p class="text-xs text-slate-500">Download and view salary statements with deduction breakdown</p>
                    </div>
                </div>
                <span class="text-xs font-bold font-mono text-slate-500">{{ $employee->employee_id }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3 px-4">Pay Period</th>
                            <th class="py-3 px-4">Slip No.</th>
                            <th class="py-3 px-4">Leave Deductions</th>
                            <th class="py-3 px-4">Statutory (PF/ESIC/Tax)</th>
                            <th class="py-3 px-4">Net Take-Home</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($myPayslips as $slip)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    {{ date('F Y', mktime(0, 0, 0, $slip->month, 1, $slip->year)) }}
                                    <span class="block text-[10px] text-slate-400 font-normal font-mono">01 to {{ $slip->end_date->format('d M Y') }}</span>
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                                    {{ $slip->payslip_number }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($slip->total_deductible_days > 0)
                                        <span class="font-mono font-bold text-rose-600 block">-₹{{ number_format($slip->leave_deduction_amount, 2) }}</span>
                                        <span class="text-[10px] text-slate-400">({{ $slip->total_deductible_days }} days deductible)</span>
                                    @else
                                        <span class="text-emerald-600 font-bold">₹0.00 (Full Attendance)</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    -₹{{ number_format($slip->statutory_deductions, 2) }}
                                </td>

                                <td class="py-3.5 px-4 font-mono font-black text-emerald-700 text-sm">
                                    ₹{{ number_format($slip->net_salary, 2) }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Generated
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a 
                                        href="{{ route('payroll.payslip', $slip->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition-colors"
                                    >
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>View Payslip</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <p class="text-xs font-semibold">No payslips generated for your profile yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- Modal: Process Monthly Payroll -->
@if($isAdmin)
    <div id="processModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-emerald-50/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i data-lucide="calculator" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Run Monthly Payroll</h3>
                        <p class="text-xs text-slate-500 font-medium">Automatic attendance & leave deductions</p>
                    </div>
                </div>
                <button type="button" onclick="closeProcessModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('payroll.process') }}">
                @csrf
                <div class="p-5 space-y-4">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-600 space-y-1">
                        <span class="font-bold text-slate-800 block">Cycle Period:</span>
                        <span>First day of the month to last calendar day of the month.</span>
                        <div class="pt-1 text-[11px] text-emerald-700 font-semibold">
                            Formula: (TakeHome / 30) × (FullDays + (HalfDays × 0.5))
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Select Year</label>
                            <select name="year" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white">
                                @for ($y = 2025; $y <= 2028; $y++)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Select Month</label>
                            <select name="month" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Company / Entity</label>
                        <select name="company_id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white">
                            <option value="">🏢 Active Company / All Entities</option>
                            @foreach (\App\Models\Company::where('is_active', true)->get() as $cmp)
                                <option value="{{ $cmp->id }}">{{ $cmp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeProcessModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-md shadow-brand-600/20 flex items-center gap-1.5">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        <span>Execute Payroll Run</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    function openProcessModal() {
        const modal = document.getElementById('processModal');
        if (modal) modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeProcessModal() {
        const modal = document.getElementById('processModal');
        if (modal) modal.classList.add('hidden');
    }
</script>
@endsection
