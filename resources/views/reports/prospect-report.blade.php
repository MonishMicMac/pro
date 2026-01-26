@extends('layouts.app')

@section('title', 'Prospect Report')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <style type="text/tailwindcss">
        @layer components {
            .glass-panel {
                @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm transition-all;
            }
            .form-input-custom {
                @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer;
            }
        }

        /* Custom Pagination Styling */
        #report-pagination .paginate_button {
            @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100;
        }
        #report-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md border-blue-600;
        }

        /* DataTable Overrides */
        table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
        .dataTables_paginate, .dataTables_info { display: none !important; }
        #prospect-table th, #prospect-table td { white-space: nowrap; word-break: keep-all; vertical-align: middle; }
        #report-pagination { justify-content: flex-end; }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc] relative z-0">

        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">Prospect</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Prospect Report</h1>
        </div>

        <div class="glass-panel rounded-2xl p-5 relative z-20">
            {{-- Grid Configuration: 1 col mobile, 3 cols tablet, 5 cols desktop --}}
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                    <select id="filter_zone" class="form-input-custom">
                        <option value="">All Zones</option>
                        @foreach ($zones as $id => $name)
                            <option value="{{ $id }}" {{ Auth::user()->zone_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">State</label>
                    <select id="filter_state" class="form-input-custom">
                        <option value="">All States</option>
                        @foreach ($states as $id => $name)
                            <option value="{{ $id }}" {{ Auth::user()->state_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                    <select id="filter_district" class="form-input-custom">
                        <option value="">All Districts</option>
                        @foreach ($districts as $id => $name)
                            <option value="{{ $id }}" {{ Auth::user()->district_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">City</label>
                    <select id="filter_city" class="form-input-custom">
                        <option value="">All Cities</option>
                        @foreach ($cities as $id => $name)
                            <option value="{{ $id }}" {{ Auth::user()->city_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Telecaller</label>
                    <select id="filter_telecaller" class="form-input-custom">
                        <option value="">All Telecallers</option>
                        @foreach (\App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Telecaller'))->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">TC Stage</label>
                    <select id="filter_tc_stage" class="form-input-custom">
                        <option value="">All Stages</option>
                        @foreach (\App\Helpers\LeadHelper::getLeadStages() as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDO Stage</label>
                    <select id="filter_bdo_stage" class="form-input-custom">
                        <option value="">All Stages</option>
                        <option value="0">Site Identification</option>
                        <option value="1">Intro</option>
                        <option value="2">FollowUp</option>
                        <option value="3">Quote Pending</option>
                        <option value="4">Quote Sent</option>
                        <option value="5">Won</option>
                        <option value="6">Site Handed Over</option>
                        <option value="7">Lost</option>
                    </select>
                </div>

                <div>
                     <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Date Range</label>
                     <div class="flex gap-2">
                        <input type="date" id="from_date" class="form-input-custom w-1/2 px-2" value="{{ date('Y-m-01') }}">
                        <input type="date" id="to_date" class="form-input-custom w-1/2 px-2" value="{{ date('Y-m-d') }}">
                     </div>
                </div>

                <div class="md:col-span-3 lg:col-span-2 flex items-end justify-end gap-2 pb-[2px]">
                    <button id="btn_reset" class="px-5 h-[38px] bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-rose-500 transition-all active:scale-95 shadow-sm">
                        Reset
                    </button>
                    <button id="btn_filter" class="flex-1 h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> Apply Filters
                    </button>
                </div>

            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden relative z-10 flex flex-col min-h-[500px]">
            <div class="p-6 flex-1 flex flex-col">
                
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">Report Data</h2>
                        <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Records: 0</p>
                    </div>
                    <div class="relative w-full md:w-72">
                        <input id="custom-search" type="text" placeholder="Search..." class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 placeholder:text-slate-300 pl-4 pr-10 py-2.5 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[18px]">search</span>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto custom-scrollbar">
                    <table id="prospect-table" class="w-full min-w-[1200px]">
                        <thead>
                            <tr class="text-left">
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">S.No</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Telecaller</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">TC Stage</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">BDO</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Name</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Phone</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Stage</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Zone</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Created</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Assigned</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-bold text-slate-700"></tbody>
                    </table>
                </div>

                <div id="report-pagination" class="flex items-center gap-1 mt-4 border-t border-slate-100 pt-4"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            // --- Helper: Reset Dropdown ---
            function resetDropdown(selector, placeholder = "All") {
                $(selector).html(`<option value="">${placeholder}</option>`);
            }

            // --- 1. Cascading Dropdown Logic (Zone -> State/TC -> District -> City) ---
            
            // Zone Change
            $('#filter_zone').change(function() {
                let zoneId = $(this).val();
                
                // Reset dependents
                resetDropdown('#filter_state', 'All States'); 
                resetDropdown('#filter_district', 'All Districts'); 
                resetDropdown('#filter_city', 'All Cities');
                resetDropdown('#filter_telecaller', 'All Telecallers'); // Also reset Telecallers
                
                if(zoneId) {
                    $.get("{{ route('get.location.data') }}", { type: 'zone', id: zoneId }, function(data) {
                        // Populate States
                        let states = data.states || [];
                        states.forEach(st => $('#filter_state').append(`<option value="${st.id}">${st.name}</option>`));

                        // Populate Telecallers (Specific to this Zone)
                        let telecallers = data.telecallers || [];
                        telecallers.forEach(tc => $('#filter_telecaller').append(`<option value="${tc.id}">${tc.name}</option>`));
                    });
                }
            });

            // State Change
            $('#filter_state').change(function() {
                let stateId = $(this).val();
                resetDropdown('#filter_district', 'All Districts'); 
                resetDropdown('#filter_city', 'All Cities');
                
                if(stateId) {
                    $.get("{{ route('get.location.data') }}", { type: 'state', id: stateId }, function(data) {
                        data.forEach(dt => $('#filter_district').append(`<option value="${dt.id}">${dt.name}</option>`));
                    });
                }
            });

            // District Change
            $('#filter_district').change(function() {
                let districtId = $(this).val();
                resetDropdown('#filter_city', 'All Cities');
                
                if(districtId) {
                    $.get("{{ route('get.location.data') }}", { type: 'district', id: districtId }, function(data) {
                        data.forEach(city => $('#filter_city').append(`<option value="${city.id}">${city.name}</option>`));
                    });
                }
            });

            // --- 2. DataTable Initialization ---
            const table = $('#prospect-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rtp', // Custom positioning: Processing, Table, Pagination
                pageLength: 10,
                ajax: {
                    url: "{{ route('prospect.report.data') }}",
                    data: function(d) {
                        // Gather all filter values
                        d.zone = $('#filter_zone').val();
                        d.state = $('#filter_state').val();
                        d.district = $('#filter_district').val();
                        d.city = $('#filter_city').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.telecaller = $('#filter_telecaller').val();
                        d.tc_stage = $('#filter_tc_stage').val();
                        d.bdo_stage = $('#filter_bdo_stage').val();
                    }
                },
                createdRow: function(row) {
                    $(row).addClass('glass-card hover:bg-blue-50/50 transition-colors group');
                    $(row).find('td').addClass('py-3 px-4 border-b border-slate-100 group-hover:border-blue-100');
                },
                columns: [
                    { 
                        data: null, 
                        orderable: false, 
                        searchable: false, 
                        className: 'text-center', 
                        render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1 
                    },
                    { data: 'telecaller', orderable: false },
                    { data: 'telecaller_stage', orderable: false },
                    { data: 'bdo', orderable: false },
                    { data: 'name' },
                    { data: 'phone_number' },
                    { data: 'lead_stage', orderable: false },
                    { data: 'zone_name', orderable: false },
                    { data: 'created_at' },
                    { data: 'assigned_at' }
                ],
                pagingType: 'simple_numbers',
                language: { 
                    paginate: { 
                        previous: '<span class="material-symbols-outlined text-[18px]">chevron_left</span>', 
                        next: '<span class="material-symbols-outlined text-[18px]">chevron_right</span>' 
                    } 
                },
                drawCallback: function(settings) {
                    const total = settings.json ? settings.json.recordsTotal : 0;
                    $('#table-info').text(`Total Records: ${total}`);
                    
                    // Move pagination to custom container
                    $('#report-pagination').html($('.dataTables_paginate').html());
                    $('.dataTables_paginate').empty();

                    // Re-bind click events for the moved pagination
                    $('#report-pagination .paginate_button').on('click', function(e) {
                         e.preventDefault();
                         if (!$(this).hasClass('disabled') && !$(this).hasClass('current')) {
                             const page = $(this).data('dt-idx');
                             table.page(page).draw('page');
                         }
                    });
                }
            });

            // --- 3. Event Listeners ---
            
            // Custom Search
            $('#custom-search').on('keyup input', function() { 
                table.search(this.value).draw(); 
            });

            // Apply Filter
            $('#btn_filter').click(() => table.draw());

            // Reset Filter
            $('#btn_reset').click(function() {
                // Reset Selects
                $('#filter_zone, #filter_state, #filter_district, #filter_city').val('').trigger('change');
                $('#filter_telecaller, #filter_tc_stage, #filter_bdo_stage').val('');
                
                // Reset Dates
                $('#from_date').val("{{ date('Y-m-01') }}");
                $('#to_date').val("{{ date('Y-m-d') }}");
                
                // Reset Search
                $('#custom-search').val('');
                
                // Redraw
                table.search('').draw();
            });
        });
    </script>
@endsection