@extends('layouts.app')

@section('title', 'Organizations & Companies')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-brand-600 font-semibold">Organizations</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Company & Organization Master</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Manage multi-company legal entities, GSTIN/CIN compliance, and workforce distribution.
            </p>
        </div>

        <button 
            type="button" 
            onclick="document.getElementById('newCompanyModal').classList.remove('hidden')" 
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-brand-500/20 hover:shadow-lg transition-all self-start sm:self-auto"
        >
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Add New Company</span>
        </button>
    </div>

    <!-- Active Global Filter Notice -->
    <div class="bg-gradient-to-r from-brand-50 to-indigo-50 border border-brand-200/80 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold shadow-sm">
                <i data-lucide="building-2" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Current Global Active Filter:</span>
                <p class="text-sm font-extrabold text-slate-900">
                    {{ $activeCompany ? $activeCompany->name : '🏢 All Companies (Combined View)' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('companies.switch') }}">
                @csrf
                <input type="hidden" name="company_id" value="all">
                <button type="submit" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-colors shadow-sm">
                    View All Companies
                </button>
            </form>
        </div>
    </div>

    <!-- Company Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($companies as $comp)
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative group">
                <div>
                    <!-- Top badge and status -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-brand-600 border border-indigo-100 flex items-center justify-center font-black text-sm">
                            {{ $comp->code ?: substr($comp->name, 0, 2) }}
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($activeCompanyId == $comp->id)
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                    Active View
                                </span>
                            @endif
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ $comp->legal_type ?: 'Entity' }}
                            </span>
                        </div>
                    </div>

                    <h3 class="text-base font-extrabold text-slate-900 leading-snug mb-1">
                        {{ $comp->name }}
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                        {{ $comp->city ? "{$comp->city}, {$comp->state}" : 'India' }}
                    </p>

                    <!-- Meta stats -->
                    <div class="grid grid-cols-2 gap-2 bg-slate-50/80 rounded-2xl p-3 border border-slate-100 text-xs mb-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Employees</span>
                            <span class="text-base font-extrabold text-brand-700">{{ $comp->employees_count }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">User Accounts</span>
                            <span class="text-base font-extrabold text-slate-800">{{ $comp->users_count }}</span>
                        </div>
                    </div>

                    @if ($comp->gstin || $comp->cin)
                        <div class="text-[11px] text-slate-500 space-y-1 mb-4">
                            @if ($comp->gstin)
                                <div class="font-mono"><span class="font-bold text-slate-400">GSTIN:</span> {{ $comp->gstin }}</div>
                            @endif
                            @if ($comp->cin)
                                <div class="font-mono"><span class="font-bold text-slate-400">CIN:</span> {{ $comp->cin }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Action Button: Switch Global View or Filter Employees -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <form method="POST" action="{{ route('companies.switch') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $comp->id }}">
                        <button 
                            type="submit" 
                            class="w-full py-2 px-3 text-xs font-bold rounded-xl transition-all {{ $activeCompanyId == $comp->id ? 'bg-brand-50 text-brand-700 border border-brand-200 cursor-default' : 'bg-slate-900 hover:bg-slate-800 text-white shadow-sm' }}"
                        >
                            {{ $activeCompanyId == $comp->id ? 'Currently Selected' : 'Filter System to This Company' }}
                        </button>
                    </form>

                    <a 
                        href="{{ route('employees.index', ['company_id' => $comp->id]) }}" 
                        class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-colors"
                        title="View Employees in this Company"
                    >
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-12 text-center border border-slate-200">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-brand-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="building-2" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Companies Found</h3>
                <p class="text-xs text-slate-500 mt-1">Get started by creating your legal business entities.</p>
            </div>
        @endforelse
    </div>

</div>

<!-- Modal: Add New Company -->
<div id="newCompanyModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="building-2" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add Company Entity</h3>
                    <p class="text-xs text-slate-500">Register new business or group entity.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('newCompanyModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('companies.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Company Legal Name *</label>
                <input type="text" name="name" required placeholder="e.g. Riangdo Venners Pvt. Ltd." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Short Code</label>
                    <input type="text" name="code" placeholder="e.g. RVPL" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono font-bold uppercase text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Entity Type</label>
                    <select name="legal_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                        <option value="Private Limited">Private Limited</option>
                        <option value="LLP">LLP</option>
                        <option value="Partnership">Partnership</option>
                        <option value="Proprietorship">Proprietorship</option>
                        <option value="Public Limited">Public Limited</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">GSTIN</label>
                    <input type="text" name="gstin" placeholder="15-digit GSTIN" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 uppercase focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">CIN / Reg No.</label>
                    <input type="text" name="cin" placeholder="e.g. U20211AS20..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 uppercase focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">City</label>
                    <input type="text" name="city" placeholder="Guwahati" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">State</label>
                    <input type="text" name="state" value="Assam" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">PIN Code</label>
                    <input type="text" name="pincode" placeholder="781028" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('newCompanyModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20">Save Company</button>
            </div>
        </form>
    </div>
</div>
@endsection
