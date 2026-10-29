@extends('layouts.app')

@section('title', 'Edit ' . $employee->full_name . ' - Employee Profile')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <a href="{{ route('employees.index') }}" class="hover:text-brand-600 transition-colors">Employees</a>
                <span>/</span>
                <a href="{{ route('employees.show', $employee->id) }}" class="hover:text-brand-600 transition-colors">{{ $employee->employee_id }}</a>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Edit Profile</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Edit Employee Profile</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Update employment type, probation duration, status (active/inactive), statutory compensation, and document uploads.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('employees.show', $employee->id) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                Cancel
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- SECTION 0: Access Role, Company, Employment Type & Status -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Employment, Role & Organization Hierarchy</h3>
                        <p class="text-xs text-slate-500">Legal entity, active status, probation period, and management reporting line.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <!-- Company / Legal Entity -->
                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Company / Legal Entity *</label>
                    <select name="company_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}" {{ old('company_id', $employee->company_id) == $comp->id ? 'selected' : '' }}>
                                {{ $comp->name }} {{ $comp->code ? '(' . $comp->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employee Status -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Employee Status *</label>
                    <select name="status" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active (Portal Login Enabled)</option>
                        <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive (Login Suspended)</option>
                        <option value="resigned" {{ old('status', $employee->status) === 'resigned' ? 'selected' : '' }}>Resigned / Notice Period</option>
                        <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>

                <!-- User Type -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">User Type *</label>
                    <select name="user_type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="user" {{ old('user_type', $employee->user_type) === 'user' ? 'selected' : '' }}>User (Standard Employee)</option>
                        <option value="admin" {{ old('user_type', $employee->user_type) === 'admin' ? 'selected' : '' }}>Admin (Staff Administrator)</option>
                    </select>
                </div>

                <!-- Employment Type -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Employment Type *</label>
                    <select name="employment_type" id="employmentType" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="Probation" {{ old('employment_type', $employee->employment_type) === 'Probation' ? 'selected' : '' }}>Probation</option>
                        <option value="Permanent" {{ old('employment_type', $employee->employment_type) === 'Permanent' ? 'selected' : '' }}>Permanent / Full-Time</option>
                        <option value="Contract" {{ old('employment_type', $employee->employment_type) === 'Contract' ? 'selected' : '' }}>Contractual</option>
                        <option value="Trainee" {{ old('employment_type', $employee->employment_type) === 'Trainee' ? 'selected' : '' }}>Graduate / Tech Trainee</option>
                        <option value="Intern" {{ old('employment_type', $employee->employment_type) === 'Intern' ? 'selected' : '' }}>Intern</option>
                    </select>
                </div>

                <!-- Probation Duration (Months) -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Probation Duration</label>
                    <select name="probation_months" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="3" {{ old('probation_months', $employee->probation_months) == '3' ? 'selected' : '' }}>3 Months</option>
                        <option value="6" {{ old('probation_months', $employee->probation_months) == '6' ? 'selected' : '' }}>6 Months</option>
                        <option value="9" {{ old('probation_months', $employee->probation_months) == '9' ? 'selected' : '' }}>9 Months</option>
                        <option value="12" {{ old('probation_months', $employee->probation_months) == '12' ? 'selected' : '' }}>12 Months</option>
                    </select>
                </div>

                <!-- Probation End Date -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Probation End Date</label>
                    <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $employee->probation_end_date ? $employee->probation_end_date->format('Y-m-d') : '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <!-- Confirmation Date -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Confirmation Date</label>
                    <input type="date" name="confirmation_date" value="{{ old('confirmation_date', $employee->confirmation_date ? $employee->confirmation_date->format('Y-m-d') : '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <!-- Department -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Department</label>
                    <select name="department_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Department</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Designation -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Designation</label>
                    <select name="designation_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Designation</option>
                        @foreach ($designations as $desig)
                            <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>
                                {{ $desig->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Manager -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Reporting Manager</label>
                    <select name="manager_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">No Manager (Executive / Head)</option>
                        @foreach ($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('manager_id', $employee->manager_id) == $mgr->id ? 'selected' : '' }}>
                                {{ $mgr->full_name }} ({{ $mgr->employee_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date of Joining -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date of Joining</label>
                    <input type="date" name="joining_date" value="{{ old('joining_date', $employee->joining_date ? $employee->joining_date->format('Y-m-d') : '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <!-- Assigned Shift -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Assigned Work Shift *</label>
                    <select name="shift_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Work Shift</option>
                        @foreach ($shifts as $sft)
                            <option value="{{ $sft->id }}" {{ old('shift_id', $employee->shift_id) == $sft->id ? 'selected' : '' }}>
                                {{ $sft->name }} ({{ substr($sft->start_time, 0, 5) }} - {{ substr($sft->end_time, 0, 5) }}, Grace: {{ $sft->grace_period_minutes }}m)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Location Type (Fixed vs Random) -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Attendance Location Type *</label>
                    <select name="location_type" id="editLocationTypeSelect" onchange="toggleEditLocationSelect()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="fixed" {{ old('location_type', $employee->location_type ?: 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Office Location (200m Geofence)</option>
                        <option value="random" {{ old('location_type', $employee->location_type) === 'random' ? 'selected' : '' }}>Random / Outside Office Location</option>
                    </select>
                </div>

                <!-- Fixed Office Location Dropdown -->
                <div class="sm:col-span-4" id="editFixedLocationWrapper">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Fixed Work Location</label>
                    <select name="location_id" id="editLocationIdSelect" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="">Select Fixed Office Location</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id', $employee->location_id) == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->city ?: 'HQ' }}, Radius: {{ $loc->radius_meters }}m)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Exit Date -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Relieving / Exit Date</label>
                    <input type="date" name="exit_date" value="{{ old('exit_date', $employee->exit_date ? $employee->exit_date->format('Y-m-d') : '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>

                <!-- Exit Reason -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Exit Reason</label>
                    <input type="text" name="exit_reason" value="{{ old('exit_reason', $employee->exit_reason) }}" placeholder="e.g. Higher Studies, Career Growth" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                </div>
            </div>
        </div>

        <!-- SECTION 1: Personal Details & Contact -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Personal Details & Residential Address</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" required value="{{ old('full_name', $employee->full_name) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Father's / Husband's Name</label>
                    <input type="text" name="father_or_husband_name" value="{{ old('father_or_husband_name', $employee->father_or_husband_name) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob', $employee->dob ? $employee->dob->format('Y-m-d') : '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Gender</label>
                    <select name="gender" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        <option value="Male" {{ old('gender', $employee->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $employee->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $employee->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Marital Status</label>
                    <select name="marital_status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        <option value="Single" {{ old('marital_status', $employee->marital_status) === 'Single' ? 'selected' : '' }}>Single</option>
                        <option value="Married" {{ old('marital_status', $employee->marital_status) === 'Married' ? 'selected' : '' }}>Married</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Blood Group</label>
                    <select name="blood_group" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $employee->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Official Login Email *</label>
                    <input type="email" name="official_email" required value="{{ old('official_email', $employee->user->email ?? '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Personal Email</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contact Number (10 Digits) *</label>
                    <input type="tel" name="contact_number" required maxlength="10" value="{{ old('contact_number', $employee->contact_number) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <!-- Address -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-2 border-t border-slate-100">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Present Street</label>
                    <input type="text" name="present_street" value="{{ old('present_street', $employee->present_street) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">City</label>
                    <input type="text" name="present_city" value="{{ old('present_city', $employee->present_city) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">PIN Code</label>
                    <input type="text" name="present_pin" value="{{ old('present_pin', $employee->present_pin) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm">
                </div>
            </div>
        </div>

        <!-- SECTION 2: Statutory Identifiers & Document Uploads -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-5">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Statutory Numbers & Attached KYC Documents</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- PAN -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">PAN Number</label>
                    <input type="text" name="pan_number" value="{{ old('pan_number', $employee->pan_number) }}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold uppercase">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Upload New Copy (PDF / PNG / JPG):</label>
                        <input type="file" name="pan_document" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700">
                        @if($employee->pan_document)
                            <a href="{{ route('employees.document', [$employee->id, 'pan']) }}" target="_blank" class="text-[11px] font-bold text-brand-600 block mt-1 hover:underline">View Current Attached PAN Copy</a>
                        @endif
                    </div>
                </div>

                <!-- Aadhaar -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Aadhaar Number</label>
                    <input type="text" name="aadhar_number" value="{{ old('aadhar_number', $employee->aadhar_number) }}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Upload New Copy (PDF / PNG / JPG):</label>
                        <input type="file" name="aadhar_document" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700">
                        @if($employee->aadhar_document)
                            <a href="{{ route('employees.document', [$employee->id, 'aadhar']) }}" target="_blank" class="text-[11px] font-bold text-brand-600 block mt-1 hover:underline">View Current Attached Aadhaar Copy</a>
                        @endif
                    </div>
                </div>

                <!-- Bank Proof -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Bank Account & IFSC</label>
                    <input type="text" name="account_number" value="{{ old('account_number', $employee->account_number) }}" placeholder="Account Number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono mb-2">
                    <input type="text" name="ifsc_code" value="{{ old('ifsc_code', $employee->ifsc_code) }}" placeholder="IFSC Code" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold uppercase">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Upload Cheque / Passbook:</label>
                        <input type="file" name="bank_proof_document" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700">
                        @if($employee->bank_proof_document)
                            <a href="{{ route('employees.document', [$employee->id, 'bank']) }}" target="_blank" class="text-[11px] font-bold text-brand-600 block mt-1 hover:underline">View Current Bank Proof</a>
                        @endif
                    </div>
                </div>

                <!-- EPFO UAN & ESIC -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">EPFO UAN Number</label>
                        <input type="text" name="uan_number" value="{{ old('uan_number', $employee->uan_number) }}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">ESIC IP Number</label>
                        <input type="text" name="esic_number" value="{{ old('esic_number', $employee->esic_number) }}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Salary Breakdown -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Statutory Compensation & Basic Salary</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Basic Salary (₹ / Month) *</label>
                    <input type="number" step="0.01" name="basic_salary" id="editBasicSalary" required value="{{ old('basic_salary', $employee->basic_salary) }}" oninput="recalcEditSalary()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Income Tax TDS (₹ / Month)</label>
                    <input type="number" step="0.01" name="tax_amount" id="editTaxAmount" value="{{ old('tax_amount', $employee->tax_amount) }}" oninput="recalcEditSalary()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm">
                </div>
                <div class="flex items-center pt-6">
                    <input type="hidden" name="pf_enabled" value="0">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="pf_enabled" id="editPfEnabled" value="1" {{ old('pf_enabled', $employee->pf_enabled) ? 'checked' : '' }} onchange="recalcEditSalary()" class="w-4 h-4 text-brand-600 rounded">
                        <span class="text-xs font-bold text-slate-700">Enable Statutory PF (12%)</span>
                    </label>
                </div>
            </div>

            <!-- Dynamic Salary Live Calculation Preview in Edit Form -->
            <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Gross Salary</span>
                    <span class="font-bold text-slate-900" id="previewGross">₹{{ number_format($employee->gross_salary, 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">EPF Deduction</span>
                    <span class="font-bold text-rose-600" id="previewPf">{{ $employee->pf_enabled ? '₹' . number_format($employee->pf_amount, 2) : '₹0.00 (Disabled)' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Total Deductions</span>
                    <span class="font-bold text-rose-700" id="previewDeductions">₹{{ number_format($employee->total_deductions + $employee->tax_amount, 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Take-Home Pay</span>
                    <span class="font-extrabold text-emerald-700" id="previewTakeHome">₹{{ number_format($employee->take_home_salary, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Submit Button Row -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('employees.show', $employee->id) }}" class="px-5 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-8 py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-500/25 hover:shadow-xl transition-all duration-200 flex items-center gap-2"
            >
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Save Profile Updates</span>
            </button>
        </div>
    </form>

</div>

<script>
function recalcEditSalary() {
    const basic = parseFloat(document.getElementById('editBasicSalary').value) || 0;
    const tax = parseFloat(document.getElementById('editTaxAmount').value) || 0;
    const pfEnabled = document.getElementById('editPfEnabled').checked;

    const da = basic * 0.20;
    const hra = basic * 0.60;
    const ca = basic * 0.20;
    const ea = basic * 0.08;
    const sa = basic * 0.20;
    const gross = basic + da + hra + ca + ea + sa;

    let pf = 0;
    if (pfEnabled) {
        pf = Math.min((basic + da) * 0.12, 3000.00);
    }

    const esic = (gross <= 21000) ? (gross * 0.0075) : 0;
    const totalDeductions = pf + esic;
    const takeHome = Math.max(0, gross - totalDeductions - tax);

    document.getElementById('previewGross').innerText = '₹' + gross.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('previewPf').innerText = pfEnabled 
        ? '₹' + pf.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) 
        : '₹0.00 (Disabled)';
    document.getElementById('previewDeductions').innerText = '₹' + (totalDeductions + tax).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('previewTakeHome').innerText = '₹' + takeHome.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function toggleEditLocationSelect() {
    const type = document.getElementById('editLocationTypeSelect').value;
    const wrapper = document.getElementById('editFixedLocationWrapper');
    const locSelect = document.getElementById('editLocationIdSelect');
    if (type === 'fixed') {
        wrapper.style.display = 'block';
        locSelect.required = true;
    } else {
        wrapper.style.display = 'none';
        locSelect.required = false;
        locSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    toggleEditLocationSelect();
});
</script>
@endsection
