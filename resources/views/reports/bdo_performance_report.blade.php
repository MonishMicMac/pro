@extends('layouts.app')

@section('title', 'BDO Performance Report')

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
        .report-header-cell {
            @apply px-2 py-2 text-[10px] font-black text-slate-500 uppercase tracking-wider text-center border font-extrabold bg-slate-100;
        }
        .report-data-cell {
            @apply px-2 py-3 text-xs font-bold text-slate-700 text-center border bg-white;
        }
    }
    table.dataTable { border-collapse: collapse !important; width: 100% !important; }
    table.dataTable thead th { border-bottom: none !important; }
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
    <div class="glass-panel rounded-none p-5 relative z-20">
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
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">From Date</label>
                <input type="date" id="filter_from_date" class="form-input-custom" value="{{ date('Y-m-01') }}">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">To Date</label>
                <input type="date" id="filter_to_date" class="form-input-custom" value="{{ date('Y-m-t') }}">
            </div>
             <div class="flex items-end">
                <button id="btn_filter" class="w-full h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-[18px]">refresh</span> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-none overflow-hidden relative z-10 p-2">
        <div class="overflow-x-auto">
            <table class="w-full border border-slate-200" id="bdo-report-table">
                <thead>
                    <!-- Top Header Row -->
                    <tr>
                        <th colspan="5" class="bg-blue-50 border p-2 text-center text-xs font-black text-blue-800 uppercase tracking-widest" id="zone-header">ZONE: ALL</th>
                        <th colspan="5" class="bg-white border p-2 text-center text-xs font-black text-slate-600 uppercase tracking-widest">Monthly Performance Report</th>
                        <th colspan="9" class="bg-orange-50 border p-2 text-center text-xs font-black text-orange-800 uppercase tracking-widest" id="date-header">Date Range</th>
                    </tr>
                    <!-- Group Header Row -->
                    <tr>
                        <th colspan="5" class="bg-slate-200 border"></th> <!-- Spacer for basic info -->
                        <th colspan="5" class="report-header-cell bg-orange-100/50 text-orange-900">MEETINGS</th>
                        <th colspan="2" class="report-header-cell bg-orange-200/50 text-orange-900">QUOTATIONS</th>
                        <th colspan="4" class="report-header-cell bg-orange-200/50 text-orange-900">QUOTE WON</th>
                        <th colspan="5" class="bg-slate-200 border"></th> <!-- Spacer for metrics -->
                    </tr>
                    <!-- Column Header Row -->
                    <tr>
                        <th class="report-header-cell bg-teal-100/30">S.No</th>
                        <th class="report-header-cell bg-teal-100/30">Executive Name</th>
                        <th class="report-header-cell bg-teal-100/30">Base City</th>
                        <th class="report-header-cell bg-teal-100/30">DOJ</th>
                        <th class="report-header-cell bg-teal-100/30">Designation</th>
                        
                        <th class="report-header-cell">HO Lead Assigned</th>
                        <th class="report-header-cell">Own Lead Gen</th>
                        <th class="report-header-cell">New (Intro)</th>
                        <th class="report-header-cell">Follow up</th>
                        <th class="report-header-cell font-black bg-slate-200">TOTAL</th>

                        <th class="report-header-cell">Quote Given</th>
                        <th class="report-header-cell">Total Sqft</th>

                        <th class="report-header-cell">Quote</th>
                        <th class="report-header-cell">White</th>
                        <th class="report-header-cell">Laminate</th>
                        <th class="report-header-cell">Total Sqft</th>

                        <th class="report-header-cell">HO Lead Won</th>
                        <th class="report-header-cell">Pipe Line Sq.Ft</th>
                        <th class="report-header-cell">Google Review</th>
                        <th class="report-header-cell">Working Days</th>
                        <th class="report-header-cell">Remarks</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="font-black text-xs text-slate-800 bg-slate-100">
                        <td colspan="5" class="text-right p-3">Total</td>
                        <td id="t-ho-assigned" class="text-center p-3">0</td>
                        <td id="t-own-gen" class="text-center p-3">0</td>
                        <td id="t-new" class="text-center p-3">0</td>
                        <td id="t-followup" class="text-center p-3">0</td>
                        <td id="t-total-meet" class="text-center p-3">0</td>
                        <td id="t-quote-given" class="text-center p-3">0</td>
                        <td id="t-quote-sqft" class="text-center p-3">0</td>
                        <td id="t-won-quote" class="text-center p-3">0</td>
                        <td id="t-won-white" class="text-center p-3">0</td>
                        <td id="t-won-lam" class="text-center p-3">0</td>
                        <td id="t-won-sqft" class="text-center p-3">0</td>
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
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                }
            },
            paging: false,
            searching: false,
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, className: 'report-data-cell bg-slate-50' },
                { data: 'name', name: 'name', className: 'report-data-cell font-black' },
                { data: 'base_city', name: 'base_city', className: 'report-data-cell' },
                { data: 'doj', name: 'doj', className: 'report-data-cell' },
                { data: 'designation', name: 'designation', className: 'report-data-cell' },
                
                { data: 'ho_lead_assigned', name: 'ho_lead_assigned', className: 'report-data-cell' },
                { data: 'own_lead_generation', name: 'own_lead_generation', className: 'report-data-cell' },
                { data: 'intro_meeting', name: 'intro_meeting', className: 'report-data-cell' },
                { data: 'follow_up_meeting', name: 'follow_up_meeting', className: 'report-data-cell' },
                { data: 'total_meetings', name: 'total_meetings', className: 'report-data-cell bg-slate-100 font-black' },
                
                { data: 'quote_given', name: 'quote_given', className: 'report-data-cell' },
                { data: 'quote_total_sqft', name: 'quote_total_sqft', className: 'report-data-cell' },
                
                { data: 'won_quote', name: 'won_quote', className: 'report-data-cell' },
                { data: 'won_white', name: 'won_white', className: 'report-data-cell' },
                { data: 'won_laminate', name: 'won_laminate', className: 'report-data-cell' },
                { data: 'won_total_sqft', name: 'won_total_sqft', className: 'report-data-cell' },
                
                { data: 'ho_lead_won', name: 'ho_lead_won', className: 'report-data-cell' },
                { data: 'pipeline_sqft', name: 'pipeline_sqft', className: 'report-data-cell' },
                { data: 'google_review', name: 'google_review', className: 'report-data-cell' },
                { data: 'working_days', name: 'working_days', className: 'report-data-cell' },
                { defaultContent: '', className: 'report-data-cell' } // Remarks
            ],
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
                $('#t-won-white').text(sumColumn(13));
                $('#t-won-lam').text(sumColumn(14));
                $('#t-won-sqft').text(sumColumn(15).toFixed(0));
                $('#t-ho-won').text(sumColumn(16));
                $('#t-pipeline').text(sumColumn(17).toFixed(0));
            }
        });

        $('#btn_filter').click(function() { table.draw(); });
    });
</script>
@endsection
