@extends('layouts.app')

@section('title', 'Work Locations & Geofence Manager - Settings')

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
                <span class="text-brand-600 font-semibold">Location Manager</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Work Locations & Geofence</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Define fixed office locations with GPS coordinates and 200m radius for attendance punch in/out.
            </p>
        </div>

        <button 
            type="button" 
            onclick="openLocationModal()" 
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all"
        >
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Add Work Location</span>
        </button>
    </div>

    <!-- Info banner regarding Fixed vs Random Location -->
    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-start gap-3.5">
        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="map-pin" class="w-4 h-4"></i>
        </div>
        <div class="text-xs text-indigo-950 space-y-1">
            <p class="font-bold">Location Policy Governance:</p>
            <p class="text-indigo-800">
                • <strong>Fixed Office Employees:</strong> Assigned to a specific location below. Punch in and punch out are strictly restricted to within <strong>200 meters</strong> of the office coordinates.<br>
                • <strong>Random / Outside Field Employees:</strong> Assigned type "Random Location" during onboarding or profile editing. No fixed geofence is required; punches are captured with live device GPS and recorded in the attendance logs.
            </p>
        </div>
    </div>

    <!-- Locations Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="building" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Configured Office Locations</h3>
                    <p class="text-[11px] text-slate-400">Total {{ $locations->count() }} active locations</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-[11px] border-b border-slate-100">
                        <th class="py-3 px-4">Location Name</th>
                        <th class="py-3 px-4">Entity / Company</th>
                        <th class="py-3 px-4">GPS Coordinates</th>
                        <th class="py-3 px-4">Allowed Radius</th>
                        <th class="py-3 px-4">Address / City</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($locations as $loc)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $loc->name }}</span>
                                @if($loc->code)
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $loc->code }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-medium text-slate-700">{{ $loc->company?->name ?: 'All Companies' }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-brand-700 font-semibold">
                                {{ number_format($loc->latitude, 6) }}, {{ number_format($loc->longitude, 6) }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $loc->radius_meters }} Meters
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="block truncate max-w-xs">{{ $loc->address ?: 'N/A' }}</span>
                                <span class="text-[10px] text-slate-400">{{ $loc->city }} {{ $loc->state ? '• ' . $loc->state : '' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($loc->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button 
                                        type="button" 
                                        onclick="editLocation({{ json_encode($loc) }})" 
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                        title="Edit Location"
                                    >
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <form method="POST" action="{{ route('settings.locations.delete', $loc->id) }}" onsubmit="return confirm('Are you sure you want to delete this location?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete Location">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <i data-lucide="map-pin-off" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-semibold">No work locations configured yet.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Click "Add Work Location" to configure head office or branch coordinates.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Location -->
<div id="locationModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900" id="locationModalTitle">Add Work Location</h3>
            <button type="button" onclick="closeLocationModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="locationForm" method="POST" action="{{ route('settings.locations.store') }}" class="space-y-4 pt-3">
            @csrf
            <div id="locationMethodField"></div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Location Name *</label>
                    <input type="text" name="name" id="locName" required placeholder="e.g. Head Office (Guwahati) / Mill Unit" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-500">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Company / Entity</label>
                    <select name="company_id" id="locCompanyId" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                        <option value="">All Companies</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Location Code</label>
                    <input type="text" name="code" id="locCode" placeholder="e.g. HQ-01" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900">
                </div>
            </div>

            <!-- GPS Coordinates & Geofence -->
            <div class="p-3.5 rounded-2xl bg-brand-50/50 border border-brand-100 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-brand-900">GPS Coordinates (WGS84)</span>
                    <button type="button" onclick="detectCurrentLocation()" class="text-[11px] font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                        <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
                        <span>Detect My GPS</span>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-600 mb-0.5">Latitude *</label>
                        <input type="number" step="any" name="latitude" id="locLatitude" required placeholder="26.144517" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-600 mb-0.5">Longitude *</label>
                        <input type="number" step="any" name="longitude" id="locLongitude" required placeholder="91.736236" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-600 mb-0.5">Radius (Meters) *</label>
                        <input type="number" name="radius_meters" id="locRadius" required value="200" min="50" max="5000" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-brand-700">
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Punches outside this radius will be rejected for fixed employees.</p>
            </div>

            <div class="space-y-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Full Street Address</label>
                    <input type="text" name="address" id="locAddress" placeholder="Street, landmark, industrial estate" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">City</label>
                        <input type="text" name="city" id="locCity" placeholder="City" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">State</label>
                        <input type="text" name="state" id="locState" placeholder="State" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">PIN Code</label>
                        <input type="text" name="pin_code" id="locPin" placeholder="781028" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeLocationModal()" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-md">Save Location</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLocationModal() {
    document.getElementById('locationModalTitle').innerText = 'Add Work Location';
    document.getElementById('locationForm').action = "{{ route('settings.locations.store') }}";
    document.getElementById('locationMethodField').innerHTML = '';
    document.getElementById('locName').value = '';
    document.getElementById('locCompanyId').value = '';
    document.getElementById('locCode').value = '';
    document.getElementById('locLatitude').value = '26.144517';
    document.getElementById('locLongitude').value = '91.736236';
    document.getElementById('locRadius').value = '200';
    document.getElementById('locAddress').value = '';
    document.getElementById('locCity').value = 'Guwahati';
    document.getElementById('locState').value = 'Assam';
    document.getElementById('locPin').value = '781028';
    document.getElementById('locationModal').classList.remove('hidden');
}

function editLocation(loc) {
    document.getElementById('locationModalTitle').innerText = 'Edit Work Location';
    document.getElementById('locationForm').action = "/settings/locations/" + loc.id;
    document.getElementById('locationMethodField').innerHTML = '@method("PUT")';
    document.getElementById('locName').value = loc.name || '';
    document.getElementById('locCompanyId').value = loc.company_id || '';
    document.getElementById('locCode').value = loc.code || '';
    document.getElementById('locLatitude').value = loc.latitude || '';
    document.getElementById('locLongitude').value = loc.longitude || '';
    document.getElementById('locRadius').value = loc.radius_meters || '200';
    document.getElementById('locAddress').value = loc.address || '';
    document.getElementById('locCity').value = loc.city || '';
    document.getElementById('locState').value = loc.state || '';
    document.getElementById('locPin').value = loc.pin_code || '';
    document.getElementById('locationModal').classList.remove('hidden');
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
}

function detectCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('locLatitude').value = position.coords.latitude.toFixed(6);
                document.getElementById('locLongitude').value = position.coords.longitude.toFixed(6);
            },
            function(error) {
                alert('Unable to detect GPS position: ' + error.message);
            },
            { enableHighAccuracy: true }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
    }
}
</script>
@endsection
