@extends('layouts.app')

@section('title', $employee->full_name . ' - Employee Profile')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <a href="{{ route('employees.index') }}" class="hover:text-brand-600 transition-colors">Employees</a>
                <span>/</span>
                <span class="text-brand-600 font-semibold">{{ $employee->employee_id }}</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $employee->full_name }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                {{ $employee->designation->name ?? 'Staff' }} • {{ $employee->department->name ?? 'General Department' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Edit Profile Button -->
            <a 
                href="{{ route('employees.edit', $employee->id) }}" 
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-brand-700 font-bold text-xs rounded-xl border border-indigo-200 transition-colors flex items-center gap-1.5 shadow-sm"
            >
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                <span>Edit Profile</span>
            </a>

            <!-- Toggle Active / Inactive Button -->
            <form method="POST" action="{{ route('employees.toggle-status', $employee->id) }}">
                @csrf
                @method('PATCH')
                <button 
                    type="submit" 
                    onclick="return confirm('Are you sure you want to change employee status to {{ $employee->status === 'active' ? 'INACTIVE' : 'ACTIVE' }}?')"
                    class="px-3.5 py-2 font-bold text-xs rounded-xl border transition-colors flex items-center gap-1.5 shadow-sm {{ $employee->status === 'active' ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' }}"
                >
                    <i data-lucide="{{ $employee->status === 'active' ? 'user-x' : 'user-check' }}" class="w-3.5 h-3.5"></i>
                    <span>{{ $employee->status === 'active' ? 'Mark Inactive' : 'Mark Active' }}</span>
                </button>
            </form>

            <!-- Reset Password Button -->
            <button 
                type="button" 
                onclick="document.getElementById('resetPwdModal').classList.remove('hidden')" 
                class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition-colors flex items-center gap-1.5 shadow-sm"
            >
                <i data-lucide="key" class="w-3.5 h-3.5"></i>
                <span>Reset Pwd</span>
            </button>

            <!-- Superadmin Only: Delete Employee -->
            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('employees.destroy', $employee->id) }}" onsubmit="return confirm('CRITICAL WARNING: Are you sure you want to permanently delete employee {{ $employee->full_name }} ({{ $employee->employee_id }})? This action CANNOT be undone and will delete linked user credentials.')">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit" 
                        class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition-colors flex items-center gap-1.5 shadow-sm"
                    >
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Delete</span>
                    </button>
                </form>
            @endif

            <a 
                href="{{ route('employees.index') }}" 
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
            >
                Back
            </a>
        </div>
    </div>

    <!-- Header Identity Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-brand-700 to-indigo-500 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-md">
            {{ substr($employee->full_name, 0, 2) }}
        </div>
        <div class="flex-1 text-center sm:text-left space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <h2 class="text-xl font-extrabold text-slate-900">{{ $employee->full_name }}</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold uppercase tracking-wider bg-brand-50 text-brand-700 border border-brand-200 self-center sm:self-auto">
                    {{ $employee->employee_id }}
                </span>
                @if($employee->company)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 self-center sm:self-auto">
                        🏢 {{ $employee->company->name }}
                    </span>
                @endif
                <!-- Status Badge -->
                @if ($employee->status === 'active')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1 self-center sm:self-auto">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 self-center sm:self-auto">
                        Inactive / {{ ucfirst($employee->status) }}
                    </span>
                @endif

                <!-- Employment Type Badge -->
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $employee->employment_type === 'Probation' ? 'bg-amber-50 text-amber-800 border border-amber-300' : 'bg-blue-50 text-blue-700 border border-blue-200' }} self-center sm:self-auto">
                    {{ $employee->employment_type ?: 'Probation' }}
                </span>

                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 self-center sm:self-auto">
                    {{ $employee->user_type === 'admin' ? 'Staff Administrator' : 'Standard User' }}
                </span>
            </div>

            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-slate-600 pt-1">
                <span class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                    {{ $employee->user->email ?? $employee->personal_email }}
                </span>
                <span class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                    +91 {{ $employee->contact_number }}
                </span>
                @if($employee->manager)
                    <span class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                        Manager: {{ $employee->manager->full_name }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg font-semibold">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-emerald-600"></i>
                    Joined: {{ $employee->joining_date ? $employee->joining_date->format('d M Y') : 'N/A' }}
                </span>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Details and Compensation -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left 7 Cols: Personal, Statutory, Bank & Education -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Personal & Present Address -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Personal & Residential Details
                </h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div><span class="text-slate-400 block">Father/Spouse Name:</span><span class="font-bold text-slate-800">{{ $employee->father_or_husband_name ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Date of Birth:</span><span class="font-bold text-slate-800">{{ $employee->dob ? $employee->dob->format('d M Y') : 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Gender / Marital:</span><span class="font-bold text-slate-800">{{ $employee->gender ?: 'N/A' }} / {{ $employee->marital_status ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Blood Group:</span><span class="font-bold text-slate-800">{{ $employee->blood_group ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Personal Email:</span><span class="font-bold text-slate-800">{{ $employee->personal_email ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Alternate Contact:</span><span class="font-bold text-slate-800">{{ $employee->alternate_number ?: 'N/A' }}</span></div>
                </div>

                <div class="pt-3 border-t border-slate-100 text-xs">
                    <span class="text-slate-400 block">Current Address:</span>
                    <span class="font-semibold text-slate-800">
                        {{ $employee->present_street }}, {{ $employee->present_city }}, {{ $employee->present_state }} - {{ $employee->present_pin }}
                    </span>
                </div>
            </div>

            <!-- Probation & Employment Lifecycle Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-brand-600"></i>
                        Probation & Employment Lifecycle
                    </h3>
                    @if($employee->isOnProbation())
                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                            In Probation ({{ $employee->probation_days_remaining ?? '0' }} days remaining)
                        </span>
                    @elseif($employee->confirmation_date)
                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                            Confirmed Permanent
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block">Employment Type:</span>
                        <span class="font-bold text-slate-800">{{ $employee->employment_type ?: 'Probation' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Joining Date:</span>
                        <span class="font-bold text-slate-800">{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Probation Duration:</span>
                        <span class="font-bold text-slate-800">{{ $employee->probation_months ?? 6 }} Months</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Probation End Date:</span>
                        <span class="font-bold text-amber-700">{{ $employee->probation_end_date ? $employee->probation_end_date->format('d M Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Confirmation Date:</span>
                        <span class="font-bold text-emerald-700">{{ $employee->confirmation_date ? $employee->confirmation_date->format('d M Y') : 'Pending Confirmation' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Exit / Relieving:</span>
                        <span class="font-bold text-slate-800">{{ $employee->exit_date ? $employee->exit_date->format('d M Y') : 'Active Service' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Assigned Work Shift:</span>
                        <span class="font-bold text-brand-700">
                            {{ $employee->shift?->name ?: 'General Day Shift' }}
                            @if($employee->shift)
                                <span class="text-[10px] text-slate-500 font-normal">({{ substr($employee->shift->start_time, 0, 5) }} - {{ substr($employee->shift->end_time, 0, 5) }}, Grace: {{ $employee->shift->grace_period_minutes }}m)</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Attendance Location:</span>
                        @if($employee->location_type === 'random')
                            <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-[11px] border border-amber-200">
                                <span>Random / Field Location</span>
                            </span>
                        @else
                            <span class="font-bold text-slate-800">
                                {{ $employee->location?->name ?: 'Fixed Office' }}
                                <span class="text-[10px] text-emerald-700 font-semibold block">200m Geofence Active</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Emergency Contact & Statutory -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Emergency Contact & Statutory Identifiers
                </h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div><span class="text-slate-400 block">Emergency Contact:</span><span class="font-bold text-slate-800">{{ $employee->primary_contact_name ?: 'N/A' }} ({{ $employee->primary_contact_relationship }})</span></div>
                    <div><span class="text-slate-400 block">Emergency Phone:</span><span class="font-bold text-slate-800">{{ $employee->primary_contact_phone ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">PAN Number:</span><span class="font-mono font-bold text-slate-800">{{ $employee->pan_number ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Aadhaar Number:</span><span class="font-mono font-bold text-slate-800">{{ $employee->aadhar_number ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">EPFO UAN:</span><span class="font-mono font-bold text-slate-800">{{ $employee->uan_number ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">ESIC IP Number:</span><span class="font-mono font-bold text-slate-800">{{ $employee->esic_number ?: 'N/A' }}</span></div>
                </div>
            </div>

            <!-- Attached Verification Documents -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center justify-between">
                    <span>Attached Documents & KYC Proofs</span>
                    <span class="text-[10px] text-slate-400 font-normal">Click to view/download</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- PAN Document -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">PAN Copy</span>
                            <span class="text-xs font-bold text-slate-800 block truncate">{{ $employee->pan_number ?: 'PAN Card' }}</span>
                        </div>
                        <div class="mt-3">
                            @if($employee->pan_document)
                                <a href="{{ route('employees.document', [$employee->id, 'pan']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition-colors w-full justify-center">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View PAN</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 italic">Not Uploaded</span>
                            @endif
                        </div>
                    </div>

                    <!-- Aadhaar Document -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Aadhaar Copy</span>
                            <span class="text-xs font-bold text-slate-800 block truncate">{{ $employee->aadhar_number ?: 'Aadhaar Card' }}</span>
                        </div>
                        <div class="mt-3">
                            @if($employee->aadhar_document)
                                <a href="{{ route('employees.document', [$employee->id, 'aadhar']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition-colors w-full justify-center">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View Aadhaar</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 italic">Not Uploaded</span>
                            @endif
                        </div>
                    </div>

                    <!-- Bank Proof Document -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Bank Proof</span>
                            <span class="text-xs font-bold text-slate-800 block truncate">{{ $employee->bank_name ?: 'Passbook/Cheque' }}</span>
                        </div>
                        <div class="mt-3">
                            @if($employee->bank_proof_document)
                                <a href="{{ route('employees.document', [$employee->id, 'bank']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition-colors w-full justify-center">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View Cheque</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 italic">Not Uploaded</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Bank Account for Salary Credit
                </h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div><span class="text-slate-400 block">Account Holder:</span><span class="font-bold text-slate-800">{{ $employee->account_holder_name ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">Bank & Branch:</span><span class="font-bold text-slate-800">{{ $employee->bank_name ?: 'N/A' }} ({{ $employee->bank_branch ?: 'Main' }})</span></div>
                    <div><span class="text-slate-400 block">Account Number:</span><span class="font-mono font-bold text-slate-800">{{ $employee->account_number ?: 'N/A' }}</span></div>
                    <div><span class="text-slate-400 block">IFSC Code:</span><span class="font-mono font-bold text-slate-800">{{ $employee->ifsc_code ?: 'N/A' }}</span></div>
                </div>
            </div>

            <!-- Educational Records -->
            @if(!empty($employee->education_details))
                <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-3">
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Educational Credentials
                    </h3>
                    <div class="space-y-2">
                        @foreach ($employee->education_details as $edu)
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-900">{{ $edu['degree'] ?? 'Degree' }}</span>
                                    <span class="text-slate-500 block">{{ $edu['institute'] ?? 'University' }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-mono font-bold text-brand-600">{{ $edu['score'] ?? '' }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $edu['year'] ?? '' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <!-- Right 5 Cols: Compensation & Indian Statutory Payslip -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Compensation Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Salary Structure (Monthly)</h3>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">INR (₹)</span>
                </div>

                <!-- Earnings -->
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 text-slate-600"><span>Basic Salary:</span><span class="font-bold text-slate-900">₹{{ number_format($employee->basic_salary, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Dearness Allowance (20%):</span><span class="font-bold text-slate-900">₹{{ number_format($employee->da_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>House Rent Allowance (60%):</span><span class="font-bold text-slate-900">₹{{ number_format($employee->hra_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Conveyance Allowance (20%):</span><span class="font-bold text-slate-900">₹{{ number_format($employee->ca_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Entertainment Allowance (8%):</span><span class="font-bold text-slate-900">₹{{ number_format($employee->ea_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Special Allowance (20%):</span><span class="font-bold text-slate-900">₹{{ number_format($employee->sa_amount, 2) }}</span></div>
                    
                    <div class="flex justify-between py-2 border-t border-b border-slate-200 font-extrabold text-slate-900 text-sm">
                        <span>Gross Salary:</span>
                        <span>₹{{ number_format($employee->gross_salary, 2) }}</span>
                    </div>
                </div>

                <!-- Deductions & Statutory -->
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 text-slate-600"><span>Gratuity (4.81%):</span><span class="font-bold text-slate-800">₹{{ number_format($employee->gratuity_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600">
                        <span class="flex items-center gap-1.5">
                            <span>Employee PF (12%):</span>
                            @if(!$employee->pf_enabled)
                                <span class="text-[10px] bg-amber-50 text-amber-700 font-bold px-1.5 py-0.5 rounded border border-amber-200">Opted Out / Disabled</span>
                            @endif
                        </span>
                        <span class="font-bold {{ $employee->pf_enabled ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $employee->pf_enabled ? '- ₹' . number_format($employee->pf_amount, 2) : '₹0.00' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Employee ESIC (0.75%):</span><span class="font-bold text-rose-600">- ₹{{ number_format($employee->esic_employee_amount, 2) }}</span></div>
                    <div class="flex justify-between py-1 text-slate-600"><span>Income Tax (TDS):</span><span class="font-bold text-rose-600">- ₹{{ number_format($employee->tax_amount, 2) }}</span></div>
                    
                    <div class="flex justify-between py-1 text-slate-700 font-semibold border-t border-slate-100">
                        <span>Total Monthly Deductions:</span>
                        <span class="font-bold text-rose-700">₹{{ number_format($employee->total_deductions + $employee->tax_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between py-1 text-purple-700 font-semibold">
                        <span>Cost to Company (CTC):</span>
                        <span class="font-extrabold">₹{{ number_format($employee->ctc_amount, 2) }}</span>
                    </div>
                </div>

                <!-- Take-Home Pay Banner -->
                <div class="p-4 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white shadow-md text-center">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-100">Monthly Net Take-Home</span>
                    <h3 class="text-2xl font-extrabold mt-0.5">₹{{ number_format($employee->take_home_salary, 2) }}</h3>
                    <span class="text-xs text-emerald-100 block mt-1">Annual Net: ₹{{ number_format($employee->take_home_salary * 12, 2) }}</span>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal: Reset Password -->
<div id="resetPwdModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-2">Reset Password</h3>
        <p class="text-xs text-slate-500 mb-4">Set a new password for {{ $employee->full_name }}.</p>
        
        <form method="POST" action="{{ route('employees.reset-password', $employee->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">New Password</label>
                <input type="password" name="new_password" required minlength="8" placeholder="At least 8 characters..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('resetPwdModal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-bold text-slate-500">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-brand-600 text-white rounded-xl text-xs font-bold">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
