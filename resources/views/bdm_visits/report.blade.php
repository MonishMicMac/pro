@extends('layouts.app')

@section('title', 'BDM Visit Report')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
        .glass-card {
            @apply bg-white/50 backdrop-blur-sm border border-white/20 transition-all duration-200;
        }
        .filter-input {
            @apply bg-white/50 border-slate-200 rounded-xl text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500;
        }
    }
    .dataTables_wrapper .dataTables_filter { @apply hidden; }
    .dataTables_wrapper .dataTables_length { @apply hidden; }
    table.dataTable.no-footer { border-bottom: none !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 bg-[#f8fafc]">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>BDM</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-indigo-600">Visit Report</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BDM Visit Status</h1>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-panel rounded-[2rem] p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDM User</label>
                <select id="filter_user_id" class="w-full filter-input">
                    <option value="">All BDMs</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">From Date</label>
                <input type="date" id="filter_from_date" value="{{ date('Y-m-d') }}" class="w-full filter-input">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">To Date</label>
                <input type="date" id="filter_to_date" value="{{ date('Y-m-d') }}" class="w-full filter-input">
            </div>
            <div class="flex items-end">
                <button id="apply-filters" class="w-full py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20 active:scale-95 transition-all">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="glass-panel rounded-[2.5rem] overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="bdm-report-table" class="w-full">
                <thead>
                    <tr class="text-left">
                        <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">BDM Name</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Visited Name</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Visit Type</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Food Allowance</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Work Type</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">In Time</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Out Time</th>
                        <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#bdm-report-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('bdm.visit-report.data') }}",
                data: function (d) {
                    d.user_id = $('#filter_user_id').val();
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
            columns: [
                { data: 'visit_date', name: 'visit_date' },
                { data: 'user.name', name: 'user.name' },
                { data: 'target_name', name: 'target_name', orderable: false, searchable: false },
                { data: 'visit_type_label', name: 'visit_type_label', orderable: false },
                { 
                    data: 'type', 
                    render: function(data) {
                        const style = data === 'planned' ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-600';
                        return `<span class="px-2 py-0.5 rounded text-[9px] font-black uppercase ${style}">${data}</span>`;
                    }
                },
                { data: 'food_label', name: 'food_label', orderable: false },
                { data: 'work_type_label', name: 'work_type_label', orderable: false },
                { data: 'intime_time', name: 'intime_time' },
                { data: 'out_time', name: 'out_time' },
                { data: 'remarks', name: 'remarks' }
            ],
            language: {
                processing: '<div class="flex items-center justify-center p-4"><span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span></div>'
            }
        });

        $('#apply-filters').click(function() {
            table.draw();
        });
    });
</script>
@endsection
