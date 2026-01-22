@extends('layouts.app')

@section('title', 'BDM Field Activity')

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
                <span>BDM</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-indigo-600">Activity</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">On Field (BDM)</h1>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
            <span class="material-symbols-outlined">explore</span>
        </div>
    </div>

    <!-- Active Visit HUD -->
    @if($activeVisit)
    <div class="bg-indigo-600 rounded-[2rem] p-6 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-20">
            <span class="material-symbols-outlined text-[120px]">location_on</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex px-2 py-1 bg-white/20 rounded-lg text-[10px] font-black uppercase tracking-wider">BDM Active Visit</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            @php
                $targetName = 'Unidentified';
                if ($activeVisit->lead) $targetName = $activeVisit->lead->name;
                elseif ($activeVisit->account) $targetName = $activeVisit->account->name;
                elseif ($activeVisit->fabricator) $targetName = $activeVisit->fabricator->shop_name;
                
                $typeLabel = [1 => 'Account', 2 => 'Leads', 3 => 'Fabricator'][$activeVisit->visit_type] ?? '-';
            @endphp
            <h2 class="text-2xl font-black mb-1 capitalize">{{ $targetName }}</h2>
            <div class="flex gap-2 mb-6">
                <span class="px-2 py-0.5 bg-white/20 rounded text-[9px] font-bold uppercase">{{ $typeLabel }}</span>
                <span class="px-2 py-0.5 bg-white/20 rounded text-[9px] font-bold uppercase">{{ $activeVisit->type }}</span>
            </div>
            
            <p class="text-indigo-100 text-xs font-bold mb-6 flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                Checked in at {{ Carbon\Carbon::parse($activeVisit->intime_time)->format('h:i A') }}
            </p>
            
            <button onclick="openCheckOutModal('{{ $activeVisit->id }}', '{{ $targetName }}')" class="w-full py-4 bg-white text-indigo-600 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">logout</span>
                Check Out Now
            </button>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4">
        <button onclick="openCheckInModal()" class="action-button">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined">add_location</span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">New Visit</span>
        </button>
        <button onclick="window.location.href='{{ route('bdm.visit-report') }}'" class="action-button">
            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined">assessment</span>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Reports</span>
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
                    @php
                        $svName = 'Unknown';
                        if ($sv->lead) $svName = $sv->lead->name;
                        elseif ($sv->account) $svName = $sv->account->name;
                        elseif ($sv->fabricator) $svName = $sv->fabricator->shop_name;
                    @endphp
                    <div>
                        <h4 class="text-sm font-black text-slate-800">{{ $svName }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Scheduled for today</p>
                    </div>
                </div>
                @if(!$activeVisit)
                <button onclick="openCheckInModal('{{ $sv->visit_type }}', '{{ $sv->lead_id ?? $sv->account_id ?? $sv->fabricator_id }}', 'planned')" class="px-3 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md">
                    Start
                </button>
                @endif
            </div>
            @empty
            <div class="text-center py-8 opacity-50">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">No BDM visits scheduled for today</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Leads for Check-in -->
    <div id="lead-selection-section" class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Available Leads</h3>
            <div class="relative">
                <input type="text" id="lead-search" placeholder="Search..." class="bg-transparent border-none focus:ring-0 text-xs font-bold text-slate-600 placeholder:text-slate-300 w-32 text-right">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3" id="leads-container">
            @foreach($leads as $lead)
            <div class="lead-card glass-panel rounded-3xl p-5 group hover:border-indigo-200 transition-all" data-name="{{ strtolower($lead->name) }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-lg">
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
                    <button onclick="openCheckInModal(2, '{{ $lead->id }}', 'unplanned')" class="flex-1 py-3 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 active:scale-95 transition-all">
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

<!-- Check-In Modal -->
<div id="check-in-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="w-full max-w-lg bg-white rounded-[2.5rem] p-6 shadow-2xl relative">
        <button onclick="closeModal('check-in-modal')" class="absolute top-4 right-4 text-slate-400"><span class="material-symbols-outlined">close</span></button>
        <h3 class="text-xl font-black text-slate-900 mb-6 mt-2">BDM Site Visit</h3>
        
        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            <!-- Visit Type -->
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Visit Type</label>
                <select id="modal-visit-type" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold" onchange="toggleVisitTargetInputs()">
                    <option value="2">Lead Visit</option>
                    <option value="1">Account Visit</option>
                    <option value="3">Fabricator Visit</option>
                </select>
            </div>

            <!-- Lead Target -->
            <div id="target-lead-div">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Select Lead</label>
                <select id="modal-lead-id" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold">
                    <option value="">- Select Lead -</option>
                    @foreach($leads as $l)
                        <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->city }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Account Target -->
            <div id="target-account-div" class="hidden">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Select Account</label>
                <select id="modal-account-id" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold">
                    <option value="">- Select Account -</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fabricator Target -->
            <div id="target-fabricator-div" class="hidden">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Select Fabricator</label>
                <select id="modal-fabricator-id" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold">
                    <option value="">- Select Fabricator -</option>
                    @foreach($fabricators as $f)
                        <option value="{{ $f->id }}">{{ $f->shop_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- Food Allowance -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Food Allowance</label>
                    <select id="modal-food-allowance" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold">
                        <option value="1">Local Station</option>
                        <option value="2">Outstation</option>
                    </select>
                </div>
                <!-- Work Type -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Work Type</label>
                    <select id="modal-work-type" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-bold" onchange="toggleJointWorkInputs()">
                        <option value="Individual">Individual</option>
                        <option value="Joint Work">Joint Work</option>
                    </select>
                </div>
            </div>

            <!-- Joint Work Options -->
            <div id="joint-work-div" class="hidden space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Collaborator (BDM)</label>
                    <select id="modal-bdm-id" class="w-full rounded-xl border-slate-200 bg-white text-xs font-bold">
                        <option value="">- Select BDM -</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Collaborator (BDO)</label>
                    <select id="modal-bdo-id" class="w-full rounded-xl border-slate-200 bg-white text-xs font-bold">
                        <option value="">- Select BDO -</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" id="modal-visit-mode" value="unplanned">
        </div>
        
        <div class="mt-8">
            <button onclick="submitCheckIn()" class="w-full py-4 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">login</span>
                Check In Now
            </button>
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
            <button id="submit-checkout" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 active:scale-95 transition-all">Complete Visit</button>
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
    function openCheckInModal(type = 2, targetId = '', mode = 'unplanned') {
        document.getElementById('modal-visit-type').value = type;
        document.getElementById('modal-visit-mode').value = mode;
        
        toggleVisitTargetInputs();
        
        if (targetId) {
            if (type == 2) document.getElementById('modal-lead-id').value = targetId;
            else if (type == 1) document.getElementById('modal-account-id').value = targetId;
            else if (type == 3) document.getElementById('modal-fabricator-id').value = targetId;
        }

        const modal = document.getElementById('check-in-modal');
        modal.classList.remove('hidden');
    }

    function toggleVisitTargetInputs() {
        const type = document.getElementById('modal-visit-type').value;
        document.getElementById('target-lead-div').classList.toggle('hidden', type != 2);
        document.getElementById('target-account-div').classList.toggle('hidden', type != 1);
        document.getElementById('target-fabricator-div').classList.toggle('hidden', type != 3);
    }

    function toggleJointWorkInputs() {
        const value = document.getElementById('modal-work-type').value;
        document.getElementById('joint-work-div').classList.toggle('hidden', value != 'Joint Work');
    }

    async function submitCheckIn() {
        const type = document.getElementById('modal-visit-type').value;
        const leadId = document.getElementById('modal-lead-id').value;
        const accountId = document.getElementById('modal-account-id').value;
        const fabricatorId = document.getElementById('modal-fabricator-id').value;
        const foodAllowance = document.getElementById('modal-food-allowance').value;
        const workType = document.getElementById('modal-work-type').value;
        const bdmId = document.getElementById('modal-bdm-id').value;
        const bdoId = document.getElementById('modal-bdo-id').value;
        const visitMode = document.getElementById('modal-visit-mode').value;

        // Validation
        if (type == 2 && !leadId) { alert('Please select a lead'); return; }
        if (type == 1 && !accountId) { alert('Please select an account'); return; }
        if (type == 3 && !fabricatorId) { alert('Please select a fabricator'); return; }
        if (workType == 'Joint Work' && !bdmId && !bdoId) { alert('Please select at least one collaborator for joint work'); return; }

        try {
            const pos = await getCurrentLocation();
            const formData = new FormData();
            formData.append('user_id', '{{ Auth::id() }}');
            if (leadId) formData.append('lead_id', leadId);
            if (accountId) formData.append('account_id', accountId);
            if (fabricatorId) formData.append('fabricator_id', fabricatorId);
            
            formData.append('visit_type', type);
            formData.append('food_allowance', foodAllowance);
            formData.append('work_type', workType);
            if (bdmId) formData.append('bdm_id', bdmId);
            if (bdoId) formData.append('bdo_id', bdoId);
            formData.append('type', visitMode);
            
            formData.append('inlat', pos.lat);
            formData.append('inlong', pos.lng);

            const response = await fetch('/api/bdm-visits/check-in', {
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

    function closeModal(id) {
        const modal = document.getElementById(id);
        const modalDiv = modal.querySelector('div');
        if (modalDiv) modalDiv.classList.add('translate-y-full');
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

            const response = await fetch('/api/bdm-visits/check-out', {
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
