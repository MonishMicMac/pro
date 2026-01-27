@extends('layouts.app')

@section('title', 'Visit Status Report')

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

            .form-input-custom {
                @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer;
            }
        }

        #report-pagination .paginate_button {
            @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100;
        }

        #report-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md border-blue-600;
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.4rem !important;
        }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc] relative z-0">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                    <span>Reports</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-blue-600">Visit Status</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Visit Status Report</h1>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-3xl shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6">

                {{-- ZONE --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">Zone</label>
                    <select id="filter_zone_id" class="form-input-custom mt-2">
                        <option value="">All Zones</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ZSM --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">Zone Manager</label>
                    <select id="filter_zsm_id" class="form-input-custom mt-2">
                        <option value="">All ZSMs</option>
                    </select>
                </div>

                {{-- MANAGER --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">Manager</label>
                    <select id="filter_bdm_id" class="form-input-custom mt-2">
                        <option value="">All Managers</option>
                    </select>
                </div>

                {{-- BDO --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">BDO / Assigned</label>
                    <select id="filter_bdo_id" class="form-input-custom mt-2">
                        <option value="">All BDOs</option>
                    </select>
                </div>

                {{-- STATE --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">State</label>
                    <select id="filter_state_id" class="form-input-custom mt-2">
                        <option value="">All States</option>
                    </select>
                </div>

                {{-- DISTRICT --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">District</label>
                    <select id="filter_district_id" class="form-input-custom mt-2">
                        <option value="">All Districts</option>
                    </select>
                </div>

                {{-- CITY --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">City</label>
                    <select id="filter_city_id" class="form-input-custom mt-2">
                        <option value="">All Cities</option>
                    </select>
                </div>

                {{-- STATUS --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">Status</label>
                    <select id="filter_status" class="form-input-custom mt-2">
                        <option value="">All</option>
                        <option value="Visited">Visited</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>

                {{-- TYPE --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">Type</label>
                    <select id="filter_type" class="form-input-custom mt-2">
                        <option value="">All</option>
                        <option value="planned">Planned</option>
                        <option value="unplanned">Unplanned</option>
                    </select>
                </div>

                {{-- DATE --}}
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">From Date</label>
                    <input type="date" id="filter_from_date" class="form-input-custom mt-2">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase">To Date</label>
                    <input type="date" id="filter_to_date" class="form-input-custom mt-2">
                </div>

                {{-- ACTIONS --}}
                <div class="flex items-end gap-3 col-span-1 md:col-span-2 lg:col-span-2">
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



        <!-- Table Section -->
        <div class="glass-panel rounded-[1.5rem] overflow-hidden relative z-10">
            <div class="px-4 overflow-x-auto pt-4">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">
                            Visit Status Report
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
                <table class="w-full" id="visit-report-table">
                    <thead>
                        <tr class="text-left">
                            <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Image</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Entity
                                Name
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Category
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Work</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Station
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">In Time
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Out Time
                            </th>
                            <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Remarks
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>
            </div>

            <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
                <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase tracking-widest"></p>
                <div id="report-pagination" class="flex items-center gap-0.5"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#visit-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('site-visits.report.data') }}",
                    data: function(d) {
                        d.zone_id = $('#filter_zone_id').val();
                        d.zsm_id = $('#filter_zsm_id').val();
                        d.manager_id = $('#filter_bdm_id').val();
                        d.bdo_id = $('#filter_bdo_id').val();
                        d.state_id = $('#filter_state_id').val();
                        d.district_id = $('#filter_district_id').val();
                        d.city_id = $('#filter_city_id').val();

                        d.status = $('#filter_status').val();
                        d.visit_type = $('#filter_visit_type').val();
                        d.type = $('#filter_type').val();
                        d.work_type = $('#filter_work_type').val();
                        d.food_allowance = $('#filter_food_allowance').val();

                        d.from_date = $('#filter_from_date').val();
                        d.to_date = $('#filter_to_date').val();
                    }
                },
                createdRow: function(row) {
                    $(row).addClass('glass-card');
                    $(row).find('td:first').addClass('pl-4 py-3 rounded-l-xl');
                    $(row).find('td:last').addClass('pr-4 py-3 rounded-r-xl');
                    $(row).find('td').addClass('py-3 px-4');
                },
                columns: [{
                        data: 'image_url',
                        render: function(data) {
                            return data ?
                                `<img src="${data}" class="w-8 h-8 rounded-lg object-cover shadow-sm cursor-pointer hover:scale-110 transition-transform" onclick="window.open('${data}', '_blank')">` :
                                '<div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center"><span class="material-symbols-outlined text-slate-300 text-[14px]">image</span></div>';
                        }
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'entity_name',
                        name: 'entity_name'
                    },
                    {
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'category',
                        render: function(data) {
                            const styles = {
                                'Account': 'bg-blue-100 text-blue-600',
                                'Lead': 'bg-indigo-100 text-indigo-600',
                                'Fabricator': 'bg-orange-100 text-orange-600'
                            };
                            return `<span class="px-2 py-0.5 rounded text-[9px] font-black uppercase ${styles[data] || 'bg-slate-100'}">${data}</span>`;
                        }
                    },
                    {
                        data: 'type_label',
                        render: function(data) {
                            const style = data === 'Planned' ? 'bg-purple-100 text-purple-600' :
                                'bg-slate-100 text-slate-600';
                            return `<span class="px-2 py-0.5 rounded text-[9px] font-black uppercase ${style}">${data}</span>`;
                        }
                    },
                    {
                        data: 'work_type',
                        name: 'work_type'
                    },
                    {
                        data: 'food_label',
                        name: 'food_label'
                    },
                    {
                        data: 'status_label',
                        render: function(data) {
                            const style = data === 'Visited' ? 'bg-emerald-100 text-emerald-600' :
                                'bg-red-100 text-red-600';
                            return `<span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase ${style}">${data}</span>`;
                        }
                    },
                    {
                        data: 'check_in',
                        name: 'check_in'
                    },
                    {
                        data: 'check_out',
                        name: 'check_out'
                    },
                    {
                        data: 'remarks',
                        name: 'remarks'
                    }
                ],
                order: [
                    [1, 'desc']
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
                    $('#report-pagination').html($('.dataTables_paginate').html());
                    $('.dataTables_paginate').empty();
                }
            });

            function clearDropdown(id, label) {
                $(id).html(`<option value="">All ${label}</option>`);
            }
            $('#filter_zone_id').on('change', function() {
                const zoneId = $(this).val();

                if (!zoneId) {
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    clearDropdown('#filter_bdm_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    return;
                }

                $.get("{{ route('get.location.data') }}", {
                    type: 'zone',
                    id: zoneId
                }, function(res) {

                    // STATES
                    clearDropdown('#filter_state_id', 'States');
                    $.each(res.states || [], function(_, s) {
                        $('#filter_state_id').append(
                            `<option value="${s.id}">${s.name}</option>`
                        );
                    });

                    // ZSM
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    $.each(res.zsms || [], function(_, u) {
                        $('#filter_zsm_id').append(
                            `<option value="${u.id}">${u.name}</option>`
                        );
                    });

                    // MANAGER
                    clearDropdown('#filter_bdm_id', 'Managers');
                    $.each(res.managers || [], function(_, u) {
                        $('#filter_bdm_id').append(
                            `<option value="${u.id}">${u.name}</option>`
                        );
                    });

                    // BDO
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    $.each(res.bdos || [], function(_, u) {
                        $('#filter_bdo_id').append(
                            `<option value="${u.id}">${u.name}</option>`
                        );
                    });
                    $('#filter_state_id').on('change', function() {
                        const stateId = $(this).val();
                        clearDropdown('#filter_district_id', 'Districts');
                        clearDropdown('#filter_city_id', 'Cities');

                        if (!stateId) return;

                        $.get("{{ route('get.location.data') }}", {
                            type: 'state',
                            id: stateId
                        }, function(res) {
                            $.each(res || [], function(_, d) {
                                $('#filter_district_id').append(
                                    `<option value="${d.id}">${d.name}</option>`
                                );
                            });
                        });
                    });


                    $.each(res.users || [], function(_, u) {
                        $('#filter_bdo_id').append(
                            `<option value="${u.id}">${u.name}</option>`
                        );
                    });
                });
            });

            $('#custom-search').on('keyup change', function() {
                table.search(this.value).draw();
            });

            $('#btn_filter').click(function() {
                table.draw();
            });
            $('#btn_reset').click(function() {
                $('#filter_user_id, #filter_status, #filter_visit_type, #filter_type, #filter_food_allowance, #filter_work_type')
                    .val('');
                $('#filter_from_date, #filter_to_date').val("{{ date('Y-m-d') }}");
                table.draw();
            });
        });
    </script>
@endsection
