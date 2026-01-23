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
                        <option>North</option>
                        <option>South</option>
                        <option>East</option>
                        <option>West</option>
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
                    <thead>
                        <tr class="text-left">
                            <th>S.No</th>
                            <th>Telecaller</th>
                            <th>Lead Name</th>
                            <th>Phone</th>
                            <th>Stage</th>
                            <th>Zone</th>
                            <th>Created</th>
                            <th>Follow-up</th>
                            <th>Handover</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
                    }
                },
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1
                    },
                    {
                        data: 'telecaller',
                        name: 'users.name'
                    },
                    {
                        data: 'name',
                        name: 'leads.name'
                    },
                    {
                        data: 'phone_number',
                        name: 'leads.phone_number'
                    },
                    {
                        data: 'lead_stage',
                        name: 'leads.lead_stage'
                    },
                    {
                        data: 'zone',
                        name: 'leads.zone'
                    },
                    {
                        data: 'created_at',
                        name: 'leads.created_at'
                    },
                    {
                        data: 'follow_up_date',
                        name: 'leads.follow_up_date'
                    },
                    {
                        data: 'handovered_date',
                        name: 'leads.handovered_date'
                    },
                ],


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
