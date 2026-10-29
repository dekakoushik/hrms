@extends('layouts.app')

@section('title', 'Shift Manager - Settings')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-slate-400">Settings</span>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Shift Manager</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Shift Manager</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Configure corporate & industrial work shifts, grace periods, half-day rules, and night shift policies.
            </p>
        </div>

        <button 
            type="button" 
            onclick="openCreateShiftModal()" 
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-brand-500/20 hover:shadow-lg transition-all self-start sm:self-auto"
        >
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Add New Shift</span>
        </button>
    </div>

    <!-- Quick Stats Cards for Shifts -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Defined Shifts</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $shifts->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Shifts</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $shifts->where('is_active', true)->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Night Rotations</p>
                <p class="text-2xl font-extrabold text-purple-600 mt-1">{{ $shifts->where('is_night_shift', true)->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="moon" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Shifts Data Table -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Configured Roster Shifts</h3>
                <p class="text-xs text-slate-500">Shifts applicable to biometrics, manual log, and geofenced attendance.</p>
            </div>
            <span class="text-xs text-slate-500 font-medium">Standard Indian Labor Standard (8h work + breaks)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="py-3.5 px-5">Shift Name & Code</th>
                        <th class="py-3.5 px-4">Timing</th>
                        <th class="py-3.5 px-4">Duration</th>
                        <th class="py-3.5 px-4">Grace Time</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($shifts as $shift)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900">{{ $shift->name }}</div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[11px] font-mono px-2 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $shift->code }}</span>
                                    @if ($shift->description)
                                        <span class="text-[11px] text-slate-400 truncate max-w-xs">{{ $shift->description }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-800">
                                    {{ date('h:i A', strtotime($shift->start_time)) }} - {{ date('h:i A', strtotime($shift->end_time)) }}
                                </div>
                                <span class="text-[11px] text-slate-400">24h format: {{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}</span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-700">{{ $shift->full_day_hours }}h Full</span>
                                <span class="text-slate-400 text-xs"> / {{ $shift->half_day_hours }}h Half</span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                    <i data-lucide="timer" class="w-3.5 h-3.5 text-slate-400"></i>
                                    {{ $shift->grace_period_minutes }} mins
                                </span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($shift->is_night_shift)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                        <i data-lucide="moon" class="w-3 h-3 text-purple-600"></i>
                                        Night Shift
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                        <i data-lucide="sun" class="w-3 h-3 text-amber-600"></i>
                                        Day Shift
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($shift->is_active)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        type="button" 
                                        onclick="openEditShiftModal({{ json_encode($shift) }})" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                        title="Edit Shift"
                                    >
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <form method="POST" action="{{ route('settings.shifts.delete', $shift->id) }}" onsubmit="return confirm('Are you sure you want to delete this shift?');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Delete Shift"
                                        >
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                No work shifts defined yet. Click "Add New Shift" to create your first shift schedule.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Create / Edit Shift -->
<div id="shiftModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 transform transition-all">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 id="modalTitle" class="text-lg font-bold text-slate-900">Create Work Shift</h3>
            <button type="button" onclick="closeShiftModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="shiftForm" method="POST" action="{{ route('settings.shifts.store') }}" class="mt-5 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <!-- Name & Code -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Shift Name *</label>
                    <input type="text" id="shift_name" name="name" required placeholder="e.g. Morning Shift" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Code *</label>
                    <input type="text" id="shift_code" name="code" required placeholder="e.g. MORN-01" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white uppercase transition-all">
                </div>
            </div>

            <!-- Start & End Time -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Start Time (24h) *</label>
                    <input type="time" id="shift_start" name="start_time" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">End Time (24h) *</label>
                    <input type="time" id="shift_end" name="end_time" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Grace Period & Hours -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Grace (Mins)</label>
                    <input type="number" id="shift_grace" name="grace_period_minutes" value="15" min="0" max="120" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Half Day (h)</label>
                    <input type="number" id="shift_half" name="half_day_hours" value="4" min="1" max="12" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Full Day (h)</label>
                    <input type="number" id="shift_full" name="full_day_hours" value="8" min="1" max="24" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Description</label>
                <input type="text" id="shift_description" name="description" placeholder="Optional details..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
            </div>

            <!-- Checkboxes: Night Shift & Active -->
            <div class="pt-2 flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="shift_night" name="is_night_shift" value="1" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                    <span class="text-xs font-medium text-slate-700 select-none">Crosses midnight (Night Shift)</span>
                </label>

                <div id="activeToggleWrapper" class="hidden">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="shift_active" name="is_active" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <span class="text-xs font-medium text-slate-700 select-none">Active</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeShiftModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20 transition-all">
                    Save Shift
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const modal = document.getElementById('shiftModal');
    const form = document.getElementById('shiftForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');
    const activeToggle = document.getElementById('activeToggleWrapper');

    function openCreateShiftModal() {
        modalTitle.textContent = 'Create Work Shift';
        form.action = "{{ route('settings.shifts.store') }}";
        methodSpoof.innerHTML = '';
        form.reset();
        activeToggle.classList.add('hidden');
        modal.classList.remove('hidden');
    }

    function openEditShiftModal(shift) {
        modalTitle.textContent = 'Edit Work Shift: ' + shift.name;
        form.action = "/settings/shifts/" + shift.id;
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        
        document.getElementById('shift_name').value = shift.name;
        document.getElementById('shift_code').value = shift.code;
        document.getElementById('shift_start').value = shift.start_time.substring(0, 5);
        document.getElementById('shift_end').value = shift.end_time.substring(0, 5);
        document.getElementById('shift_grace').value = shift.grace_period_minutes;
        document.getElementById('shift_half').value = shift.half_day_hours;
        document.getElementById('shift_full').value = shift.full_day_hours;
        document.getElementById('shift_description').value = shift.description || '';
        document.getElementById('shift_night').checked = Boolean(shift.is_night_shift);
        document.getElementById('shift_active').checked = Boolean(shift.is_active);

        activeToggle.classList.remove('hidden');
        modal.classList.remove('hidden');
    }

    function closeShiftModal() {
        modal.classList.add('hidden');
    }
</script>
@endpush
