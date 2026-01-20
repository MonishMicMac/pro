@extends('layouts.app')
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
            transform: translateY(-1px);
        }
    }
    #table-pagination .paginate_button {
        @apply px-2 py-1 mx-0.5 rounded-md border-none bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px];
    }
    #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md shadow-blue-500/30; }

    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; width: 100% !important; }

    /* Ensure scrollbar doesn't shift layout */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<div class="h-screen flex flex-col bg-[#f8fafc] overflow-hidden relative">

    <div class="p-5 pb-2 relative z-5">
        <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
            <span>Marketing</span>
            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
            <span class="text-blue-600">Leads Tracking</span>
        </nav>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Lead Status Management</h1>
    </div>

    <div class="flex-1 overflow-hidden p-5 pt-0 relative z-5">
        <div class="glass-panel rounded-[1.5rem] flex flex-col overflow-hidden">

            <div class="p-4 bg-white/30 border-b border-white/50 space-y-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative w-full max-w-xs">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input id="customSearch" class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Search leads..." type="text"/>
                    </div>

                    <div class="w-44">
                        <select id="filter-stage" class="w-full px-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">All Stages</option>
                            @foreach($leadStages as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-blue-500 uppercase pointer-events-none">Future:</span>
                        <input type="date" id="filter-future-date" class="pl-16 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-[10px] font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20"/>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-amber-600 uppercase pointer-events-none">Potential:</span>
                        <input type="date" id="filter-potential-date" class="pl-20 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-[10px] font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20"/>
                    </div>

                    {{-- New Filter Action Button --}}
                    <button id="btn-apply-filter" class="px-4 py-1.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all flex items-center gap-2 shadow-lg shadow-slate-200">
                        <span class="material-symbols-outlined text-[16px]">filter_list</span> Filter
                    </button>

                    <button id="reset-filters" class="flex items-center gap-1 text-[10px] font-black text-slate-400 hover:text-rose-500 uppercase tracking-widest transition-colors">
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span> Reset
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-4 custom-scrollbar">
                <table class="w-full" id="leads-table">
                    <thead>
                        <tr class="text-left sticky top-0 bg-[#f8fafc] z-20">
                            <th class="pl-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox"/></th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Phone</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Stage</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-blue-500">Future Follow Up</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-amber-600">Potential Follow Up</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Assigned</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Zone</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Status</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Lead Details</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-bold text-slate-700"></tbody>
                </table>
            </div>

            <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
                <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase tracking-widest"></p>
                <div id="table-pagination" class="flex items-center gap-0.5"></div>
            </div>
        </div>
    </div>


{{-- Floating Bar --}}
<div id="floating-bar" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[50] bg-slate-900 rounded-2xl px-5 py-2.5 flex items-center gap-5 shadow-2xl transition-all duration-500 translate-y-32 opacity-0 invisible pointer-events-none">
    <div class="flex items-center gap-2 text-white border-r border-slate-700 pr-4">
        <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-black" id="selected-count">0</span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
    </div>
    <div class="flex items-center gap-4">
        @can('leads.edit')
        <button id="floating-edit" class="hidden flex items-center gap-1.5 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-bold uppercase tracking-widest">
            <span class="material-symbols-outlined text-[18px]">edit_square</span> Process
        </button>
        @endcan
        @can('leads.delete')
        <button onclick="handleBulkDelete()" class="flex items-center gap-1.5 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-bold uppercase tracking-widest">
            <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Delete
        </button>
        @endcan
    </div>
</div>

{{-- Lead Modal --}}
<div id="LeadModal" class="absolute inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none p-4">
    <div class="modal-content glass-panel w-full max-w-2xl max-h-[90%] overflow-y-auto rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
        <form id="LeadForm">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" id="Lead_id" name="id">

            <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-lg font-black text-slate-800" id="modalTitle">Update Requirements</h3>
                <button type="button" onclick="closeModal('LeadModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Stage</label>
                        <select name="stage" id="stage" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Stage</option>
                            @foreach($leadStages as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div id="disqualified_reason_section" class="hidden">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Disqualified Reason</label>
                        <select name="disqualified_reason" id="disqualified_reason" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Reason</option>
                            <option value="Other location">Other location</option>
                            <option value="Wrong Number">Wrong Number</option>
                            <option value="Did not inquire">Did not inquire</option>
                            <option value="Out of scope">Out of scope</option>
                            <option value="Invalid Number">Invalid Number</option>
                            <option value="Job Inquiry">Job Inquiry</option>
                            <option value="Other Material">Other Material</option>
                        </select>
                    </div>

                    <div id="rnr_reason_section" class="hidden">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">RNR Reason</label>
                        <select name="rnr_reason" id="rnr_reason" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Reason</option>
                            <option value="RNR (Ring No Response)">RNR (Ring No Response)</option>
                            <option value="Switched Off">Switched Off</option>
                            <option value="Wrong Number">Wrong Number</option>
                        </select>
                    </div>
                    <div id="future_follow_up_section" class="hidden md:col-span-3 grid grid-cols-2 gap-4 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                        <div>
                            <label class="block text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Future Follow Up Date</label>
                            <input type="date" name="future_follow_up_date" id="future_follow_up_date" class="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Future Follow Up Time</label>
                            <input type="time" name="future_follow_up_time" id="future_follow_up_time" class="w-full px-3 py-2 bg-white border border-blue-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>
                    </div>

                    <div id="potential_follow_up_section" class="hidden md:col-span-3 grid grid-cols-2 gap-4 bg-amber-50/50 p-3 rounded-xl border border-amber-100">
                        <div>
                            <label class="block text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Potential Follow Up Date</label>
                            <input type="date" name="potential_follow_up_date" id="potential_follow_up_date" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Potential Follow Up Time</label>
                            <input type="time" name="potential_follow_up_time" id="potential_follow_up_time" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer Type</label>
                        <select name="customer_type" id="lead_customer_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Customer Type</option>
                            @foreach($customerTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Colour Choice</label>
                        <input type="text" name="colour" id="lead_colour" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Order Sqft</label>
                        <input type="number" step="0.01" name="total_order_sqft" id="lead_total_order_sqft" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Building Status</label>
                        <select name="building_status" id="lead_building_status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Building Status</option>
                            @foreach($buildingStatuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Building Type</label>
                        <select name="building_type" id="lead_building_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Type</option>
                            @foreach($buildingTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Assigned To</label>
                        <select name="assigned_to" id="lead_assigned_to" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Zone</label>
                        <select name="zone" id="lead_zone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Remarks / Internal Notes</label>
                    <textarea name="remarks" id="lead_remarks" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none"></textarea>
                </div>
            </div>

            <div class="mt-8 flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('LeadModal')" class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

        const table = $('#leads-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('marketing.leads.index') }}",
                data: function (d) {
                    d.stage = $('#filter-stage').val();
                    d.future_date = $('#filter-future-date').val();
                    d.potential_date = $('#filter-potential-date').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
            },
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        const id = data || row.id;
                        return `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${id}"/>`;
                    }
                },
                { data: 'date', name: 'date', defaultContent: '-' },
                {
                    data: 'name',
                    name: 'name',
                    render: function(data, type, row) {
                        let url = `{{ url('marketing/leads') }}/${row.id}/history`;
                        return `<a href="${url}" target="_blank" class="text-blue-600 hover:underline decoration-2 underline-offset-4 cursor-pointer">${data}</a>`;
                    }
                },
                { data: 'phone_number', name: 'phone_number', defaultContent: '-' },
                { data: 'stage', name: 'stage', defaultContent: '-' },
                { data: 'future_follow_up_date', name: 'future_follow_up_date', defaultContent: '-' },
                { data: 'potential_follow_up_date', name: 'potential_follow_up_date', defaultContent: '-' },
                { data: 'assigned_user.name', name: 'assigned_user.name', searchable: false, defaultContent: '-' },
                { data: 'zone_details.name', name: 'zone_details.name', searchable: false, defaultContent: '-' },
                {
                    data: 'otp_status',
                    render: (d) => d === 'Verified'
                        ? `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest">Verified</span>`
                        : `<span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase tracking-widest">Pending</span>`
                },
                {
                    data: 'lead_id',
                    name: 'lead_id',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        if (data) {
                             let url = `{{ url('leads') }}/${data}`;
                             return `<a href="${url}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[9px] font-black uppercase tracking-widest hover:bg-blue-100 transition-colors group/link">
                                        <span class="material-symbols-outlined text-[14px] group-hover/link:scale-110 transition-transform">visibility</span> View
                                    </a>`;
                        }
                        return '<span class="text-slate-300 text-[10px] font-bold uppercase tracking-wider">-</span>';
                    }
                }
            ],
            dom: 'rtp',
            drawCallback: function(settings) {
                $('#table-info').text(`Total Records: ${settings.json ? settings.json.recordsTotal : 0}`);
                $('#table-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
                $('#selectAll').prop('checked', false);
                updateUIState();
            }
        });

        // Apply Filter on Button Click
        $('#btn-apply-filter').on('click', function() {
            table.draw();
        });

        // Reset Filters
        $('#reset-filters').on('click', function() {
            $('#filter-stage').val('');
            $('#filter-future-date').val('');
            $('#filter-potential-date').val('');
            table.draw();
        });

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        function updateUIState() {
            const count = $('.row-checkbox:checked').length;
            $('#selected-count').text(count);
            if (count > 0) {
                $('#floating-bar').removeClass('translate-y-32 opacity-0 invisible pointer-events-none').addClass('translate-y-0 opacity-100 visible pointer-events-auto');
                count === 1 ? $('#floating-edit').removeClass('hidden') : $('#floating-edit').addClass('hidden');
            } else {
                $('#floating-bar').addClass('translate-y-32 opacity-0 invisible pointer-events-none').removeClass('translate-y-0 opacity-100 visible pointer-events-auto');
            }
        }

        $(document).on('click', '#selectAll', function() {
            $('.row-checkbox').prop('checked', $(this).prop('checked'));
            updateUIState();
        });

        $(document).on('click', '.row-checkbox', function(e) {
            e.stopPropagation();
            const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
            $('#selectAll').prop('checked', allChecked);
            updateUIState();
        });

        window.closeModal = (id) => {
            const el = $('#' + id);
            el.find('.modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            el.removeClass('opacity-100 visible pointer-events-auto').addClass('opacity-0 invisible pointer-events-none');
        };

        $('#floating-edit').click(function() {
            const id = $('.row-checkbox:checked').first().val();
            $.get(`{{ url('marketing/leads') }}/${id}/edit`, function(data) {
                $('#Lead_id').val(data.id);
                $('#method').val('PUT');

                $('#stage').val(data.stage).change();
                $('#disqualified_reason').val(data.disqualified_reason);
                $('#rnr_reason').val(data.rnr_reason);
                $('#lead_customer_type').val(data.customer_type).change();
                $('#lead_colour').val(data.colour);
                $('#lead_total_order_sqft').val(data.total_order_sqft);
                $('#lead_building_status').val(data.building_status).change();

                $('#lead_building_type').val(data.building_type).change();
                $('#lead_assigned_to').val(data.assigned_to).change();
                $('#lead_zone').val(data.zone).change();
                $('#lead_remarks').val(data.remarks);

                $('#future_follow_up_date').val(data.future_follow_up_date);
                $('#future_follow_up_time').val(data.future_follow_up_time);
                $('#potential_follow_up_date').val(data.potential_follow_up_date);
                $('#potential_follow_up_time').val(data.potential_follow_up_time);

                $('#LeadModal').removeClass('opacity-0 invisible pointer-events-none').addClass('opacity-100 visible pointer-events-auto');
                $('#LeadModal .modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            });
        });

        function toggleFollowUpFields(stageValue) {
            $('#future_follow_up_section, #potential_follow_up_section, #disqualified_reason_section, #rnr_reason_section').addClass('hidden');
            if (stageValue == '5') {
                $('#future_follow_up_section').removeClass('hidden');
            } else if (stageValue == '6') {
                $('#potential_follow_up_section').removeClass('hidden');
            } else if (stageValue == '2') { // Disqualified
                $('#disqualified_reason_section').removeClass('hidden');
            } else if (stageValue == '0') { // RNR
                $('#rnr_reason_section').removeClass('hidden');
            }
        }

        $('#stage').on('change', function() {
            toggleFollowUpFields($(this).val());
        });

        $('#LeadForm').submit(function(e) {
            e.preventDefault();
            const id = $('#Lead_id').val();
            $.ajax({
                url: `{{ url('marketing/leads') }}/${id}`,
                type: 'POST',
                data: $(this).serialize(),
                success: function() {
                    closeModal('LeadModal');
                    // Ensure you have a Toast helper or replace with alert
                    if(typeof Toast !== 'undefined') {
                        Toast.fire({ icon: 'success', title: 'Data Saved' });
                    } else {
                        alert('Lead updated successfully');
                    }
                    table.draw(false);
                }
            });
        });

        window.handleBulkDelete = () => {
            const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(!confirm(`Delete ${ids.length} records?`)) return;
            $.post("{{ route('marketing.leads.bulkDelete') }}", { ids: ids }, () => table.draw(false));
        };
    });
</script>
@endsection
