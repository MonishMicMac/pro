@extends('layouts.app')

@section('title', 'BDO Joint Work Report')

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
    .form-input-custom { width: 100%; padding: 0.5rem 0.75rem; background-color: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; color: #475569; outline: none; transition: all 0.2s; cursor: pointer; }
    
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
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">BDO Joint Visit</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Joint Work & Visit</h1>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="glass-panel rounded-[1.5rem] p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            
            {{-- ROW 1 --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                <select id="filter_zone_id" class="form-input-custom">
                    <option value="">All Zones</option>
                    @foreach($zones as $z) <option value="{{ $z->id }}">{{ $z->name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">State</label>
                <select id="filter_state_id" class="form-input-custom">
                    <option value="">All States</option>
                    @foreach($states as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                <select id="filter_district_id" class="form-input-custom">
                    <option value="">All Districts</option>
                    @foreach($districts as $d) <option value="{{ $d->id }}">{{ $d->district_name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">City</label>
                <select id="filter_city_id" class="form-input-custom">
                    <option value="">All Cities</option>
                    @foreach($cities as $c) <option value="{{ $c->id }}">{{ $c->city_name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">ZSM</label>
                <select id="filter_zsm_id" class="form-input-custom">
                    <option value="">All ZSMs</option>
                </select>
            </div>

            {{-- ROW 2 --}}
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Manager (BDM)</label>
                <select id="filter_manager_id" class="form-input-custom">
                    <option value="">All Managers</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDO Name</label>
                <select id="filterUser" class="form-input-custom">
                    <option value="">All BDOs</option>
                    @foreach($bdos as $bdo) <option value="{{ $bdo->id }}">{{ $bdo->name }}</option> @endforeach
                </select>
            </div>
            
            <div class="lg:col-span-2">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Date Range</label>
                <div class="flex gap-2">
                    <input type="date" id="startDate" class="form-input-custom" value="{{ date('Y-m-01') }}">
                    <input type="date" id="endDate" class="form-input-custom" value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="flex items-end justify-end gap-2 pb-[2px]">
                <button id="btn_reset" class="px-4 h-[38px] bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                    Reset
                </button>
                <button onclick="table.draw()" class="flex-1 h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply
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
            <button onclick="table.draw()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-slate-400 hover:text-blue-600 shadow-sm transition-all" title="Refresh">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </button>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="joint-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 pb-2">#</th>
                        <th class="px-4 pb-2">Date</th>
                        <th class="px-4 pb-2">BDO Name</th>
                        <th class="px-4 pb-2 text-indigo-600">Manager (BDM)</th>
                        <th class="px-4 pb-2">Client</th>
                        <th class="px-4 pb-2">Name</th>
                        <th class="px-4 pb-2">Request Status</th>
                        <th class="px-4 pb-2">Location</th>
                        <th class="px-4 pb-2 text-right">Visit Status</th>
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

</div>

<script>
let table;

$(document).ready(function() {
    
    // Helper: Reset Dropdown
    function resetDropdown(selector, placeholder = "All") {
        $(selector).html(`<option value="">${placeholder}</option>`);
    }

    // --- Cascading Logic ---
    
    // Zone -> State & ZSM
    $('#filter_zone_id').change(function() {
        let zoneId = $(this).val();
        resetDropdown('#filter_state_id', 'All States'); 
        resetDropdown('#filter_district_id', 'All Districts'); 
        resetDropdown('#filter_city_id', 'All Cities');
        resetDropdown('#filter_zsm_id', 'All ZSMs'); 
        resetDropdown('#filter_manager_id', 'All Managers'); 
        resetDropdown('#filterUser', 'All BDOs'); 
        
        if(zoneId) {
            // NOTE: Ensure this route exists in web.php and points to BdoJointWorkReportController@getLocationData
            $.get("{{ route('bdo-joint-visits.get-locations') }}", { type: 'zone', id: zoneId }, function(data) {
                let states = data.states || [];
                states.forEach(st => $('#filter_state_id').append(`<option value="${st.id}">${st.name}</option>`));
                
                let zsms = data.zsms || [];
                zsms.forEach(u => $('#filter_zsm_id').append(`<option value="${u.id}">${u.name}</option>`));

                // Fallback BDOs
                let bdos = data.bdos || [];
                bdos.forEach(u => $('#filterUser').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // ZSM -> Managers
    $('#filter_zsm_id').change(function() {
        let zsmId = $(this).val();
        resetDropdown('#filter_manager_id', 'All Managers'); 
        resetDropdown('#filterUser', 'All BDOs'); 
        
        if(zsmId) {
            $.get("{{ route('bdo-joint-visits.get-locations') }}", { type: 'zsm', id: zsmId }, function(data) {
                data.forEach(u => $('#filter_manager_id').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // Manager -> BDOs
    $('#filter_manager_id').change(function() {
        let managerId = $(this).val();
        resetDropdown('#filterUser', 'All BDOs'); 
        
        if(managerId) {
            $.get("{{ route('bdo-joint-visits.get-locations') }}", { type: 'manager', id: managerId }, function(data) {
                data.forEach(u => $('#filterUser').append(`<option value="${u.id}">${u.name}</option>`));
            });
        }
    });

    // State -> District
    $('#filter_state_id').change(function() {
        let stateId = $(this).val();
        resetDropdown('#filter_district_id', 'All Districts'); 
        resetDropdown('#filter_city_id', 'All Cities');
        if(stateId) {
            $.get("{{ route('bdo-joint-visits.get-locations') }}", { type: 'state', id: stateId }, function(data) {
                data.forEach(dt => $('#filter_district_id').append(`<option value="${dt.id}">${dt.name}</option>`));
            });
        }
    });

    // District -> City
    $('#filter_district_id').change(function() {
        let districtId = $(this).val();
        resetDropdown('#filter_city_id', 'All Cities');
        if(districtId) {
            $.get("{{ route('bdo-joint-visits.get-locations') }}", { type: 'district', id: districtId }, function(data) {
                data.forEach(city => $('#filter_city_id').append(`<option value="${city.id}">${city.name}</option>`));
            });
        }
    });

    // --- DataTable ---
    table = $('#joint-table').DataTable({
        processing: true, 
        serverSide: true,
        ajax: {
            url: "{{ route('bdo-joint-visits.data') }}",
            data: function (d) { 
                d.zone_id = $('#filter_zone_id').val();
                d.state_id = $('#filter_state_id').val();
                d.district_id = $('#filter_district_id').val();
                d.city_id = $('#filter_city_id').val();
                
                d.zsm_id = $('#filter_zsm_id').val();
                d.manager_id = $('#filter_manager_id').val();
                d.user_id = $('#filterUser').val(); 
                
                d.start_date = $('#startDate').val(); 
                d.end_date = $('#endDate').val(); 
            }
        },
        createdRow: (row) => $(row).addClass('glass-card group hover:bg-white/60 transition-all'),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'schedule_date', name: 'schedule_date' },
            { data: 'bdo_name', name: 'user.name' },
            { data: 'manager_name', name: 'bdm.name', className: 'text-indigo-600 font-bold' },
            { data: 'client_type', name: 'visit_type' },
            { data: 'client_name', name: 'client_name', orderable: false },
            { data: 'request_status', name: 'request_status', orderable: false, searchable: false },
            { data: 'location', name: 'location', orderable: false },
            { data: 'visit_status', name: 'action', className: 'text-right' }
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
    $('#btn_reset').click(function() {
        $('#filter_zone_id, #filter_state_id, #filter_district_id, #filter_city_id').val('').trigger('change');
        $('#filter_zsm_id, #filter_manager_id, #filterUser').val('');
        $('#startDate').val("{{ date('Y-m-01') }}");
        $('#endDate').val("{{ date('Y-m-d') }}");
        table.search('').draw();
    });
});
</script>
@endsection