@extends('layouts.app')

@section('title', 'Visit Status Report')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
    #report-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md border-blue-600; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
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
    <div class="glass-panel rounded-[1.5rem] p-4 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">User</label>
                <select id="filter_user_id" class="form-input-custom">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">From Date</label>
                <input type="date" id="filter_from_date" class="form-input-custom" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">To Date</label>
                <input type="date" id="filter_to_date" class="form-input-custom" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                <select id="filter_status" class="form-input-custom">
                    <option value="">All Status</option>
                    <option value="Visited">Visited</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                <select id="filter_visit_type" class="form-input-custom">
                    <option value="">All Categories</option>
                    <option value="1">Account</option>
                    <option value="2">Lead</option>
                    <option value="3">Fabricator</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Type</label>
                <select id="filter_type" class="form-input-custom">
                    <option value="">All Types</option>
                    <option value="planned">Planned</option>
                    <option value="unplanned">Unplanned</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Station</label>
                <select id="filter_food_allowance" class="form-input-custom">
                    <option value="">All</option>
                    <option value="1">Local Station</option>
                    <option value="2">Out Station</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Work Type</label>
                <select id="filter_work_type" class="form-input-custom">
                    <option value="">All</option>
                    <option value="Individual">Individual</option>
                    <option value="Joint Work">Joint Work</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-4 lg:col-span-2">
                <button id="btn_filter" class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                </button>
                <button id="btn_reset" class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-[1.5rem] overflow-hidden relative z-10">
        <div class="px-4 overflow-x-auto pt-4">
            <table class="w-full" id="visit-report-table">
                <thead>
                    <tr class="text-left">
                        <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Image</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Entity Name</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Work</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Station</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">In Time</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Out Time</th>
                        <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Remarks</th>
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
                data: function (d) {
                    d.user_id = $('#filter_user_id').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                    d.status = $('#filter_status').val();
                    d.visit_type = $('#filter_visit_type').val();
                    d.type = $('#filter_type').val();
                    d.food_allowance = $('#filter_food_allowance').val();
                    d.work_type = $('#filter_work_type').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card');
                $(row).find('td:first').addClass('pl-4 py-3 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-3 rounded-r-xl');
                $(row).find('td').addClass('py-3 px-4');
            },
            columns: [
                { 
                    data: 'image_url', 
                    render: function(data) {
                        return data ? `<img src="${data}" class="w-8 h-8 rounded-lg object-cover shadow-sm cursor-pointer hover:scale-110 transition-transform" onclick="window.open('${data}', '_blank')">` : '<div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center"><span class="material-symbols-outlined text-slate-300 text-[14px]">image</span></div>';
                    }
                },
                { data: 'date', name: 'date' },
                { data: 'entity_name', name: 'entity_name' },
                { data: 'user_name', name: 'user_name' },
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
                        const style = data === 'Planned' ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-600';
                        return `<span class="px-2 py-0.5 rounded text-[9px] font-black uppercase ${style}">${data}</span>`;
                    }
                },
                { data: 'work_type', name: 'work_type' },
                { data: 'food_label', name: 'food_label' },
                { 
                    data: 'status_label', 
                    render: function(data) {
                        const style = data === 'Visited' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600';
                        return `<span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase ${style}">${data}</span>`;
                    }
                },
                { data: 'check_in', name: 'check_in' },
                { data: 'check_out', name: 'check_out' },
                { data: 'remarks', name: 'remarks' }
            ],
            order: [[1, 'desc']],
            dom: 'rtp',
            drawCallback: function(settings) {
                const total = settings.json ? settings.json.recordsTotal : 0;
                $('#table-info').text(`Total Records: ${total}`);
                $('#report-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
            }
        });

        $('#btn_filter').click(function() { table.draw(); });
        $('#btn_reset').click(function() {
            $('#filter_user_id, #filter_status, #filter_visit_type, #filter_type, #filter_food_allowance, #filter_work_type').val('');
            $('#filter_from_date, #filter_to_date').val("{{ date('Y-m-d') }}");
            table.draw();
        });
    });
</script>
@endsection
