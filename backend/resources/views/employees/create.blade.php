@extends('layouts.app')

@section('title', 'Onboard New Employee')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Breadcrumbs -->
    <div>
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
            <span>/</span>
            <a href="{{ route('employees.index') }}" class="hover:text-brand-600 transition-colors">Employees</a>
            <span>/</span>
            <span class="text-brand-600 font-semibold">New Onboarding</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Employee Onboarding Process</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Register a new employee with statutory compliance, bank details, educational records, and real-time Indian salary computation.
        </p>
    </div>

    <!-- Onboarding Form -->
    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- SECTION 0: Access Role & Organization Hierarchy -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">User Role & Reporting Hierarchy</h3>
                        <p class="text-xs text-slate-500">Access permission, department, designation, and reporting line.</p>
                    </div>
                </div>
                <span class="text-xs font-mono font-bold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-lg">Step 1 of 6</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <!-- Company / Legal Entity -->
                <div class="sm:col-span-6">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Company / Legal Entity *</label>
                        <button type="button" onclick="openQuickCompModal()" class="text-[11px] font-bold text-brand-600 hover:text-brand-700">+ Add Company</button>
                    </div>
                    <select id="companySelect" name="company_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Company</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}" {{ (old('company_id', $activeCompanyId) == $comp->id) ? 'selected' : '' }}>
                                {{ $comp->name }} {{ $comp->code ? '(' . $comp->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Type -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        User Type *
                    </label>
                    <select name="user_type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="user" {{ old('user_type') === 'user' ? 'selected' : '' }}>User (Standard Employee)</option>
                        <option value="admin" {{ old('user_type') === 'admin' ? 'selected' : '' }}>Admin (Staff Administrator)</option>
                    </select>
                </div>

                <!-- Employee ID -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Employee ID *
                    </label>
                    <input 
                        type="text" 
                        name="employee_id" 
                        required 
                        value="{{ old('employee_id', $suggestedEmployeeId) }}" 
                        placeholder="e.g. EMP-0001"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 uppercase focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>

                <!-- Department (with quick add & delete/manage) -->
                <div class="sm:col-span-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Department</label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openManageDeptModal()" class="text-[11px] font-bold text-rose-600 hover:text-rose-700">Manage / Delete</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="openQuickDeptModal()" class="text-[11px] font-bold text-brand-600 hover:text-brand-700">+ Add New</button>
                        </div>
                    </div>
                    <select id="departmentSelect" name="department_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Department</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Designation (with quick add & delete/manage) -->
                <div class="sm:col-span-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Designation</label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openManageDesigModal()" class="text-[11px] font-bold text-rose-600 hover:text-rose-700">Manage / Delete</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="openQuickDesigModal()" class="text-[11px] font-bold text-brand-600 hover:text-brand-700">+ Add New</button>
                        </div>
                    </div>
                    <select id="designationSelect" name="designation_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Designation</option>
                        @foreach ($designations as $desig)
                            <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>
                                {{ $desig->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Select Manager -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Reporting Manager
                    </label>
                    <select name="manager_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">No Manager (Executive / Head)</option>
                        @foreach ($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('manager_id') == $mgr->id ? 'selected' : '' }}>
                                {{ $mgr->full_name }} ({{ $mgr->employee_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Joining Date -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Date of Joining *
                    </label>
                    <input 
                        type="date" 
                        name="joining_date" 
                        required
                        value="{{ old('joining_date', date('Y-m-d')) }}" 
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>

                <!-- Employment Type -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Employment Type *
                    </label>
                    <select name="employment_type" id="employmentTypeSelect" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="Probation" {{ old('employment_type', 'Probation') === 'Probation' ? 'selected' : '' }}>Probation (Standard Onboarding)</option>
                        <option value="Permanent" {{ old('employment_type') === 'Permanent' ? 'selected' : '' }}>Permanent / Full-Time</option>
                        <option value="Contract" {{ old('employment_type') === 'Contract' ? 'selected' : '' }}>Contractual</option>
                        <option value="Trainee" {{ old('employment_type') === 'Trainee' ? 'selected' : '' }}>Graduate / Tech Trainee</option>
                        <option value="Intern" {{ old('employment_type') === 'Intern' ? 'selected' : '' }}>Intern</option>
                    </select>
                </div>

                <!-- Probation Period (Months) -->
                <div class="sm:col-span-4" id="probationPeriodWrapper">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Probation Duration
                    </label>
                    <select name="probation_months" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="3" {{ old('probation_months') == '3' ? 'selected' : '' }}>3 Months</option>
                        <option value="6" {{ old('probation_months', '6') == '6' ? 'selected' : '' }}>6 Months (Standard India)</option>
                        <option value="9" {{ old('probation_months') == '9' ? 'selected' : '' }}>9 Months</option>
                        <option value="12" {{ old('probation_months') == '12' ? 'selected' : '' }}>12 Months (1 Year)</option>
                    </select>
                </div>

                <!-- Shift Assignment -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Assigned Work Shift *
                    </label>
                    <select name="shift_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Work Shift</option>
                        @foreach ($shifts as $sft)
                            <option value="{{ $sft->id }}" {{ old('shift_id', 1) == $sft->id ? 'selected' : '' }}>
                                {{ $sft->name }} ({{ substr($sft->start_time, 0, 5) }} - {{ substr($sft->end_time, 0, 5) }}, Grace: {{ $sft->grace_period_minutes }}m)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Location Type (Fixed vs Random) -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Attendance Location Type *
                    </label>
                    <select name="location_type" id="locationTypeSelect" onchange="toggleLocationSelect()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="fixed" {{ old('location_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Office Location (200m Geofence)</option>
                        <option value="random" {{ old('location_type') === 'random' ? 'selected' : '' }}>Random / Outside Office Location</option>
                    </select>
                </div>

                <!-- Fixed Office Location Dropdown -->
                <div class="sm:col-span-4" id="fixedLocationWrapper">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Fixed Work Location *
                    </label>
                    <select name="location_id" id="locationIdSelect" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Fixed Office Location</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city ?: 'HQ' }}, Radius: {{ $loc->radius_meters }}m)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Punching is restricted within 200m radius of this location</p>
                </div>
            </div>
        </div>

        <!-- SECTION 1: Personal Details -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Section 1: Personal Details</h3>
                    <p class="text-xs text-slate-500">Identity, contact numbers, present & permanent residential address.</p>
                </div>
            </div>

            <!-- Full Name, Father/Husband, DOB, Gender -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" required value="{{ old('full_name') }}" placeholder="As per Aadhaar Card" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Father's / Husband's Name</label>
                    <input type="text" name="father_or_husband_name" value="{{ old('father_or_husband_name') }}" placeholder="Father or Spouse Name" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Gender</label>
                    <select name="gender" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Marital Status</label>
                    <select name="marital_status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="Single" {{ old('marital_status') === 'Single' ? 'selected' : '' }}>Single</option>
                        <option value="Married" {{ old('marital_status') === 'Married' ? 'selected' : '' }}>Married</option>
                        <option value="Divorced" {{ old('marital_status') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                        <option value="Widowed" {{ old('marital_status') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Blood Group</label>
                    <select name="blood_group" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Blood Group</option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Contact Numbers & Emails -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Official Login Email *</label>
                    <input type="email" name="official_email" required value="{{ old('official_email') }}" placeholder="employee@company.in" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <p class="text-[10px] text-slate-400 mt-1">Credentials will be sent to this email</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Personal Email ID</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email') }}" placeholder="personal@gmail.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contact Number (India) *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-400">+91</span>
                        <input type="tel" name="contact_number" required maxlength="15" value="{{ old('contact_number') }}" placeholder="9876543210" class="w-full pl-11 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Alternate Number</label>
                    <input type="tel" name="alternate_number" maxlength="15" value="{{ old('alternate_number') }}" placeholder="Landline or second phone" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Present Address -->
            <div class="pt-3 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Current / Present Address</h4>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <input type="text" name="present_street" id="present_street" value="{{ old('present_street') }}" placeholder="Street / House No. / Landmark" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="present_city" id="present_city" value="{{ old('present_city') }}" placeholder="City (e.g. Gurugram)" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="present_state" id="present_state" value="{{ old('present_state') }}" placeholder="State (e.g. Haryana)" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="present_pin" id="present_pin" maxlength="10" value="{{ old('present_pin') }}" placeholder="PIN Code (e.g. 122002)" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                </div>
            </div>

            <!-- Permanent Address -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Permanent Address</h4>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="sameAddressCheck" name="permanent_same_as_present" value="1" onchange="togglePermanentAddress(this.checked)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <span class="text-xs font-semibold text-brand-600 select-none">Same as present address</span>
                    </label>
                </div>
                <div id="permanentAddressFields" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <input type="text" name="permanent_street" id="permanent_street" value="{{ old('permanent_street') }}" placeholder="Street / House No." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="permanent_city" id="permanent_city" value="{{ old('permanent_city') }}" placeholder="City" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="permanent_state" id="permanent_state" value="{{ old('permanent_state') }}" placeholder="State" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                    <div>
                        <input type="text" name="permanent_pin" id="permanent_pin" maxlength="10" value="{{ old('permanent_pin') }}" placeholder="PIN Code" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Primary Emergency Contact -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="phone-call" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Section 2: Primary Emergency Contact</h3>
                    <p class="text-xs text-slate-500">Authorized contact in case of workplace emergency.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contact Person Name</label>
                    <input type="text" name="primary_contact_name" value="{{ old('primary_contact_name') }}" placeholder="e.g. Suman Sharma" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Relationship</label>
                    <input type="text" name="primary_contact_relationship" value="{{ old('primary_contact_relationship') }}" placeholder="e.g. Father / Mother / Spouse / Brother" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Emergency Phone Number</label>
                    <input type="tel" name="primary_contact_phone" maxlength="15" value="{{ old('primary_contact_phone') }}" placeholder="+91 98765 00000" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Emergency Contact Address</label>
                    <input type="text" name="primary_contact_address" value="{{ old('primary_contact_address') }}" placeholder="Residential address of contact person" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>
        </div>

        <!-- SECTION 3: Statutory & Identity Details -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="file-badge" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Section 3: Statutory & Identity Details (India)</h3>
                    <p class="text-xs text-slate-500">Government identifiers and compliance documentation.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- PAN Number & File -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">PAN Card Number</label>
                    <input type="text" name="pan_number" maxlength="15" value="{{ old('pan_number') }}" placeholder="ABCDE1234F" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono uppercase text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    
                    <div class="mt-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Attach PAN Copy (PDF/JPG)</label>
                        <input type="file" name="pan_document" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                </div>

                <!-- Aadhaar Number & File -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Aadhaar Card Number</label>
                    <input type="text" name="aadhar_number" maxlength="20" value="{{ old('aadhar_number') }}" placeholder="12-digit Aadhaar e.g. 1234 5678 9012" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    
                    <div class="mt-2">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Attach Aadhaar Copy (PDF/JPG)</label>
                        <input type="file" name="aadhar_document" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                </div>

                <!-- UAN & ESIC -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">EPFO UAN (Universal Account Number)</label>
                    <input type="text" name="uan_number" maxlength="30" value="{{ old('uan_number') }}" placeholder="12-digit UAN if previously allocated" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">ESIC IP Number</label>
                    <input type="text" name="esic_number" maxlength="30" value="{{ old('esic_number') }}" placeholder="17-digit ESIC IP number (if available)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>
        </div>

        <!-- SECTION 4: Bank Account Details -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="landmark" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Section 4: Bank Account Details (For Salary Credit)</h3>
                    <p class="text-xs text-slate-500">Bank account for monthly automated NEFT / RTGS salary dispatches.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Account Holder Name</label>
                    <input type="text" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="As per passbook" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Bank Name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. HDFC Bank / SBI / ICICI" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Branch Name</label>
                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="e.g. Cyber City Branch" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Account Number</label>
                    <input type="text" name="account_number" maxlength="30" value="{{ old('account_number') }}" placeholder="Account Number" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">IFSC Code (11 Chars)</label>
                    <input type="text" name="ifsc_code" maxlength="15" value="{{ old('ifsc_code') }}" placeholder="e.g. HDFC0000123" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono uppercase text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Account Type</label>
                    <select name="account_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="Savings" {{ old('account_type') === 'Savings' ? 'selected' : '' }}>Savings Account</option>
                        <option value="Current" {{ old('account_type') === 'Current' ? 'selected' : '' }}>Current Account</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Attach Cancelled Cheque / Passbook Front Page</label>
                    <input type="file" name="bank_proof_document" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>
            </div>
        </div>

        <!-- SECTION 5: Educational Qualifications -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Section 5: Educational Qualifications (Highest First)</h3>
                        <p class="text-xs text-slate-500">Academic credentials and degrees.</p>
                    </div>
                </div>
                <button type="button" onclick="addEducationRow()" class="text-xs font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add Degree
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="educationTable">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase font-bold tracking-wider">
                            <th class="py-2.5 px-3">Qualification / Degree</th>
                            <th class="py-2.5 px-3">Institute / University</th>
                            <th class="py-2.5 px-3 w-32">Year of Passing</th>
                            <th class="py-2.5 px-3 w-36">Percentage / CGPA</th>
                            <th class="py-2.5 px-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="educationTableBody">
                        <tr>
                            <td class="p-2"><input type="text" name="education_degrees[]" placeholder="e.g. B.Tech / MBA / B.Sc" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
                            <td class="p-2"><input type="text" name="education_institutes[]" placeholder="University / College" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
                            <td class="p-2"><input type="text" name="education_years[]" placeholder="e.g. 2022" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
                            <td class="p-2"><input type="text" name="education_scores[]" placeholder="e.g. 82% / 8.5 CGPA" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
                            <td class="p-2 text-center"></td>
                        </tr>
                        <tr>
                            <td class="p-2"><input type="text" name="education_degrees[]" placeholder="e.g. Higher Secondary (12th)" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
                            <td class="p-2"><input type="text" name="education_institutes[]" placeholder="CBSE / State Board" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
                            <td class="p-2"><input type="text" name="education_years[]" placeholder="e.g. 2018" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
                            <td class="p-2"><input type="text" name="education_scores[]" placeholder="e.g. 88%" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
                            <td class="p-2 text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION 6: Salary Part (Auto-Calculation Take Home) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="calculator" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Section 6: Salary Part (Auto Calculation Take Home)</h3>
                        <p class="text-xs text-slate-500">Live Indian compensation engine according to statutory salary rules.</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">Real-Time Reactive</span>
            </div>

            <!-- Basic & Tax Inputs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-brand-50/50 border border-brand-100">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-1">
                        Basic Salary (Monthly ₹) *
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="basic_salary" 
                        id="calc_basic" 
                        required 
                        value="{{ old('basic_salary', 25000) }}" 
                        oninput="recalculateSalary()"
                        placeholder="e.g. 25000" 
                        class="w-full px-3.5 py-2.5 bg-white border border-brand-300 rounded-xl text-base font-extrabold text-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all"
                    >
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-1">
                        Monthly Tax / TDS (₹)
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="tax_amount" 
                        id="calc_tax" 
                        value="{{ old('tax_amount', 0) }}" 
                        oninput="recalculateSalary()"
                        placeholder="Income tax deduction" 
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-base font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all"
                    >
                </div>

                <div class="flex flex-col justify-center">
                    <label class="flex items-center gap-2 cursor-pointer mt-3">
                        <input 
                            type="checkbox" 
                            name="pf_enabled" 
                            id="calc_pf_enabled" 
                            value="1" 
                            checked 
                            onchange="recalculateSalary()"
                            class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500"
                        >
                        <span class="text-xs font-bold text-slate-800 select-none">Enable EPF Deduction (12%)</span>
                    </label>
                    <p class="text-[10px] text-slate-500 mt-1">Capped at max ₹3,000 as per rule</p>
                </div>
            </div>

            <!-- Breakdown Display Table -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Earnings Breakdown -->
                <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 space-y-2">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        Monthly Earnings & Allowances
                    </h4>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Basic Salary:</span><span class="font-bold text-slate-800" id="disp_basic">₹25,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Dearness Allowance (DA - 20%):</span><span class="font-bold text-slate-800" id="disp_da">₹5,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">House Rent Allowance (HRA - 60%):</span><span class="font-bold text-slate-800" id="disp_hra">₹15,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Conveyance Allowance (CA - 20%):</span><span class="font-bold text-slate-800" id="disp_ca">₹5,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Entertainment Allowance (EA - 8%):</span><span class="font-bold text-slate-800" id="disp_ea">₹2,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Special Allowance (SA - 20%):</span><span class="font-bold text-slate-800" id="disp_sa">₹5,000.00</span></div>
                    <div class="flex justify-between text-sm py-2 border-t border-slate-200 font-extrabold text-slate-900">
                        <span>Gross Salary:</span><span id="disp_gross">₹57,000.00</span>
                    </div>
                </div>

                <!-- Statutory & Deductions Breakdown -->
                <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 space-y-2">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        Statutory Benefits, Deductions & Net Pay
                    </h4>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Gratuity (Basic+DA * 4.81%):</span><span class="font-bold text-slate-800" id="disp_gratuity">₹1,443.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Employee PF (Max ₹3000):</span><span class="font-bold text-rose-600" id="disp_pf">₹3,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Employee ESIC (0.75%):</span><span class="font-bold text-rose-600" id="disp_esic_emp">₹0.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Employer ESIC (3.25%):</span><span class="font-bold text-slate-800" id="disp_esic_employer">₹0.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-500">Income Tax (TDS):</span><span class="font-bold text-rose-600" id="disp_tax">₹0.00</span></div>
                    <div class="flex justify-between text-xs py-1 border-t border-slate-200"><span class="text-slate-700 font-semibold">Total Deductions:</span><span class="font-bold text-rose-700" id="disp_deductions">₹3,000.00</span></div>
                    <div class="flex justify-between text-xs py-1"><span class="text-slate-700 font-semibold">Cost to Company (CTC):</span><span class="font-extrabold text-purple-700" id="disp_ctc">₹61,443.00</span></div>
                </div>
            </div>

            <!-- Big Take-home Callout -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg shadow-emerald-500/10">
                <div>
                    <span class="text-xs uppercase tracking-wider font-extrabold text-emerald-100">Calculated Monthly Take-Home Pay</span>
                    <h2 class="text-3xl font-extrabold mt-0.5 tracking-tight" id="disp_take_home">₹54,000.00</h2>
                    <p class="text-xs text-emerald-100 mt-1">Formula: Gross Salary - Total Deductions - Tax</p>
                </div>

                <div class="text-right">
                    <span class="text-xs font-semibold text-emerald-100 block">Annual Net Pay:</span>
                    <span class="text-xl font-extrabold" id="disp_annual_take_home">₹6,48,000.00 / yr</span>
                </div>
            </div>
        </div>

        <!-- Submit Button Row -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('employees.index') }}" class="px-5 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-8 py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-500/25 hover:shadow-xl transition-all duration-200 flex items-center gap-2"
            >
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Complete Onboarding & Dispatch Credentials</span>
            </button>
        </div>
    </form>

</div>

<!-- Modal: Manage / Delete Departments -->
<div id="manageDeptModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Manage Departments</h3>
                <p class="text-xs text-slate-500">View or remove unused departments.</p>
            </div>
            <button type="button" onclick="closeManageDeptModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div id="deptListContainer" class="max-h-60 overflow-y-auto space-y-2 mb-4 pr-1">
            <div class="text-xs text-slate-400 text-center py-4">Loading departments...</div>
        </div>
        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="closeManageDeptModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">Done</button>
        </div>
    </div>
</div>

<!-- Modal: Manage / Delete Designations -->
<div id="manageDesigModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Manage Designations</h3>
                <p class="text-xs text-slate-500">View or remove unused job designations.</p>
            </div>
            <button type="button" onclick="closeManageDesigModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div id="desigListContainer" class="max-h-60 overflow-y-auto space-y-2 mb-4 pr-1">
            <div class="text-xs text-slate-400 text-center py-4">Loading designations...</div>
        </div>
        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="closeManageDesigModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">Done</button>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Department -->
<div id="quickDeptModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-3">Add Department</h3>
        <input type="text" id="newDeptName" placeholder="e.g. Legal & Compliance" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 mb-4 focus:ring-2 focus:ring-brand-500">
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeQuickDeptModal()" class="px-3 py-1.5 text-xs font-bold text-slate-500">Cancel</button>
            <button type="button" onclick="saveQuickDept()" class="px-4 py-1.5 bg-brand-600 text-white rounded-xl text-xs font-bold">Add</button>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Designation -->
<div id="quickDesigModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-3">Add Designation</h3>
        <input type="text" id="newDesigName" placeholder="e.g. DevOps Engineer" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 mb-4 focus:ring-2 focus:ring-brand-500">
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeQuickDesigModal()" class="px-3 py-1.5 text-xs font-bold text-slate-500">Cancel</button>
            <button type="button" onclick="saveQuickDesig()" class="px-4 py-1.5 bg-brand-600 text-white rounded-xl text-xs font-bold">Add</button>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Company -->
<div id="quickCompModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-bold text-slate-900 mb-1">Add Company / Entity</h3>
        <p class="text-xs text-slate-500 mb-3">Register a legal company unit.</p>
        <div class="space-y-3 mb-4">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Company Legal Name *</label>
                <input type="text" id="newCompName" placeholder="e.g. Riangdo Venners Pvt. Ltd." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Short Code</label>
                <input type="text" id="newCompCode" placeholder="e.g. RVPL" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-bold uppercase text-slate-900 focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Entity Type</label>
                <select id="newCompType" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                    <option value="Private Limited">Private Limited</option>
                    <option value="LLP">LLP (Limited Liability Partnership)</option>
                    <option value="Partnership">Partnership</option>
                    <option value="Proprietorship">Proprietorship</option>
                    <option value="Public Limited">Public Limited</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeQuickCompModal()" class="px-3 py-1.5 text-xs font-bold text-slate-500">Cancel</button>
            <button type="button" onclick="saveQuickComp()" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold">Add Company</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Manage Departments Modal
    function openManageDeptModal() {
        document.getElementById('manageDeptModal').classList.remove('hidden');
        loadDepartmentList();
    }
    function closeManageDeptModal() {
        document.getElementById('manageDeptModal').classList.add('hidden');
    }
    function loadDepartmentList() {
        const container = document.getElementById('deptListContainer');
        container.innerHTML = '<div class="text-xs text-slate-400 text-center py-4">Loading departments...</div>';
        fetch('{{ route("departments.list") }}')
            .then(res => res.json())
            .then(data => {
                const list = data.departments || (Array.isArray(data) ? data : []);
                if (!list.length) {
                    container.innerHTML = '<div class="text-xs text-slate-400 text-center py-3">No departments created yet.</div>';
                    return;
                }
                container.innerHTML = list.map(d => `
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200/80 transition-colors">
                        <div>
                            <span class="text-xs font-semibold text-slate-800">${d.name}</span>
                            <span class="text-[10px] text-slate-500 block">${d.employees_count || 0} employees assigned</span>
                        </div>
                        <button type="button" onclick="deleteDepartment(${d.id}, '${d.name}', ${d.employees_count || 0})" class="px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors border border-rose-200 hover:border-rose-300">
                            Delete
                        </button>
                    </div>
                `).join('');
                if (window.lucide) lucide.createIcons();
            })
            .catch(() => {
                container.innerHTML = '<div class="text-xs text-rose-500 text-center py-3">Failed to load departments.</div>';
            });
    }

    function deleteDepartment(id, name, count) {
        if (count > 0) {
            alert(`Cannot delete "${name}" because it is currently assigned to ${count} active employee(s). Please reassign them first.`);
            return;
        }
        if (!confirm(`Are you sure you want to permanently delete department "${name}"?`)) return;

        fetch(`/departments/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                // Remove from select element
                const select = document.getElementById('departmentSelect');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value == id) {
                        select.remove(i);
                        break;
                    }
                }
                loadDepartmentList();
            } else {
                alert(res.message || 'Error deleting department');
            }
        })
        .catch(() => alert('Network error deleting department'));
    }

    // Manage Designations Modal
    function openManageDesigModal() {
        document.getElementById('manageDesigModal').classList.remove('hidden');
        loadDesignationList();
    }
    function closeManageDesigModal() {
        document.getElementById('manageDesigModal').classList.add('hidden');
    }
    function loadDesignationList() {
        const container = document.getElementById('desigListContainer');
        container.innerHTML = '<div class="text-xs text-slate-400 text-center py-4">Loading designations...</div>';
        fetch('{{ route("designations.list") }}')
            .then(res => res.json())
            .then(data => {
                const list = data.designations || (Array.isArray(data) ? data : []);
                if (!list.length) {
                    container.innerHTML = '<div class="text-xs text-slate-400 text-center py-3">No designations created yet.</div>';
                    return;
                }
                container.innerHTML = list.map(d => `
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200/80 transition-colors">
                        <div>
                            <span class="text-xs font-semibold text-slate-800">${d.name}</span>
                            <span class="text-[10px] text-slate-500 block">${d.employees_count || 0} employees assigned</span>
                        </div>
                        <button type="button" onclick="deleteDesignation(${d.id}, '${d.name}', ${d.employees_count || 0})" class="px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors border border-rose-200 hover:border-rose-300">
                            Delete
                        </button>
                    </div>
                `).join('');
                if (window.lucide) lucide.createIcons();
            })
            .catch(() => {
                container.innerHTML = '<div class="text-xs text-rose-500 text-center py-3">Failed to load designations.</div>';
            });
    }

    function deleteDesignation(id, name, count) {
        if (count > 0) {
            alert(`Cannot delete "${name}" because it is currently assigned to ${count} active employee(s). Please reassign them first.`);
            return;
        }
        if (!confirm(`Are you sure you want to permanently delete designation "${name}"?`)) return;

        fetch(`/designations/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                // Remove from select element
                const select = document.getElementById('designationSelect');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value == id) {
                        select.remove(i);
                        break;
                    }
                }
                loadDesignationList();
            } else {
                alert(res.message || 'Error deleting designation');
            }
        })
        .catch(() => alert('Network error deleting designation'));
    }
    // Permanent Address Sync
    function togglePermanentAddress(isSame) {
        const fields = document.getElementById('permanentAddressFields');
        if (isSame) {
            document.getElementById('permanent_street').value = document.getElementById('present_street').value;
            document.getElementById('permanent_city').value = document.getElementById('present_city').value;
            document.getElementById('permanent_state').value = document.getElementById('present_state').value;
            document.getElementById('permanent_pin').value = document.getElementById('present_pin').value;
            fields.classList.add('opacity-50', 'pointer-events-none');
        } else {
            fields.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    // Real-time Salary Calculation (Client-side mirror of backend engine)
    function recalculateSalary() {
        const basic = parseFloat(document.getElementById('calc_basic').value) || 0;
        const tax = parseFloat(document.getElementById('calc_tax').value) || 0;
        const pfEnabled = document.getElementById('calc_pf_enabled').checked;

        const da = Math.round((basic * 0.20) * 100) / 100;
        const hra = Math.round((basic * 0.60) * 100) / 100;
        const ca = Math.round((basic * 0.20) * 100) / 100;
        const ea = Math.round((basic * 0.08) * 100) / 100;
        const sa = Math.round((basic * 0.20) * 100) / 100;
        const gross = Math.round((basic + da + hra + ca + ea + sa) * 100) / 100;

        const gratuity = Math.round(((basic + da) * 0.0481) * 100) / 100;

        let pf = 0;
        if (pfEnabled) {
            const pfCalc = (basic + da) * 0.12;
            pf = Math.round(Math.min(pfCalc, 3000) * 100) / 100;
        }

        const esicAvailable = (gross <= 21000);
        const esicEmployer = esicAvailable ? Math.round((gross * 0.0325) * 100) / 100 : 0;
        const esicEmployee = esicAvailable ? Math.round((gross * 0.0075) * 100) / 100 : 0;

        const ctc = Math.round((gross + gratuity + pf + esicEmployer) * 100) / 100;
        const totalDeductions = Math.round((pf + esicEmployee) * 100) / 100;
        const takeHome = Math.max(0, Math.round((gross - totalDeductions - tax) * 100) / 100);

        // Format and display
        const fmt = (num) => '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('disp_basic').textContent = fmt(basic);
        document.getElementById('disp_da').textContent = fmt(da);
        document.getElementById('disp_hra').textContent = fmt(hra);
        document.getElementById('disp_ca').textContent = fmt(ca);
        document.getElementById('disp_ea').textContent = fmt(ea);
        document.getElementById('disp_sa').textContent = fmt(sa);
        document.getElementById('disp_gross').textContent = fmt(gross);

        document.getElementById('disp_gratuity').textContent = fmt(gratuity);
        document.getElementById('disp_pf').textContent = fmt(pf);
        document.getElementById('disp_esic_emp').textContent = fmt(esicEmployee) + (esicAvailable ? ' (Gross <= 21k)' : ' (N/A)');
        document.getElementById('disp_esic_employer').textContent = fmt(esicEmployer);
        document.getElementById('disp_tax').textContent = fmt(tax);
        document.getElementById('disp_deductions').textContent = fmt(totalDeductions);
        document.getElementById('disp_ctc').textContent = fmt(ctc);

        document.getElementById('disp_take_home').textContent = fmt(takeHome);
        document.getElementById('disp_annual_take_home').textContent = fmt(takeHome * 12) + ' / yr';
    }

    // Dynamic Education Rows
    function addEducationRow() {
        const tbody = document.getElementById('educationTableBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="p-2"><input type="text" name="education_degrees[]" placeholder="Qualification" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
            <td class="p-2"><input type="text" name="education_institutes[]" placeholder="University" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs"></td>
            <td class="p-2"><input type="text" name="education_years[]" placeholder="Year" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
            <td class="p-2"><input type="text" name="education_scores[]" placeholder="Percentage" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono"></td>
            <td class="p-2 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-rose-500 hover:text-rose-700">&times;</button></td>
        `;
        tbody.appendChild(tr);
    }

    // Quick Add Department
    function openQuickDeptModal() { document.getElementById('quickDeptModal').classList.remove('hidden'); }
    function closeQuickDeptModal() { document.getElementById('quickDeptModal').classList.add('hidden'); }
    function saveQuickDept() {
        const name = document.getElementById('newDeptName').value.trim();
        if (!name) return;
        fetch('{{ route("employees.quick-department") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ name })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                const opt = new Option(data.department.name, data.department.id, true, true);
                document.getElementById('departmentSelect').add(opt);
                closeQuickDeptModal();
                document.getElementById('newDeptName').value = '';
            }
        });
    }

    // Quick Add Designation
    function openQuickDesigModal() { document.getElementById('quickDesigModal').classList.remove('hidden'); }
    function closeQuickDesigModal() { document.getElementById('quickDesigModal').classList.add('hidden'); }
    function saveQuickDesig() {
        const name = document.getElementById('newDesigName').value.trim();
        if (!name) return;
        fetch('{{ route("employees.quick-designation") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ name })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                const opt = new Option(data.designation.name, data.designation.id, true, true);
                document.getElementById('designationSelect').add(opt);
                closeQuickDesigModal();
                document.getElementById('newDesigName').value = '';
            }
        });
    }

    // Quick Add Company
    function openQuickCompModal() { document.getElementById('quickCompModal').classList.remove('hidden'); }
    function closeQuickCompModal() { document.getElementById('quickCompModal').classList.add('hidden'); }
    function saveQuickComp() {
        const name = document.getElementById('newCompName').value.trim();
        const code = document.getElementById('newCompCode').value.trim();
        const legal_type = document.getElementById('newCompType').value;
        if (!name) {
            alert('Please enter company name');
            return;
        }
        fetch('{{ route("companies.quick-store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ name, code, legal_type })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                const label = data.company.name + (data.company.code ? ' (' + data.company.code + ')' : '');
                const opt = new Option(label, data.company.id, true, true);
                document.getElementById('companySelect').add(opt);
                closeQuickCompModal();
                document.getElementById('newCompName').value = '';
                document.getElementById('newCompCode').value = '';
            } else if (data.message) {
                alert(data.message);
            }
        }).catch(err => {
            alert('Error creating company. Please check uniqueness.');
        });
    }

    // Toggle Fixed vs Random Location
    function toggleLocationSelect() {
        const type = document.getElementById('locationTypeSelect').value;
        const wrapper = document.getElementById('fixedLocationWrapper');
        const locSelect = document.getElementById('locationIdSelect');
        if (type === 'fixed') {
            wrapper.style.display = 'block';
            locSelect.required = true;
        } else {
            wrapper.style.display = 'none';
            locSelect.required = false;
            locSelect.value = '';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        recalculateSalary();
        toggleLocationSelect();
    });
</script>
@endpush
