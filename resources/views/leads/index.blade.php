@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <style type="text/tailwindcss">
        @layer components {
            .glass-panel {
                @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
            }

            .glass-card {
                @apply bg-white/50 backdrop-blur-sm border border-white/20 transition-all duration-200;
            }

            .glass-card:hover {
                @apply bg-white/90 -translate-y-0.5 shadow-md;
            }
        }

        #table-pagination .paginate_button {
            @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100;
        }

        #table-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md border-blue-600;
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.4rem !important;
        }

        .dataTables_paginate {
            display: flex !important;
            align-items: center;
            gap: 0.25rem;
            float: none !important;
        }

        .dataTables_paginate span {
            display: flex;
            gap: 0.25rem;
        }

        .dataTables_paginate .paginate_button {
            margin: 0 !important;
        }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
        <div class="flex items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                    <span>Modules</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-blue-600">Leads</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">All Leads</h1>
            </div>
        </div>

        <div class="glass-panel rounded-[1.5rem] p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Stage</label>
                    <select id="filter_lead_stage"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
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
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                    <select id="filter_zone_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Zones</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone
                        Manager</label>
                    <select id="filter_zsm_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All ZSMs</option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager</label>
                    <select id="filter_manager_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Managers</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO /
                        Assigned To</label>
                    <select id="filter_bdo_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All BDOs</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">State</label>
                    <select id="filter_state_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All States</option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">District</label>
                    <select id="filter_district_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Districts</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">City</label>
                    <select id="filter_city_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Cities</option>
                    </select>
                </div>


                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">From
                        Date</label>
                    <input type="date" id="filter_from_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">To
                        Date</label>
                    <input type="date" id="filter_to_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                </div>

                <div class="flex items-end gap-2 lg:col-span-1">
                    <button id="btn_filter"
                        class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                    </button>
                    <button id="btn_reset"
                        class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-[1.5rem] overflow-hidden relative z-10">
            <div class="px-4 overflow-x-auto pt-4">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">
                            Lead Report
                        </h2>
                        <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                            Total Records: 0
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <input id="custom-search" type="text" placeholder="Search..."
                                class="bg-white/50 border-slate-200 rounded-xl text-xs font-bold text-slate-600 placeholder:text-slate-300 w-64 pr-10">
                            <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-300">
                                search
                            </span>
                        </div>


                    </div>
                </div>
                <table class="w-full" id="leads-table">
                    <thead>
                        <tr class="text-left">
                            {{-- Changed ID to S.No --}}
                            <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">S.No</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Name
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Phone</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">City</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Assigned To
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Priority
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Created
                                Date</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">
                                Action</th>
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            // --- 1. User Geography Config ---
            const authGeo = {
                zone: "{{ Auth::user()->zone_id }}",
                state: "{{ Auth::user()->state_id }}",
                district: "{{ Auth::user()->district_id }}",
                city: "{{ Auth::user()->city_id }}"
            };

            function clearDropdown(id, placeholder) {
                $(id).empty().append(`<option value="">All ${placeholder}</option>`);
            }

            // --- 2. Chained Dropdown Logic ---

            // Load States and Users (Combined)
            function getZoneData(zoneId, callback) {
                if (!zoneId) {
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    return;
                }
                $.get("{{ route('get.location.data') }}", {
                    type: 'zone',
                    id: zoneId
                }, function(data) {
                    // Populate States
                    clearDropdown('#filter_state_id', 'States');
                    $.each(data.states, function(i, item) {
                        $('#filter_state_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });

                    // Populate ZSMs
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    $.each(data.zsms, function(i, item) {
                        $('#filter_zsm_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });

                    // Populate BDMs
                    clearDropdown('#filter_manager_id', 'Managers');
                    $.each(data.bdms, function(i, item) {
                        $('#filter_manager_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });

                    // Populate BDOs
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    $.each(data.bdos, function(i, item) {
                        $('#filter_bdo_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });

                    if (callback) callback();
                });
            }

            // Load Districts
            function getDistricts(stateId, callback) {
                if (!stateId) {
                    clearDropdown('#filter_district_id', 'Districts');
                    return;
                }
                $.get("{{ route('get.location.data') }}", {
                    type: 'state',
                    id: stateId
                }, function(data) {
                    clearDropdown('#filter_district_id', 'Districts');
                    $.each(data, function(i, item) {
                        $('#filter_district_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });
                    if (callback) callback();
                });
            }

            // Load Cities
            function getCities(districtId, callback) {
                if (!districtId) {
                    clearDropdown('#filter_city_id', 'Cities');
                    return;
                }
                $.get("{{ route('get.location.data') }}", {
                    type: 'district',
                    id: districtId
                }, function(data) {
                    clearDropdown('#filter_city_id', 'Cities');
                    $.each(data, function(i, item) {
                        $('#filter_city_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });
                    if (callback) callback();
                });
            }
            $('#custom-search').on('keyup change', function() {
                table.search(this.value).draw();
            });
            // Load Managers (BDM)
            function getManagers(zsmId, callback) {
                if (!zsmId) {
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    return;
                }
                $.get("{{ route('get.location.data') }}", {
                    type: 'bdm',
                    id: zsmId
                }, function(data) {
                    clearDropdown('#filter_manager_id', 'Managers');
                    $.each(data, function(i, item) {
                        $('#filter_manager_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });
                    if (callback) callback();
                });
            }

            // Load BDOs
            function getBdos(bdmId, callback) {
                if (!bdmId) {
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    return;
                }
                $.get("{{ route('get.location.data') }}", {
                    type: 'bdo',
                    id: bdmId
                }, function(data) {
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    $.each(data, function(i, item) {
                        $('#filter_bdo_id').append(
                            `<option value="${item.id}">${item.name}</option>`);
                    });
                    if (callback) callback();
                });
            }

            // --- 3. Event Listeners for Manual Changes ---
            $('#filter_zone_id').on('change', function() {
                const zoneId = $(this).val();
                getZoneData(zoneId);
                clearDropdown('#filter_district_id', 'Districts');
                clearDropdown('#filter_city_id', 'Cities');
            });

            $('#filter_zsm_id').on('change', function() {
                getManagers($(this).val());
            });

            $('#filter_manager_id').on('change', function() {
                getBdos($(this).val());
            });

            $('#filter_state_id').on('change', function() {
                getDistricts($(this).val());
                clearDropdown('#filter_city_id', 'Cities');
            });

            $('#filter_district_id').on('change', function() {
                getCities($(this).val());
            });

            // --- 4. Initialization Logic (Role Based) ---
            if (authGeo.zone) {
                $('#filter_zone_id').val(authGeo.zone).prop('disabled', true);
                getZoneData(authGeo.zone, function() {
                    if (authGeo.state) {
                        $('#filter_state_id').val(authGeo.state).prop('disabled', true);
                        getDistricts(authGeo.state, function() {
                            if (authGeo.district) {
                                $('#filter_district_id').val(authGeo.district).prop('disabled',
                                    true);
                                getCities(authGeo.district);
                            }
                        });
                    }
                });
            }

            // --- 5. DataTable Implementation ---
            var table = $('#leads-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('leads.data') }}",
                    data: function(d) {
                        d.lead_stage = $('#filter_lead_stage').val();
                        d.from_date = $('#filter_from_date').val();
                        d.to_date = $('#filter_to_date').val();
                        // Send location values even if disabled
                        d.zone_id = $('#filter_zone_id').val();
                        d.zsm_id = $('#filter_zsm_id').val();
                        d.manager_id = $('#filter_manager_id').val();
                        d.bdo_id = $('#filter_bdo_id').val();
                        d.state_id = $('#filter_state_id').val();
                        d.district_id = $('#filter_district_id').val();
                        d.city_id = $('#filter_city_id').val();
                    }
                },
                createdRow: function(row) {
                    $(row).addClass('glass-card group');
                    $(row).find('td:first').addClass('pl-4 py-3 rounded-l-xl');
                    $(row).find('td:last').addClass('pr-4 py-3 rounded-r-xl');
                    $(row).find('td').addClass('py-3 px-4');
                },
                columns: [{
                        data: null,
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'phone_number',
                        name: 'phone_number'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'assigned_to',
                        name: 'assignedUser.name'
                    },
                    {
                        data: 'lead_stage',
                        name: 'lead_stage',
                        className: 'text-center'
                    },
                    {
                        data: 'priority',
                        name: 'priority',
                        className: 'text-center'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                dom: 'rtp',
                language: {
                    paginate: {
                        previous: '<span class="material-symbols-outlined">chevron_left</span>',
                        next: '<span class="material-symbols-outlined">chevron_right</span>'
                    }
                },
                drawCallback: function(settings) {
                    const total = settings.json ? settings.json.recordsTotal : 0;
                    $('#table-info').text(`Total Records: ${total}`);
                    $('.dataTables_paginate').appendTo('#table-pagination');
                }
            });

            $('#btn_filter').click(function() {
                table.draw();
            });

            $('#btn_reset').click(function() {
                // Only clear non-disabled fields
                $('#filter_lead_stage, #filter_user_id, #filter_from_date, #filter_to_date').val('');
                $('select:not([disabled])').val('');

                // If user is Admin (no zone), clear everything
                if (!authGeo.zone) {
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_district_id', 'Districts');
                    clearDropdown('#filter_city_id', 'Cities');
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                }
                table.draw();
            });
        });
    </script>
@endsection
