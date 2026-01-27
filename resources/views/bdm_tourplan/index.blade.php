@extends('layouts.app')

@section('title', 'BDM Tour Plans')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700" rel="stylesheet"/>

<style>
    .glass-panel { background: rgba(255, 255, 255, 0.70); backdrop-filter: blur(12px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); }
    .glass-card { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2); transition: all 0.2s ease; }
    .glass-card:hover { background: rgba(255, 255, 255, 0.9); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .form-input-custom { width: 100%; padding: 0.5rem 0.75rem; background-color: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; color: #475569; outline: none; transition: all 0.2s; cursor: pointer; }
    .form-input-custom:focus { ring: 2px; ring-color: rgba(59, 130, 246, 0.2); border-color: #3b82f6; }
    #table-pagination .paginate_button { padding: 4px 10px; margin: 0 2px; border-radius: 8px; background: white; color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; min-width: 28px; transition: all 0.2s; }
    #table-pagination .paginate_button:hover:not(.current) { color: #2563eb; background: #eff6ff; }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    table.dataTable tbody tr td { padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.5); border-bottom: 1px solid rgba(255,255,255,0.5); vertical-align: middle; }
    table.dataTable tbody tr td:first-child { border-left: 1px solid rgba(255,255,255,0.5); border-radius: 12px 0 0 12px; }
    table.dataTable tbody tr td:last-child { border-right: 1px solid rgba(255,255,255,0.5); border-radius: 0 12px 12px 0; }
    .dataTables_paginate, .dataTables_info { display: none !important; }
</style>

<div class="relative flex-1 p-6 space-y-4 pb-24 bg-[#f8fafc] min-h-screen">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">BDM Tour Plans</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BDM Visit Schedules</h1>
        </div>
    </div>

    {{-- CARD 1: FILTERS --}}
    <div class="glass-panel rounded-[1.5rem] p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            
            {{-- ROW 1 --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                <select id="filter_zone_id" class="form-input-custom">
                    <option value="">All Zones</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" {{ Auth::user()->zone_id == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">State</label>
                <select id="filter_state_id" class="form-input-custom">
                    <option value="">All States</option>
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                <select id="filter_district_id" class="form-input-custom">
                    <option value="">All Districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->district_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">City</label>
                <select id="filter_city_id" class="form-input-custom">
                    <option value="">All Cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->city_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ZSM (Empty Initially) --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">ZSM</label>
                <select id="filter_zsm_id" class="form-input-custom">
                    <option value="">All ZSMs</option>
                </select>
            </div>

            {{-- ROW 2 --}}
            
            {{-- BDM (Empty Initially) --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDM Name</label>
                <select id="filterBdm" class="form-input-custom">
                    <option value="">All BDMs</option>
                </select>
            </div>

            {{-- BDO (Empty Initially) --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDO Name</label>
                <select id="filterBdo" class="form-input-custom">
                    <option value="">All BDOs</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Location Status</label>
                <select id="filterLocation" class="form-input-custom">
                    <option value="">All Locations</option>
                    <option value="1">Local</option>
                    <option value="2">Out Station</option>
                    <option value="3">Meeting</option>
                    <option value="4">Leave</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Work Mode</label>
                <select id="filterWorkMode" class="form-input-custom">
                    <option value="">All Work Modes</option>
                    <option value="Individual">Individual</option>
                    <option value="Joint Work">Joint Work</option>
                </select>
            </div>
            
            <div class="hidden lg:block"></div> 

            {{-- ROW 3 --}}
            <div class="lg:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Date Range</label>
                <div class="flex gap-2">
                    <input type="date" id="startDate" class="form-input-custom">
                    <input type="date" id="endDate" class="form-input-custom">
                </div>
            </div>

            <div class="lg:col-span-3 flex items-end justify-end gap-2 pb-[2px]">
                <button onclick="resetFilters()" class="px-4 h-[38px] bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                    Reset
                </button>
                <button onclick="table.draw()" class="px-6 h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply Filters
                </button>
            </div>
        </div>
    </div>

    {{-- DATA TABLE --}}
    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-10 pr-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Search..." type="text"/>
            </div>
            <button onclick="table.draw()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-slate-400 hover:text-blue-600 shadow-sm transition-all" title="Refresh Data">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </button>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="tour-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 pb-2">#</th>
                        <th class="px-4 pb-2">Date</th>
                        <th class="px-4 pb-2">BDM Name</th>
                        <th class="px-4 pb-2">Location Status</th>
                        <th class="px-4 pb-2">Work Mode</th>
                        <th class="px-4 pb-2 text-center">Total Visits</th>
                        <th class="px-4 pb-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
            </table>
        </div>
        
        <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
            <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest"></p>
            <div id="table-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>

    {{-- DETAILS MODAL --}}
    <div id="tourModal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="glass-modal relative transform overflow-hidden rounded-2xl text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl p-6">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-800" id="modalTitle">Visit Details</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="modalDate">Loading...</p>
                        </div>
                        <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all"><span class="material-symbols-outlined text-[20px]">close</span></button>
                    </div>
                    <div id="modalLoader" class="text-center py-8"><span class="material-symbols-outlined animate-spin text-blue-500 text-3xl">sync</span></div>
                    <div id="modalContent" class="hidden space-y-6">
                        <div>
                            <h4 class="flex items-center gap-2 text-[11px] font-black text-indigo-900 uppercase tracking-widest mb-3">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-sm"></span> Joint Work
                            </h4>
                            <div class="bg-indigo-50/50 rounded-xl border border-indigo-100 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-indigo-100/50 text-[10px] uppercase text-indigo-800 font-bold">
                                        <tr><th class="p-3">Type</th><th class="p-3">Client Name</th><th class="p-3">BDO Name</th><th class="p-3 text-right">Status</th></tr>
                                    </thead>
                                    <tbody id="jointList" class="text-[11px] font-bold text-slate-600 divide-y divide-indigo-100/50"></tbody>
                                </table>
                                <p id="noJoint" class="hidden p-4 text-center text-[10px] text-indigo-400 italic font-medium">No Joint Work Requests</p>
                            </div>
                        </div>
                        <div>
                            <h4 class="flex items-center gap-2 text-[11px] font-black text-slate-900 uppercase tracking-widest mb-3">
                                <span class="w-2 h-2 rounded-full bg-slate-500 shadow-sm"></span> Individual Work
                            </h4>
                            <div class="bg-slate-50/50 rounded-xl border border-slate-100 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-100/50 text-[10px] uppercase text-slate-600 font-bold">
                                        <tr><th class="p-3">Type</th><th class="p-3">Client Name</th><th class="p-3 text-right">Status</th></tr>
                                    </thead>
                                    <tbody id="individualList" class="text-[11px] font-bold text-slate-600 divide-y divide-slate-200/50"></tbody>
                                </table>
                                <p id="noIndividual" class="hidden p-4 text-center text-[10px] text-slate-400 italic font-medium">No Individual Visits</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
let table;

$(document).ready(function() {
    
    // --- Helper: Reset Dropdown ---
    function resetDropdown(selector, placeholder = "All") {
        $(selector).html(`<option value="">${placeholder}</option>`);
    }

    // --- Cascading Logic ---
    
    // 1. Zone -> State, ZSM, BDMs, & BDOs
    $('#filter_zone_id').change(function() {
        let zoneId = $(this).val();
        resetDropdown('#filter_state_id', 'All States'); 
        resetDropdown('#filter_district_id', 'All Districts'); 
        resetDropdown('#filter_city_id', 'All Cities');
        
        resetDropdown('#filter_zsm_id', 'All ZSMs'); 
        resetDropdown('#filterBdm', 'All BDMs'); 
        resetDropdown('#filterBdo', 'All BDOs'); 
        
        if(zoneId) {
            $.get("{{ route('bdm-tour-plans.get-locations') }}", { type: 'zone', id: zoneId }, function(data) {
                let states = data.states || [];
                states.forEach(st => $('#filter_state_id').append(`<option value="${st.id}">${st.name}</option>`));
                
                let zsms = data.zsms || [];
                zsms.forEach(u => $('#filter_zsm_id').append(`<option value="${u.id}">${u.name}</option>`));

                let bdms = data.bdms || [];
                bdms.forEach(u => $('#filterBdm').append(`<option value="${u.id}">${u.name}</option>`));

                let bdos = data.bdos || [];
                bdos.forEach(u => $('#filterBdo').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // 2. ZSM -> BDMs
    $('#filter_zsm_id').change(function() {
        let zsmId = $(this).val();
        resetDropdown('#filterBdm', 'All BDMs'); 
        resetDropdown('#filterBdo', 'All BDOs'); 
        
        if(zsmId) {
            $.get("{{ route('bdm-tour-plans.get-locations') }}", { type: 'zsm', id: zsmId }, function(data) {
                data.forEach(u => $('#filterBdm').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // 3. BDM -> BDOs (Manager Logic)
    $('#filterBdm').change(function() {
        let bdmId = $(this).val();
        resetDropdown('#filterBdo', 'All BDOs'); 
        
        if(bdmId) {
            $.get("{{ route('bdm-tour-plans.get-locations') }}", { type: 'manager', id: bdmId }, function(data) {
                data.forEach(u => $('#filterBdo').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // 4. State -> District
    $('#filter_state_id').change(function() {
        let stateId = $(this).val();
        resetDropdown('#filter_district_id', 'All Districts'); 
        resetDropdown('#filter_city_id', 'All Cities');
        if(stateId) {
            $.get("{{ route('bdm-tour-plans.get-locations') }}", { type: 'state', id: stateId }, function(data) {
                data.forEach(dt => $('#filter_district_id').append(`<option value="${dt.id}">${dt.name}</option>`));
            });
        }
    });

    // 5. District -> City
    $('#filter_district_id').change(function() {
        let districtId = $(this).val();
        resetDropdown('#filter_city_id', 'All Cities');
        if(districtId) {
            $.get("{{ route('bdm-tour-plans.get-locations') }}", { type: 'district', id: districtId }, function(data) {
                data.forEach(city => $('#filter_city_id').append(`<option value="${city.id}">${city.name}</option>`));
            });
        }
    });


    // --- DataTable ---
    table = $('#tour-table').DataTable({
        processing: true, 
        serverSide: true,
        ajax: {
            url: "{{ route('bdm-tour-plans.data') }}",
            data: function (d) { 
                d.zone_id = $('#filter_zone_id').val();
                d.state_id = $('#filter_state_id').val();
                d.district_id = $('#filter_district_id').val();
                d.city_id = $('#filter_city_id').val();
                d.zsm_id = $('#filter_zsm_id').val();
                d.user_id = $('#filterBdm').val(); // BDM ID
                d.bdo_id = $('#filterBdo').val();  // BDO ID (New)
                
                d.location_status = $('#filterLocation').val();
                d.work_mode = $('#filterWorkMode').val(); 
                d.start_date = $('#startDate').val(); 
                d.end_date = $('#endDate').val(); 
            }
        },
        createdRow: (row) => $(row).addClass('glass-card group hover:bg-white/60 transition-all'),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'schedule_date', name: 'schedule_date' },
            { data: 'bdm_name', name: 'user.name' },
            { data: 'location_status', name: 'food_allowance' },
            { data: 'work_modes', name: 'work_modes' }, 
            { data: 'total_visits', name: 'total_visits', className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: 'rtp',
        language: {
            paginate: { 
                previous: '<span class="material-symbols-outlined font-black text-[28px]">chevron_left</span>', 
                next: '<span class="material-symbols-outlined font-black text-[28px]">chevron_right</span>' 
            }
        },
        drawCallback: function(settings) {
            $('#table-info').text(`Showing ${settings.json.data.length} of ${settings.json.recordsTotal} Records`);
            $('#table-pagination').html($('.dataTables_paginate').html());
            $('.dataTables_paginate').empty();
            
            $('#table-pagination .paginate_button').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;
                if ($(this).hasClass('previous')) table.page('previous').draw('page');
                else if ($(this).hasClass('next')) table.page('next').draw('page');
                else table.page(parseInt($(this).text()) - 1).draw('page');
            });
        }
    });

    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    
    // Auto-refresh when specific filters change
    $('#filterBdm, #filterBdo, #filterLocation, #filterWorkMode, #startDate, #endDate').on('change', function() { table.draw(); });

    window.resetFilters = function() {
        $('#filter_zone_id, #filter_state_id, #filter_district_id, #filter_city_id').val('').trigger('change');
        $('#filter_zsm_id, #filterBdm, #filterBdo, #filterLocation, #filterWorkMode, #startDate, #endDate').val('');
        $('#customSearch').val('');
        table.search('').draw();
    };
});

// Modal Logic
window.openTourModal = function(userId, date) {
    $('#tourModal').removeClass('hidden');
    $('#modalLoader').show();
    $('#modalContent').addClass('hidden');
    $('#modalDate').text(formatDate(date)); 

    $.get("{{ route('bdm-tour-plans.details') }}", { user_id: userId, date: date }, function(data) {
        $('#modalLoader').hide();
        $('#modalContent').removeClass('hidden');
        renderList('joint', data.joint);
        renderList('individual', data.individual);
    });
};

function renderList(type, items) {
    const list = $(`#${type}List`);
    const emptyMsg = $(`#no${type.charAt(0).toUpperCase() + type.slice(1)}`);
    list.empty();

    if (items.length === 0) {
        emptyMsg.removeClass('hidden');
    } else {
        emptyMsg.addClass('hidden');
        items.forEach(item => {
            const statusColor = item.status === 'Visited' ? 'text-emerald-500' : 'text-amber-500';
            const icon = item.status === 'Visited' ? 'check_circle' : 'schedule';
            const partnerColumn = type === 'joint' ? `<td class="p-3 text-indigo-600 font-bold">${item.partner_name}</td>` : '';
            const html = `
                <tr class="hover:bg-white/50 transition-colors">
                    <td class="p-3 w-20">
                        <span class="px-2 py-1 bg-white border border-slate-200 rounded-md text-[9px] font-bold uppercase text-slate-500 tracking-wide">${item.type}</span>
                    </td>
                    <td class="p-3 text-slate-700">${item.name}</td>
                    ${partnerColumn}
                    <td class="p-3 text-right">
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase ${statusColor}">
                            <span class="material-symbols-outlined text-[14px]">${icon}</span> ${item.status}
                        </span>
                    </td>
                </tr>`;
            list.append(html);
        });
    }
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

window.closeModal = function() {
    $('#tourModal').addClass('hidden');
};
</script>
@endsection