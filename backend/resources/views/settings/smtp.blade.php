@extends('layouts.app')

@section('title', 'SMTP Configuration - Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
            <span>/</span>
            <span class="text-slate-400">Settings</span>
            <span>/</span>
            <span class="text-brand-600 font-semibold">SMTP Configuration</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">SMTP Mail Configuration</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Configure system email gateways for employee offer letters, salary slips, leave notifications, and password resets.
        </p>
    </div>

    <!-- Active Status Card -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-brand-600 flex items-center justify-center shrink-0">
                <i data-lucide="mail-check" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-slate-900 text-base">Outbound Mail Gateway</h3>
                    @if ($smtp->is_active)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Active
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                            Disabled
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Current Gateway: <strong class="text-slate-700 font-mono">{{ $smtp->mail_host }}:{{ $smtp->mail_port }}</strong> ({{ strtoupper($smtp->mail_encryption) }})
                </p>
            </div>
        </div>

        <!-- Quick Test Email Trigger -->
        <button 
            type="button" 
            onclick="document.getElementById('testEmailModal').classList.remove('hidden')" 
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center gap-2 transition-colors self-start sm:self-auto"
        >
            <i data-lucide="send" class="w-3.5 h-3.5"></i>
            <span>Send Test Email</span>
        </button>
    </div>

    <!-- SMTP Form Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm">
        <form method="POST" action="{{ route('settings.smtp.update') }}" class="space-y-6">
            @csrf

            <!-- Driver & Host -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Mail Driver / Mailer *
                    </label>
                    <select name="mail_mailer" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="smtp" {{ $smtp->mail_mailer === 'smtp' ? 'selected' : '' }}>SMTP (Standard)</option>
                        <option value="sendmail" {{ $smtp->mail_mailer === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        <option value="mailgun" {{ $smtp->mail_mailer === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        <option value="ses" {{ $smtp->mail_mailer === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                        <option value="log" {{ $smtp->mail_mailer === 'log' ? 'selected' : '' }}>Log (Dev Test)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        SMTP Host *
                    </label>
                    <input 
                        type="text" 
                        name="mail_host" 
                        required 
                        value="{{ old('mail_host', $smtp->mail_host) }}" 
                        placeholder="smtp.gmail.com / smtp.office365.com / mail.domain.com"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>
            </div>

            <!-- Port & Encryption -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        SMTP Port *
                    </label>
                    <input 
                        type="number" 
                        name="mail_port" 
                        required 
                        value="{{ old('mail_port', $smtp->mail_port) }}" 
                        placeholder="587 / 465 / 25"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Recommended: 587 for TLS, 465 for SSL</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Encryption Protocol *
                    </label>
                    <select name="mail_encryption" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <option value="tls" {{ $smtp->mail_encryption === 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                        <option value="ssl" {{ $smtp->mail_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ $smtp->mail_encryption === 'none' ? 'selected' : '' }}>None (Plaintext)</option>
                    </select>
                </div>
            </div>

            <!-- Username & Password -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Username / Auth Email
                    </label>
                    <input 
                        type="text" 
                        name="mail_username" 
                        value="{{ old('mail_username', $smtp->mail_username) }}" 
                        placeholder="notifications@yourdomain.com"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        SMTP Password / App Key
                    </label>
                    <input 
                        type="password" 
                        name="mail_password" 
                        value="{{ $smtp->mail_password ? '••••••••' : '' }}" 
                        placeholder="Leave blank to retain current password"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">For Gmail/Workspace, generate a 16-character App Password.</p>
                </div>
            </div>

            <!-- Sender Identity -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        From Email Address *
                    </label>
                    <input 
                        type="email" 
                        name="mail_from_address" 
                        required 
                        value="{{ old('mail_from_address', $smtp->mail_from_address) }}" 
                        placeholder="no-reply@hrms.in"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        From Display Name *
                    </label>
                    <input 
                        type="text" 
                        name="mail_from_name" 
                        required 
                        value="{{ old('mail_from_name', $smtp->mail_from_name) }}" 
                        placeholder="Enterprise HRMS India"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                    >
                </div>
            </div>

            <!-- Enable Toggle -->
            <div class="pt-2 flex items-center justify-between">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1" 
                        {{ $smtp->is_active ? 'checked' : '' }} 
                        class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500"
                    >
                    <span class="text-xs font-semibold text-slate-800 select-none">Enable Outbound Email Service</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button 
                    type="submit" 
                    class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl shadow-md shadow-brand-500/20 hover:shadow-lg transition-all flex items-center gap-2"
                >
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save SMTP Settings</span>
                </button>
            </div>

        </form>
    </div>

</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Send Test Email</h3>
            <button type="button" onclick="document.getElementById('testEmailModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('settings.smtp.test') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Recipient Email Address *</label>
                <input 
                    type="email" 
                    name="test_email" 
                    required 
                    value="{{ Auth::user()->email }}" 
                    placeholder="you@domain.com" 
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                >
                <p class="text-[11px] text-slate-400 mt-1">A sample verification email will be dispatched to this mailbox.</p>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button 
                    type="button" 
                    onclick="document.getElementById('testEmailModal').classList.add('hidden')" 
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20 transition-all flex items-center gap-1.5"
                >
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Send Verification</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
