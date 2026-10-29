@extends('layouts.app')

@section('title', 'Resignation & Exit Management')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <span>Core HRMS</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-700">Exit Management</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Resignation & Exit Workflow</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $isAdmin ? 'bg-indigo-50 text-brand-700 border border-brand-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ $isAdmin ? 'Admin Authorization Center' : 'Employee Exit Portal' }}
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Manage resignation notices, notice period extension, last working day (LWD) signoffs and company handover.
            </p>
        </div>

        @if($employee && (!$activeResignation || $activeResignation->status === 'rejected'))
            <div>
                <button 
                    type="button" 
                    onclick="openResignationModal()"
                    class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 hover:shadow-lg transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="user-minus" class="w-4 h-4"></i>
                    <span>Apply for Resignation / Exit</span>
                </button>
            </div>
        @endif
    </div>

    <!-- Active Resignation Banner for Employee -->
    @if($activeResignation)
        <div class="p-5 rounded-2xl {{ $activeResignation->status === 'approved' ? 'bg-gradient-to-r from-emerald-900 to-slate-900' : 'bg-gradient-to-r from-amber-900 via-orange-950 to-slate-900' }} text-white shadow-xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $activeResignation->status === 'approved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30' }}">
                            Resignation {{ ucfirst($activeResignation->status) }}
                        </span>
                        <span class="text-xs text-slate-300">Submitted: {{ $activeResignation->resignation_date->format('d M Y') }}</span>
                    </div>
                    <h3 class="text-lg font-black text-white">
                        @if($activeResignation->status === 'approved')
                            Official Last Working Day: {{ $activeResignation->approved_last_working_day?->format('d M Y') ?: 'Confirmed' }}
                        @else
                            Requested Last Working Day: {{ $activeResignation->requested_last_working_day->format('d M Y') }} (Under Admin Review)
                        @endif
                    </h3>
                    <p class="text-xs text-slate-300 max-w-2xl">
                        <strong>Reason:</strong> {{ $activeResignation->reason }}
                    </p>
                    @if($activeResignation->action_remarks)
                        <div class="mt-2 p-2.5 rounded-xl bg-white/10 text-xs text-amber-200">
                            <strong>Admin Remarks:</strong> {{ $activeResignation->action_remarks }}
                            @if($activeResignation->actionedByUser)
                                <span class="text-slate-400"> (by {{ $activeResignation->actionedByUser->name }} on {{ $activeResignation->actioned_at?->format('d M Y') }})</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10 text-center shrink-0 min-w-[160px]">
                    <span class="text-[10px] uppercase font-bold text-slate-300 block">Notice Period</span>
                    <span class="text-2xl font-black text-white font-mono block mt-1">{{ $activeResignation->notice_period_days }} Days</span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">
                        {{ $activeResignation->status === 'approved' ? 'Handover In Progress' : 'Pending Authorization' }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- Resignation Registry Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-700">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900">
                        {{ $isAdmin ? 'All Exit & Resignation Records' : 'My Resignation History' }}
                    </h2>
                    <p class="text-xs text-slate-500">Record of resignation filings, approved notice periods, and clearance dates</p>
                </div>
            </div>
            <span class="text-xs font-bold text-slate-400">{{ $resignations->total() }} Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Applied Date</th>
                        <th class="py-3 px-4">Requested LWD</th>
                        <th class="py-3 px-4">Notice Days</th>
                        <th class="py-3 px-4">Approved LWD</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Reason & Remarks</th>
                        @if($isAdmin)
                            <th class="py-3 px-4 text-right">Admin Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($resignations as $res)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                                        {{ substr($res->employee?->first_name ?: 'E', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 block">{{ $res->employee?->full_name ?: 'Employee' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $res->employee?->employee_id }} • {{ $res->employee?->designation?->name ?: 'Staff' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                {{ $res->resignation_date->format('d M Y') }}
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                {{ $res->requested_last_working_day->format('d M Y') }}
                            </td>

                            <td class="py-3.5 px-4 font-mono">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 font-bold">{{ $res->notice_period_days }}d</span>
                            </td>

                            <td class="py-3.5 px-4 font-mono">
                                @if($res->approved_last_working_day)
                                    <span class="font-bold text-emerald-700">{{ $res->approved_last_working_day->format('d M Y') }}</span>
                                @else
                                    <span class="text-slate-400 italic">Pending approval</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($res->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i>
                                        Approved
                                    </span>
                                @elseif($res->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i data-lucide="x-circle" class="w-3 h-3"></i>
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        Under Review
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="text-[11px] text-slate-700 truncate" title="{{ $res->reason }}">
                                    <strong>Reason:</strong> {{ $res->reason }}
                                </p>
                                @if($res->action_remarks)
                                    <p class="text-[10px] text-slate-400 truncate mt-0.5" title="{{ $res->action_remarks }}">
                                        <strong>Admin:</strong> {{ $res->action_remarks }}
                                    </p>
                                @endif
                            </td>

                            @if($isAdmin)
                                <td class="py-3.5 px-4 text-right">
                                    @if($res->status === 'pending')
                                        <button 
                                            type="button" 
                                            onclick='openAdminActionModal(@json($res))'
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition-colors"
                                        >
                                            <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                            <span>Review / Action</span>
                                        </button>
                                    @else
                                        <button 
                                            type="button" 
                                            onclick='openAdminActionModal(@json($res))'
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                                        >
                                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                                            <span>Update LWD</span>
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 8 : 7 }}" class="py-10 text-center text-slate-400">
                                <i data-lucide="user-check" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                <p class="text-xs font-semibold">No resignation requests found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($resignations->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $resignations->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL: Apply for Resignation (Employee) -->
<div id="applyResignationModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-rose-50 to-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
                    <i data-lucide="user-minus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Resignation / Exit Submission</h3>
                    <p class="text-xs text-slate-500">Initiate separation notice with management</p>
                </div>
            </div>
            <button type="button" onclick="closeResignationModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('resignations.store') }}" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Requested Last Working Day (LWD) <span class="text-rose-600">*</span>
                </label>
                <input 
                    type="date" 
                    name="requested_last_working_day" 
                    id="req_lwd" 
                    required 
                    min="{{ date('Y-m-d') }}"
                    value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                <span class="text-[11px] text-slate-400 block mt-1">Recommended standard notice is 30 days as per company employment terms.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Notice Period Days
                </label>
                <input 
                    type="number" 
                    name="notice_period_days" 
                    value="30" 
                    min="0" 
                    max="180" 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Reason for Resignation <span class="text-rose-600">*</span>
                </label>
                <textarea 
                    name="reason" 
                    rows="4" 
                    required 
                    placeholder="Please specify detailed rationale for exit (e.g., Higher education, better opportunity, relocation, personal reasons)..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                ></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button 
                    type="button" 
                    onclick="closeResignationModal()" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Submit Resignation Notice</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Admin Review, Approve, Reject or Extend Notice Period -->
<div id="adminActionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-brand-50 to-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Admin Resignation Decision</h3>
                    <p class="text-xs text-slate-500" id="adminModalEmpInfo">Approve, reject, or adjust notice period</p>
                </div>
            </div>
            <button type="button" onclick="closeAdminActionModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="adminActionForm" method="POST" action="" class="p-6 space-y-4">
            @csrf

            <!-- Action Select -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Decision Action <span class="text-rose-600">*</span>
                </label>
                <select 
                    name="action" 
                    id="actionSelect" 
                    onchange="toggleActionFields()" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="approve">✅ Approve Resignation (Set Final LWD)</option>
                    <option value="extend_notice">⏳ Extend / Adjust Notice Period (Revise LWD)</option>
                    <option value="reject">❌ Reject Resignation Request</option>
                </select>
            </div>

            <!-- Approved Last Working Day -->
            <div id="approvedLwdField">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Official Last Working Day (LWD)
                </label>
                <input 
                    type="date" 
                    name="approved_last_working_day" 
                    id="admin_approved_lwd" 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                <span class="text-[11px] text-slate-400 block mt-1">This will be updated in the employee profile as their official exit date.</span>
            </div>

            <!-- Notice Period Days (for extend_notice) -->
            <div id="noticeDaysField">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Notice Period Days
                </label>
                <input 
                    type="number" 
                    name="notice_period_days" 
                    id="admin_notice_days" 
                    min="0" 
                    max="180" 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
            </div>

            <!-- Remarks -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Official Remarks / Feedback <span class="text-rose-600">*</span>
                </label>
                <textarea 
                    name="action_remarks" 
                    id="admin_remarks" 
                    rows="3" 
                    required 
                    placeholder="Provide justification, handover notes, or reasons for approval/rejection/extension..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500"
                ></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button 
                    type="button" 
                    onclick="closeAdminActionModal()" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirm Decision</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openResignationModal() {
    document.getElementById('applyResignationModal').classList.remove('hidden');
}

function closeResignationModal() {
    document.getElementById('applyResignationModal').classList.add('hidden');
}

function openAdminActionModal(resignation) {
    const modal = document.getElementById('adminActionModal');
    const form = document.getElementById('adminActionForm');
    const empInfo = document.getElementById('adminModalEmpInfo');
    const lwdInput = document.getElementById('admin_approved_lwd');
    const noticeInput = document.getElementById('admin_notice_days');

    form.action = `/resignations/${resignation.id}/action`;
    empInfo.innerText = `Employee: ${resignation.employee ? resignation.employee.full_name : 'N/A'} (Requested LWD: ${resignation.requested_last_working_day.substring(0, 10)})`;
    
    lwdInput.value = resignation.approved_last_working_day 
        ? resignation.approved_last_working_day.substring(0, 10) 
        : resignation.requested_last_working_day.substring(0, 10);
    
    noticeInput.value = resignation.notice_period_days || 30;

    modal.classList.remove('hidden');
    toggleActionFields();
}

function closeAdminActionModal() {
    document.getElementById('adminActionModal').classList.add('hidden');
}

function toggleActionFields() {
    const action = document.getElementById('actionSelect').value;
    const lwdField = document.getElementById('approvedLwdField');
    const noticeField = document.getElementById('noticeDaysField');

    if (action === 'reject') {
        lwdField.style.display = 'none';
        noticeField.style.display = 'none';
    } else if (action === 'extend_notice') {
        lwdField.style.display = 'block';
        noticeField.style.display = 'block';
    } else {
        lwdField.style.display = 'block';
        noticeField.style.display = 'block';
    }
}
</script>
@endsection
