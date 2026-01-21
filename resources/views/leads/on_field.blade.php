@extends('layouts.app')

@section('title', 'Field Activity')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
        .glass-card {
            @apply bg-white/50 backdrop-blur-sm border border-white/20 transition-all duration-200;
        }
        .action-button {
            @apply flex flex-col items-center justify-center p-4 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all;
        }
    }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 pb-24 bg-[#f8fafc]">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Field</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Activity</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">On Field</h1>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg">
            <span class="material-symbols-outlined">explore</span>
        </div>
    </div>

    <!-- Active Visit HUD -->
    @if($activeVisit)
    <div class="bg-blue-600 rounded-[2rem] p-6 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-20">
            <span class="material-symbols-outlined text-[120px]">location_on</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex px-2 py-1 bg-white/20 rounded-lg text-[10px] font-black uppercase tracking-wider">Currently In Visit</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <h2 class="text-2xl font-black mb-1">{{ $activeVisit->lead->name }}</h2>
            <p class="text-blue-100 text-xs font-bold mb-6 flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                Checked in at {{ Carbon\Carbon::parse($activeVisit->intime_time)->format('h:i A') }}
            </p>
            
            <button onclick="openCheckOutModal('{{ $activeVisit->id }}', '{{ $activeVisit->lead->name }}')" class="w-full py-4 bg-white text-blue-600 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-blue-50 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">logout</span>
                Check Out Now
            </button>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4">
        <button onclick="document.getElementById('lead-selection-section').scrollIntoView({behavior: 'smooth'})" class="action-button">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined">add_location</span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">New Visit</span>
        </button>
        <button onclick="openScheduleModal()" class="action-button">
            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined">event</span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Schedule</span>
        </button>
    </div>

    <!-- Scheduled Today -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Scheduled Today</h3>
            <span class="px-2 py-0.5 bg-slate-200 text-slate-600 rounded font-black text-[9px]">{{ $scheduledVisits->count() }}</span>
        </div>
        
        <div class="space-y-3">
            @forelse($scheduledVisits as $sv)
            <div class="glass-panel rounded-3xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                        <span class="material-symbols-outlined text-[20px]">person</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">{{ $sv->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $sv->city }}</p>
                    </div>
                </div>
                @if(!$activeVisit)
                <button onclick="handleCheckIn('{{ $sv->id }}')" class="px-3 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md">
                    Check-in
                </button>
                @endif
            </div>
            @empty
            <div class="text-center py-8 opacity-50">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">No visits scheduled for today</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Lead Selection (All My Leads) -->
    <div id="lead-selection-section" class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Active Leads</h3>
            <div class="relative">
                <input type="text" id="lead-search" placeholder="Search..." class="bg-transparent border-none focus:ring-0 text-xs font-bold text-slate-600 placeholder:text-slate-300 w-32 text-right">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3" id="leads-container">
            @foreach($leads as $lead)
            <div class="lead-card glass-panel rounded-3xl p-5 group hover:border-blue-200 transition-all" data-name="{{ strtolower($lead->name) }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-lg">
                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="text-base font-black text-slate-900">{{ $lead->name }}</h4>
                            <p class="text-[10px] text-slate-400 font-bold flex items-center gap-1 uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                {{ $lead->city }}
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex px-2 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-wider">
                        Stage {{ $lead->lead_stage }}
                    </span>
                </div>
                
                <div class="flex items-center gap-2">
                    @if(!$activeVisit)
                    <button onclick="handleCheckIn('{{ $lead->id }}')" class="flex-1 py-3 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/20 active:scale-95 transition-all">
                        Check In
                    </button>
                    @endif
                    <a href="tel:{{ $lead->phone_number }}" class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-100 transition-all">
                        <span class="material-symbols-outlined">call</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Check-out Modal -->
<div id="check-out-modal" class="fixed inset-0 z-50 hidden flex items-end justify-center bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
    <div class="w-full max-w-lg bg-white rounded-t-[3rem] p-8 shadow-2xl transform translate-y-full transition-transform duration-300">
        <div class="w-12 h-1.5 bg-slate-100 rounded-full mx-auto mb-8"></div>
        <h3 class="text-xl font-black text-slate-900 mb-2">Check Out</h3>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6" id="checkout-lead-name"></p>
        
        <input type="hidden" id="visit-id-to-checkout">
        <div class="space-y-4 mb-8">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Visit Remarks</label>
            <textarea id="checkout-remarks" rows="4" class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:ring-blue-500 focus:border-blue-500 text-sm font-bold placeholder:text-slate-300" placeholder="What happened during the visit?"></textarea>
        </div>
        
        <div class="flex gap-3">
            <button onclick="closeModal('check-out-modal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest">Cancel</button>
            <button id="submit-checkout" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/20 active:scale-95 transition-all">Complete Visit</button>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div id="schedule-modal" class="fixed inset-0 z-50 hidden flex items-end justify-center bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
    <div class="w-full max-w-lg bg-white rounded-t-[3rem] p-8 shadow-2xl transform translate-y-full transition-transform duration-300">
        <div class="w-12 h-1.5 bg-slate-100 rounded-full mx-auto mb-8"></div>
        <h3 class="text-xl font-black text-slate-900 mb-6">Schedule Visit</h3>
        
        <div class="space-y-4 mb-8">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Select Lead</label>
                <select id="schedule-lead-id" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold">
                    @foreach($leads as $l)
                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Visit Date</label>
                <input type="date" id="schedule-date" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold" min="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Building Status (Optional)</label>
                <input type="text" id="schedule-status" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold" placeholder="e.g. Painting stage">
            </div>
        </div>
        
        <div class="flex gap-3">
            <button onclick="closeModal('schedule-modal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest">Cancel</button>
            <button id="submit-schedule" class="flex-[2] py-4 bg-purple-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-purple-500/20 active:scale-95 transition-all">Set Schedule</button>
        </div>
    </div>
</div>

<script>
    // Lead Search
    document.getElementById('lead-search').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.lead-card').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(query) ? 'block' : 'none';
        });
    });

    // Check-in Logic
    async function handleCheckIn(leadId) {
        if (!confirm('Start visit check-in? Your current location will be recorded.')) return;

        try {
            const pos = await getCurrentLocation();
            const formData = new FormData();
            formData.append('lead_id', leadId);
            formData.append('inlat', pos.lat);
            formData.append('inlong', pos.lng);
            
            // Note: Camera integration can be added here with a separate modal
            // For now, we proceed with location only as per API capability

            const response = await fetch('/api/leads/check-in', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();
            if (data.status) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            alert('Location access denied or unavailable. Please enable GPS.');
        }
    }

    // Modal Helpers
    function openCheckOutModal(id, name) {
        document.getElementById('visit-id-to-checkout').value = id;
        document.getElementById('checkout-lead-name').innerText = name;
        const modal = document.getElementById('check-out-modal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.querySelector('div').classList.remove('translate-y-full'), 10);
    }

    function openScheduleModal() {
        const modal = document.getElementById('schedule-modal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.querySelector('div').classList.remove('translate-y-full'), 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.querySelector('div').classList.add('translate-y-full');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // Check-out Submit
    document.getElementById('submit-checkout').addEventListener('click', async () => {
        const visitId = document.getElementById('visit-id-to-checkout').value;
        const remarks = document.getElementById('checkout-remarks').value;

        if (!remarks) {
            alert('Please enter visit remarks');
            return;
        }

        try {
            const pos = await getCurrentLocation();
            const formData = new FormData();
            formData.append('visit_id', visitId);
            formData.append('outlat', pos.lat);
            formData.append('outlong', pos.lng);
            formData.append('remarks', remarks);

            const response = await fetch('/api/leads/check-out', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();
            if (data.status) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            alert('Location required for check-out.');
        }
    });

    // Schedule Submit
    document.getElementById('submit-schedule').addEventListener('click', async () => {
        const leadId = document.getElementById('schedule-lead-id').value;
        const date = document.getElementById('schedule-date').value;
        const status = document.getElementById('schedule-status').value;

        if (!date) {
            alert('Please select a date');
            return;
        }

        try {
            const response = await fetch(`/api/leads/${leadId}/followup`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    follow_up_date: date,
                    building_status: status
                }),
            });

            const data = await response.json();
            if (data.status) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            alert('An error occurred while scheduling.');
        }
    });

    function getCurrentLocation() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                p => resolve({ lat: p.coords.latitude, lng: p.coords.longitude }),
                e => reject(e),
                { enableHighAccuracy: true }
            );
        });
    }
</script>
@endsection
