@extends('layouts.app')

@section('title', 'Prospect Report')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
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

        #report-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md border-blue-600;
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.4rem !important;
        }
    </style>
    <div class="flex-1 p-5 space-y-6 bg-[#f8fafc]">

        <!-- HEADER -->
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Prospect</span>
            </nav>
            <h1 class="text-2xl font-black">Prospect Report</h1>
        </div>

        <!-- FILTER -->
        <div class="bg-white p-5 rounded-xl border">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">Zone</label>
                    <select id="filter_zone" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Models\Zone::pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">Telecaller</label>
                    <select id="filter_telecaller" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'Telecaller');
        })->whereIn('id', \App\Models\DigitalMarketingLead::select('updated_by')->distinct())->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">Telecaller Stage</label>
                    <select id="filter_tc_stage" class="form-input-custom">
                        <option value="">All</option>
                        @foreach (\App\Helpers\LeadHelper::getLeadStages() as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">BDO Stage</label>
                    <select id="filter_bdo_stage" class="form-input-custom">
                        <option value="">All</option>
                        <option value="0">Site Identification</option>
                        <option value="1">Intro</option>
                        <option value="2">FollowUp</option>
                        <option value="3">Quote Pending</option>
                        <option value="4">Quote Sent</option>
                        <option value="5">Won</option>
                        <option value="6">Site Handed Over</option>
                        <option value="7">Lost</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">From Date</label>
                    <input type="date" id="from_date" class="form-input-custom" value="{{ date('Y-m-01') }}">
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400">To Date</label>
                    <input type="date" id="to_date" class="form-input-custom" value="{{ date('Y-m-d') }}">
                </div>

                <div class="flex items-end gap-2">
                    <button id="btn_filter" class="px-6 h-[42px] bg-blue-600 text-white text-[10px] font-black rounded-xl">
                        Apply
                    </button>

                    <button id="btn_reset" class="px-6 h-[42px] border text-[10px] font-black rounded-xl">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white p-5 rounded-xl border">
            <div class="overflow-x-auto">
                <table id="prospect-table" class="w-full">
                    <thead class="bg-slate-50">
                        <tr class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3">SNO</th>
                            <th class="px-4 py-3">Telecaller</th>
                            <th class="px-4 py-3">Telecaller Stage</th>
                            <th class="px-4 py-3">BDO</th>
                            <th class="px-4 py-3">Lead Name</th>
                            <th class="px-4 py-3">BDO Stage</th>
                            <th class="px-4 py-3">Zone</th>
                            <th class="px-4 py-3">Created</th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-semibold text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {

                    const table = $('#prospect-table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: "{{ route('prospect.report.data') }}",
                                data: function(d) {
                                    d.zone = $('#filter_zone').val();
                                    d.from_date = $('#from_date').val();
                                    d.to_date = $('#to_date').val();
                                    d.telecaller = $('#filter_telecaller').val();
                                    d.tc_stage = $('#filter_tc_stage').val();
                                    d.bdo_stage = $('#filter_bdo_stage').val();
                                }
                            },
                            columns: [{
                                    data: null,
                                    orderable: false,
                                    searchable: false,
                                    className: 'text-center',
                                    render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1
                                },
                                {
                                    data: 'telecaller',
                                    orderable: false
                                },
                                {
                                    data: 'telecaller_stage',
                                    orderable: false
                                },
                                {
                                    data: 'bdo',
                                    orderable: false
                                },
                                {
                                    data: 'name'
                                },

                                {
                                    data: 'lead_stage',
                                    orderable: false
                                },
                                {
                                    data: 'zone_name',
                                    orderable: false
                                },
                                {
                                    data: 'created_at'
                                },
                            ],
                            dom: 'rtp',
                            language: {
                                paginate: {
                                    previous: '<span class="material-symbols-outlined text-[14px]">arrow_back_ios</span>',
                                    next: '<span class="material-symbols-outlined text-[14px]">arrow_forward_ios</span>'
                                }

                            });


                        $('#btn_filter').click(() => table.draw());

                        $('#btn_reset').click(function() {
                            $('#filter_zone').val('');
                            $('#from_date').val("{{ date('Y-m-01') }}");
                            $('#to_date').val("{{ date('Y-m-d') }}");
                            table.draw();
                        });
                    });
    </script>
@endsection
