@extends('layouts.app')
@section('title', 'BDO Targets')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel { @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm; }
        .glass-card { @apply bg-white/50 backdrop-blur-sm border border-white/20 transition-all duration-200; }
        .glass-card:hover { @apply bg-white/90; }
        .form-input-custom { @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer; }
    }
    #table-pagination .paginate_button { @apply px-2 py-1 mx-0.5 rounded-md border-none bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px]; }
    #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md shadow-blue-500/30; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Modules</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">BDO Targets</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BDO Monthly Targets</h1>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] font-bold">add</span> Set Target
            </button>
        </div>
    </div>

    <div class="glass-panel rounded-[1.5rem] p-4">
        {{-- Grid: 5 Cols on large --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
            
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                <select id="filter_zone_id" class="form-input-custom">
                    <option value="">All Zones</option>
                    @foreach($zones as $z) <option value="{{ $z->id }}">{{ $z->name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">ZSM</label>
                <select id="filter_zsm_id" class="form-input-custom">
                    <option value="">All ZSMs</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager (BDM)</label>
                <select id="filter_manager_id" class="form-input-custom">
                    <option value="">All Managers</option>
                </select>
            </div>

            <div>
                 <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO Name</label>
                <select id="filter_user_id" class="form-input-custom">
                    <option value="">All BDOs</option>
                    @foreach($bdos as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Month</label>
                <input type="month" id="filter_month" class="form-input-custom">
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">State</label>
                <select id="filter_state_id" class="form-input-custom">
                    <option value="">All States</option>
                    @foreach($states as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">District</label>
                <select id="filter_district_id" class="form-input-custom">
                    <option value="">All Districts</option>
                    @foreach($districts as $d) <option value="{{ $d->id }}">{{ $d->district_name }}</option> @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">City</label>
                <select id="filter_city_id" class="form-input-custom">
                    <option value="">All Cities</option>
                    @foreach($cities as $c) <option value="{{ $c->id }}">{{ $c->city_name }}</option> @endforeach
                </select>
            </div>

            <div class="md:col-span-2 lg:col-span-2 flex justify-end gap-2 items-end">
                <button id="btn_reset" class="px-4 py-2 bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95">Reset</button>
                <button onclick="refreshTable()" class="px-6 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                </button>
            </div>
        </div>
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none" placeholder="Search..." type="text"/>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="targetsTable">
                <thead>
                    <tr>
                         <th colspan="3"></th>
                         <th colspan="3" class="pb-2 text-center text-[9px] font-black text-blue-600 bg-blue-50/50 rounded-t-lg mx-1">NEW CALLS</th>
                         <th colspan="3" class="pb-2 text-center text-[9px] font-black text-fuchsia-600 bg-fuchsia-50/50 rounded-t-lg mx-1">QUOTATIONS</th>
                         <th colspan="3" class="pb-2 text-center text-[9px] font-black text-amber-600 bg-amber-50/50 rounded-t-lg mx-1">FOLLOW-UPS</th>
                         <th colspan="3" class="pb-2 text-center text-[9px] font-black text-emerald-600 bg-emerald-50/50 rounded-t-lg mx-1">CONVERSION</th>
                         <th colspan="2" class="pb-2 text-center text-[9px] font-black text-slate-600 bg-slate-50/50 rounded-t-lg mx-1">SALES (₹)</th>
                         <th></th>
                    </tr>
                    <tr class="text-left">
                        <th class="px-3 pb-2 text-[9px] font-black text-slate-400 uppercase tracking-wider">#</th>
                        <th class="px-3 pb-2 text-[9px] font-black text-slate-400 uppercase tracking-wider">BDO</th>
                        <th class="px-3 pb-2 text-[9px] font-black text-slate-400 uppercase tracking-wider">Month</th>
                        
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-blue-50/30">Tgt</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-blue-50/30">Act</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-blue-50/30">%</th>

                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-fuchsia-50/30">Tgt</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-fuchsia-50/30">Act</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-fuchsia-50/30">%</th>
                        
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-amber-50/30">Tgt</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-amber-50/30">Act</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-amber-50/30">%</th>
                        
                         <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-emerald-50/30">Tgt</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-emerald-50/30">Act</th>
                        <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-emerald-50/30">%</th>
                        
                         <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-slate-50/30">Tgt</th>
                         <th class="px-2 pb-2 text-[9px] font-black text-slate-500 text-center bg-slate-50/30">%</th>

                        <th class="px-3 pb-2 text-[9px] font-black text-slate-400 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
            </table>
        </div>
        
        <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
            <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase tracking-widest"></p>
            <div id="table-pagination" class="flex items-center gap-0.5"></div>
        </div>
    </div>
</div>

<div id="targetModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/0 backdrop-blur-0 transition-all duration-300 opacity-0 pointer-events-none">
    <div class="modal-content glass-panel w-full max-w-[600px] rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0 overflow-y-auto">
         <div class="flex items-start justify-between mb-5">
            <div><h2 id="modalTitle" class="text-lg font-bold text-slate-800">Set Monthly Target</h2></div>
            <button onclick="closeModal('targetModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all"><span class="material-symbols-outlined text-[20px]">close</span></button>
        </div>
        <form id="targetForm" class="space-y-4">
             @csrf
             <input type="hidden" id="target_id" name="id">
             <div class="grid grid-cols-2 gap-4">
                 <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO</label>
                    <select name="user_id" id="target_user_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all" required>
                        <option value="">Select BDO</option>
                        @foreach($bdos as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                    </select>
                 </div>
                  <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Month</label>
                    <input type="month" name="target_month" id="target_month" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all" required>
                 </div>
             </div>
             <div class="grid grid-cols-3 gap-4">
                  <div><label class="block text-[9px] font-black text-blue-600 uppercase mb-1.5">New Calls</label><input type="number" name="target_new_calls" id="target_new_calls" class="form-input-custom" required></div>
                  <div><label class="block text-[9px] font-black text-fuchsia-600 uppercase mb-1.5">Quotations</label><input type="number" name="target_quotations" id="target_quotations" class="form-input-custom" required></div>
                  <div><label class="block text-[9px] font-black text-amber-600 uppercase mb-1.5">Follow-ups</label><input type="number" name="target_followups" id="target_followups" class="form-input-custom" required></div>
             </div>
             <div class="grid grid-cols-2 gap-4">
                  <div><label class="block text-[9px] font-black text-emerald-600 uppercase mb-1.5">Conversion (Sqft)</label><input type="number" step="0.01" name="target_conversion_sqft" id="target_conversion_sqft" class="form-input-custom" required></div>
                  <div><label class="block text-[9px] font-black text-slate-600 uppercase mb-1.5">Sales Value (₹)</label><input type="number" step="0.01" name="target_sales_value" id="target_sales_value" class="form-input-custom" required></div>
             </div>
             <div class="mt-8 flex gap-3">
                <button type="button" onclick="closeModal('targetModal')" class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg">Save Target</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    let table;
    $(document).ready(function() {
        
        function resetDropdown(selector, placeholder = "All") {
            $(selector).html(`<option value="">${placeholder}</option>`);
        }

        // --- CASCADING LOGIC ---
        
        // Zone -> State & ZSM
        $('#filter_zone_id').change(function() {
            let zoneId = $(this).val();
            resetDropdown('#filter_state_id', 'States'); 
            resetDropdown('#filter_district_id', 'Districts'); 
            resetDropdown('#filter_city_id', 'Cities');
            resetDropdown('#filter_zsm_id', 'ZSMs');
            resetDropdown('#filter_manager_id', 'Managers');
            resetDropdown('#filter_user_id', 'BDOs'); 
            
            if(zoneId) {
                $.get("{{ route('bdo-targets.get-locations') }}", { type: 'zone', id: zoneId }, function(data) {
                    // Populate States
                    let states = data.states || [];
                    states.forEach(st => $('#filter_state_id').append(`<option value="${st.id}">${st.name}</option>`));
                    // Populate ZSMs
                    let zsms = data.zsms || [];
                    zsms.forEach(u => $('#filter_zsm_id').append(`<option value="${u.id}">${u.name}</option>`));
                    // Populate fallback BDOs
                    let bdos = data.bdos || [];
                    bdos.forEach(u => $('#filter_user_id').append(`<option value="${u.id}">${u.name}</option>`));
                });
            }
        });
        
        // ZSM -> Managers (BDM)
        $('#filter_zsm_id').change(function() {
            let zsmId = $(this).val();
            resetDropdown('#filter_manager_id', 'Managers');
            resetDropdown('#filter_user_id', 'BDOs');
            
            if(zsmId) {
                 $.get("{{ route('bdo-targets.get-locations') }}", { type: 'zsm', id: zsmId }, function(data) {
                     data.forEach(u => $('#filter_manager_id').append(`<option value="${u.id}">${u.name}</option>`));
                 });
            }
        });
        
        // Manager -> BDOs
        $('#filter_manager_id').change(function() {
            let managerId = $(this).val();
            resetDropdown('#filter_user_id', 'BDOs');
            
            if(managerId) {
                 $.get("{{ route('bdo-targets.get-locations') }}", { type: 'manager', id: managerId }, function(data) {
                     data.forEach(u => $('#filter_user_id').append(`<option value="${u.id}">${u.name}</option>`));
                 });
            }
        });

        // State -> District
        $('#filter_state_id').change(function() {
            let stateId = $(this).val();
            resetDropdown('#filter_district_id', 'Districts'); 
            resetDropdown('#filter_city_id', 'Cities');
            if(stateId) {
                $.get("{{ route('bdo-targets.get-locations') }}", { type: 'state', id: stateId }, function(data) {
                    data.forEach(dt => $('#filter_district_id').append(`<option value="${dt.id}">${dt.name}</option>`));
                });
            }
        });

        // District -> City
        $('#filter_district_id').change(function() {
            let districtId = $(this).val();
            resetDropdown('#filter_city_id', 'Cities');
            if(districtId) {
                $.get("{{ route('bdo-targets.get-locations') }}", { type: 'district', id: districtId }, function(data) {
                    data.forEach(city => $('#filter_city_id').append(`<option value="${city.id}">${city.name}</option>`));
                });
            }
        });

        // --- DataTable ---
        table = $('#targetsTable').DataTable({
            processing: true, serverSide: true,
            ajax: {
                url: "{{ route('bdo-targets.data') }}",
                data: function(d) {
                    d.zone_id = $('#filter_zone_id').val();
                    d.state_id = $('#filter_state_id').val();
                    d.district_id = $('#filter_district_id').val();
                    d.city_id = $('#filter_city_id').val();
                    d.user_id = $('#filter_user_id').val();
                    d.month = $('#filter_month').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.manager_id = $('#filter_manager_id').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
            },
            columns: [
                 { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                 { data: 'user_name', name: 'user_name', className: 'whitespace-nowrap' },
                 { data: 'target_month', name: 'target_month' },
                 
                 { data: 'target_new_calls', name: 'target_new_calls', className: 'text-center bg-blue-50/30' },
                 { data: 'new_calls_actual', name: 'new_calls_actual', className: 'text-center font-bold text-slate-600 bg-blue-50/30' },
                 { data: 'new_calls_percent', name: 'new_calls_percent', className: 'text-center bg-blue-50/30' },

                 { data: 'target_quotations', name: 'target_quotations', className: 'text-center bg-fuchsia-50/30' },
                 { data: 'quotes_actual', name: 'quotes_actual', className: 'text-center font-bold text-slate-600 bg-fuchsia-50/30' },
                 { data: 'quotes_percent', name: 'quotes_percent', className: 'text-center bg-fuchsia-50/30' },

                 { data: 'target_followups', name: 'target_followups', className: 'text-center bg-amber-50/30' },
                 { data: 'followups_actual', name: 'followups_actual', className: 'text-center font-bold text-slate-600 bg-amber-50/30' },
                 { data: 'followups_percent', name: 'followups_percent', className: 'text-center bg-amber-50/30' },

                 { data: 'target_conversion_sqft', name: 'target_conversion_sqft', className: 'text-center bg-emerald-50/30' },
                 { data: 'conversion_actual', name: 'conversion_actual', className: 'text-center font-bold text-slate-600 bg-emerald-50/30' },
                 { data: 'conversion_percent', name: 'conversion_percent', className: 'text-center bg-emerald-50/30' },
                 
                 { data: 'target_sales_value', name: 'target_sales_value', className: 'text-center bg-slate-50/30' },
                 { data: 'sales_value_percent', name: 'sales_value_percent', className: 'text-center bg-slate-50/30' },

                 { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            dom: 'rtp',
            drawCallback: function(settings) {
                 const total = settings.json ? settings.json.recordsTotal : 0;
                 $('#table-info').text(`Total Records: ${total}`);
                 $('.dataTables_paginate').appendTo('#table-pagination');
            }
        });
        
        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        $('#btn_reset').click(function() {
            $('#filter_zone_id, #filter_state_id, #filter_district_id, #filter_city_id').val('').trigger('change');
            $('#filter_user_id, #filter_month, #filter_zsm_id, #filter_manager_id').val('');
            $('#customSearch').val('');
            table.search('').draw();
        });
    });

    function refreshTable() { table.draw(); }
    
    function showModalUI() {
        $('#targetModal').removeClass('hidden'); 
        setTimeout(() => {
            $('#targetModal').removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
            $('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }

    window.openAddModal = () => {
        $('#targetForm')[0].reset();
        $('#target_id').val('');
        $('#modalTitle').text('Set Monthly Target');
        showModalUI();
    };

    window.closeModal = (id) => {
        $('.modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        $('#' + id).removeClass('opacity-100 bg-slate-900/30 backdrop-blur-sm').addClass('pointer-events-none opacity-0');
        setTimeout(() => { $('#' + id).addClass('hidden'); }, 300); 
    };
    
    window.editTarget = (id) => {
         $.get(`{{ url('bdo-targets') }}/${id}/edit`, function(data) {
             $('#target_id').val(data.id);
             $('#target_user_id').val(data.user_id);
             $('#target_month').val(data.target_month);
             $('#target_new_calls').val(data.target_new_calls);
             $('#target_quotations').val(data.target_quotations);
             $('#target_followups').val(data.target_followups);
             $('#target_conversion_sqft').val(data.target_conversion_sqft);
             $('#target_sales_value').val(data.target_sales_value);
              $('#modalTitle').text('Update Target');
              showModalUI();
         });
    }

    $('#targetForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('bdo-targets.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                closeModal('targetModal');
                refreshTable();
                alert(res.success); 
            },
            error: function(err) { alert('Error saving target'); }
        });
    });
</script>
@endpush
@endsection