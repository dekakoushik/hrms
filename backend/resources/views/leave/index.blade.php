@extends('layouts.app')

@section('title', 'Leave Manager - Approvals & Policy')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <span>Core HRMS</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-700">Leave Manager</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Leave Manager</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                    Policy & Requests
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage leave requests, pending approvals, global policy quotas, and balance tracking.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Apply Leave Button -->
            <button 
                type="button" 
                onclick="openApplyLeaveModal()"
                class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 hover:shadow-lg transition-all flex items-center gap-1.5"
            >
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Apply Leave</span>
            </button>

            @if($isAdminOrSuperAdmin)
                <!-- Configure Global Policy Button -->
                <button 
                    type="button" 
                    onclick="openPolicyModal()"
                    class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold text-xs shadow-xs hover:border-slate-300 transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="settings-2" class="w-4 h-4 text-purple-600"></i>
                    <span>Global Leave Policy</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pending Approvals -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pending Approvals</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block">{{ $totalPendingCount }}</span>
                <span class="text-[10px] text-slate-400">Requires manager review</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- On Leave Today -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">On Leave Today</span>
                <span class="text-2xl font-black text-rose-600 mt-1 block">{{ $onLeaveTodayCount }}</span>
                <span class="text-[10px] text-slate-400">Absent on approved leave</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
                <i data-lucide="user-minus" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Approved This Month -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Approved This Month</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ $approvedThisMonthCount }}</span>
                <span class="text-[10px] text-slate-400">Current calendar month</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Active Leave Types / Policy -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Global Policies</span>
                <span class="text-2xl font-black text-indigo-600 mt-1 block">{{ $leaveTypes->count() }} Types</span>
                <span class="text-[10px] text-slate-400">CL, SL, EL initialized</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Employee's Current Leave Balances Widget (If associated with an employee profile) -->
    @if($employee && $employee->leaveBalances->count() > 0)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-brand-900 via-indigo-900 to-slate-900 text-white shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-brand-300">My Leave Quota & Balances</span>
                    <h2 class="text-base font-extrabold text-white mt-0.5">{{ $employee->full_name }} ({{ date('Y') }})</h2>
                </div>
                <button 
                    type="button" 
                    onclick="openApplyLeaveModal()"
                    class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold transition-colors w-fit flex items-center gap-1.5"
                >
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Apply Request</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($employee->leaveBalances as $bal)
                    <div class="p-3.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-white">{{ $bal->leaveType->name }}</span>
                            <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-white/20 text-white font-bold">{{ $bal->leaveType->code }}</span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-white font-mono">{{ $bal->remaining_days }}</span>
                            <span class="text-xs text-brand-200">/ {{ $bal->total_allocated }} days</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300">
                            <span>Used: <strong>{{ $bal->used_days }}d</strong></span>
                            @if($bal->pending_days > 0)
                                <span class="text-amber-300">Pending: <strong>{{ $bal->pending_days }}d</strong></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- SECTION 1: Pending Leave Approvals for Superadmin / Admin -->
    @if($isAdminOrSuperAdmin)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-amber-50/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Pending Leave Approvals</h2>
                        <p class="text-xs text-slate-500">Applications waiting for Superadmin / Admin authorization</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                    {{ $pendingApprovals->count() }} Pending
                </span>
            </div>

            @if($pendingApprovals->count() > 0)
                <div class="divide-y divide-slate-100">
                    @foreach ($pendingApprovals as $req)
                        <div class="p-5 hover:bg-slate-50/60 transition-colors flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <img src="{{ $req->employee->user?->avatar_url }}" alt="{{ $req->employee->full_name }}" class="w-10 h-10 rounded-xl object-cover ring-2 ring-slate-100 shrink-0">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 text-sm">{{ $req->employee->full_name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">({{ $req->employee->employee_id }})</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ $req->leaveType->name }}
                                        </span>
                                        @if($req->is_half_day)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                Half Day ({{ ucfirst(str_replace('_', ' ', $req->half_day_type)) }})
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                                        <span>📅 <strong>{{ $req->start_date->format('d M, Y') }}</strong> to <strong>{{ $req->end_date->format('d M, Y') }}</strong> ({{ $req->days_count }} days)</span>
                                        <span>🏢 {{ $req->employee->department?->name ?? 'Dept' }}</span>
                                        <span class="text-slate-400">Applied {{ $req->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-slate-700 mt-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                        <strong class="text-slate-900">Reason:</strong> {{ $req->reason }}
                                    </p>
                                </div>
                            </div>

                            <!-- Approval Action Buttons -->
                            <div class="flex items-center gap-2 shrink-0 self-end lg:self-center">
                                <form method="POST" action="{{ route('leave.action', $req->id) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="remarks" value="Approved by management">
                                    <button 
                                        type="submit" 
                                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 hover:shadow-lg transition-all flex items-center gap-1.5"
                                        onclick="return confirm('Approve leave for {{ addslashes($req->employee->full_name) }}?')"
                                    >
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>Approve</span>
                                    </button>
                                </form>

                                <button 
                                    type="button" 
                                    onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee->full_name) }}')"
                                    class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition-colors flex items-center gap-1.5"
                                >
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                    <span>Reject</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-slate-400">
                    <i data-lucide="check-check" class="w-8 h-8 mx-auto text-emerald-400 mb-2"></i>
                    <p class="text-sm font-semibold text-slate-700">No pending leave requests!</p>
                    <p class="text-xs text-slate-400 mt-0.5">All incoming employee leave applications have been authorized.</p>
                </div>
            @endif
        </div>
    @endif

    <!-- SECTION 2: Complete Leave Register / History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-sm font-black text-slate-900">Leave Register & History</h2>
                <p class="text-xs text-slate-500">Record of all employee leave applications and authorizations</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('leave.index') }}" class="flex flex-wrap items-center gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search employee..." 
                    class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition-all"
                >

                <select name="status" onchange="this.form.submit()" class="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                <button type="submit" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Leave Type</th>
                        <th class="py-3 px-4">Dates</th>
                        <th class="py-3 px-4">Days</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Actioned By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leaveRequests as $lr)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $lr->employee->user?->avatar_url }}" alt="{{ $lr->employee->full_name }}" class="w-8 h-8 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold text-slate-900 block">{{ $lr->employee->full_name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $lr->employee->employee_id }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-800 block">{{ $lr->leaveType->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $lr->leaveType->code }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-900 block font-mono">
                                    {{ $lr->start_date->format('d M, Y') }}
                                </span>
                                @if($lr->start_date->ne($lr->end_date))
                                    <span class="text-[10px] text-slate-400 font-mono">to {{ $lr->end_date->format('d M, Y') }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                {{ $lr->days_count }}d
                                @if($lr->is_half_day)
                                    <span class="text-[9px] block text-amber-600 font-sans">Half Day</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 max-w-xs truncate text-slate-600" title="{{ $lr->reason }}">
                                {{ $lr->reason }}
                            </td>

                            <td class="py-3.5 px-4">
                                @if($lr->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Approved
                                    </span>
                                @elseif($lr->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending
                                    </span>
                                @elseif($lr->status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ ucfirst($lr->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                @if($lr->actionedByUser)
                                    <span class="font-semibold text-slate-800 block">{{ $lr->actionedByUser->name }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $lr->actioned_at?->format('d M, h:i A') }}</span>
                                    @if($lr->action_remarks)
                                        <span class="text-[10px] text-slate-500 italic block">"{{ $lr->action_remarks }}"</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-semibold">No leave applications found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leaveRequests->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal 1: Apply Leave Modal -->
<div id="applyLeaveModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-700">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Apply for Leave</h3>
                    <p class="text-xs text-slate-500 font-medium">Submit leave application to administration</p>
                </div>
            </div>
            <button type="button" onclick="closeApplyLeaveModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('leave.store') }}">
            @csrf
            <div class="p-5 space-y-4">
                @if($isAdminOrSuperAdmin)
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Applying For Employee</label>
                        <select name="target_employee_id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                            @if($employee)
                                <option value="{{ $employee->id }}">Myself ({{ $employee->full_name }})</option>
                            @endif
                            @foreach (\App\Models\Employee::where('status', 'active')->orderBy('full_name')->get() as $emp)
                                @if(!$employee || $emp->id !== $employee->id)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_id }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Leave Type <span class="text-rose-500">*</span></label>
                    <select name="leave_type_id" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                        @foreach ($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }}) — Quota: {{ $lt->default_annual_quota }}d/yr</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Start Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">End Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="end_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Is Half Day?</span>
                        <span class="text-[10px] text-slate-400">0.5 day deduction</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_half_day" value="1" id="isHalfDayCheckbox" onchange="toggleHalfDaySelect(this.checked)" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                        <select name="half_day_type" id="halfDayTypeSelect" class="hidden text-xs font-semibold px-2 py-1 rounded-lg border border-slate-200 bg-white">
                            <option value="first_half">First Half</option>
                            <option value="second_half">Second Half</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Leave <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Please provide details for leave application..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600"></textarea>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeApplyLeaveModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-md shadow-brand-600/20 transition-all flex items-center gap-1.5">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Submit Application</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Global Policy Quota Configuration Modal (Admin / Superadmin only) -->
@if($isAdminOrSuperAdmin)
    <div id="policyModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-700">
                        <i data-lucide="settings-2" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Global Leave Policy & Quotas</h3>
                        <p class="text-xs text-slate-500 font-medium">Set global annual leave quotas for all company employees</p>
                    </div>
                </div>
                <button type="button" onclick="closePolicyModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('leave.global-policy') }}">
                @csrf
                <div class="p-5 space-y-4">
                    <p class="text-xs text-slate-500 bg-purple-50/60 p-3 rounded-xl border border-purple-100 text-purple-900 leading-relaxed">
                        💡 <strong>Global Leave Balance Settings:</strong> Modifying quotas here updates baseline allowances for all onboarding employees. Check <em>"Synchronize Existing Employees"</em> to update current year employee balances immediately.
                    </p>

                    <div class="space-y-3">
                        @foreach ($leaveTypes as $index => $lt)
                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                                <input type="hidden" name="policies[{{ $index }}][id]" value="{{ $lt->id }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900">{{ $lt->name }} ({{ $lt->code }})</span>
                                    <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600">
                                        <input type="checkbox" name="policies[{{ $index }}][carry_forward]" value="1" {{ $lt->carry_forward ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span>Carry Forward</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-3 gap-3 items-center">
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Annual Quota (Days)</label>
                                        <input 
                                            type="number" 
                                            step="0.5" 
                                            name="policies[{{ $index }}][default_annual_quota]" 
                                            value="{{ $lt->default_annual_quota }}" 
                                            required 
                                            class="w-full text-xs font-mono font-bold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white"
                                        >
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Policy Description</label>
                                        <input 
                                            type="text" 
                                            name="policies[{{ $index }}][description]" 
                                            value="{{ $lt->description }}" 
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3">
                        <input type="checkbox" id="syncCheck" name="sync_existing_employees" value="1" checked class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                        <label for="syncCheck" class="text-xs font-bold text-amber-900 cursor-pointer">
                            Synchronize existing active employees for Year {{ date('Y') }}
                            <span class="block text-[10px] font-normal text-amber-700">Applies new total quotas to active employee balances right now.</span>
                        </label>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closePolicyModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-xl shadow-md shadow-purple-600/20 transition-all flex items-center gap-1.5">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save & Sync Policy</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Reject Reason Modal -->
    <div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-rose-50/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Reject Leave Request</h3>
                        <p id="rejectSubtitle" class="text-xs text-slate-500 font-medium">Specify rejection justification</p>
                    </div>
                </div>
                <button type="button" onclick="closeRejectModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="rejectForm" method="POST" action="">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="p-5 space-y-3">
                    <label class="block text-xs font-bold text-slate-700">Rejection Reason / Remark <span class="text-rose-500">*</span></label>
                    <textarea name="remarks" required rows="3" placeholder="e.g. Critical project milestone in progress; insufficient team coverage..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-600"></textarea>
                    <p class="text-[10px] text-slate-400">This remark will be sent to the employee via the Notification Centre.</p>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-md shadow-rose-600/20">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    function openApplyLeaveModal() {
        document.getElementById('applyLeaveModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeApplyLeaveModal() {
        document.getElementById('applyLeaveModal').classList.add('hidden');
    }

    function toggleHalfDaySelect(isChecked) {
        const select = document.getElementById('halfDayTypeSelect');
        if (isChecked) {
            select.classList.remove('hidden');
        } else {
            select.classList.add('hidden');
        }
    }

    function openPolicyModal() {
        const modal = document.getElementById('policyModal');
        if (modal) modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closePolicyModal() {
        const modal = document.getElementById('policyModal');
        if (modal) modal.classList.add('hidden');
    }

    function openRejectModal(id, empName) {
        const modal = document.getElementById('rejectModal');
        const subtitle = document.getElementById('rejectSubtitle');
        const form = document.getElementById('rejectForm');

        if (subtitle) subtitle.innerText = `Rejection for ${empName}`;
        if (form) form.action = `/leave/${id}/action`;
        if (modal) modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (modal) modal.classList.add('hidden');
    }
</script>
@endsection
