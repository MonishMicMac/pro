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

        .dataTables_paginate,
        .dataTables_info {
            display: none !important;
        }

        #prospect-table th {
            white-space: nowrap;
        }

        .glass-panel label {
            white-space: nowrap;
        }

        /* Force table data to stay on one line */
        #prospect-table th,
        #prospect-table td {
            white-space: nowrap;
            word-break: keep-all;
        }

        /* Prevent hyphen-based breaking */
        #prospect-table td {
            overflow-wrap: normal;
        }

        /* Optional: cleaner ellipsis if text is too long */
        #prospect-table td {
            text-overflow: ellipsis;
            overflow: hidden;
        }

        #report-pagination {
            justify-content: flex-end;
        }
    </style>
    <div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc] relative z-0">

        <!-- HEADER -->
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Prospect</span>
            </nav>
            <h1 class="text-2xl font-black">Prospect Report</h1>
        </div>

        <!-- FILTER -->
        <div class="glass-panel rounded-none p-5 relative z-20">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                    <select id="filter_zone" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Models\Zone::where('action', '0')->orderBy('name')->pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Telecaller</label>
                    <select id="filter_telecaller" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'Telecaller');
        })->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1 whitespace-nowrap">
                        Telecaller Stage
                    </label>

                    <select id="filter_tc_stage" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Helpers\LeadHelper::getLeadStages() as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDO
                        Stage</label>
                    <select id="filter_bdo_stage" class="form-input-custom">
                        <option value="">All</option>
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
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">From
                        Date</label>
                    <input type="date" id="from_date" class="form-input-custom" value="{{ date('Y-m-01') }}">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">To
                        Date</label>
                    <input type="date" id="to_date" class="form-input-custom" value="{{ date('Y-m-d') }}">
                </div>

                <div class="mt-4 flex justify-end gap-2 lg:col-span-6">
                    <button id="btn_reset"
                        class="px-4 py-2 bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95">
                        Reset Filters
                    </button>

                    <button id="btn_filter"
                        class="px-8 h-[42px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLE -->


        <div class="glass-panel rounded-none overflow-x-auto relative z-10">


            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">
                            Prospect Report
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
                <table id="prospect-table" class="w-full min-w-[1200px]">

                    <thead>
                        <tr class="text-left">
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">SNO</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Telecaller
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Telecaller
                                Stage</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">BDO</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Name
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Phone
                                Number
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Stage
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Zone</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Created
                                Date
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Prospect
                                Date
                            </th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>
            </div>
        </div>
        <div id="report-pagination" class="flex items-center gap-0.5"></div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {

            const table = $('#prospect-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rtp',

                ajax: {
                    url: "{{ route('prospect.report.data') }}",
                    data: function(d) {
                        d.zone = $('#filter_zone').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.telecaller = $('#filter_telecaller').val();
                        d.tc_stage = $('#filter_tc_stage').val();
                        d.bdo_stage = $('#filter_bdo_stage').val();
                    }
                },

                createdRow: function(row) {
                    $(row).addClass('glass-card hover:bg-slate-50 transition-colors');
                    $(row).find('td').addClass('py-3 px-4 border-b border-slate-100');
                },

                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (d, t, r, m) =>
                            m.row + m.settings._iDisplayStart + 1
                    },
                    {
                        data: 'telecaller',
                        orderable: false
                    },
                    {
                        data: 'telecaller_stage',
                        orderable: false
                    },
                    {
                        data: 'bdo',
                        orderable: false
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'phone_number'
                    },
                    {
                        data: 'lead_stage',
                        orderable: false
                    },
                    {
                        data: 'zone_name',
                        orderable: false
                    },
                    {
                        data: 'created_at'
                    }, {
                        data: 'assigned_at'
                    }
                ],

                pagingType: 'simple_numbers',

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
            $('#custom-search').on('keyup input', function() {
                table.search(this.value).draw();
            });



            $('#btn_filter').click(() => table.draw());

            $('#btn_reset').click(function() {
                $('#filter_zone').val('');
                $('#from_date').val("{{ date('Y-m-01') }}");
                $('#to_date').val("{{ date('Y-m-d') }}");
                table.draw();
            });
        });
    </script>
@endsection
