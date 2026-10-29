@extends('layouts.app')

@section('title', 'Attendance Management & Logs - Enterprise HRMS')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Attendance</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $isAdmin ? 'Attendance & Timesheets' : 'My Attendance History' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                {{ $isAdmin ? 'Real-time geo-verified punches, shift grace period tracking, and work hours calculation.' : 'Log of your daily punch records, hours worked, and attendance status.' }}
            </p>
        </div>

        @if($isAdmin)
            <div class="flex items-center gap-2">
                <a href="{{ route('settings.locations') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs shadow-sm transition-colors">
                    <i data-lucide="map-pin" class="w-4 h-4 text-brand-600"></i>
                    <span>Manage Office Geofences</span>
                </a>
                <a href="{{ route('settings.shifts') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs shadow-sm transition-colors">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                    <span>Shift Rules & Grace Period</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Quick Daily KPI Metric Counters -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Today Total Punches</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-slate-900">{{ $totalPunched }}</span>
                <span class="text-xs text-slate-500">Employees</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Present (On Time)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-emerald-700">{{ $presentCount }}</span>
                <span class="text-xs text-emerald-600 font-semibold">Active</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Late Arrival (Grace/Limit)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-amber-700">{{ $lateCount }}</span>
                <span class="text-xs text-amber-600 font-semibold">Late</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider block">Marked Half Day</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-rose-700">{{ $halfDayCount }}</span>
                <span class="text-xs text-rose-600 font-semibold">Half Day</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-800">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="half_day" {{ request('status') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                </select>
            </div>

            @if($isAdmin)
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Search Employee</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or Employee ID..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                </div>
            @endif

            <div class="flex items-end gap-2">
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors w-full">
                    Filter Logs
                </button>
                <a href="{{ route('attendance.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Attendance Log Records</h3>
                    <p class="text-[11px] text-slate-400">Date: {{ \Carbon\Carbon::parse($date)->format('d M, Y (l)') }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Assigned Shift</th>
                        <th class="py-3 px-4">Location Mode</th>
                        <th class="py-3 px-4">Punch In Time</th>
                        <th class="py-3 px-4">Punch Out Time</th>
                        <th class="py-3 px-4">Total Hours</th>
                        <th class="py-3 px-4">Status & Grace</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $att)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $att->employee->user?->avatar_url }}" alt="{{ $att->employee->full_name }}" class="w-8 h-8 rounded-lg object-cover">
                                    <div>
                                        <a href="{{ route('employees.show', $att->employee_id) }}" class="font-bold text-slate-900 hover:text-brand-600 transition-colors block">
                                            {{ $att->employee->full_name }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $att->employee->employee_id }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800 block">{{ $att->shift?->name ?: 'General Day' }}</span>
                                <span class="text-[10px] text-slate-400">
                                    {{ $att->shift ? substr($att->shift->start_time, 0, 5) . ' - ' . substr($att->shift->end_time, 0, 5) : '09:30 - 18:30' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($att->is_field_visit)
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-300">
                                            <i data-lucide="briefcase" class="w-3 h-3 text-amber-700"></i>
                                            <span>Field Visit</span>
                                        </span>
                                        @if($att->punch_in_lat && $att->punch_in_lng)
                                            <span class="text-[10px] text-slate-500 block font-mono">
                                                {{ round($att->punch_in_lat, 4) }}, {{ round($att->punch_in_lng, 4) }}
                                            </span>
                                        @endif
                                        @if($att->field_visit_selfie)
                                            <button 
                                                type="button" 
                                                onclick="openSelfieModal('{{ asset('storage/' . $att->field_visit_selfie) }}', '{{ addslashes($att->employee->full_name) }}', '{{ addslashes($att->field_visit_remarks ?? '') }}', '{{ $att->punch_in_lat }}, {{ $att->punch_in_lng }}')" 
                                                class="inline-flex items-center gap-1 text-[10px] font-semibold text-brand-600 hover:text-brand-800 hover:underline"
                                            >
                                                <i data-lucide="camera" class="w-3 h-3"></i>
                                                <span>View Selfie & Notes</span>
                                            </button>
                                        @elseif($att->field_visit_remarks)
                                            <span class="text-[10px] text-slate-500 italic block line-clamp-1" title="{{ $att->field_visit_remarks }}">
                                                "{{ $att->field_visit_remarks }}"
                                            </span>
                                        @endif
                                    </div>
                                @elseif($att->punch_in_location_type === 'random')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        <i data-lucide="compass" class="w-3 h-3"></i>
                                        <span>Random / GPS</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span>{{ $att->punchInLocation?->name ?: 'Fixed Office' }}</span>
                                    </span>
                                    @if($att->punch_in_distance_meters !== null)
                                        <span class="text-[10px] text-slate-400 block font-mono">Distance: {{ round($att->punch_in_distance_meters) }}m</span>
                                    @endif
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-emerald-700 block">
                                    {{ $att->punch_in_at ? $att->punch_in_at->format('h:i A') : '—' }}
                                </span>
                                @if($att->is_late)
                                    <span class="text-[10px] text-rose-600 font-semibold block">Late by {{ $att->late_minutes }}m</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-slate-800 block">
                                    {{ $att->punch_out_at ? $att->punch_out_at->format('h:i A') : 'Not Punched Out' }}
                                </span>
                                @if($att->is_early_exit)
                                    <span class="text-[10px] text-amber-600 font-semibold block">Early exit by {{ $att->early_minutes }}m</span>
                                @endif
                                @if($att->punch_out_count > 1)
                                    <span class="text-[10px] text-indigo-600 font-semibold block">Punched out {{ $att->punch_out_count }} times</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                {{ $att->working_hours > 0 ? $att->working_hours . ' hrs' : '—' }}
                            </td>

                            <td class="py-3.5 px-4">
                                @if($att->status === 'half_day')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Half Day
                                    </span>
                                @elseif($att->status === 'present')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Present
                                    </span>
                                @elseif($att->status === 'on_duty')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        On Duty
                                    </span>
                                @elseif($att->status === 'leave')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Leave
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ ucfirst($att->status) }}
                                    </span>
                                @endif

                                @if($att->is_regularized)
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded mt-1 block w-fit" title="Regularized by {{ $att->regularizer?->name ?? 'Admin' }}: {{ $att->regularization_reason }}">
                                        <i data-lucide="check-check" class="w-2.5 h-2.5"></i>
                                        <span>Regularized</span>
                                    </span>
                                @elseif($att->used_grace_period)
                                    <span class="text-[10px] text-amber-700 block mt-0.5 font-medium">Used Grace (≤5 days/mo)</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                                    <button 
                                        type="button"
                                        onclick="openRegularizeModal({
                                            id: {{ $att->id }},
                                            name: '{{ addslashes($att->employee->full_name) }}',
                                            date: '{{ $att->attendance_date->format('d M, Y') }}',
                                            status: '{{ $att->status }}',
                                            punchIn: '{{ $att->punch_in_at ? $att->punch_in_at->format('H:i') : '' }}',
                                            punchOut: '{{ $att->punch_out_at ? $att->punch_out_at->format('H:i') : '' }}',
                                            reason: '{{ addslashes($att->regularization_reason ?? '') }}'
                                        })"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 border border-slate-200 transition-all shadow-xs"
                                        title="Regularise Attendance"
                                    >
                                        <i data-lucide="sliders" class="w-3.5 h-3.5 text-brand-600"></i>
                                        <span>Regularise</span>
                                    </button>
                                @else
                                    <span class="text-[11px] text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-semibold">No attendance records found for this date.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Employees can mark their Punch In directly from their portal Dashboard.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Attendance Regularization Modal -->
<div id="regularizeModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-700">
                    <i data-lucide="sliders" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Regularise Attendance</h3>
                    <p id="regularizeSubtitle" class="text-xs text-slate-500 font-medium">Employee attendance override</p>
                </div>
            </div>
            <button type="button" onclick="closeRegularizeModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="regularizeForm" method="POST" action="">
            @csrf
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Attendance Status <span class="text-rose-500">*</span></label>
                    <select name="status" id="regStatus" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition-all">
                        <option value="present">Present (Full Day)</option>
                        <option value="half_day">Half Day</option>
                        <option value="on_duty">On Duty (Field / Client Site)</option>
                        <option value="leave">Leave Approved</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Punch In Time (24h)</label>
                        <input type="time" name="punch_in_time" id="regPunchIn" class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Punch Out Time (24h)</label>
                        <input type="time" name="punch_out_time" id="regPunchOut" class="w-full text-xs font-mono font-semibold px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Regularization Reason / Remark <span class="text-rose-500">*</span></label>
                    <textarea name="reason" id="regReason" required rows="2" placeholder="e.g. Employee visited client meeting; approved early exit due to company event..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">This remark will be permanently logged with your Admin ID and timestamp.</p>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRegularizeModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-xl shadow-md shadow-brand-600/20 transition-all flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Apply Regularisation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Field Visit Selfie & Verification Modal -->
<div id="selfieModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200">
        <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                <h4 class="text-xs font-bold" id="selfieModalTitle">Field Visit Verification</h4>
            </div>
            <button type="button" onclick="closeSelfieModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <div class="rounded-xl overflow-hidden bg-slate-100 border border-slate-200 text-center">
                <img id="selfieModalImg" src="" alt="Field Selfie" class="w-full max-h-72 object-cover">
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                <div class="flex items-start justify-between gap-2">
                    <span class="text-[10px] font-bold uppercase text-slate-400">GPS Coordinates</span>
                    <span id="selfieModalCoords" class="text-xs font-mono font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Remarks / Reason</span>
                    <p id="selfieModalRemarks" class="text-xs text-slate-700 italic"></p>
                </div>
            </div>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100 text-right">
            <button type="button" onclick="closeSelfieModal()" class="px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function openRegularizeModal(data) {
        document.getElementById('regularizeSubtitle').innerText = `${data.name} • ${data.date}`;
        document.getElementById('regStatus').value = data.status;
        document.getElementById('regPunchIn').value = data.punchIn || '';
        document.getElementById('regPunchOut').value = data.punchOut || '';
        document.getElementById('regReason').value = data.reason || '';

        const form = document.getElementById('regularizeForm');
        form.action = `/attendance/${data.id}/regularize`;

        document.getElementById('regularizeModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeRegularizeModal() {
        document.getElementById('regularizeModal').classList.add('hidden');
    }

    function openSelfieModal(url, name, remarks, coords) {
        document.getElementById('selfieModalTitle').innerText = `Field Visit: ${name}`;
        document.getElementById('selfieModalImg').src = url;
        document.getElementById('selfieModalRemarks').innerText = remarks ? `"${remarks}"` : 'No remarks provided.';
        document.getElementById('selfieModalCoords').innerText = coords || 'Not available';
        document.getElementById('selfieModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeSelfieModal() {
        document.getElementById('selfieModal').classList.add('hidden');
    }
</script>
@endsection
