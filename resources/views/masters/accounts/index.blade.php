@extends('layouts.app')
@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        #table-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md shadow-blue-500/30;
        }

        /* table row spacing and internal padding (py-3) */
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.65rem !important;
        }

        table.dataTable tbody tr td {
            @apply py-3 px-4;
        }

        #accountModal.pointer-events-none {
            visibility: hidden;
        }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                    <span>Masters</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-blue-600">Account</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account Master</h1>
            </div>

            @can('accounts.create')
                <button onclick="openAddModal()"
                    class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] font-bold">add</span> Add Account
                </button>
            @endcan
        </div>

        <div class="glass-panel rounded-[1.5rem] p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                    <select id="filter_zone_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Zones</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
    
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">State</label>
                    <select id="filter_state_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All States</option>
                    </select>
                </div>
    
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager</label>
                    <select id="filter_manager_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Managers</option>
                    </select>
                </div>
    
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO / User</label>
                    <select id="filter_bdo_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                        <option value="">All Users</option>
                    </select>
                </div>
    
                <div class="flex items-end gap-2 lg:col-span-1">
                    <button id="btn_filter" class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                    </button>
                    <button id="btn_reset" class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-[1.5rem] overflow-hidden">
            <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
                <div class="relative w-full max-w-xs">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input id="customSearch"
                        class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none"
                        placeholder="Search account..." type="text" />
                </div>
            </div>

            <div class="px-4 overflow-x-auto">
                <table class="w-full" id="account-table">
                    <thead>
                        <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                            <th class="pl-4 pb-2 w-10"><input id="selectAll"
                                    class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" />
                            </th>
                            <th class="px-4 pb-2">ID</th>
                            <th class="px-4 pb-2">Account Name</th>
                            <th class="px-4 pb-2">Mobile</th>
                            <th class="px-4 pb-2">Type</th>
                            <th class="px-4 pb-2">Location</th>
                            <th class="px-4 pb-2">Created By</th>
                            <th class="px-4 pb-2 text-center">Status</th>
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

    <div id="floating-bar"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 rounded-2xl px-5 py-2.5 flex items-center gap-5 shadow-2xl transition-all duration-500 translate-y-20 opacity-0 pointer-events-none">
        <div class="flex items-center gap-2 text-white border-r border-slate-700 pr-4">
            <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-black"
                id="selected-count">0</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
        </div>
        <div class="flex items-center gap-4">
            @can('accounts.edit')
                <button id="floating-edit"
                    class="hidden flex items-center gap-1.5 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span> Edit
                </button>
            @endcan
            @can('accounts.delete')
                <button onclick="handleBulkDelete()"
                    class="flex items-center gap-1.5 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Delete
                </button>
            @endcan
        </div>
    </div>

    <div id="accountModal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/0 backdrop-blur-0 transition-all duration-300 opacity-0 pointer-events-none">
        <div
            class="modal-content glass-panel w-full max-w-sm rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
            <form id="accountForm">
                @csrf
                <input type="hidden" name="_method" id="method">
                <input type="hidden" id="account_id" name="id">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-slate-800" id="modalTitle">Add New Account</h3>
                    <button type="button" onclick="closeModal('accountModal')"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Account
                                Name</label>
                            <input type="text" name="name" id="account_name"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required>
                        </div>
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Mobile
                                Number</label>
                            <input type="text" name="mobile_number" id="account_mobile"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required>
                        </div>
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Account
                                Type</label>
                            <select name="account_type_id" id="account_type_id"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required>
                                <option value="">Select Type</option>
                                @foreach ($accountTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                            <select name="zone_id" id="account_zone"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required>
                                <option value="">Select Zone</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">State</label>
                            <select name="state_id" id="account_state"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required disabled>
                                <option value="">Select State</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">District</label>
                            <select name="district_id" id="account_district"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                required disabled>
                                <option value="">Select District</option>
                            </select>
                        </div>
                        {{-- <div class="col-span-1">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Pincode</label>
                            <input type="text" name="pincode" id="account_pincode"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                        </div> --}}
                        <div class="col-span-2">
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Address</label>
                            <textarea name="address" id="account_address" rows="2"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('accountModal')"
                        class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                    <button type="submit"
                        class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-slate-900/20">Save
                        Record</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            const token = $('meta[name="csrf-token"]').attr('content');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token
                }
            });

            // --- Filter Logic ---
            function clearDropdown(id, placeholder) {
                $(id).empty().append(`<option value="">All ${placeholder}</option>`);
            }
        
            function getZoneData(zoneId) {
                 if (!zoneId) {
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'Users');
                    return;
                }
                 $.get("{{ route('get.location.data') }}", { type: 'zone', id: zoneId }, function(data) {
                    // Populate States
                    clearDropdown('#filter_state_id', 'States');
                    $.each(data.states, function(i, item) {
                        $('#filter_state_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    
                    // Populate BDMs (Managers)
                    clearDropdown('#filter_manager_id', 'Managers');
                    $.each(data.bdms, function(i, item) {
                        $('#filter_manager_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
    
                     // Populate BDOs
                    clearDropdown('#filter_bdo_id', 'Users');
                    $.each(data.bdos, function(i, item) {
                        $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                });
            }
    
            // Load BDOs for a Manager
            function getBdos(bdmId) {
                if (!bdmId) {
                    clearDropdown('#filter_bdo_id', 'Users');
                    return;
                }
                $.get("{{ route('get.location.data') }}", { type: 'bdo', id: bdmId }, function(data) {
                    clearDropdown('#filter_bdo_id', 'Users');
                    $.each(data, function(i, item) {
                        $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                });
            }
    
            $('#filter_zone_id').change(function() {
                getZoneData($(this).val());
            });
    
            $('#filter_manager_id').change(function() {
                getBdos($(this).val());
            });

            let editMode = false;
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });

            const table = $('#account-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('masters.accounts.index') }}",
                    data: function(d) {
                        d.zone_id = $('#filter_zone_id').val();
                        d.state_id = $('#filter_state_id').val();
                        d.manager_id = $('#filter_manager_id').val();
                        d.bdo_id = $('#filter_bdo_id').val();
                    }
                },
                createdRow: (row) => $(row).addClass('glass-card group'),
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: (d) =>
                            `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${d}"/>`
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'mobile_number',
                        name: 'mobile_number',
                        defaultContent: '-'
                    },
                    {
                        data: 'type_name',
                        name: 'type_name',
                        defaultContent: '-'
                    },
                    {
                        data: 'location',
                        name: 'location',
                        defaultContent: '-'
                    },
                    {
                        data: 'created_by',
                        name: 'user.name',
                        defaultContent: '-',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        className: 'text-center',
                        render: (d) =>
                            `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest"><span class="w-1 h-1 bg-green-500 rounded-full"></span>Active</span>`
                    }
                ],
                dom: 'rtp',
                language: {
                    paginate: {
                        previous: '<span class="material-symbols-outlined text-[14px]">arrow_back_ios</span>',
                        next: '<span class="material-symbols-outlined text-[14px]">arrow_forward_ios</span>'
                    }
                },
                drawCallback: function(settings) {

                    const total = settings.json ? settings.json.recordsTotal : 0;
                    $('#table-info').text(`Total Records: ${total}`);
                    $('#table-pagination').html($('.dataTables_paginate').html());
                    $('.dataTables_paginate').empty();

                    // RESTORE CHECKBOX STATE
                    $('.row-checkbox').each(function() {
                        if (selectedIds.includes($(this).val())) {
                            $(this).prop('checked', true);
                        }
                    });

                    updateFloatingBar();

                    $('#table-pagination .paginate_button').on('click', function(e) {
                        e.preventDefault();

                        if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;

                        if ($(this).hasClass('previous')) {
                            table.page('previous').draw('page');
                        } else if ($(this).hasClass('next')) {
                            table.page('next').draw('page');
                        } else {
                            const pageNum = parseInt($(this).text()) - 1;
                            table.page(pageNum).draw('page');
                        }
                    });
                }

            });

            $('#btn_filter').click(function() {
                table.draw();
            });
    
            $('#btn_reset').click(function() {
               $('#filter_zone_id').val('').trigger('change');
               $('#customSearch').val('');
               table.search('').draw();
            });

            function updateFloatingBar() {

                const count = selectedIds.length;
                $('#selected-count').text(count);

                if (count > 0) {

                    $('#floating-bar')
                        .removeClass('translate-y-20 opacity-0 pointer-events-none')
                        .addClass('translate-y-0 opacity-100 pointer-events-auto');

                    count === 1 ?
                        $('#floating-edit').removeClass('hidden') :
                        $('#floating-edit').addClass('hidden');

                } else {

                    $('#floating-bar')
                        .addClass('translate-y-20 opacity-0 pointer-events-none')
                        .removeClass('translate-y-0 opacity-100 pointer-events-auto');
                }
            }

            $('#customSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            window.openAddModal = () => {
                editMode = false;
                $('#accountForm')[0].reset();
                $('#account_id').val('');
                $('#method').val('POST');
                $('#modalTitle').text('Add New Account');
                toggleModal('accountModal', true);
            };

            window.closeModal = (id) => toggleModal(id, false);

            function toggleModal(id, show) {
                const el = $('#' + id);
                const content = el.find('.modal-content');
                if (show) {
                    el.removeClass('pointer-events-none opacity-0').addClass(
                        'opacity-100 bg-slate-900/30 backdrop-blur-sm');
                    content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
                } else {
                    content.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
                    el.addClass('pointer-events-none opacity-0').removeClass(
                        'opacity-100 bg-slate-900/30 backdrop-blur-sm');
                }
            }

            $('#floating-edit').click(function() {
                // 1. Get the ID of the selected row
                const id = selectedIds[0];
                editMode = true;

                // 2. Fetch the data from your Controller
                $.get(`{{ url('') }}/masters/accounts/${id}/edit`, function(data) {

                    // 3. Fill basic text fields
                    $('#modalTitle').text('Update Account');
                    $('#account_id').val(data.id);
                    $('#account_name').val(data.name);
                    $('#account_mobile').val(data.mobile_number);
                    $('#account_address').val(data.address);
                    $('#method').val('PUT');

                    // ------------------------------------------------------------------
                    // FIX 1: Account Type (Static Dropdown)
                    // ------------------------------------------------------------------
                    // If data.account_type_id is null, use "" to select the placeholder.
                    // This prevents the issue where "null" string is passed.
                    $('#account_type_id').val(data.account_type_id || "").trigger('change');

                    // ------------------------------------------------------------------
                    // FIX 2: State & District (Dynamic Dropdowns)
                    // ------------------------------------------------------------------
                    // We must define what happens "After States Load" BEFORE we trigger the zone change.

                    // A. Define the listener for when STATES are loaded
                    $('#account_state').off('states:loaded').on('states:loaded', function() {
                        if (data.state_id) {
                            // Select the state and trigger change to load districts
                            $(this).val(data.state_id).trigger('change');
                        } else {
                            $(this).val('');
                        }
                    });

                    // B. Define the listener for when DISTRICTS are loaded
                    $('#account_district').off('districts:loaded').on('districts:loaded',
                function() {
                        if (data.district_id) {
                            // Just select the district (end of chain)
                            $(this).val(data.district_id);
                        } else {
                            $(this).val('');
                        }
                    });

                    // ------------------------------------------------------------------
                    // 4. Start the Chain
                    // ------------------------------------------------------------------
                    // Setting the Zone triggers 'change' -> calls AJAX -> fires 'states:loaded' -> selects State -> fires 'change' -> ...
                    if (data.zone_id) {
                        $('#account_zone').val(data.zone_id).trigger('change');
                    } else {
                        // If no zone, reset everything
                        $('#account_zone').val('');
                        $('#account_state').empty().append(
                        '<option value="">Select State</option>');
                        $('#account_district').empty().append(
                            '<option value="">Select District</option>');
                    }

                    // 5. Open the Modal
                    toggleModal('accountModal', true);

                }).fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not fetch account details.'
                    });
                });
            });



            // Dependent Dropdowns Logic
            $('#account_zone').change(function() {
                const id = $(this).val();
                const stateSelect = $('#account_state');
                const districtSelect = $('#account_district');

                stateSelect.empty().append('<option value="">Select State</option>').prop('disabled', true);
                districtSelect.empty().append('<option value="">Select District</option>').prop('disabled',
                    true);

                if (id) {
                    $.get(`{{ route('get.location.data') }}`, {
                        type: 'zone',
                        id: id
                    }, function(data) {

                        const states = data.states || [];

                        states.forEach(item => {
                            stateSelect.append(
                                `<option value="${item.id}">${item.name}</option>`);
                        });

                        stateSelect.prop('disabled', false);

                        // 🔥 notify that states are loaded
                        stateSelect.trigger('states:loaded');

                        stateSelect.prop('disabled', false);
                    });
                }
            });

            $('#account_state').change(function() {
                const id = $(this).val();
                const districtSelect = $('#account_district');

                districtSelect.empty().append('<option value="">Select District</option>').prop('disabled',
                    true);

                if (id) {
                    $.get(`{{ route('get.location.data') }}`, {
                        type: 'state',
                        id: id
                    }, function(data) {
                        data.forEach(item => {
                            districtSelect.append(
                                `<option value="${item.id}">${item.name}</option>`);
                        });

                        districtSelect.prop('disabled', false);

                        // 🔥 notify that districts are loaded
                        districtSelect.trigger('districts:loaded');
                    });
                }
            });

            $('#accountForm').submit(function(e) {
                e.preventDefault();
                const id = $('#account_id').val();
                const url = editMode ? `{{ url('') }}/masters/accounts/${id}` :
                    "{{ route('masters.accounts.store') }}";

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function() {
                        toggleModal('accountModal', false);
                        table.draw(false);
                        Toast.fire({
                            icon: 'success',
                            title: editMode ? 'Account Updated' : 'Account Created'
                        });
                        resetFloatingBar();
                    }
                });
            });

            let selectedIds = [];

            $(document).on('change', '#selectAll, .row-checkbox', function() {

                // SELECT ALL
                if ($(this).attr('id') === 'selectAll') {

                    $('.row-checkbox').each(function() {

                        const id = $(this).val();

                        if ($('#selectAll').is(':checked')) {

                            $(this).prop('checked', true);
                            if (!selectedIds.includes(id)) selectedIds.push(id);

                        } else {

                            $(this).prop('checked', false);
                            selectedIds = selectedIds.filter(x => x != id);
                        }
                    });

                }
                // SINGLE CHECK
                else {

                    const id = $(this).val();

                    if ($(this).is(':checked')) {

                        if (!selectedIds.includes(id)) selectedIds.push(id);

                    } else {

                        selectedIds = selectedIds.filter(x => x != id);
                    }
                }

                updateFloatingBar();
            });

            window.handleBulkDelete = () => {

                Swal.fire({
                    title: 'Deactivate Accounts?',
                    text: `You are about to deactivate ${selectedIds.length} record(s).`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Yes, Deactivate'
                }).then((result) => {

                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('masters.accounts.bulkDelete') }}",
                            type: "POST",
                            data: {
                                ids: selectedIds
                            },
                            success: function() {
                                table.draw(false);
                                selectedIds = [];
                                updateFloatingBar();
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Records deactivated'
                                });
                            }
                        });
                    }
                });
            };


            function resetFloatingBar() {
                selectedIds = [];
                $('#selectAll, .row-checkbox').prop('checked', false);
                updateFloatingBar();
            }
        });
    </script>
@endsection
