@extends('layouts.app')

@section('title', 'Fabricator Performance Report')

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
        .stat-card {
            @apply glass-panel p-6 cursor-pointer hover:border-blue-400 hover:shadow-md active:scale-[0.98];
        }
        .stat-card.active {
            @apply border-blue-600 bg-blue-50/30 ring-2 ring-blue-500/10;
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
                <span class="text-blue-600">Fabricator Performance</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fabricator Status Report</h1>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div data-status="quote_given" class="stat-card active rounded-none border-l-4 border-l-slate-400">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Quote Given</p>
            <div class="flex items-end justify-between">
                <h3 id="stat-quote-given" class="text-3xl font-black text-slate-900">0</h3>
                <span id="stat-quote-sqft" class="text-xs font-bold text-slate-400">0.00 Sqft</span>
            </div>
        </div>
        <div data-status="won" class="stat-card rounded-none border-l-4 border-l-indigo-500">
            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Won</p>
            <div class="flex items-end justify-between">
                <h3 id="stat-won" class="text-3xl font-black text-indigo-600">0</h3>
                <span id="stat-won-sqft" class="text-xs font-bold text-indigo-400">0.00 Sqft</span>
            </div>
        </div>
        <div data-status="completed" class="stat-card rounded-none border-l-4 border-l-emerald-500">
            <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Completed Sites</p>
            <div class="flex items-end justify-between">
                <h3 id="stat-completed" class="text-3xl font-black text-emerald-600">0</h3>
                <span id="stat-completed-sqft" class="text-xs font-bold text-emerald-400">0.00 Sqft</span>
            </div>
        </div>
        <div data-status="pending" class="stat-card rounded-none border-l-4 border-l-amber-500">
            <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-1">Pending Sites</p>
            <div class="flex items-end justify-between">
                <h3 id="stat-pending" class="text-3xl font-black text-amber-600">0</h3>
                <span id="stat-pending-sqft" class="text-xs font-bold text-amber-400">0.00 Sqft</span>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-none overflow-hidden relative z-10">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 id="detail-title" class="text-lg font-black text-slate-800 tracking-tight">Records</h2>
                    <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Records: 0</p>
                </div>
                <div id="report-pagination" class="flex items-center gap-0.5"></div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full" id="fabricator-report-table">
                    <thead>
                        <tr class="text-left">
                            <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead Name</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Fabricator</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">BDO Name</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Sqft</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Install Date</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    let currentFilter = 'quote_given';

    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#fabricator-report-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('fabricator.report.data') }}",
                data: function (d) {
                    d.zone_id = $('#filter_zone_id').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.bdm_id = $('#filter_bdm_id').val();
                    d.fabricator_id = $('#filter_fabricator_id').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                    d.status_filter = currentFilter;
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card hover:bg-slate-50 transition-colors');
                $(row).find('td').addClass('py-3 px-4 border-b border-slate-100');
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'fab_name', name: 'fab_name', orderable: false },
                { data: 'bdo_name', name: 'bdo_name' },
                { 
                    data: 'total_required_area_sqft', 
                    name: 'total_required_area_sqft', 
                    className: 'text-center',
                    render: function(data) { return (Number(data) || 0).toFixed(2); }
                },
                { data: 'lead_stage', name: 'lead_stage', orderable: false },
                { data: 'installation_date_label', name: 'installed_date', orderable: true }
            ],
            order: [[0, 'asc']],
            dom: 'rtp',
            pageLength: 20,
            drawCallback: function(settings) {
                const total = settings.json ? settings.json.recordsTotal : 0;
                $('#table-info').text(`Total Records: ${total}`);
                $('#report-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
            }
        });

        // Hierarchical Filtering Logic
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
                const response = await fetch(`{{ route('fabricator.report.location-data') }}?type=${type}&id=${id}`);
                const data = await response.json();
                
                // 1. Update Hierarchical User Dropdowns
                if (type === 'zone' && data.zsms) {
                    populateOptions('#filter_zsm_id', data.zsms, 'Select ZSM');
                } else if (type === 'zsm' && data.bdms) {
                    populateOptions('#filter_bdm_id', data.bdms, 'Select BDM');
                }

                // 2. Update Fabricator Dropdown (at all levels)
                if (data.fabricators) {
                    populateOptions('#filter_fabricator_id', data.fabricators, 'All Fabricators');
                }
            } catch (e) { console.error('Fetch error', e); }
        }

        function populateOptions(selector, items, defaultText) {
            let options = `<option value="">${defaultText}</option>`;
            items.forEach(item => {
                options += `<option value="${item.id}">${item.name || item.shop_name}</option>`;
            });
            $(selector).html(options);
        }

        // Fetch and Update Summary Cards
        async function fetchSummary() {
            const params = new URLSearchParams({
                zone_id: $('#filter_zone_id').val(),
                zsm_id: $('#filter_zsm_id').val(),
                bdm_id: $('#filter_bdm_id').val(),
                fabricator_id: $('#filter_fabricator_id').val(),
                from_date: $('#filter_from_date').val(),
                to_date: $('#filter_to_date').val()
            });

            try {
                const response = await fetch("{{ route('fabricator.report.summary') }}?" + params);
                const data = await response.json();
                if (data.status) {
                    const s = data.summary;
                    $('#stat-quote-given').text(s.quote_given);
                    $('#stat-quote-sqft').text(s.quote_sqft.toFixed(2) + ' Sqft');
                    $('#stat-won').text(s.won);
                    $('#stat-won-sqft').text(s.won_sqft.toFixed(2) + ' Sqft');
                    $('#stat-completed').text(s.completed);
                    $('#stat-completed-sqft').text(s.completed_sqft.toFixed(2) + ' Sqft');
                    $('#stat-pending').text(s.pending);
                    $('#stat-pending-sqft').text(s.pending_sqft.toFixed(2) + ' Sqft');
                }
            } catch (e) { console.error('Summary fetch failed', e); }
        }

        // Action Handlers
        $('#btn_filter').click(function() {
            table.draw();
            fetchSummary();
        });

        $('#btn_reset').click(function() {
            $('#filter_zone_id, #filter_zsm_id, #filter_bdm_id, #filter_fabricator_id').val('');
            $('#filter_from_date').val("{{ date('Y-m-01') }}");
            $('#filter_to_date').val("{{ date('Y-m-d') }}");
            table.draw();
            fetchSummary();
        });

        $('.stat-card').click(function() {
            $('.stat-card').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('status');
            
            const titles = {
                quote_given: 'Quotation Records',
                won: 'Won Lead Records',
                completed: 'Completed Installation Records',
                pending: 'Pending Installation Records'
            };
            $('#detail-title').text(titles[currentFilter]);
            
            table.draw();
        });

        // Initial Load
        fetchSummary();
    });
</script>
@endsection
