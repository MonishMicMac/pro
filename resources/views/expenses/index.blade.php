@extends('layouts.app')

@section('title', 'Users Expense')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style>
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
    }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc]">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Dashboard</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">User Expenses</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">User Expenses</h1>
        </div>
        
        <!-- Filters -->
        <div class="flex items-center gap-2">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-slate-400">person</span>
                </div>
                <select id="userFilter" class="pl-10 pr-8 py-2 bg-white border-0 ring-1 ring-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer hover:shadow-md">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            
             <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-slate-400">calendar_month</span>
                </div>
                 <input type="date" id="fromDate" class="pl-10 pr-3 py-2 bg-white border-0 ring-1 ring-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all hover:shadow-md" placeholder="From Date">
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <span class="material-symbols-outlined text-slate-400">calendar_month</span>
                </div>
                <input type="date" id="toDate" class="pl-10 pr-3 py-2 bg-white border-0 ring-1 ring-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all hover:shadow-md" placeholder="To Date">
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="overflow-x-auto">
            <table id="expensesTable" class="w-full">
                <thead class="bg-slate-50/50">
                    <tr class="text-left">
                        <th class="pl-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                         <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Expense Type</th>
                         <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Proof</th>
                         <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Amount</th>
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Status</th>
                         <th class="pr-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <!-- Data will be populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#expensesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('expenses.data') }}",
                data: function (d) {
                    d.user_id = $('#userFilter').val();
                    d.from_date = $('#fromDate').val();
                    d.to_date = $('#toDate').val();
                }
            },
            columns: [
                { data: 'expense_date', name: 'expense_date', class: 'pl-6 py-3 text-xs font-bold text-slate-700' },
                { data: 'user_name', name: 'user.name', class: 'px-4 py-3 text-xs font-bold text-slate-700' },
                 { data: 'expense_type_name', name: 'expenseType.name', class: 'px-4 py-3 text-xs font-bold text-slate-700' },
                 { data: 'proof', name: 'proof', class: 'px-4 py-3 text-center', orderable: false, searchable: false },
                { 
                    data: 'expense_amount', 
                    name: 'expense_amount', 
                    class: 'px-4 py-3 text-sm font-black text-slate-800 text-right',
                    render: function(data, type, row) {
                        return '₹ ' + parseFloat(data).toFixed(2);
                    }
                },
                 { data: 'action', name: 'action', class: 'px-4 py-3 text-center' },
                 { data: 'created_at', name: 'created_at', class: 'pr-6 py-3 text-[10px] font-bold text-slate-400 text-right' },
            ],
            language: {
                processing: '<div class="flex items-center justify-center p-4"><div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div></div>',
                emptyTable: '<div class="flex flex-col items-center justify-center py-8 text-slate-400"><span class="material-symbols-outlined text-[32px] mb-2">savings</span><p class="text-xs font-bold uppercase tracking-wider">No expenses found</p></div>',
                zeroRecords: '<div class="flex flex-col items-center justify-center py-8 text-slate-400"><span class="material-symbols-outlined text-[32px] mb-2">search_off</span><p class="text-xs font-bold uppercase tracking-wider">No matching records found</p></div>'
            },
            dom: '<"p-4"rt><"px-6 py-4 border-t border-slate-100 flex items-center justify-between"ip>',
            drawCallback: function() {
                $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 rounded-lg text-xs font-bold mx-1 hover:bg-slate-100 transition-all');
                $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-50 text-blue-600');
            }
        });

        $('#userFilter, #fromDate, #toDate').change(function() {
            table.draw();
        });
    });
</script>
@endsection
