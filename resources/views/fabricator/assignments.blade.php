@extends('layouts.fabricator')

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<style>
    .glass-panel {
        background: rgba(255, 255, 255, .75);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, .4);
        box-shadow: 0 4px 20px rgba(0, 0, 0, .04);
    }

    table.dataTable {
        border-collapse: separate !important;
        border-spacing: 0 .4rem !important;
    }

    .dataTables_paginate,
    .dataTables_info,
    .dataTables_filter,
    .dataTables_length {
        display: none !important;
    }

    #report-pagination .paginate_button {
        padding: 4px 8px;
        margin: 0 2px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        background: white;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    #report-pagination .paginate_button.current {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }
</style>

@section('content')
    <div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc]">

        <!-- HEADER -->
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Fabricator</span>
            </nav>
            <h1 class="text-2xl font-black">Fabricator Requests</h1>
        </div>

        <!-- FILTER -->
        <div class="glass-panel rounded-none p-5">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Status</label>
                    <select id="filter_status"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        <option value="">All</option>
                        <option value="0">Pending</option>
                        <option value="1">Completed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">From Date</label>
                    <input type="date" id="filter_from_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">To Date</label>
                    <input type="date" id="filter_to_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                </div>

                <div class="flex items-end gap-2 lg:col-span-3">
                    <button id="btn_reset"
                        class="h-[42px] px-6 bg-white border border-slate-200 rounded-xl
               text-[10px] font-black uppercase flex items-center justify-center">
                        Reset
                    </button>

                    <button id="btn_filter"
                        class="h-[42px] px-8 bg-blue-600 text-white
               text-[10px] font-black uppercase rounded-xl
               shadow-lg flex items-center justify-center">
                        Filters
                    </button>
                </div>


            </div>
        </div>

        <!-- TABLE -->
        <div class="glass-panel rounded-none overflow-x-auto">
            <div class="p-6">

                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Fabricator Requests</h2>
                        <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                            Total Records: 0
                        </p>
                    </div>

                    <div class="relative">
                        <input id="custom-search" type="text" placeholder="Search..."
                            class="bg-white/50 border-slate-200 rounded-xl text-xs font-bold text-slate-600 w-64 pr-10">
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-300">
                            search
                        </span>
                    </div>
                </div>

                <table id="assignmentTable" class="w-full min-w-[1200px]">
                    <thead>
                        <tr class="text-left">
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Lead</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Bdo</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Sqft</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Status</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Rate</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">PDF</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Date</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">View</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>

            </div>
        </div>

        <!-- PAGINATION -->
        <div id="report-pagination" class="flex justify-end gap-1"></div>

    </div>

    <script>
        $(function() {

            const table = $('#assignmentTable').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rtp',
                pagingType: 'simple_numbers',

                ajax: {
                    url: "{{ route('fabricator.assignments') }}",
                    data: d => {
                        d.status = $('#filter_status').val();
                        d.from_date = $('#filter_from_date').val();
                        d.to_date = $('#filter_to_date').val();
                    }
                },

                createdRow: function(row) {
                    $(row).addClass('hover:bg-slate-50 transition');
                    $(row).find('td').addClass('py-3 px-4 border-b border-slate-100');
                },

                columns: [{
                        data: 'lead'
                    },
                    {
                        data: 'bdo',
                        name: 'bdo'
                    },

                    {
                        data: 'approx_sqft'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'rate_per_sqft'
                    },
                    {
                        data: 'quotation_pdf',
                        name: 'quotation_pdf',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'created_at'
                    },
                    {
                        data: 'view',
                        orderable: false,
                        searchable: false
                    }
                ],
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

            $('#btn_reset').click(() => {
                $('#filter_status').val('');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                table.draw();
            });

        });
    </script>
@endsection
