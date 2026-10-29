@extends('layouts.app')

@section('title', 'Company Contact Directory • HRMS')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/10 text-indigo-200 border border-white/10">
                        Company Directory
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/20 text-emerald-300">
                        {{ $totalContacts }} Active Contacts
                    </span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Workplace Contact Directory</h1>
                <p class="text-xs text-indigo-200/80 mt-1 max-w-xl">
                    Search colleagues, view corporate email addresses, phone extensions, and explore teams department-wise.
                </p>
            </div>

            <!-- Quick Stats in Header -->
            <div class="flex items-center gap-3">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-white/10 text-center">
                    <span class="text-lg font-bold font-mono block leading-tight">{{ $departments->count() }}</span>
                    <span class="text-[10px] uppercase font-semibold text-indigo-200">Departments</span>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl px-4 py-2.5 border border-white/10 text-center">
                    <span class="text-lg font-bold font-mono block leading-tight">{{ $companies->count() }}</span>
                    <span class="text-[10px] uppercase font-semibold text-indigo-200">Entities</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
        <form method="GET" action="{{ route('contacts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            
            <!-- Search Input -->
            <div class="lg:col-span-5 relative">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by Employee Name, Employee ID, Email or Phone..."
                    class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all placeholder:text-slate-400"
                >
            </div>

            <!-- Department Filter -->
            <div class="lg:col-span-3">
                <select name="department_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->employees_count }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Work Location Filter -->
            <div class="lg:col-span-2">
                <select name="location_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl transition-colors shadow-sm flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['search', 'department_id', 'location_id', 'company_id']))
                    <a href="{{ route('contacts.index') }}" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors" title="Clear Filters">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>

        <!-- Department Quick Badges / Tabs -->
        <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center gap-2 overflow-x-auto pb-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase shrink-0 mr-1">Teams:</span>
            <a 
                href="{{ route('contacts.index', array_merge(request()->except('department_id'), ['page' => 1])) }}"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold shrink-0 transition-colors {{ empty(request('department_id')) ? 'bg-brand-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >
                All ({{ $totalContacts }})
            </a>
            @foreach($departments as $d)
                <a 
                    href="{{ route('contacts.index', array_merge(request()->all(), ['department_id' => $d->id, 'page' => 1])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold shrink-0 transition-colors {{ request('department_id') == $d->id ? 'bg-brand-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    {{ $d->name }} ({{ $d->employees_count }})
                </a>
            @endforeach
        </div>
    </div>

    <!-- Contacts Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($contacts as $contact)
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md transition-all p-4 flex flex-col justify-between group">
                <div>
                    <!-- Top Avatar & Department Badge -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <img 
                                src="{{ $contact->user?->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($contact->full_name) . '&background=6366f1&color=fff' }}" 
                                alt="{{ $contact->full_name }}" 
                                class="w-12 h-12 rounded-xl object-cover ring-2 ring-slate-100 shadow-2xs"
                            >
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm group-hover:text-brand-600 transition-colors leading-tight">
                                    {{ $contact->full_name }}
                                </h3>
                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">
                                    {{ $contact->employee_id }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role & Organization -->
                    <div class="mb-3 space-y-1">
                        <div class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-brand-600 shrink-0"></i>
                            <span class="truncate">{{ $contact->designation?->name ?: 'Member' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span class="truncate">{{ $contact->department?->name ?: 'Unassigned' }}</span>
                        </div>
                        @if($contact->company)
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span class="truncate">{{ $contact->company->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Contact Details Card Footer -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <!-- Email -->
                    @php
                        $email = $contact->user?->email ?: $contact->official_email;
                    @endphp
                    @if($email)
                        <a href="mailto:{{ $email }}" class="flex items-center gap-2 text-xs text-slate-600 hover:text-brand-600 transition-colors truncate" title="{{ $email }}">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-brand-600 flex items-center justify-center shrink-0">
                                <i data-lucide="mail" class="w-3 h-3"></i>
                            </div>
                            <span class="truncate font-medium">{{ $email }}</span>
                        </a>
                    @endif

                    <!-- Phone Number -->
                    @if($contact->contact_number)
                        <a href="tel:+91{{ $contact->contact_number }}" class="flex items-center gap-2 text-xs text-slate-600 hover:text-emerald-600 transition-colors" title="+91 {{ $contact->contact_number }}">
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <i data-lucide="phone" class="w-3 h-3"></i>
                            </div>
                            <span class="font-mono font-medium">+91 {{ $contact->contact_number }}</span>
                        </a>
                    @endif

                    <!-- Work Location -->
                    @if($contact->location || $contact->present_city)
                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                            <div class="w-6 h-6 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                            </div>
                            <span class="truncate">{{ $contact->location?->name ?: ($contact->present_city . ($contact->present_state ? ', ' . $contact->present_state : '')) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 bg-white rounded-2xl border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Contacts Found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    No active employees matched your search query. Try broadening your keywords or clearing selected filters.
                </p>
                <div class="mt-4">
                    <a href="{{ route('contacts.index') }}" class="px-4 py-2 bg-brand-600 text-white rounded-xl text-xs font-bold shadow-sm inline-flex items-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Reset All Filters</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($contacts->hasPages())
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
            {{ $contacts->links() }}
        </div>
    @endif

</div>
@endsection
