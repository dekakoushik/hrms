@extends('layouts.app')

@section('title', 'Notification Centre - Activity & Alerts')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <span>System</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-700">Notification Centre</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Notification Centre</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                    Live Feed
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Real-time alerts for late employee check-ins, early exits, leave applications, and approvals.</p>
        </div>

        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button 
                type="submit" 
                class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5"
            >
                <i data-lucide="check-check" class="w-4 h-4 text-brand-600"></i>
                <span>Mark All As Read</span>
            </button>
        </form>
    </div>

    <!-- Notification Feed Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">All Notifications & Activity</span>
            <span class="text-xs text-slate-400 font-medium">Total {{ $notifications->total() }} alerts</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notif)
                <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition-colors flex items-start gap-4 {{ $notif->is_read ? 'opacity-70 bg-white' : 'bg-slate-50/30' }}">
                    <div class="w-10 h-10 rounded-xl bg-{{ $notif->color }}-50 text-{{ $notif->color }}-600 border border-{{ $notif->color }}-200 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="{{ $notif->icon ?: 'bell' }}" class="w-5 h-5"></i>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span>{{ $notif->title }}</span>
                                    @if(!$notif->is_read)
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    @endif
                                </h3>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $notif->message }}</p>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium shrink-0 whitespace-nowrap">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            @if($notif->link_url)
                                <a href="{{ $notif->link_url }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                                    <span>Review details</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            @endif

                            @if(!$notif->is_read)
                                <form method="POST" action="{{ route('notifications.read', $notif->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-slate-700 hover:underline">
                                        Mark read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400">
                    <i data-lucide="bell-off" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                    <p class="text-sm font-semibold text-slate-700">No notifications in your inbox.</p>
                    <p class="text-xs text-slate-400 mt-0.5">System alerts, employee late check-ins, and leave updates will show up here.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
