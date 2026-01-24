@extends('layouts.app')

@section('title', 'BDO Performance Report')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 20px 0 rgba(31, 38, 135, 0.05);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            @apply transition-all duration-200;
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.9);
            @apply shadow-md transform -translate-y-0.5;
        }
        .form-input-custom {
            @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer;
        }
    }
    
    #report-pagination .paginate_button {
        @apply px-2 py-1 mx-0.5 rounded-md border-none bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px];
    }
    #report-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md shadow-blue-500/30; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc] relative z-0">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Performance Analytics</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BDO Performance Report</h1>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="glass-panel rounded-[1.5rem] p-5 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">BDO</label>
                <select id="filter_bdo_id" class="form-input-custom">
                    <option value="">Select BDO</option>
                </select>
            </div>
             <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">State</label>
                <select id="filter_state_id" class="form-input-custom">
                    <option value="">Select State</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                <select id="filter_district_id" class="form-input-custom">
                    <option value="">Select District</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">City</label>
                <select id="filter_city_id" class="form-input-custom">
                    <option value="">Select City</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                 <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">From Date</label>
                    <input type="date" id="filter_from_date" class="form-input-custom" value="{{ date('Y-m-01') }}">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">To Date</label>
                    <input type="date" id="filter_to_date" class="form-input-custom" value="{{ date('Y-m-t') }}">
                </div>
            </div>
           
            <div class="flex items-end justify-between md:col-span-4 mt-2">
                <div class="relative w-64">
                   <input type="text" id="custom-search" placeholder="Search..." class="bg-white/50 border-slate-200 rounded-xl text-xs font-bold text-slate-600 placeholder:text-slate-300 w-full pr-10 focus:ring-blue-500">
                   <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-300 text-lg">search</span>
               </div>
                <button id="btn_filter" class="w-48 h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-[18px]">refresh</span> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-[1.5rem] overflow-hidden relative z-10">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
             <div>
               <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Records: 0</p>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="bdo-report-table">
                <thead>
                    <!-- Top Header Row -->
                    <tr>
                         <th colspan="5" class="pb-2 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest" id="zone-header">ZONE: ALL</th>
                        <th colspan="5" class="pb-2 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Monthly Performance</th>
                        <th colspan="9" class="pb-2 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest" id="date-header">Date Range</th>
                    </tr>
                    <!-- Group Header Row -->
                    <tr>
                        <th colspan="5"></th>
                        <th colspan="5" class="pb-2 text-center text-[10px] font-black text-blue-600 bg-blue-50/50 uppercase tracking-widest rounded-t-xl mx-1 border-x border-t border-blue-100/50">MEETINGS</th>
                        <th colspan="2" class="pb-2 text-center text-[10px] font-black text-orange-600 bg-orange-50/50 uppercase tracking-widest rounded-t-xl mx-1 border-x border-t border-orange-100/50">QUOTATIONS</th>
                        <th colspan="4" class="pb-2 text-center text-[10px] font-black text-green-600 bg-green-50/50 uppercase tracking-widest rounded-t-xl mx-1 border-x border-t border-green-100/50">QUOTE WON</th>
                        <th colspan="5"></th>
                    </tr>
                    <!-- Column Header Row -->
                    <tr class="text-left">
                        <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">S.No</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Executive</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Base City</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">DOJ</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Role</th>
                        
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-blue-50/30 uppercase tracking-wider text-center border-l border-blue-100/50">HO Leads</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-blue-50/30 uppercase tracking-wider text-center">Own Leads</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-blue-50/30 uppercase tracking-wider text-center">New</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-blue-50/30 uppercase tracking-wider text-center">F/Up</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-blue-600 bg-blue-50/50 uppercase tracking-wider text-center border-r border-blue-100/50">TOTAL</th>

                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-orange-50/30 uppercase tracking-wider text-center border-l border-orange-100/50">Given</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-orange-600 bg-orange-50/50 uppercase tracking-wider text-center border-r border-orange-100/50">Sqft</th>

                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-green-50/30 uppercase tracking-wider text-center border-l border-green-100/50">Count</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-green-50/30 uppercase tracking-wider text-center">White</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-500 bg-green-50/30 uppercase tracking-wider text-center">Lam</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-green-600 bg-green-50/50 uppercase tracking-wider text-center border-r border-green-100/50">Total Sqft</th>

                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">HO Won</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Pipeline</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Review</th>
                        <th class="px-2 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Days</th>
                        <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
                <tfoot>
                    <tr class="font-black text-xs text-slate-800 bg-slate-50/50">
                        <td colspan="5" class="text-right p-3 uppercase tracking-wider text-[10px]">Total</td>
                        <td id="t-ho-assigned" class="text-center p-3 bg-blue-50/30 text-slate-600">0</td>
                        <td id="t-own-gen" class="text-center p-3 bg-blue-50/30 text-slate-600">0</td>
                        <td id="t-new" class="text-center p-3 bg-blue-50/30 text-slate-600">0</td>
                        <td id="t-followup" class="text-center p-3 bg-blue-50/30 text-slate-600">0</td>
                        <td id="t-total-meet" class="text-center p-3 bg-blue-100/50 text-blue-700">0</td>
                        <td id="t-quote-given" class="text-center p-3 bg-orange-50/30 text-slate-600">0</td>
                        <td id="t-quote-sqft" class="text-center p-3 bg-orange-100/50 text-orange-700">0</td>
                        <td id="t-won-quote" class="text-center p-3 bg-green-50/30 text-slate-600">0</td>
                        <td id="t-won-white" class="text-center p-3 bg-green-50/30 text-slate-600">0</td>
                        <td id="t-won-lam" class="text-center p-3 bg-green-50/30 text-slate-600">0</td>
                        <td id="t-won-sqft" class="text-center p-3 bg-green-100/50 text-green-700">0</td>
                        <td id="t-ho-won" class="text-center p-3">0</td>
                        <td id="t-pipeline" class="text-center p-3">0</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        const table = $('#bdo-report-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('bdo_performance.report.data') }}",
                data: function (d) {
                    d.zone_id = $('#filter_zone_id').val();
                    d.state_id = $('#filter_state_id').val();
                    d.district_id = $('#filter_district_id').val();
                    d.city_id = $('#filter_city_id').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.bdm_id = $('#filter_bdm_id').val();
                    d.bdo_id = $('#filter_bdo_id').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
                $(row).find('td').not(':first').not(':last').addClass('py-2 text-center');
            },
            paging: true,
            searching: true,
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, className: 'text-center' },
                { data: 'name', name: 'name', className: 'font-black' },
                { data: 'base_city', name: 'base_city' },
                { data: 'doj', name: 'doj' },
                { data: 'designation', name: 'designation' },
                
                { data: 'ho_lead_assigned', name: 'ho_lead_assigned', className: 'bg-blue-50/30' },
                { data: 'own_lead_generation', name: 'own_lead_generation', className: 'bg-blue-50/30' },
                { data: 'intro_meeting', name: 'intro_meeting', className: 'bg-blue-50/30' },
                { data: 'follow_up_meeting', name: 'follow_up_meeting', className: 'bg-blue-50/30' },
                { data: 'total_meetings', name: 'total_meetings', className: 'font-black text-blue-600 bg-blue-50/50' },
                
                { data: 'quote_given', name: 'quote_given', className: 'bg-orange-50/30' },
                { data: 'quote_total_sqft', name: 'quote_total_sqft', className: 'font-black text-orange-600 bg-orange-50/50' },
                
                { data: 'won_quote', name: 'won_quote', className: 'bg-green-50/30' },
                { data: 'won_white', name: 'won_white', className: 'bg-green-50/30' },
                { data: 'won_laminate', name: 'won_laminate', className: 'bg-green-50/30' },
                { data: 'won_total_sqft', name: 'won_total_sqft', className: 'font-black text-green-600 bg-green-50/50' },
                
                { data: 'ho_lead_won', name: 'ho_lead_won' },
                { data: 'pipeline_sqft', name: 'pipeline_sqft' },
                { data: 'google_review', name: 'google_review' },
                { data: 'working_days', name: 'working_days' },
                { defaultContent: '' } // Remarks
            ],
            dom: 'rtp',
            drawCallback: function(settings) {
                // Update Header
                const zoneText = $('#filter_zone_id option:selected').text();
                $('#zone-header').text('ZONE : ' + (zoneText === 'All Zones' ? 'ALL' : zoneText));
                
                const fromDate = new Date($('#filter_from_date').val()).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                const toDate = new Date($('#filter_to_date').val()).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                $('#date-header').text(fromDate + ' to ' + toDate);

                // Update Footer Totals (Client-side calculation for visible page)
                let api = this.api();
                
                const sumColumn = (idx) => {
                    return api.column(idx, {page:'current'}).data().reduce(function(a, b) {
                        return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                    }, 0);
                };

                $('#t-ho-assigned').text(sumColumn(5));
                $('#t-own-gen').text(sumColumn(6));
                $('#t-new').text(sumColumn(7));
                $('#t-followup').text(sumColumn(8));
                $('#t-total-meet').text(sumColumn(9));
                $('#t-quote-given').text(sumColumn(10));
                $('#t-quote-sqft').text(sumColumn(11).toFixed(0)); // Sqft as integer
                $('#t-won-quote').text(sumColumn(12));
                $('#t-won-white').text(sumColumn(13).toFixed(2));
                $('#t-won-lam').text(sumColumn(14).toFixed(2));
                $('#t-won-sqft').text(sumColumn(15).toFixed(0));
                $('#t-ho-won').text(sumColumn(16));
                $('#t-pipeline').text(sumColumn(17).toFixed(0));

                const total = settings.json ? settings.json.recordsTotal : 0;
                $('#table-info').text(`Total Records: ${total}`);
                $('.dataTables_paginate').appendTo('#table-pagination');
                $('#report-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
            }
        });

        $('#custom-search').on('keyup input', function() {
            table.search($(this).val()).draw();
        });

        // Hierarchical Filtering Logic
        $('#filter_zone_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_state_id', 'Select State');
            resetSelect('#filter_district_id', 'Select District');
            resetSelect('#filter_city_id', 'Select City');
            resetSelect('#filter_zsm_id', 'Select ZSM');
            resetSelect('#filter_bdm_id', 'Select BDM');
            resetSelect('#filter_bdo_id', 'Select BDO');
            if (id) fetchLocationData('zone', id);
        });

        $('#filter_state_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_district_id', 'Select District');
            resetSelect('#filter_city_id', 'Select City');
            if (id) fetchLocationData('state', id);
        });

        $('#filter_district_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_city_id', 'Select City');
            if (id) fetchLocationData('district', id);
        });

        $('#filter_zsm_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_bdm_id', 'Select BDM');
            resetSelect('#filter_bdo_id', 'Select BDO');
            if (id) fetchLocationData('zsm', id);
        });

        $('#filter_bdm_id').change(function() {
            const id = $(this).val();
            resetSelect('#filter_bdo_id', 'Select BDO');
            if (id) fetchLocationData('bdm', id);
        });

        function resetSelect(selector, defaultText) {
            $(selector).html(`<option value="">${defaultText}</option>`);
        }

        async function fetchLocationData(type, id) {
            try {
                const response = await fetch(`{{ route('bdo_performance.report.location-data') }}?type=${type}&id=${id}`);
                const data = await response.json();
                
                if (type === 'zone') {
                    if (data.states) populateOptions('#filter_state_id', data.states, 'Select State');
                    if (data.zsms) populateOptions('#filter_zsm_id', data.zsms, 'Select ZSM');
                } else if (type === 'state' && data.districts) {
                    populateOptions('#filter_district_id', data.districts, 'Select District');
                } else if (type === 'district' && data.cities) {
                    populateOptions('#filter_city_id', data.cities, 'Select City');
                } else if (type === 'zsm' && data.bdms) {
                    populateOptions('#filter_bdm_id', data.bdms, 'Select BDM');
                } else if (type === 'bdm' && data.bdos) {
                    populateOptions('#filter_bdo_id', data.bdos, 'Select BDO');
                }
            } catch (e) { console.error('Fetch error', e); }
        }

        function populateOptions(selector, items, defaultText) {
            let options = `<option value="">${defaultText}</option>`;
            items.forEach(item => {
                options += `<option value="${item.id}">${item.name}</option>`;
            });
            $(selector).html(options);
        }

        $('#btn_filter').click(function() { table.draw(); });
    });
</script>
@endsection
