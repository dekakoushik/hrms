@extends('layouts.app')

@section('title', ($isAdmin ? 'Superadmin Profile - Settings' : 'My Profile - Employee Portal'))

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    <!-- Page Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-slate-400">{{ $isAdmin ? 'Settings' : 'My Account' }}</span>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Profile</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $isAdmin ? 'Superadmin Profile Settings' : 'My Employee Profile' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                @if($isAdmin)
                    Manage your credentials, personal identity, Indian contact details, and security configuration.
                @else
                    Official employee identity, employment details, statutory records, and security settings.
                @endif
            </p>
        </div>
        
        <!-- Live Role Badge -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold {{ $isAdmin ? 'bg-indigo-50 text-brand-700 border border-brand-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                <i data-lucide="{{ $isAdmin ? 'shield' : 'user-check' }}" class="w-3.5 h-3.5 {{ $isAdmin ? 'text-brand-600' : 'text-emerald-600' }}"></i>
                {{ $isAdmin ? ($user->role === 'superadmin' ? 'Super Administrator' : 'Administrator') : 'Active Employee' }}
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                Verified
            </span>
        </div>
    </div>

    @if(!$isAdmin)
        <!-- View-Only Policy Notice for Regular User/Employee -->
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-start gap-3.5 text-amber-900">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <i data-lucide="lock" class="w-4 h-4"></i>
            </div>
            <div>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900">View-Only Employee Record</h4>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                    As per organization policy, official employee records (salary, department, designation, and identity details) can only be modified by Human Resources / Superadmin. If any details need updating, please reach out to your HR department. You may update your account password on the right.
                </p>
            </div>
        </div>
    @endif

    <!-- Profile Overview Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            
            <!-- Avatar -->
            <div class="relative group">
                <img 
                    id="avatarPreviewImg" 
                    src="{{ $user->avatar_url }}" 
                    alt="{{ $user->name }}" 
                    class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover ring-4 ring-slate-100 shadow-md"
                >
                @if($isAdmin)
                    <label for="avatarInput" class="absolute -bottom-2 -right-2 w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center cursor-pointer shadow-md hover:bg-brand-700 transition-colors group-hover:scale-105" title="Change Avatar Image">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                    </label>
                @endif
            </div>

            <!-- Profile Summary Info -->
            <div class="flex-1 text-center sm:text-left space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $isAdmin ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} self-center sm:self-auto">
                        {{ $employee ? 'Employee' : ($user->role === 'superadmin' ? 'Super Admin' : ucfirst($user->role)) }}
                    </span>
                    @if($employee)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200 self-center sm:self-auto">
                            {{ $employee->employee_id }}
                        </span>
                    @endif
                </div>

                <p class="text-xs sm:text-sm font-medium text-slate-600">
                    {{ $employee?->designation?->name ?: ($user->designation ?: 'Team Member') }}
                    <span class="text-slate-300 mx-1.5">•</span>
                    <span class="text-slate-500">{{ $employee?->department?->name ?: ($user->department ?: 'Operations') }}</span>
                    @if($employee?->company)
                        <span class="text-slate-300 mx-1.5">•</span>
                        <span class="text-brand-600 font-semibold">{{ $employee->company->name }}</span>
                    @endif
                </p>

                <!-- Key Chips -->
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 pt-2 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1 rounded-xl">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                        {{ $user->email }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1 rounded-xl">
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                        +91 {{ $user->mobile_number ?: 'Not Set' }}
                    </span>
                    @if($user->city || $user->state || $employee?->present_city)
                        <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1 rounded-xl">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                            {{ $employee?->present_city ?: ($user->city ?: 'Guwahati') }}, {{ $employee?->present_state ?: ($user->state ?: 'Assam') }}
                        </span>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Column 1: Personal & Official Details (8 Cols) -->
        <div class="lg:col-span-8 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">
                        {{ $isAdmin ? 'Personal & Official Details' : 'Official Employee Records' }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isAdmin ? 'Update your identity information, Indian mobile contact, and address specifications.' : 'Registered profile data maintained by human resources.' }}
                    </p>
                </div>
                @if(!$isAdmin)
                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200 flex items-center gap-1">
                        <i data-lucide="eye" class="w-3 h-3"></i>
                        View-Only
                    </span>
                @endif
            </div>

            @if($isAdmin)
                <!-- ADMIN EDITABLE FORM -->
                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <!-- Avatar chooser -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                                <i data-lucide="image" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Profile Photo</p>
                                <p class="text-[11px] text-slate-500">Supports JPG, PNG, WEBP up to 4MB.</p>
                            </div>
                        </div>
                        <div>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden">
                            <button type="button" onclick="document.getElementById('avatarInput').click()" class="px-4 py-2 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 text-xs font-bold rounded-xl shadow-sm hover:bg-slate-50 transition-colors">
                                Choose New File
                            </button>
                        </div>
                    </div>

                    <!-- Name & Email Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                required 
                                value="{{ old('name', $user->name) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                value="{{ old('email', $user->email) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Indian Mobile & Designation -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mobile_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Mobile Number (India) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="tel" 
                                id="mobile_number" 
                                name="mobile_number" 
                                required 
                                maxlength="15" 
                                value="{{ old('mobile_number', $user->mobile_number) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>

                        <div>
                            <label for="designation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Designation / Title
                            </label>
                            <input 
                                type="text" 
                                id="designation" 
                                name="designation" 
                                value="{{ old('designation', $user->designation) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Department & PIN -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="department" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Department
                            </label>
                            <input 
                                type="text" 
                                id="department" 
                                name="department" 
                                value="{{ old('department', $user->department) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>

                        <div>
                            <label for="pincode" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                PIN Code
                            </label>
                            <input 
                                type="text" 
                                id="pincode" 
                                name="pincode" 
                                maxlength="10" 
                                value="{{ old('pincode', $user->pincode) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Office / Residential Address
                        </label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            value="{{ old('address', $user->address) }}" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                        >
                    </div>

                    <!-- City & State -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                City
                            </label>
                            <input 
                                type="text" 
                                id="city" 
                                name="city" 
                                value="{{ old('city', $user->city) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>

                        <div>
                            <label for="state" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                State (India)
                            </label>
                            <input 
                                type="text" 
                                id="state" 
                                name="state" 
                                value="{{ old('state', $user->state) }}" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Bio -->
                    <div>
                        <label for="bio" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Professional Summary / Bio
                        </label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            rows="3" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                        >{{ old('bio', $user->bio) }}</textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl shadow-md shadow-brand-500/20 hover:shadow-lg transition-all flex items-center gap-2"
                        >
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            @else
                <!-- EMPLOYEE / REGULAR USER VIEW-ONLY DISPLAY -->
                <div class="space-y-6">
                    
                    <!-- Section A: Identity & Contact -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Contact & Work Information</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Full Name</span>
                                <span class="text-sm font-bold text-slate-900">{{ $user->name }}</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Official Email</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $user->email }}</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Contact Number</span>
                                <span class="text-sm font-mono font-bold text-slate-900">+91 {{ $user->mobile_number }}</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Designation & Department</span>
                                <span class="text-sm font-bold text-slate-900">
                                    {{ $employee?->designation?->name ?: ($user->designation ?: 'Team Member') }}
                                    <span class="text-xs text-slate-500 font-normal">({{ $employee?->department?->name ?: ($user->department ?: 'General') }})</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Employment & Shift Parameters -->
                    @if($employee)
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Employment & Operations</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Employment Status</span>
                                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md inline-block mt-1">
                                        {{ ucfirst($employee->status ?: 'Active') }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Assigned Shift</span>
                                    <span class="text-xs font-bold text-slate-900 block mt-1">
                                        {{ $employee->shift?->name ?: 'General Day Shift' }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">
                                        {{ $employee->shift ? substr($employee->shift->start_time, 0, 5) . ' - ' . substr($employee->shift->end_time, 0, 5) : '09:30 - 18:30' }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Attendance Mode</span>
                                    <span class="text-xs font-bold text-brand-700 block mt-1">
                                        {{ $employee->location_type === 'random' ? 'Random / Field GPS' : 'Fixed Geofence (200m)' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Section C: Address & Residential Information -->
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Residential Details</h4>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                                <div class="flex items-start gap-2">
                                    <i data-lucide="home" class="w-4 h-4 text-slate-400 shrink-0 mt-0.5"></i>
                                    <div>
                                        <strong class="text-slate-800">Present Address:</strong>
                                        <p class="text-slate-600 mt-0.5">
                                            {{ $employee->present_street ?: ($user->address ?: 'Not provided') }}, 
                                            {{ $employee->present_city ?: ($user->city ?: '') }}, 
                                            {{ $employee->present_state ?: ($user->state ?: '') }} 
                                            {{ $employee->present_pin ? ' - ' . $employee->present_pin : '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section D: Statutory Details (Masked for privacy) -->
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Statutory & Bank Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">PAN Number</span>
                                    <span class="text-xs font-mono font-bold text-slate-800">
                                        {{ $employee->pan_number ? substr($employee->pan_number, 0, 5) . 'XXXX' . substr($employee->pan_number, -1) : 'Not Provided' }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Aadhaar (Masked)</span>
                                    <span class="text-xs font-mono font-bold text-slate-800">
                                        {{ $employee->aadhar_number ? 'XXXX-XXXX-' . substr($employee->aadhar_number, -4) : 'Not Provided' }}
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Bank Account</span>
                                    <span class="text-xs font-mono font-bold text-slate-800">
                                        {{ $employee->bank_name ? $employee->bank_name . ' (•' . substr($employee->account_number, -4) . ')' : 'Configured in Payroll' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            @endif

        </div>

        <!-- Column 2: Change Password & Security (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i data-lucide="key" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Change Password</h3>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Ensure your account uses a strong passphrase with min 8 characters.
                    </p>
                </div>

                <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Current Password <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required 
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                        >
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            New Password <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="password" 
                            required 
                            placeholder="At least 8 characters"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                        >
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Confirm Password <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            required 
                            placeholder="Repeat new password"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white focus:border-brand-500 transition-all"
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2"
                    >
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Update Password</span>
                    </button>
                </form>
            </div>

            <!-- Security & Role Info -->
            <div class="bg-indigo-50/50 rounded-3xl p-5 border border-indigo-100 text-xs text-indigo-900 space-y-3">
                <div class="flex items-center gap-2 font-bold text-brand-900">
                    <i data-lucide="{{ $isAdmin ? 'shield-alert' : 'user-check' }}" class="w-4 h-4 text-brand-600"></i>
                    <span>{{ $isAdmin ? 'Superadmin Privileges' : 'Employee Access Tier' }}</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    @if($isAdmin)
                        This account holds master administrative authority over organization hierarchies, employee statutory compliance (EPF, ESI, TDS), and branch configurations.
                    @else
                        This account has self-service employee portal access to mark attendance punches, view monthly attendance quotas, apply for leaves, access confidential payslips, and manage exit requests.
                    @endif
                </p>
                <div class="pt-2 border-t border-indigo-100 text-[10px] text-slate-500 flex items-center justify-between">
                    <span>Role: {{ strtoupper($user->role) }}</span>
                    <span>Region: India (IST)</span>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreviewImg = document.getElementById('avatarPreviewImg');

    if (avatarInput && avatarPreviewImg) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    avatarPreviewImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>
@endpush
