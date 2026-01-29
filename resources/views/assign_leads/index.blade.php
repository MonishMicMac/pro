@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel { background: rgba(255, 255, 255, 0.70); backdrop-filter: blur(12px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); }
        .glass-row { @apply border-b border-slate-100 hover:bg-white/80 transition-all duration-200; }
        .table-head { @apply text-[10px] font-black text-slate-400 uppercase tracking-wider py-4 px-4 text-left; }
        .table-cell { @apply py-3 px-4 text-xs font-bold text-slate-700 align-middle border-t border-slate-50; }
        .custom-input { 
            @apply w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm;
        }
        .input-label { @apply text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 block ml-1; }
    }
    .custom-checkbox { @apply w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer; }
    .pagination { @apply flex gap-1 justify-end; }
    .page-item { @apply inline-block; }
    .page-link { @apply px-3 py-1.5 text-xs font-bold border-none rounded-lg bg-white text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-all; }
    .page-item.active .page-link { @apply bg-blue-600 text-white shadow-md shadow-blue-500/30; }
</style>

<div class="relative flex-1 p-6 space-y-6 pb-32 bg-[#f8fafc] min-h-screen font-['Inter']">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Leads</span> 
                <span class="material-symbols-outlined text-[12px]">chevron_right</span> 
                <span class="text-blue-600">Lead Assignment</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Assign Leads</h1>
        </div>
    </div>

    <div class="glass-panel rounded-[1.5rem] p-6">
        <form method="GET" action="{{ route('assign-leads.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <div class="col-span-1 md:col-span-2">
                    <label class="input-label">Status</label>
                    <select name="status" onchange="this.form.submit()" class="custom-input cursor-pointer">
                        <option value="">All Leads</option>
                        <option value="unassigned" {{ request('status') == 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="input-label">Telecaller</label>
                    <select name="telecaller_id" onchange="this.form.submit()" class="custom-input cursor-pointer">
                        <option value="">All</option>
                        @foreach($telecallers as $tc)
                            <option value="{{ $tc->id }}" {{ request('telecaller_id') == $tc->id ? 'selected' : '' }}>{{ $tc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="input-label">Cross Selling</label>
                    <select name="is_cross_selling" onchange="this.form.submit()" class="custom-input cursor-pointer">
                        <option value="">All</option>
                        <option value="1" {{ request('is_cross_selling') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ request('is_cross_selling') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="input-label">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="custom-input uppercase">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="input-label">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="custom-input uppercase">
                </div>

                <div class="col-span-1 md:col-span-1">
                    <button type="submit" class="w-full h-[40px] bg-blue-600 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:shadow-blue-600/40 transition-all flex items-center justify-center">
                        FILTER
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        
        <div class="px-6 py-4 border-b border-white/50 bg-white/30 flex justify-between items-center backdrop-blur-sm">
            <h2 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">list_alt</span> Leads List
            </h2>
            <a href="{{ route('assign-leads.index') }}" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:shadow-md transition-all flex items-center justify-center" title="Refresh">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-separate" style="border-spacing: 0;">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="table-head w-10 text-center pl-6">
                            <input type="checkbox" id="selectAll" class="custom-checkbox">
                        </th>
                        <th class="table-head">Date</th>
                        <th class="table-head">Customer Name</th>
                        <th class="table-head">Phone Number</th>
                        <th class="table-head">Zone</th>
                        <th class="table-head">Source</th>
                        <th class="table-head">Assigned To</th>
                        <th class="table-head text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white/40">
                    @forelse($leads as $lead)
                    <tr class="glass-row group">
                        <td class="table-cell text-center pl-6 border-l border-transparent group-hover:border-blue-500/30">
                            <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" class="custom-checkbox lead-checkbox">
                        </td>
                        <td class="table-cell text-slate-500">
                            {{ $lead->date ? \Carbon\Carbon::parse($lead->date)->format('d M, Y') : '-' }}
                        </td>
                        <td class="table-cell">
                            <div class="flex flex-col">
                                <span class="text-slate-800 font-bold">{{ $lead->name }}</span>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">{{ Str::limit($lead->email, 20) }}</span>
                            </div>
                        </td>
                        <td class="table-cell font-mono text-slate-600">{{ $lead->phone_number }}</td>
                        <td class="table-cell text-slate-500">
                            {{ $lead->zoneDetails->name ?? 'N/A' }}
                        </td>
                        <td class="table-cell">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wide w-fit">
                                    {{ $lead->source ?? 'Unknown' }}
                                </span>
                                @if($lead->is_cross_selling == '1')
                                    @php
                                        $isNewOpportunity = !is_null($lead->crossed_lead_id);
                                        $typeLabel = $isNewOpportunity ? 'New Opportunity' : 'Wrong Brand';
                                        $bgColor = $isNewOpportunity ? 'bg-emerald-500/10' : 'bg-rose-500/10';
                                        $textColor = $isNewOpportunity ? 'text-emerald-600' : 'text-rose-600';
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 {{ $bgColor }} {{ $textColor }} rounded-md text-[8px] font-black uppercase tracking-widest w-fit">
                                        {{ $typeLabel }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="table-cell">
                            @if($lead->telecaller)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold uppercase shadow-sm">
                                        {{ substr($lead->telecaller->name, 0, 1) }}
                                    </div>
                                    <span class="text-blue-700 font-bold">{{ $lead->telecaller->name }}</span>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 border border-amber-100 text-amber-600">
                                    <span class="material-symbols-outlined text-[14px]">warning</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wide">Unassigned</span>
                                </span>
                            @endif
                        </td>
                        <td class="table-cell">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" 
                                    onclick="viewLeadDetails({{ json_encode($lead->only(['id', 'date', 'name', 'phone_number', 'email', 'is_cross_selling', 'crossed_lead_id', 'transfter_remarks', 'source', 'building_status', 'city'])) }}, '{{ $lead->targetBrand->name ?? '' }}', '{{ $lead->telecaller->name ?? '' }}', '{{ $lead->zoneDetails->name ?? '' }}')"
                                    class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center group/btn shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-5xl mb-3 text-slate-200">inbox</span>
                                <p class="text-xs font-bold uppercase tracking-widest text-slate-300">No leads found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-white/40 border-t border-white/50 flex items-center justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-2">
                Showing {{ $leads->firstItem() ?? 0 }} - {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} Records
            </p>
            <div>
                {{ $leads->withQueryString()->links() }}
            </div>
        </div>
    </div>

    {{-- Lead Details Modal --}}
    <div id="LeadDetailsModal" class="fixed inset-0 z-[100] hidden">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeDetailsModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
            <div class="glass-modal w-full max-w-2xl transform overflow-hidden rounded-[2rem] p-0 shadow-2xl transition-all border border-white/20">
                {{-- Header --}}
                <div class="bg-slate-900 px-8 py-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-400">contact_page</span>
                            Lead Details
                        </h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Full context & information</p>
                    </div>
                    <button onclick="closeDetailsModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 text-white hover:bg-rose-500 hover:text-white transition-all transform hover:rotate-90">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-8 bg-white/90 backdrop-blur-xl">
                    {{-- Cross Selling Context (Conditional) --}}
                    <div id="modal_cross_selling_context" class="hidden mb-8 bg-rose-50 border border-rose-100 rounded-[1.5rem] p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-rose-600">swap_horizontal_circle</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-[11px] font-black text-rose-600 uppercase tracking-widest">Cross Selling Context</h4>
                                    <span id="modal_cross_selling_type" class="px-2 py-0.5 bg-rose-100 text-rose-600 rounded text-[9px] font-black uppercase tracking-tighter"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[8px] font-black text-rose-400 uppercase tracking-widest mb-0.5">Target Brand</label>
                                        <p id="modal_target_brand" class="text-xs font-bold text-slate-800 uppercase"></p>
                                    </div>
                                    <div id="modal_link_container">
                                        {{-- Link to original lead will go here --}}
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-rose-100/50">
                                    <label class="block text-[8px] font-black text-rose-400 uppercase tracking-widest mb-0.5">Transfer Remarks</label>
                                    <p id="modal_transfer_remarks" class="text-xs font-medium text-slate-600 italic leading-relaxed"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- General Info Grid --}}
                    <div class="grid grid-cols-2 gap-8">
                        {{-- Left Column --}}
                        <div class="space-y-6">
                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">person</span> Customer Info
                                </label>
                                <p id="modal_name" class="text-sm font-black text-slate-800 uppercase"></p>
                                <p id="modal_email" class="text-[11px] font-bold text-slate-500 mt-0.5"></p>
                            </div>

                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">call</span> contact details
                                </label>
                                <p id="modal_phone" class="text-sm font-black text-slate-800 font-mono"></p>
                            </div>

                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">calendar_month</span> Lead creation
                                </label>
                                <p id="modal_date" class="text-xs font-bold text-slate-700"></p>
                            </div>
                        </div>

                        {{-- Right Column --}}
                        <div class="space-y-6">
                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">explore</span> Region Info
                                </label>
                                <p id="modal_zone" class="text-xs font-bold text-slate-700 uppercase"></p>
                                <p id="modal_city" class="text-[11px] font-bold text-blue-600 mt-0.5 uppercase"></p>
                            </div>

                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">info</span> status & source
                                </label>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span id="modal_source" class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[9px] font-black uppercase tracking-widest"></span>
                                    <span id="modal_building_status" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest"></span>
                                </div>
                            </div>

                            <div class="group/item">
                                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-blue-500">support_agent</span> current handler
                                </label>
                                <div class="flex items-center gap-2 pt-1">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black uppercase shadow-lg shadow-blue-500/20" id="modal_handler_avatar">?</div>
                                    <div>
                                        <p id="modal_telecaller" class="text-xs font-black text-slate-800 uppercase"></p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Telecaller assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex gap-4">
                        <button onclick="closeDetailsModal()" class="flex-1 py-4 bg-slate-900 text-white rounded-[1.2rem] font-black uppercase text-[11px] tracking-widest shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition-all transform active:scale-[0.98]">
                            Close Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="bulkAssignForm" action="{{ route('assign-leads.update') }}" method="POST">
    @csrf
    <div id="hiddenInputsContainer"></div>

    <div id="floating-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 rounded-2xl px-4 py-2.5 flex items-center gap-4 shadow-2xl shadow-slate-900/40 transition-all duration-500 translate-y-32 opacity-0 pointer-events-none border border-slate-700/50 backdrop-blur-md">
        
        <div class="flex items-center gap-3 text-white border-r border-slate-700 pr-4">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-600 text-white text-[11px] font-black shadow-lg shadow-blue-500/30" id="selected-count">0</span>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Selected</span>
        </div>

        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[16px] text-slate-400">map</span>
            <select id="assign_zone_id" class="pl-9 pr-8 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs font-bold text-white outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer w-40 hover:bg-slate-700 transition-all appearance-none">
                <option value="">Select Zone</option>
                @foreach($zones as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[16px] text-slate-400">person_add</span>
            <select name="telecaller_id" id="assign_telecaller_id" required class="pl-9 pr-8 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs font-bold text-white outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer w-48 disabled:opacity-50 hover:bg-slate-700 transition-all appearance-none">
                <option value="">Select Telecaller</option>
            </select>
        </div>

        <button type="submit" class="pl-4 pr-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2 transform active:scale-95">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span>Assign</span>
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.lead-checkbox');
        const floatingBar = document.getElementById('floating-bar');
        const selectedCount = document.getElementById('selected-count');
        const inputsContainer = document.getElementById('hiddenInputsContainer');
        const zoneSelect = document.getElementById('assign_zone_id');
        const telecallerSelect = document.getElementById('assign_telecaller_id');

        // Toast Setup
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
        });
        @if(session('success')) Toast.fire({ icon: 'success', title: "{{ session('success') }}" }); @endif
        @if(session('error')) Toast.fire({ icon: 'error', title: "{{ session('error') }}" }); @endif

        // Floating Bar Logic
        function updateFloatingBar() {
            const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
            const count = checkedBoxes.length;
            selectedCount.innerText = count;
            inputsContainer.innerHTML = '';
            
            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'lead_ids[]'; input.value = cb.value;
                inputsContainer.appendChild(input);
            });

            if (count > 0) {
                floatingBar.classList.remove('translate-y-32', 'opacity-0', 'pointer-events-none');
                floatingBar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
            } else {
                floatingBar.classList.add('translate-y-32', 'opacity-0', 'pointer-events-none');
                floatingBar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
            }
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateFloatingBar();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked) selectAll.checked = false;
                updateFloatingBar();
            });
        });

        // AJAX: Zone -> Telecaller
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            telecallerSelect.innerHTML = '<option value="">Loading...</option>';
            telecallerSelect.disabled = true;

            if(!zoneId) {
                telecallerSelect.innerHTML = '<option value="">Select Telecaller</option>';
                return;
            }

            fetch(`{{ route('get.telecallers.by.zone') }}?zone_id=${zoneId}`)
                .then(response => response.json())
                .then(data => {
                    telecallerSelect.innerHTML = '<option value="">Select Telecaller</option>';
                    data.forEach(user => {
                        telecallerSelect.innerHTML += `<option value="${user.id}">${user.name}</option>`;
                    });
                    telecallerSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    telecallerSelect.innerHTML = '<option value="">Error</option>';
                });
        });

        // --- Lead Details Modal Functions ---
        window.viewLeadDetails = function(lead, brandName, telecallerName, zoneName) {
            // Basic Info
            document.getElementById('modal_name').textContent = lead.name || 'N/A';
            document.getElementById('modal_email').textContent = lead.email || 'NO EMAIL PROVIDED';
            document.getElementById('modal_phone').textContent = lead.phone_number || 'N/A';
            document.getElementById('modal_date').textContent = lead.date ? new Date(lead.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : 'N/A';
            document.getElementById('modal_zone').textContent = zoneName || 'N/A';
            document.getElementById('modal_city').textContent = lead.city || 'N/A';
            document.getElementById('modal_source').textContent = lead.source || 'UNKNOWN SOURCE';
            document.getElementById('modal_building_status').textContent = lead.building_status || 'N/A';
            document.getElementById('modal_telecaller').textContent = telecallerName || 'UNASSIGNED';
            document.getElementById('modal_handler_avatar').textContent = telecallerName ? telecallerName.substring(0, 1).toUpperCase() : '?';

            // Cross Selling Logic
            const crossPanel = document.getElementById('modal_cross_selling_context');
            if (lead.is_cross_selling == '1') {
                crossPanel.classList.remove('hidden');
                document.getElementById('modal_cross_selling_type').textContent = lead.crossed_lead_id ? 'New Opportunity' : 'Wrong Brand';
                document.getElementById('modal_target_brand').textContent = brandName || 'Unknown';
                document.getElementById('modal_transfer_remarks').textContent = lead.transfter_remarks ? `"${lead.transfter_remarks}"` : 'No remarks provided.';
                
                const linkContainer = document.getElementById('modal_link_container');
                if (lead.crossed_lead_id) {
                    linkContainer.innerHTML = `
                        <label class="block text-[8px] font-black text-rose-400 uppercase tracking-widest mb-0.5">Context Link</label>
                        <a href="{{ url('leads') }}/${lead.crossed_lead_id}" target="_blank" class="text-[10px] font-bold text-blue-600 hover:underline flex items-center gap-1 uppercase">
                            <span class="material-symbols-outlined text-[14px]">link</span> View Original Lead
                        </a>
                    `;
                } else {
                    linkContainer.innerHTML = '';
                }
            } else {
                crossPanel.classList.add('hidden');
            }

            // Show Modal
            const modal = document.getElementById('LeadDetailsModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeDetailsModal = function() {
            const modal = document.getElementById('LeadDetailsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        };
    });
</script>
@endsection