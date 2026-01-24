@extends('layouts.app')

@section('content')
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
    .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    
    #table-pagination .paginate_button { padding: 4px 10px; margin: 0 2px; border-radius: 8px; background: white; color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; min-width: 28px; transition: all 0.2s; }
    #table-pagination .paginate_button:hover:not(.current) { color: #2563eb; background: #eff6ff; }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    table.dataTable tbody tr td { padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.5); border-bottom: 1px solid rgba(255,255,255,0.5); }
    table.dataTable tbody tr td:first-child { border-left: 1px solid rgba(255,255,255,0.5); border-radius: 12px 0 0 12px; }
    table.dataTable tbody tr td:last-child { border-right: 1px solid rgba(255,255,255,0.5); border-radius: 0 12px 12px 0; }
</style>

<div class="relative flex-1 p-6 space-y-4 pb-24 bg-[#f8fafc] min-h-screen">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">BDO Tour Plans</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daily Visit Schedules</h1>
        </div>
    </div>

    {{-- CARD 1: FILTERS --}}
    <div class="glass-panel rounded-[1.5rem] p-6 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            
            {{-- BDO Filter --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">BDO Name</label>
                <select id="filterUser" class="w-48 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">All BDOs</option>
                    @foreach($bdos as $bdo) <option value="{{ $bdo->id }}">{{ $bdo->name }}</option> @endforeach
                </select>
            </div>

            {{-- Location Status Filter --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Location Status</label>
                <select id="filterLocation" class="w-40 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">All Locations</option>
                    <option value="1">Local</option>
                    <option value="2">Out Station</option>
                    <option value="3">Meeting</option>
                    <option value="4">Leave</option>
                </select>
            </div>

            {{-- Work Mode Filter --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Work Mode</label>
                <select id="filterWorkMode" class="w-40 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">All Work Modes</option>
                    <option value="Individual">Individual</option>
                    <option value="Joint Work">Joint Work</option>
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

            {{-- Filter Button --}}
            <button onclick="table.draw()" class="mb-[2px] px-6 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply Filters
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
            <button onclick="table.draw()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-slate-400 hover:text-blue-600 shadow-sm transition-all" title="Refresh Data">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </button>
        </div>

        {{-- Table --}}
        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="tour-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 pb-2">#</th>
                        <th class="px-4 pb-2">Date</th>
                        <th class="px-4 pb-2">BDO Name</th>
                        <th class="px-4 pb-2">Location Status</th>
                        <th class="px-4 pb-2">Work Mode</th>
                        <th class="px-4 pb-2 text-center">Total Visits</th>
                        <th class="px-4 pb-2 text-center">Action</th>
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
                        
                        {{-- Joint Work Section --}}
                        <div>
                            <h4 class="flex items-center gap-2 text-[11px] font-black text-indigo-900 uppercase tracking-widest mb-3">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-sm"></span> Joint Work
                            </h4>
                            <div class="bg-indigo-50/50 rounded-xl border border-indigo-100 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-indigo-100/50 text-[10px] uppercase text-indigo-800 font-bold">
                                        <tr>
                                            <th class="p-3">Type</th>
                                            <th class="p-3">Client Name</th>
                                            {{-- ADDED BDM COLUMN --}}
                                            <th class="p-3">BDM Name</th>
                                            <th class="p-3 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="jointList" class="text-[11px] font-bold text-slate-600 divide-y divide-indigo-100/50"></tbody>
                                </table>
                                <p id="noJoint" class="hidden p-4 text-center text-[10px] text-indigo-400 italic font-medium">No Joint Work Requests</p>
                            </div>
                        </div>

                        {{-- Individual Work Section --}}
                        <div>
                            <h4 class="flex items-center gap-2 text-[11px] font-black text-slate-900 uppercase tracking-widest mb-3">
                                <span class="w-2 h-2 rounded-full bg-slate-500 shadow-sm"></span> Individual Work
                            </h4>
                            <div class="bg-slate-50/50 rounded-xl border border-slate-100 overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-100/50 text-[10px] uppercase text-slate-600 font-bold">
                                        <tr>
                                            <th class="p-3">Type</th>
                                            <th class="p-3">Client Name</th>
                                            <th class="p-3 text-right">Status</th>
                                        </tr>
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
    
    // Initialize DataTable
    table = $('#tour-table').DataTable({
        processing: true, 
        serverSide: true,
        ajax: {
            url: "{{ route('tour-plans.data') }}",
            data: function (d) { 
                // Pass Filters to Controller
                d.user_id = $('#filterUser').val(); 
                d.location_status = $('#filterLocation').val(); // New Filter
                d.work_mode = $('#filterWorkMode').val();       // New Filter
                d.start_date = $('#startDate').val(); 
                d.end_date = $('#endDate').val();     
            }
        },
        createdRow: (row) => $(row).addClass('glass-card group hover:bg-white/60 transition-all'),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'schedule_date', name: 'schedule_date' },
            { data: 'bdo_name', name: 'user.name' },
            { data: 'location_status', name: 'food_allowance' },
            { data: 'work_modes', name: 'work_modes' }, 
            { data: 'total_visits', name: 'total_visits', className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: 'rtp', // Hides default Search/Pagination controls (we used custom ones)
        language: {
            // Custom Icons for Pagination
            paginate: { 
                previous: '<span class="material-symbols-outlined font-black text-[28px]">chevron_left</span>', 
                next: '<span class="material-symbols-outlined font-black text-[28px]">chevron_right</span>' 
            }
        },
        drawCallback: function(settings) {
            // Update Info Text
            $('#table-info').text(`Showing ${settings.json.data.length} of ${settings.json.recordsTotal} Records`);
            
            // Move Pagination to our custom container
            $('#table-pagination').html($('.dataTables_paginate').html());
            $('.dataTables_paginate').empty();
            
            // Re-bind click events
            $('#table-pagination .paginate_button').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;
                if ($(this).hasClass('previous')) table.page('previous').draw('page');
                else if ($(this).hasClass('next')) table.page('next').draw('page');
                else table.page(parseInt($(this).text()) - 1).draw('page');
            });
        }
    });

    // Event Listeners for Filters
    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#filterUser, #filterLocation, #filterWorkMode, #startDate, #endDate').on('change', function() { table.draw(); });
});

// --- Modal Functions ---

window.openTourModal = function(userId, date) {
    $('#tourModal').removeClass('hidden');
    $('#modalLoader').show();
    $('#modalContent').addClass('hidden');
    $('#modalDate').text(formatDate(date)); // Optional: Helper to format date nicely

    // Fetch Details
    $.get("{{ route('tour-plans.details') }}", { user_id: userId, date: date }, function(data) {
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
            // Style badge color based on status
            const statusColor = item.status === 'Visited' ? 'text-emerald-500' : 'text-amber-500';
            const icon = item.status === 'Visited' ? 'check_circle' : 'schedule';
            
            // Conditional Column: Only show BDM column for Joint Work (For BDO View)
            const bdmColumn = type === 'joint' 
                ? `<td class="p-3 text-indigo-600 font-bold">${item.bdm_name}</td>` 
                : '';

            const html = `
                <tr class="hover:bg-white/50 transition-colors">
                    <td class="p-3 w-20">
                        <span class="px-2 py-1 bg-white border border-slate-200 rounded-md text-[9px] font-bold uppercase text-slate-500 tracking-wide">${item.type}</span>
                    </td>
                    <td class="p-3 text-slate-700">${item.name}</td>
                    ${bdmColumn} {{-- Insert BDM Column if Joint --}}
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