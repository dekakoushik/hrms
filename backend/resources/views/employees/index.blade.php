@extends('layouts.app')

@section('title', 'Employee Directory')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Employee Directory</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Employee Directory</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Manage your India workforce, onboarding records, hierarchy assignments, and account resets.
            </p>
        </div>

        <a 
            href="{{ route('employees.create') }}" 
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-brand-500/20 hover:shadow-lg transition-all self-start sm:self-auto"
        >
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Onboard New Employee</span>
        </a>
    </div>

    <!-- Quick Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Onboarded</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $employees->total() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Staff</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $employees->where('status', 'active')->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Administrators</p>
                <p class="text-2xl font-extrabold text-purple-600 mt-1">{{ $employees->where('user_type', 'admin')->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Departments</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $departments->count() }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="building-2" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            
            <div class="sm:col-span-3 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search ID, Name, Mobile, Email..." 
                    class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                >
            </div>

            <div class="sm:col-span-3">
                <select name="company_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <option value="all" {{ ($filterCompanyId === null || request('company_id') === 'all') ? 'selected' : '' }}>🏢 All Companies</option>
                    @foreach ($companies as $comp)
                        <option value="{{ $comp->id }}" {{ ($filterCompanyId == $comp->id) ? 'selected' : '' }}>
                            {{ $comp->name }} {{ $comp->code ? '(' . $comp->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="department_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="user_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <option value="">All User Types</option>
                    <option value="user" {{ request('user_type') === 'user' ? 'selected' : '' }}>Standard User</option>
                    <option value="admin" {{ request('user_type') === 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'company_id', 'department_id', 'user_type']))
                    <a href="{{ route('employees.index', ['company_id' => 'all']) }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Employee Table -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="py-3.5 px-5">Employee & Company</th>
                        <th class="py-3.5 px-4">Contact & Login</th>
                        <th class="py-3.5 px-4">Department & Designation</th>
                        <th class="py-3.5 px-4">Reporting Manager</th>
                        <th class="py-3.5 px-4">Gross & CTC</th>
                        <th class="py-3.5 px-4">User Type</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($employees as $emp)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($emp->full_name, 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('employees.show', $emp->id) }}" class="font-bold text-slate-900 hover:text-brand-600 transition-colors block leading-snug">
                                            {{ $emp->full_name }}
                                        </a>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] font-mono font-bold text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded">
                                                {{ $emp->employee_id }}
                                            </span>
                                            @if($emp->company)
                                                <span class="text-[10px] font-semibold text-slate-600 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded" title="{{ $emp->company->name }}">
                                                    {{ $emp->company->code ?: Str::limit($emp->company->name, 15) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-800">{{ $emp->user->email ?? $emp->personal_email }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                    <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                    +91 {{ $emp->contact_number }}
                                </div>
                            </td>

                            <td class="py-4 px-4">
                                <div class="text-xs font-bold text-slate-800">{{ $emp->designation->name ?? 'Unassigned' }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ $emp->department->name ?? 'No Department' }}</div>
                            </td>

                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($emp->manager)
                                    <div class="text-xs font-semibold text-slate-800">{{ $emp->manager->full_name }}</div>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $emp->manager->employee_id }}</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Self / Head</span>
                                @endif
                            </td>

                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-xs font-bold text-slate-900">₹{{ number_format($emp->gross_salary, 2) }}</div>
                                <span class="text-[10px] text-slate-500 font-medium">CTC: ₹{{ number_format($emp->ctc_amount, 2) }}</span>
                            </td>

                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($emp->user_type === 'admin')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 uppercase tracking-wide">
                                        <i data-lucide="shield" class="w-3 h-3 text-purple-600"></i>
                                        Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase tracking-wide">
                                        User
                                    </span>
                                @endif

                                <div class="mt-1">
                                    @if ($emp->status === 'active')
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded-md border border-rose-200">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <a 
                                        href="{{ route('employees.show', $emp->id) }}" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                        title="View Profile & Documents"
                                    >
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <a 
                                        href="{{ route('employees.edit', $emp->id) }}" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                        title="Edit Complete Profile"
                                    >
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Quick Toggle Active / Inactive -->
                                    <form method="POST" action="{{ route('employees.toggle-status', $emp->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit" 
                                            onclick="return confirm('Toggle status for {{ addslashes($emp->full_name) }} to {{ $emp->status === 'active' ? 'INACTIVE' : 'ACTIVE' }}?')"
                                            class="p-1.5 rounded-lg text-slate-500 hover:{{ $emp->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                            title="{{ $emp->status === 'active' ? 'Deactivate (Suspend Login)' : 'Activate (Enable Login)' }}"
                                        >
                                            <i data-lucide="{{ $emp->status === 'active' ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                                        </button>
                                    </form>

                                    <button 
                                        type="button" 
                                        onclick="openResetPasswordModal('{{ $emp->id }}', '{{ addslashes($emp->full_name) }}', '{{ $emp->employee_id }}')" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                        title="Reset Password"
                                    >
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Superadmin Only: Delete -->
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('employees.destroy', $emp->id) }}" onsubmit="return confirm('Permanently delete {{ addslashes($emp->full_name) }} and linked user?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                title="Delete Employee (Superadmin Only)"
                                            >
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                <i data-lucide="user-x" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                No employees found. Click "Onboard New Employee" to register your workforce.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Reset Employee Password</h3>
            <button type="button" onclick="closeResetPasswordModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="resetPasswordForm" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            <div>
                <p class="text-xs text-slate-500 mb-1">Resetting password for:</p>
                <p id="resetEmployeeName" class="text-sm font-bold text-slate-900"></p>
                <p id="resetEmployeeId" class="text-xs font-mono text-brand-600"></p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">New Password *</label>
                <input 
                    type="password" 
                    name="new_password" 
                    required 
                    minlength="8"
                    placeholder="Enter at least 8 characters..." 
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                >
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button 
                    type="button" 
                    onclick="closeResetPasswordModal()" 
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                    <span>Confirm Reset</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openResetPasswordModal(id, name, empId) {
        document.getElementById('resetEmployeeName').textContent = name;
        document.getElementById('resetEmployeeId').textContent = empId;
        document.getElementById('resetPasswordForm').action = '/employees/' + id + '/reset-password';
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    }
</script>
@endpush
