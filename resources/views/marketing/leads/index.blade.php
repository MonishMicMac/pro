@extends('layouts.app')
@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700" rel="stylesheet"/>

<style>
    /* Glassmorphism Styles */
    .glass-panel { background: rgba(255, 255, 255, 0.70); backdrop-filter: blur(12px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); }
    .glass-card { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2); transition: all 0.2s ease; }
    .glass-card:hover { background: rgba(255, 255, 255, 0.9); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    
    /* Pagination Styles */
    #table-pagination .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; background: white; color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; min-width: 32px; transition: all 0.2s; border: 1px solid #e2e8f0; }
    #table-pagination .paginate_button:hover:not(.current) { color: #2563eb; background: #eff6ff; border-color: #bfdbfe; }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); border-color: #2563eb; }
    
    /* Table Spacing */
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.5rem !important; width: 100% !important; margin-top: 0 !important; }
    table.dataTable tbody tr td { padding: 14px 16px; border-top: 1px solid rgba(255,255,255,0.5); border-bottom: 1px solid rgba(255,255,255,0.5); background-color: rgba(255,255,255,0.3); }
    table.dataTable tbody tr td:first-child { border-left: 1px solid rgba(255,255,255,0.5); border-radius: 12px 0 0 12px; }
    table.dataTable tbody tr td:last-child { border-right: 1px solid rgba(255,255,255,0.5); border-radius: 0 12px 12px 0; }
</style>

{{-- Main Container: REMOVED h-screen, ADDED min-h-screen to allow scrolling --}}
<div class="flex flex-col bg-[#f8fafc] min-h-screen relative pb-20">

    {{-- Header --}}
    <div class="px-6 pt-6 pb-2">
        <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
            <span>Marketing</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">Leads Tracking</span>
        </nav>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Lead Status Management</h1>
    </div>

    <div class="flex flex-col px-6 gap-6">
        
        {{-- CARD 1: FILTERS --}}
        <div class="glass-panel rounded-[1.5rem] p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                
                {{-- Row 1 --}}
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Stage</label>
                    <select id="filter-stage" class="w-full px-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 uppercase">
                        <option value="">All Stages</option>
                        @foreach($leadStages as $key => $value) <option value="{{ $key }}">{{ $value }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1 block">Future Date</label>
                    <input type="date" id="filter-future-date" class="w-full px-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1 block">Potential Date</label>
                    <input type="date" id="filter-potential-date" class="w-full px-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1 block">Cross Selling</label>
                    <select id="filter-cross-selling" class="w-full px-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 uppercase">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="flex items-end lg:col-span-1">
                    <button id="btn-apply-filter" class="w-full px-6 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold shadow-lg shadow-slate-200 hover:bg-blue-600 hover:shadow-blue-200 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> Apply Filters
                    </button>
                </div>

                {{-- Row 2: Location --}}
                <div class="lg:col-span-5 pt-3 border-t border-slate-100 mt-1 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Zone</label>
                        <select id="filter-zone" class="w-full px-3 py-1.5 bg-white/50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone) <option value="{{ $zone->id }}" {{ Auth::user()->zone_id == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 block">State</label>
                        <select id="filter-state" class="w-full px-3 py-1.5 bg-white/50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">All States</option>
                            @foreach($states as $state) <option value="{{ $state->id }}" {{ Auth::user()->state_id == $state->id ? 'selected' : '' }}>{{ $state->name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 block">District</label>
                        <select id="filter-district" class="w-full px-3 py-1.5 bg-white/50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">All Districts</option>
                            @foreach($districts as $district) <option value="{{ $district->id }}" {{ Auth::user()->district_id == $district->id ? 'selected' : '' }}>{{ $district->district_name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 block">City</label>
                        <select id="filter-city" class="w-full px-3 py-1.5 bg-white/50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">All Cities</option>
                            @foreach($cities as $city) <option value="{{ $city->id }}" {{ Auth::user()->city_id == $city->id ? 'selected' : '' }}>{{ $city->city_name }}</option> @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: TABLE --}}
        {{-- REMOVED flex-1 and overflow-hidden to allow natural height growth --}}
        <div class="glass-panel rounded-[1.5rem] w-full mb-10">
            
            {{-- Table Header --}}
            <div class="p-5 bg-white/30 border-b border-white/50 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="relative w-full md:w-96">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input id="customSearch" class="w-full pl-10 pr-4 py-2.5 bg-white/80 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all placeholder:text-slate-400" placeholder="Search by name, phone number..." type="text"/>
                </div>

                <button id="reset-filters" class="flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-rose-50 border border-slate-200 text-slate-500 hover:text-rose-500 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span> Reset & Refresh
                </button>
            </div>

            {{-- Table Body: REMOVED fixed height / overflow-y --}}
            <div class="px-5 py-2 overflow-x-auto">
                <table class="w-full" id="leads-table">
                    <thead>
                        <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                            <th class="pl-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox"/></th>
                            <th class="px-4 pb-2">Date</th>
                            <th class="px-4 pb-2">Name</th>
                            <th class="px-4 pb-2">Phone</th>
                            <th class="px-4 pb-2">Stage</th>
                            <th class="px-4 pb-2 text-blue-500">Future Follow Up</th>
                            <th class="px-4 pb-2 text-amber-600">Potential Follow Up</th>
                            <th class="px-4 pb-2">Assigned</th>
                            <th class="px-4 pb-2">Zone</th>
                            <th class="px-4 pb-2">Cross Selling</th>
                            <th class="px-4 pb-2 text-center">Status</th>
                            <th class="px-4 pb-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="p-5 bg-white/40 border-t border-white/60 flex items-center justify-between">
                <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest"></p>
                <div id="table-pagination" class="flex items-center gap-1"></div>
            </div>
        </div>
    </div>


    {{-- Floating Bar --}}
    <div id="floating-bar" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[50] bg-slate-900 rounded-2xl px-6 py-3 flex items-center gap-6 shadow-2xl transition-all duration-500 translate-y-32 opacity-0 invisible pointer-events-none">
        <div class="flex items-center gap-3 text-white border-r border-slate-700 pr-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-500 text-[11px] font-black" id="selected-count">0</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
        </div>
        <div class="flex items-center gap-4">
            @can('leads.edit')
            <button id="floating-edit" class="hidden flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                <span class="material-symbols-outlined text-[20px]">edit_square</span> Process
            </button>
            @endcan
            @can('leads.delete')
            <button onclick="handleBulkDelete()" class="flex items-center gap-2 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                <span class="material-symbols-outlined text-[20px]">delete_sweep</span> Delete
            </button>
            @endcan
        </div>
    </div>

    {{-- Lead Modal --}}
    <div id="LeadModal" class="fixed inset-0 z-[100] hidden">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('LeadModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
            <div class="glass-modal w-full max-w-2xl transform overflow-hidden rounded-[1.5rem] p-8 shadow-2xl transition-all">
                <form id="LeadForm">
                    @csrf
                    <input type="hidden" name="_method" id="method">
                    <input type="hidden" id="Lead_id" name="id">

                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 pb-5">
                        <h3 class="text-xl font-black text-slate-800">Update Lead</h3>
                        <button type="button" onclick="closeModal('LeadModal')" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                            <span class="material-symbols-outlined text-[24px]">close</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
                        <div id="cross_selling_info_panel" class="hidden col-span-2 bg-rose-50 border border-rose-100 rounded-2xl p-4 mb-2">
                             <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-rose-600">swap_horizontal_circle</span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-[11px] font-black text-rose-600 uppercase tracking-widest mb-1">Cross Selling Context</h4>
                                    <p id="modal_cross_selling_brand" class="text-[10px] font-bold text-slate-700 uppercase"></p>
                                    <p id="modal_cross_selling_remarks" class="text-[10px] text-slate-500 italic mt-1"></p>
                                    <div id="modal_original_lead_link" class="mt-2"></div>
                                </div>
                             </div>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stage</label>
                            <select name="stage" id="stage" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                                <option value="">Select Stage</option>
                                @foreach($leadStages as $key => $value) <option value="{{ $key }}">{{ $value }}</option> @endforeach
                            </select>
                        </div>

                        <div id="future_follow_up_section" class="hidden col-span-2 grid grid-cols-2 gap-4 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                            <div>
                                <label class="block text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Future Date</label>
                                <input type="date" name="future_follow_up_date" id="future_follow_up_date" class="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-xs font-bold outline-none">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Time</label>
                                <input type="time" name="future_follow_up_time" id="future_follow_up_time" class="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-xs font-bold outline-none">
                            </div>
                        </div>

                        <div id="potential_follow_up_section" class="hidden col-span-2 grid grid-cols-2 gap-4 bg-amber-50/50 p-4 rounded-xl border border-amber-100">
                            <div>
                                <label class="block text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Potential Date</label>
                                <input type="date" name="potential_follow_up_date" id="potential_follow_up_date" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs font-bold outline-none">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Time</label>
                                <input type="time" name="potential_follow_up_time" id="potential_follow_up_time" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs font-bold outline-none">
                            </div>
                        </div>

                        <div id="disqualified_reason_section" class="hidden col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reason</label>
                            <select name="disqualified_reason" id="disqualified_reason" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                                <option value="">Select Reason</option>
                                <option value="Other location">Other location</option>
                                <option value="Wrong Number">Wrong Number</option>
                                <option value="Did not inquire">Did not inquire</option>
                                <option value="Out of scope">Out of scope</option>
                            </select>
                        </div>
                        <div id="rnr_reason_section" class="hidden col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reason</label>
                            <select name="rnr_reason" id="rnr_reason" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                                <option value="">Select Reason</option>
                                <option value="RNR (Ring No Response)">RNR (Ring No Response)</option>
                                <option value="Switched Off">Switched Off</option>
                            </select>
                        </div>

                        <div id="assigned_to_section" class="hidden col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                            <div>
                                <label class="block text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">person_add</span> Assigned To
                                </label>
                                <select name="assigned_to" id="lead_assigned_to" class="w-full px-4 py-2 bg-white border border-emerald-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                    <option value="">Select User</option>
                                    @foreach($users as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">explore</span> Zone
                                </label>
                                <select name="zone" id="lead_zone" class="w-full px-4 py-2 bg-white border border-emerald-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone) <option value="{{ $zone->id }}">{{ $zone->name }}</option> @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">apartment</span> Building Status
                            </label>
                            <select name="building_status" id="lead_building_status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                <option value="">Select Status</option>
                                @foreach($buildingStatuses as $status) <option value="{{ $status->id }}">{{ $status->name }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">construction</span> Building Type
                            </label>
                            <select name="building_type" id="lead_building_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                <option value="">Select Type</option>
                                @foreach($buildingTypes as $key => $value) <option value="{{ $key }}">{{ $value }}</option> @endforeach
                            </select>
                        </div>
                         <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">square_foot</span> SQFT
                            </label>
                             <input type="number" step="0.01" name="total_order_sqft" id="lead_total_order_sqft" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">palette</span> Colour
                            </label>
                             <input type="text" name="colour" id="lead_colour" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>

                         <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">chat</span> Remarks
                            </label>
                            <textarea name="remarks" id="lead_remarks" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-4 pt-4 border-t border-slate-100">
                        <button type="button" onclick="closeModal('LeadModal')" class="flex-1 py-3 font-black text-slate-400 uppercase text-[10px] tracking-widest hover:text-slate-600">Cancel</button>
                        <button type="submit" class="flex-1 py-3 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:bg-blue-600 transition-all">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

        // --- Cascading Dropdowns ---
        function resetDropdown(selector, placeholder = "Select option") {
            $(selector).html(`<option value="">${placeholder}</option>`);
        }

        $('#filter-zone').change(function() {
            let zoneId = $(this).val();
            resetDropdown('#filter-state', 'All States');
            resetDropdown('#filter-district', 'All Districts');
            resetDropdown('#filter-city', 'All Cities');
            if(zoneId) {
                $.get("{{ route('marketing.leads.get-locations') }}", { type: 'zone', id: zoneId }, function(data) {
                    let states = data.states || [];
                    states.forEach(st => $('#filter-state').append(`<option value="${st.id}">${st.name}</option>`));
                });
            }
        });

        $('#filter-state').change(function() {
            let stateId = $(this).val();
            resetDropdown('#filter-district', 'All Districts');
            resetDropdown('#filter-city', 'All Cities');
            if(stateId) {
                $.get("{{ route('marketing.leads.get-locations') }}", { type: 'state', id: stateId }, function(data) {
                    data.forEach(dt => $('#filter-district').append(`<option value="${dt.id}">${dt.name}</option>`));
                });
            }
        });

        $('#filter-district').change(function() {
            let districtId = $(this).val();
            resetDropdown('#filter-city', 'All Cities');
            if(districtId) {
                $.get("{{ route('marketing.leads.get-locations') }}", { type: 'district', id: districtId }, function(data) {
                    data.forEach(city => $('#filter-city').append(`<option value="${city.id}">${city.name}</option>`));
                });
            }
        });
        
        // --- DataTable ---
        const table = $('#leads-table').DataTable({
            processing: true, serverSide: true,
            pageLength: 10, // Explicitly set 10 max
            lengthChange: false, // Remove "Show X entries"
            ajax: {
                url: "{{ route('marketing.leads.index') }}",
                data: function (d) {
                    d.stage = $('#filter-stage').val();
                    d.future_date = $('#filter-future-date').val();
                    d.potential_date = $('#filter-potential-date').val();
                    d.zone_id = $('#filter-zone').val();
                    d.state_id = $('#filter-state').val();
                    d.district_id = $('#filter-district').val();
                    d.city_id = $('#filter-city').val();
                    d.is_cross_selling = $('#filter-cross-selling').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card group hover:bg-white/60');
                $(row).find('td:first').addClass('pl-4 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 rounded-r-xl');
            },
            columns: [
                {
                    data: 'id', name: 'id', orderable: false, searchable: false,
                    render: function(data, type, row) {
                        return `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${data || row.id}"/>`;
                    }
                },
                { data: 'date', name: 'date', defaultContent: '-' },
                {
                    data: 'name', name: 'name',
                    render: function(data, type, row) {
                        let url = `{{ url('marketing/leads') }}/${row.id}/history`;
                        return `<a href="${url}" target="_blank" class="text-blue-600 hover:underline decoration-2 underline-offset-4 cursor-pointer font-bold">${data}</a>`;
                    }
                },
                { data: 'phone_number', name: 'phone_number', defaultContent: '-' },
                { data: 'stage', name: 'stage', defaultContent: '-' },
                { data: 'future_follow_up_date', name: 'future_follow_up_date', defaultContent: '-' },
                { data: 'potential_follow_up_date', name: 'potential_follow_up_date', defaultContent: '-' },
                { data: 'assigned_user.name', name: 'assigned_user.name', searchable: false, defaultContent: '-' },
                { data: 'zone_details.name', name: 'zone_details.name', searchable: false, defaultContent: '-' },
                {
                    data: 'is_cross_selling',
                    name: 'is_cross_selling',
                    render: function(data, type, row) {
                        if (data == '1') {
                            let typeLabel = row.crossed_lead_id ? 'New Opportunity' : 'Wrong Brand';
                            let bgColor = row.crossed_lead_id ? 'bg-emerald-500/10' : 'bg-rose-500/10';
                            let textColor = row.crossed_lead_id ? 'text-emerald-600' : 'text-rose-600';
                            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 ${bgColor} ${textColor} rounded-md text-[8px] font-black uppercase tracking-widest w-fit">${typeLabel}</span>`;
                        }
                        return '<span class="text-slate-300 text-[10px] font-bold uppercase tracking-wider">No</span>';
                    }
                },
                {
                    data: 'otp_status',
                    render: (d) => d === 'Verified'
                        ? `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest">Verified</span>`
                        : `<span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase tracking-widest">Pending</span>`
                },
                {
                    data: 'lead_id', name: 'lead_id', orderable: false, searchable: false, className: 'text-center',
                    render: function(data) {
                        if (data) {
                             let url = `{{ url('leads') }}/${data}`;
                             return `<a href="${url}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[9px] font-black uppercase tracking-widest hover:bg-blue-100 transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">visibility</span> View
                                     </a>`;
                        }
                        return '<span class="text-slate-300 text-[10px] font-bold uppercase tracking-wider">-</span>';
                    }
                }
            ],
            dom: 'rtp', // No length change, just search, table, pagination
            language: {
                paginate: { 
                    previous: '<span class="material-symbols-outlined font-black text-[20px]">chevron_left</span>', 
                    next: '<span class="material-symbols-outlined font-black text-[20px]">chevron_right</span>' 
                }
            },
            drawCallback: function(settings) {
                $('#table-info').text(`Total Records: ${settings.json ? settings.json.recordsTotal : 0}`);
                $('#table-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
                $('#selectAll').prop('checked', false);
                updateUIState();
                
                $('#table-pagination .paginate_button').on('click', function(e) {
                    e.preventDefault();
                    if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;
                    if ($(this).hasClass('previous')) table.page('previous').draw('page');
                    else if ($(this).hasClass('next')) table.page('next').draw('page');
                    else table.page(parseInt($(this).text()) - 1).draw('page');
                });
            }
        });

        // Apply Filter
        $('#btn-apply-filter').on('click', function() { table.draw(); });

        // Reset
        $('#reset-filters').on('click', function() {
            $('#filter-stage, #filter-future-date, #filter-potential-date, #filter-cross-selling').val('');
            $('#filter-zone').val('').trigger('change');
            $('#customSearch').val('');
            table.search('').draw();
        });

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        // --- Selection Logic ---
        function updateUIState() {
            const count = $('.row-checkbox:checked').length;
            $('#selected-count').text(count);
            if (count > 0) {
                $('#floating-bar').removeClass('translate-y-32 opacity-0 invisible pointer-events-none').addClass('translate-y-0 opacity-100 visible pointer-events-auto');
                count === 1 ? $('#floating-edit').removeClass('hidden') : $('#floating-edit').addClass('hidden');
            } else {
                $('#floating-bar').addClass('translate-y-32 opacity-0 invisible pointer-events-none').removeClass('translate-y-0 opacity-100 visible pointer-events-auto');
            }
        }

        $(document).on('click', '#selectAll', function() {
            $('.row-checkbox').prop('checked', $(this).prop('checked'));
            updateUIState();
        });

        $(document).on('click', '.row-checkbox', function(e) {
            e.stopPropagation();
            const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
            $('#selectAll').prop('checked', allChecked);
            updateUIState();
        });

        // --- Modal Logic ---
        window.closeModal = (id) => {
            $('#' + id).addClass('hidden');
        };

        $('#floating-edit').click(function() {
            const id = $('.row-checkbox:checked').first().val();
            $.get(`{{ url('marketing/leads') }}/${id}/edit`, function(data) {
                $('#Lead_id').val(data.id);
                $('#method').val('PUT');

                // If lead is in Prospect stage, disable stage changing as requested
                if (data.stage == 4) {
                    $('#stage').val(4).prop('disabled', true);
                    $('#LeadForm button[type="submit"]').addClass('hidden');
                    alert('Prospect stage is final and cannot be edited.');
                } else {
                    $('#stage').val(data.stage).prop('disabled', false).change();
                    $('#LeadForm button[type="submit"]').removeClass('hidden');
                }

                // Cross Selling Info
                if (data.is_cross_selling == '1') {
                    $('#cross_selling_info_panel').removeClass('hidden');
                    let typeLabel = data.crossed_lead_id ? 'New Opportunity (Possible Lead)' : 'Wrong Brand (Reassignment)';
                    $('#modal_cross_selling_brand').html(`<span class="text-rose-600">[${typeLabel}]</span><br>Brand: ` + (data.target_brand ? data.target_brand.name : 'Unknown'));
                    $('#modal_cross_selling_remarks').text(data.transfter_remarks ? `"${data.transfter_remarks}"` : 'No remarks.');
                    
                    if (data.crossed_lead_id) {
                        let originalUrl = `{{ url('leads') }}/${data.crossed_lead_id}`;
                        $('#modal_original_lead_link').html(`<a href="${originalUrl}" target="_blank" class="text-[9px] text-blue-500 hover:underline font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">history</span> View Original Lead context
                        </a>`);
                    } else {
                        $('#modal_original_lead_link').empty();
                    }
                } else {
                    $('#cross_selling_info_panel').addClass('hidden');
                }

                $('#lead_customer_type').val(data.customer_type);
                $('#lead_remarks').val(data.remarks);
                $('#lead_colour').val(data.colour);
                $('#lead_total_order_sqft').val(data.total_order_sqft);
                $('#lead_building_status').val(data.building_status);
                $('#lead_building_type').val(data.building_type);
                $('#lead_assigned_to').val(data.assigned_to);
                $('#lead_zone').val(data.zone);
                $('#future_follow_up_date').val(data.future_follow_up_date);
                $('#future_follow_up_time').val(data.future_follow_up_time);
                $('#potential_follow_up_date').val(data.potential_follow_up_date);
                $('#potential_follow_up_time').val(data.potential_follow_up_time);
                $('#disqualified_reason').val(data.disqualified_reason);
                $('#rnr_reason').val(data.rnr_reason);
                $('#LeadModal').removeClass('hidden');
            });
        });

        function toggleFollowUpFields(stageValue) {
            $('#future_follow_up_section, #potential_follow_up_section, #disqualified_reason_section, #rnr_reason_section, #assigned_to_section').addClass('hidden');
            if (stageValue == '5') $('#future_follow_up_section').removeClass('hidden');
            else if (stageValue == '6') $('#potential_follow_up_section').removeClass('hidden');
            else if (stageValue == '2') $('#disqualified_reason_section').removeClass('hidden');
            else if (stageValue == '7') $('#rnr_reason_section').removeClass('hidden');
            else if (stageValue == '4') $('#assigned_to_section').removeClass('hidden');
        }

        $('#stage').on('change', function() { toggleFollowUpFields($(this).val()); });

        $('#LeadForm').submit(function(e) {
            e.preventDefault();
            const id = $('#Lead_id').val();
            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).html('<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> Saving...');

            $.ajax({
                url: `{{ url('marketing/leads') }}/${id}`, 
                type: 'POST', 
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        closeModal('LeadModal');
                        alert(response.message || 'Lead updated successfully');
                        table.draw(false);
                    } else {
                        alert('Error: ' + (response.message || 'Something went wrong'));
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred while saving.';
                    if (xhr.status === 422 && xhr.responseJSON) {
                        errorMsg = xhr.responseJSON.message || 'Validation failed.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });

        window.handleBulkDelete = () => {
            const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(!confirm(`Delete ${ids.length} records?`)) return;
            $.post("{{ route('marketing.leads.bulkDelete') }}", { ids: ids }, () => table.draw(false));
        };
    });
</script>
@endsection