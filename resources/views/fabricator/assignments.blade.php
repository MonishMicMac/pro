@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<style>
    .dataTables_wrapper {
        padding: 0 !important;
    }

    table.dataTable {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 10px;
        /* row gap */
    }

    table.dataTable thead th {
        text-align: left;
        padding: 12px 16px;
        font-size: 11px;
    }

    table.dataTable tbody tr {
        background: #fff;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .05);
        border-radius: 14px;
    }

    table.dataTable tbody td {
        padding: 14px 16px;
        vertical-align: middle;
    }

    /* Fix show entries overlap */
    .dataTables_length {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .dataTables_length select {
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
    }

    /* Pagination spacing */
    .dataTables_paginate {
        margin-top: 10px;
    }
</style>
@section('content')
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">

        <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">
            Fabricator Requests
        </h3>
        <div class="flex items-center justify-between mb-6">

            <!-- FILTER -->
            <div class="glass-panel rounded-[1.5rem] p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Status
                        </label>
                        <select id="filter_status"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">All</option>
                            <option value="0">Pending</option>
                            <option value="1">Completed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            From Date
                        </label>
                        <input type="date" id="filter_from_date"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            To Date
                        </label>
                        <input type="date" id="filter_to_date"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <button id="btn_filter"
                            class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg">
                            Filter
                        </button>

                        <button id="btn_reset" class="px-3 py-2 bg-white border border-slate-200 rounded-xl">
                            Reset
                        </button>
                    </div>

                </div>
            </div>

        </div>

        <div class="glass-panel rounded-[1.5rem] overflow-hidden">

            <div class="px-4 overflow-x-auto pt-4">
                <table class="w-full" id="assignmentTable">
                    <thead>
                        <tr class="text-left">
                            <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Lead</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Sqft</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Status</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Rate</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">PDF</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">Date</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase">
                                View
                            </th>

                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700">
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-white/40 border-t flex justify-between">
                <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase"></p>
                <div id="table-pagination"></div>
            </div>
        </div>

    </div>
    <script>
        $(document).ready(function() {

            let table = $('#assignmentTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('fabricator.assignments') }}",
                    data: function(d) {
                        d.status = $('#filter_status').val();
                        d.from_date = $('#filter_from_date').val();
                        d.to_date = $('#filter_to_date').val();
                    }
                },
                columns: [{
                        data: 'lead'
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
                        data: 'fabrication_pdf',
                        render: function(d) {
                            return d ?
                                `<a href="/prominance_new/storage/${d}" target="_blank" class="text-red-600">PDF</a>` :
                                '-';
                        }
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'view',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // FILTER
            $('#btn_filter').click(function() {
                table.ajax.reload();
            });

            // RESET
            $('#btn_reset').click(function() {
                $('#filter_status').val('');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                table.ajax.reload();
            });

        });
    </script>
@endsection
