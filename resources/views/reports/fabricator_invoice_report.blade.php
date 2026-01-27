@extends('layouts.app')

@section('title', 'Fabricator Invoice Report')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
    #report-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md border-blue-600; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc] relative z-0">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Fabricator Invoices</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fabricator Invoice Report</h1>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="glass-panel rounded-none p-5 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                <select id="filter_zone_id" class="form-input-custom">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone Manager</label>
                <select id="filter_zsm_id" class="form-input-custom">
                    <option value="">Select ZSM</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Manager</label>
                <select id="filter_bdm_id" class="form-input-custom">
                    <option value="">Select BDM</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Fabricator</label>
                <select id="filter_fabricator_id" class="form-input-custom">
                    <option value="">All Fabricators</option>
                    @foreach($fabricators as $fab)
                        <option value="{{ $fab->id }}">{{ $fab->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">From Date</label>
                <input type="date" id="filter_from_date" class="form-input-custom" value="{{ date('Y-m-01') }}">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">To Date</label>
                <input type="date" id="filter_to_date" class="form-input-custom" value="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button id="btn_reset" class="px-4 py-2 bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95">
                Reset Filters
            </button>
            <button id="btn_filter" class="px-8 h-[42px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                <span class="material-symbols-outlined text-[18px]">refresh</span> Apply Filters
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-none overflow-hidden relative z-10">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 id="detail-title" class="text-lg font-black text-slate-800 tracking-tight">Invoice Records</h2>
                    <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Records: 0</p>
                </div>
                <div id="report-pagination" class="flex items-center gap-0.5"></div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs" id="invoice-report-table">
                    <thead>
                        <tr class="text-left border-b border-slate-100">
                            <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Fabricator</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Inv No</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Original Inv</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Amount</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Debit</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="font-bold text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        const table = $('#invoice-report-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('fabricator.report.invoices.data') }}",
                data: function (d) {
                    d.zone_id = $('#filter_zone_id').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.bdm_id = $('#filter_bdm_id').val();
                    d.fabricator_id = $('#filter_fabricator_id').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('hover:bg-slate-50 transition-colors');
                $(row).find('td').addClass('py-3 px-4 border-b border-slate-50');
            },
            columns: [
                { data: 'invoice_date', name: 'invoice_date' },
                { data: 'shop_name', name: 'shop_name', orderable: false },
                { 
                    data: 'invoice_type', 
                    name: 'invoice_type',
                    render: function(data) {
                        let cls = 'bg-slate-100 text-slate-600';
                        if (data === 'INVOICE') cls = 'bg-blue-100 text-blue-600';
                        if (data === 'CREDI NOTE') cls = 'bg-emerald-100 text-emerald-600';
                        if (data === 'CANCEL') cls = 'bg-rose-100 text-rose-600';
                        return `<span class='px-2 py-0.5 rounded-[4px] text-[9px] font-black uppercase tracking-wider ${cls}'>${data}</span>`;
                    }
                },
                { data: 'invoice_no', name: 'invoice_no' },
                { 
                    data: 'original_invoice_no', 
                    name: 'original_invoice_no',
                    render: function(data) {
                        return data ? `<span class="text-slate-400 font-medium">${data}</span>` : `<span class="text-slate-300">-</span>`;
                    }
                },
                { data: 'amount', name: 'amount', className: 'text-right' },
                { data: 'debit', name: 'debit', className: 'text-right text-blue-600' },
                { data: 'credit', name: 'credit', className: 'text-right text-emerald-600' },
            ],
            order: [[0, 'desc']],
            dom: 'rtp',
            pageLength: 25,
            drawCallback: function(settings) {
                const total = settings.json ? settings.json.recordsTotal : 0;
                $('#table-info').text(`Total Records: ${total}`);
                $('#report-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
            }
        });

        // Hierarchical Filters
        $('#filter_zone_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_zsm_id', 'Select ZSM');
            resetSelect('#filter_bdm_id', 'Select BDM');
            resetSelect('#filter_fabricator_id', 'All Fabricators');
            if (id) fetchLocationData('zone', id);
        });

        $('#filter_zsm_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_bdm_id', 'Select BDM');
            resetSelect('#filter_fabricator_id', 'All Fabricators');
            if (id) fetchLocationData('zsm', id);
        });

        $('#filter_bdm_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_fabricator_id', 'All Fabricators');
            if (id) fetchLocationData('bdm', id);
        });

        function resetSelect(selector, defaultText) {
            $(selector).html(`<option value="">${defaultText}</option>`);
        }

        async function fetchLocationData(type, id) {
            try {
                const response = await fetch(`{{ route('fabricator.report.accounting.location-data') }}?type=${type}&id=${id}`);
                const data = await response.json();
                if (type === 'zone' && data.zsms) populateOptions('#filter_zsm_id', data.zsms, 'Select ZSM');
                else if (type === 'zsm' && data.bdms) populateOptions('#filter_bdm_id', data.bdms, 'Select BDM');
                if (data.fabricators) populateOptions('#filter_fabricator_id', data.fabricators, 'All Fabricators');
            } catch (e) { console.error('Fetch error', e); }
        }

        function populateOptions(selector, items, defaultText) {
            let options = `<option value="">${defaultText}</option>`;
            items.forEach(item => { options += `<option value="${item.id}">${item.name}</option>`; });
            $(selector).html(options);
        }

        $('#btn_filter').click(() => table.draw());
        $('#btn_reset').click(function() {
            $('#filter_zone_id, #filter_zsm_id, #filter_bdm_id, #filter_fabricator_id').val('');
            $('#filter_from_date').val("{{ date('Y-m-01') }}");
            $('#filter_to_date').val("{{ date('Y-m-d') }}");
            table.draw();
        });
    });
</script>
@endsection
