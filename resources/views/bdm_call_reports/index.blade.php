@extends('layouts.app')

@section('content')
{{-- Libraries --}}
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
    
    /* Pagination Styles */
    #table-pagination .paginate_button { padding: 4px 10px; margin: 0 2px; border-radius: 8px; background: white; color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; min-width: 28px; transition: all 0.2s; }
    #table-pagination .paginate_button:hover:not(.current) { color: #2563eb; background: #eff6ff; }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    
    /* Table Spacing */
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    
    /* Vertical Align Top for Long Remarks */
    table.dataTable tbody tr td { padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.5); border-bottom: 1px solid rgba(255,255,255,0.5); vertical-align: top; }
    
    table.dataTable tbody tr td:first-child { border-left: 1px solid rgba(255,255,255,0.5); border-radius: 12px 0 0 12px; }
    table.dataTable tbody tr td:last-child { border-right: 1px solid rgba(255,255,255,0.5); border-radius: 0 12px 12px 0; }
</style>

<div class="relative flex-1 p-6 space-y-4 pb-24 bg-[#f8fafc] min-h-screen">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">Calls</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BDM Call Report</h1>
        </div>
    </div>

    {{-- CARD 1: FILTERS --}}
    <div class="glass-panel rounded-[1.5rem] p-6 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            
            {{-- BDM Filter --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">BDM Name</label>
                <select id="filterBdm" class="w-48 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">All BDMs</option>
                    @foreach($bdms as $bdm) <option value="{{ $bdm->id }}">{{ $bdm->name }}</option> @endforeach
                </select>
            </div>

            {{-- Call Status Filter --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</label>
                <select id="filterStatus" class="w-40 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">All Statuses</option>
                    <option value="Connected">Connected</option>
                    <option value="Busy">Busy</option>
                    <option value="No Answer">No Answer</option>
                </select>
            </div>
            
            {{-- Date Range --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date Range</label>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase pl-2">From</span>
                        <input type="date" id="startDate" class="py-2 bg-transparent border-0 text-xs font-bold text-slate-600 outline-none focus:ring-0">
                    </div>
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase pl-2">To</span>
                        <input type="date" id="endDate" class="py-2 bg-transparent border-0 text-xs font-bold text-slate-600 outline-none focus:ring-0">
                    </div>
                </div>
            </div>

            <button onclick="table.draw()" class="mb-[2px] px-6 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply
            </button>
        </div>
    </div>

    {{-- CARD 2: DATA TABLE --}}
    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        
        {{-- Search Header --}}
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-10 pr-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Search..." type="text"/>
            </div>
            <button onclick="table.draw()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-slate-400 hover:text-blue-600 shadow-sm transition-all" title="Refresh">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </button>
        </div>

        {{-- Table --}}
        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="call-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 pb-2">#</th>
                        <th class="px-4 pb-2">Date & Time</th>
                        <th class="px-4 pb-2 text-indigo-600">BDM Name</th>
                        <th class="px-4 pb-2">Client Type</th>
                        <th class="px-4 pb-2">Client Name</th>
                        <th class="px-4 pb-2">Status</th>
                        <th class="px-4 pb-2">Duration</th>
                        {{-- Added min-width for Remarks --}}
                        <th class="px-4 pb-2 min-w-[250px]">Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
            </table>
        </div>
        
        {{-- Footer --}}
        <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
            <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest"></p>
            <div id="table-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>

</div>

<script>
let table;

$(document).ready(function() {
    
    table = $('#call-table').DataTable({
        processing: true, 
        serverSide: true,
        ajax: {
            url: "{{ route('bdm-call-reports.data') }}",
            data: function (d) { 
                d.bdm_id = $('#filterBdm').val(); 
                d.call_status = $('#filterStatus').val(); 
                d.start_date = $('#startDate').val(); 
                d.end_date = $('#endDate').val();     
            }
        },
        createdRow: (row) => $(row).addClass('glass-card group hover:bg-white/60 transition-all'),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'called_at', name: 'called_at', className: 'whitespace-nowrap' }, // Keep Date on one line
            { data: 'bdm_name', name: 'bdm.name', className: 'text-indigo-600 font-bold whitespace-nowrap' },
            { data: 'client_type', name: 'callable_type' },
            { data: 'client_name', name: 'client_name', orderable: false },
            { data: 'call_status', name: 'call_status' },
            { data: 'duration', name: 'duration' },
            // Added 'whitespace-normal' to allow text wrapping for long remarks
            { data: 'remarks', name: 'remarks', orderable: false, className: 'whitespace-normal text-slate-600 leading-relaxed' }
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
    $('#filterBdm, #filterStatus, #startDate, #endDate').on('change', function() { table.draw(); });
});
</script>
@endsection