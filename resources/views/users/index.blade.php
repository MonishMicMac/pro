@extends('layouts.app')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
                background: rgba(255, 255, 255, 0.6);
                @apply border border-white/40 transition-all duration-300;
            }

            .glass-card:hover {
                background: rgba(255, 255, 255, 1);
                @apply shadow-md -translate-y-0.5 border-blue-100;
            }

            .multi-check-container {
                @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-40 overflow-y-auto p-4 bg-white border border-slate-200 rounded-2xl mt-1;
            }

            .check-item {
                @apply flex items-center gap-2 p-2 rounded-xl hover:bg-slate-50 cursor-pointer border border-transparent transition-all;
            }

            .check-item input:checked+span {
                @apply text-blue-600 font-bold;
            }

            .check-item:has(input:checked) {
                @apply border-blue-100 bg-blue-50/50;
            }
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.5rem !important;
        }

        table.dataTable thead th {
            @apply px-6 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest;
        }

        table.dataTable tbody tr td {
            @apply px-6 py-4;
        }

        #table-pagination .paginate_button {
            @apply px-3 py-1.5 mx-0.5 rounded-lg bg-white text-slate-500 font-bold text-[11px] cursor-pointer transition-all inline-flex items-center min-w-[32px] shadow-sm hover:bg-slate-50;
        }

        #table-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-blue-500/30;
        }

        .modal-hidden {
            visibility: hidden !important;
            opacity: 0 !important;
        }

        .modal-visible {
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>

    {{-- Workspace Wrapper --}}
    <div class="relative flex-1 p-5 space-y-4 bg-[#f8fafc] min-h-screen flex flex-col">

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">User Accounts</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">Manage access &
                    regional assignments</p>
            </div>
            <button onclick="openAddModal()"
                class="px-5 py-2.5 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">person_add</span> Add User
            </button>
        </div>

        <div class="glass-panel rounded-[2rem] overflow-hidden flex-1 flex flex-col">
            <div class="p-4 border-b border-white/50 bg-white/30 flex justify-between items-center">
                <div class="relative w-full max-w-xs">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input id="customSearch"
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-500/10"
                        placeholder="Search accounts..." />
                </div>
            </div>

            <div class="flex-1 overflow-x-auto px-4">
                <table class="w-full" id="users-table">
                    <thead>
                        <tr>
                            <th class="w-10"><input id="selectAll" type="checkbox"
                                    class="rounded border-slate-300 text-blue-600 cursor-pointer" /></th>
                            <th>EMP Code</th>
                            <th>Name</th>
                            <th>Brands</th>
                            <th>Email</th>
                            <th>Role</th>
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

        {{-- Backdrop (Full Screen Black Shade) --}}
        <div id="modalBackdrop"
            class="fixed inset-0 z-[90] hidden bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 opacity-0 pointer-events-none">
        </div>

        {{-- Modal Wrapper (items-start ensures it anchors to top) --}}
        <div id="userModal"
            class="absolute inset-0 z-[100] flex items-start justify-center p-4 transition-all duration-300 modal-hidden overflow-hidden"
            style="position: absolute; top: -67px;">
            {{-- Modal Content: Reduced mt-0 and increased -translate-y-80 for tighter top gap --}}
            <div
                class="modal-content glass-panel w-full max-w-4xl max-h-[95vh] mt-0 overflow-y-auto rounded-[2.5rem] p-6 md:p-10 shadow-2xl transition-all transform -translate-y-80 opacity-0">
                <form id="userForm">
                    @csrf
                    <input type="hidden" name="_method" id="method">
                    <input type="hidden" id="user_id" name="id">

                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-xl font-black text-slate-800" id="modalTitle">Account Details</h3>
                        <button type="button" onclick="toggleModal('userModal', false)"
                            class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400"><span
                                class="material-symbols-outlined">close</span></button>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">User
                                    Role</label>
                                <select name="role" id="user_role"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/10 transition-all"
                                    required>
                                    <option value="">Select Role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">EMP
                                    Code</label>
                                <input type="text" name="emp_code" id="user_emp_code"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"
                                    required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="name" id="user_name" placeholder="Full Name"
                                class="w-full px-4 py-2.5 border-slate-200 rounded-xl text-xs font-bold" required>
                            <input type="text" name="phone" id="user_phone" placeholder="Phone" maxlength="10"
                                class="w-full px-4 py-2.5 border-slate-200 rounded-xl text-xs font-bold" required>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="email" name="email" id="user_email" placeholder="Email Address"
                                class="w-full px-4 py-2.5 border-slate-200 rounded-xl text-xs font-bold" required>
                            <input type="password" name="password" id="user_password" placeholder="Password"
                                class="w-full px-4 py-2.5 border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">
                                Address
                            </label>

                            <textarea name="address" id="user_address" rows="3" placeholder="House No, Street, Area, Landmark"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold resize-none focus:ring-2 focus:ring-blue-500/10 transition-all"></textarea>
                        </div>

                        <div>
                            <label
                                class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Assigned
                                Brands</label>
                            <div class="multi-check-container">
                                @foreach ($brands as $brand)
                                    <label class="check-item">
                                        <input type="checkbox" name="brand_id[]" value="{{ $brand->id }}"
                                            class="brand-check w-4 h-4 rounded border-slate-300 text-blue-600">
                                        <span class="text-[11px] text-slate-600 uppercase">{{ $brand->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div id="geo-section" class="pt-6 border-t border-slate-100 space-y-6 hidden">
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase mb-1">Zone</label>
                                    <select id="user_zone" name="zone_id"
                                        class="w-full text-xs border-slate-200 rounded-xl focus:ring-blue-500/10">
                                        <option value="">Select Zone</option>
                                        @foreach ($zones as $z)
                                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase mb-1">State</label>
                                    <select id="user_state" name="state_id"
                                        class="w-full text-xs border-slate-200 rounded-xl">
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[8px] font-black text-slate-400 uppercase mb-1">District</label>
                                    <select id="user_district" name="district_id"
                                        class="w-full text-xs border-slate-200 rounded-xl">
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] font-black text-slate-400 uppercase mb-1">City</label>
                                    <select id="user_city" name="city_id"
                                        class="w-full text-xs border-slate-200 rounded-xl">
                                        <option value="">Select</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Assigned
                                    Areas</label>
                                <div class="multi-check-container" id="area_checkbox_list">
                                    <p class="text-[10px] text-slate-400 p-2 italic">Select a city first...</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Assigned
                                    Pincodes</label>
                                <div class="multi-check-container" id="pincode_checkbox_list">
                                    <p class="text-[10px] text-slate-400 p-2 italic">Select area(s) first...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4 mt-10">
                        <button type="button" onclick="toggleModal('userModal', false)"
                            class="flex-1 py-3 font-black text-slate-400 uppercase text-[10px]">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase shadow-xl hover:bg-black transition-all">Save
                            User Account</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Floating Bar --}}
        <div id="floating-bar"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[80] bg-slate-900 rounded-2xl px-5 py-2.5 flex items-center gap-5 shadow-2xl transition-all duration-500 translate-y-20 opacity-0 pointer-events-none">
            <div class="flex items-center gap-2 text-white border-r border-slate-700 pr-4">
                <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-black"
                    id="selected-count">0</span>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
            </div>
            <div class="flex items-center gap-4">
                <button id="floating-edit"
                    class="hidden flex items-center gap-1.5 text-blue-400 hover:text-blue-300 text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span> Edit
                </button>
                <button id="floating-delete"
                    class="flex items-center gap-1.5 text-rose-400 hover:text-rose-300 text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Delete
                </button>
            </div>
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

        const table = $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('users.data') }}",
            createdRow: (row) => $(row).addClass('glass-card'),
            columns: [{
                    data: 'id',
                    orderable: false,
                    className: 'rounded-l-2xl',
                    render: (d) =>
                        `<input class="row-checkbox w-4 h-4 rounded-md border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${d}"/>`
                },
                {
                    data: 'emp_code',
                    render: (d) =>
                        `<span class="font-mono text-slate-400 font-bold">#${d || '---'}</span>`
                },
                {
                    data: 'name',
                    render: (d, t, r) =>
                        `<div class="flex flex-col"><span class="text-slate-900 font-black">${d}</span><span class="text-[10px] text-slate-400 font-medium">${r.phone || ''}</span></div>`
                },
                {
                    data: 'brand_names',
                    orderable: false,
                    render: (d) =>
                        `<div class="flex flex-wrap gap-1">${d ? d.split(',').map(b => `<span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[9px] border border-slate-200">${b.trim()}</span>`).join('') : ''}</div>`
                },
                {
                    data: 'email',
                    render: (d) => `<span class="text-slate-500 font-medium lowercase">${d}</span>`
                },
                {
                    data: 'role_name',
                    className: 'rounded-r-2xl',
                    render: (d) =>
                        `<span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] uppercase tracking-widest border border-blue-100">${d || 'User'}</span>`
                }
            ],
            dom: 'rtp',
            language: {
                paginate: {
                    previous: '<span class="material-symbols-outlined text-[16px]">arrow_back_ios</span>',
                    next: '<span class="material-symbols-outlined text-[16px]">arrow_forward_ios</span>'
                }
            },
            drawCallback: function() {
                $('#table-info').text(`Total Accounts: ${this.api().page.info().recordsTotal}`);
                $('#table-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
                $('#table-pagination .paginate_button').on('click', function(e) {
                    e.preventDefault();
                    if (!$(this).hasClass('disabled') && !$(this).hasClass('current')) {
                        if ($(this).hasClass('previous')) table.page('previous').draw(
                            'page');
                        else if ($(this).hasClass('next')) table.page('next').draw('page');
                        else table.page(parseInt($(this).text()) - 1).draw('page');
                    }
                });
            }
        });

        $(document).on('change', '#selectAll, .row-checkbox', function() {
            if ($(this).attr('id') === 'selectAll') $('.row-checkbox').prop('checked', $(this).prop(
                'checked'));
            const count = $('.row-checkbox:checked').length;
            $('#selected-count').text(count);
            if (count > 0) {
                $('#floating-bar').removeClass('translate-y-20 opacity-0 pointer-events-none').addClass(
                    'translate-y-0 opacity-100 pointer-events-auto');
                count === 1 ? $('#floating-edit').removeClass('hidden') : $('#floating-edit').addClass(
                    'hidden');
            } else {
                $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none').removeClass(
                    'translate-y-0 opacity-100 pointer-events-auto');
            }
        });

        async function fetchAndPopulate(type, parentId, targetSelector, selectedValue, isCheckbox = false) {
            if (!parentId || (Array.isArray(parentId) && parentId.length === 0)) return;
            return new Promise((resolve) => {
                $.get("{{ route('users.geodata') }}", {
                    type: type,
                    id: parentId
                }, function(data) {
                    let html = isCheckbox ? '' : `<option value="">Select</option>`;
                    data.forEach(item => {
                        const name = item.name || item.district_name || item
                            .city_name || item.area_name || item.pincode;
                        const id = item.id;
                        if (isCheckbox) {
                            const checked = (Array.isArray(selectedValue) &&
                                selectedValue.map(String).includes(String(id))
                            ) ? 'checked' : '';
                            html += `<label class="check-item">
                                    <input type="checkbox" name="${type === 'areas' ? 'area_id[]' : 'pincode_id[]'}" 
                                           value="${id}" ${checked} class="${type === 'areas' ? 'area-check' : 'pincode-check'} w-4 h-4 rounded text-blue-600">
                                    <span class="text-[11px]">${name}</span>
                                 </label>`;
                        } else {
                            html += `<option value="${id}">${name}</option>`;
                        }
                    });
                    $(targetSelector).html(html || (isCheckbox ?
                        '<p class="text-[10px] text-slate-400 p-2 italic">No data found</p>' :
                        ''));
                    if (!isCheckbox && selectedValue) $(targetSelector).val(selectedValue);
                    resolve();
                });
            });
        }

        $('#floating-edit').click(function() {
            const id = $('.row-checkbox:checked').val();
            let editUrl = "{{ route('users.edit', ':id') }}".replace(':id', id);
            $.get(editUrl, async function(data) {
                $('#userForm')[0].reset();
                $('#user_id').val(data.user.id);
                $('#user_name').val(data.user.name);
                $('#user_email').val(data.user.email);
                $('#user_phone').val(data.user.phone);
                $('#user_address').val(data.user.address);
                $('#user_emp_code').val(data.user.emp_code);
                $('#user_role').val(data.role).trigger('change');
                $('#method').val('PUT');
                $('#modalTitle').text('Update Account');
                $('.brand-check').prop('checked', false);
                if (data.brand_ids) data.brand_ids.forEach(bid => $(
                    `.brand-check[value="${bid}"]`).prop('checked', true));

                if (data.user.zone_id) {
                    $('#user_zone').val(data.user.zone_id);
                    await fetchAndPopulate('states', data.user.zone_id, '#user_state', data
                        .user.state_id);
                    if (data.user.state_id) {
                        await fetchAndPopulate('districts', data.user.state_id,
                            '#user_district', data.user.district_id);
                        if (data.user.district_id) {
                            await fetchAndPopulate('cities', data.user.district_id,
                                '#user_city', data.user.city_id);
                            if (data.user.city_id) {
                                await fetchAndPopulate('areas', data.user.city_id,
                                    '#area_checkbox_list', data.area_ids, true);
                                if (data.area_ids && data.area_ids.length > 0) {
                                    await fetchAndPopulate('pincodes', data.area_ids,
                                        '#pincode_checkbox_list', data.pincode_ids, true
                                    );
                                }
                            }
                        }
                    }
                }
                toggleModal('userModal', true);
            });
        });

        $('#user_zone').change(function() {
            fetchAndPopulate('states', $(this).val(), '#user_state');
            $('#user_district, #user_city').html('<option value="">Select</option>');
            $('#area_checkbox_list, #pincode_checkbox_list').html(
                '<p class="text-[10px] text-slate-400 p-2 italic">Select above first...</p>');
        });
        $('#user_state').change(function() {
            fetchAndPopulate('districts', $(this).val(), '#user_district');
            $('#user_city').html('<option value="">Select</option>');
        });
        $('#user_district').change(function() {
            fetchAndPopulate('cities', $(this).val(), '#user_city');
        });
        $('#user_city').change(function() {
            fetchAndPopulate('areas', $(this).val(), '#area_checkbox_list', null, true);
        });
        $(document).on('change', '.area-check', function() {
            const ids = $('.area-check:checked').map(function() {
                return $(this).val();
            }).get();
            fetchAndPopulate('pincodes', ids, '#pincode_checkbox_list', null, true);
        });

        $('#user_role').on('change', function() {
            $(this).val() ? $('#geo-section').slideDown() : $('#geo-section').slideUp();
        });
        $('#customSearch').on('keyup', function() {
            table.search(this.value).draw();
        });

        window.toggleModal = (id, show) => {
            const el = $('#' + id),
                content = el.find('.modal-content'),
                backdrop = $('#modalBackdrop');
            if (show) {
                el.removeClass('modal-hidden').addClass('modal-visible');
                backdrop.removeClass('hidden');
                setTimeout(() => {
                    backdrop.removeClass('opacity-0').addClass('opacity-100');
                    content.removeClass('-translate-y-80 opacity-0').addClass(
                        'translate-y-0 opacity-100');
                }, 10);
            } else {
                content.removeClass('translate-y-0 opacity-100').addClass('-translate-y-80 opacity-0');
                backdrop.removeClass('opacity-100').addClass('opacity-0');
                setTimeout(() => {
                    el.addClass('modal-hidden').removeClass('modal-visible');
                    backdrop.addClass('hidden');
                }, 300);
            }
        };

        window.openAddModal = () => {
            $('#userForm')[0].reset();
            $('#user_id').val('');
            $('#method').val('POST');
            $('#geo-section').hide();
            $('#modalTitle').text('New Account');
            toggleModal('userModal', true);
        };

        // MODIFIED SUBMIT HANDLER WITH ERROR VALIDATION
        $('#userForm').submit(function(e) {
            e.preventDefault();
            const id = $('#user_id').val();
            let url = id ? "{{ route('users.update', ':id') }}".replace(':id', id) :
                "{{ route('users.store') }}";
            
            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(),
                success: () => {
                    toggleModal('userModal', false);
                    table.draw(false);
                    Swal.fire('Success', 'Action completed', 'success');
                },
                error: function(xhr) {
                    let errorMessage = 'Something went wrong.';
                    
                    // Check if it's a validation error (Status 422)
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        errorMessage = '';
                        // Loop through the errors and list them
                        $.each(errors, function(key, value) {
                            errorMessage += value[0] + '<br>'; 
                        });
                        
                        // Show validation errors
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            html: errorMessage, // Use html to render line breaks
                        });
                    } else {
                        // Handle generic server errors (Status 500, etc.)
                        Swal.fire('Error', xhr.responseJSON.message || errorMessage, 'error');
                    }
                }
            });
        });
    });
</script>
@endsection
