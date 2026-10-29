@extends('layouts.app')

@section('title', 'Payroll Batch Details - ' . $payrollRun->cycle_period)

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('payroll.index') }}" class="hover:text-brand-600">Payroll</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-700">Batch Details</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>{{ $payrollRun->cycle_period }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $payrollRun->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-brand-700 border border-brand-200' }}">
                    {{ ucfirst($payrollRun->status) }}
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Entity: <strong>{{ $payrollRun->company?->name ?: 'All Companies' }}</strong> • Processed by {{ $payrollRun->processedByUser?->name ?? 'Admin' }} on {{ $payrollRun->processed_at?->format('d M Y, h:i A') }}
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a 
                href="{{ route('payroll.index') }}" 
                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors flex items-center gap-1.5"
            >
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Payroll</span>
            </a>

            @if($isAdmin && $payrollRun->status !== 'paid')
                <form method="POST" action="{{ route('payroll.mark-paid', $payrollRun->id) }}">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 hover:shadow-lg transition-all flex items-center gap-1.5"
                        onclick="return confirm('Confirm disbursement of ₹{{ number_format($payrollRun->total_net_payout, 2) }}? All payslips will be marked as PAID.')"
                    >
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        <span>Mark Batch as Paid</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Batch Summary Financial Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Staff</span>
            <span class="text-2xl font-black text-slate-900 mt-1 block font-mono">{{ $payrollRun->employee_count }}</span>
            <span class="text-[10px] text-slate-400">Included in cycle</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Gross Salary</span>
            <span class="text-2xl font-black text-slate-900 mt-1 block font-mono">₹{{ number_format($payrollRun->total_gross, 2) }}</span>
            <span class="text-[10px] text-slate-400">Before deductions</span>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Deductions</span>
            <span class="text-2xl font-black text-rose-600 mt-1 block font-mono">-₹{{ number_format($payrollRun->total_deductions, 2) }}</span>
            <span class="text-[10px] text-slate-400">Leave + PF/ESIC/Tax</span>
        </div>

        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block">Net Bank Payout</span>
            <span class="text-2xl font-black text-emerald-700 mt-1 block font-mono">₹{{ number_format($payrollRun->total_net_payout, 2) }}</span>
            <span class="text-[10px] text-emerald-600 font-semibold">Total disbursement</span>
        </div>
    </div>

    <!-- Employee Payslip Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Employee Payslips in Cycle</h3>
                <p class="text-[11px] text-slate-500">Includes leave deduction: (TakeHome / 30) × (FullDays + (HalfDays × 0.5))</p>
            </div>

            <form method="GET" action="{{ route('payroll.show', $payrollRun->id) }}" class="flex items-center gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search staff..." 
                    class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                >
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Slip No.</th>
                        <th class="py-3 px-4">Base Take-Home</th>
                        <th class="py-3 px-4">Leave Deductible Days</th>
                        <th class="py-3 px-4">Leave Deduction</th>
                        <th class="py-3 px-4">Statutory (PF/ESIC/Tax)</th>
                        <th class="py-3 px-4">Net Salary (INR)</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payslips as $ps)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $ps->employee->user?->avatar_url }}" alt="{{ $ps->employee->full_name }}" class="w-8 h-8 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold text-slate-900 block">{{ $ps->employee->full_name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $ps->employee->employee_id }} • {{ $ps->employee->department?->name ?? 'Dept' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                                {{ $ps->payslip_number }}
                            </td>

                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                ₹{{ number_format($ps->baseline_take_home, 2) }}
                                <span class="block text-[9px] text-slate-400 font-normal">₹{{ number_format($ps->daily_take_home_rate, 2) }}/day</span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($ps->total_deductible_days > 0)
                                    <span class="font-mono font-bold text-rose-600 block">{{ $ps->total_deductible_days }} days</span>
                                    <span class="text-[9px] text-slate-400 font-sans block">
                                        {{ $ps->full_day_leaves }} full + {{ $ps->half_day_leaves }} half (×0.5)
                                    </span>
                                @else
                                    <span class="text-emerald-700 font-bold">0.0 days</span>
                                    <span class="text-[9px] text-emerald-600 block">Full attendance</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-rose-600">
                                @if($ps->leave_deduction_amount > 0)
                                    -₹{{ number_format($ps->leave_deduction_amount, 2) }}
                                @else
                                    ₹0.00
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                -₹{{ number_format($ps->statutory_deductions, 2) }}
                            </td>

                            <td class="py-3.5 px-4 font-mono font-black text-emerald-700 text-sm">
                                ₹{{ number_format($ps->net_salary, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a 
                                    href="{{ route('payroll.payslip', $ps->id) }}" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition-colors"
                                >
                                    <i data-lucide="printer" class="w-3 h-3"></i>
                                    <span>Slip</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                <p class="text-xs font-semibold">No payslips found matching search.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payslips->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $payslips->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
