@extends('layouts.app')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(12px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 4px 20px 0 rgba(31, 38, 135, 0.05); }
        .glass-card { background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2); @apply transition-all duration-200; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.9); transform: translateY(-1px); }
    }
    
    #table-pagination .paginate_button { @apply px-2 py-1 mx-0.5 rounded-md border-none bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px]; }
    #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md shadow-blue-500/30; }
    
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    table.dataTable tbody tr td { @apply py-3 px-4; } 

    #typeModal.pointer-events-none { visibility: hidden; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Masters</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Account Type</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account Type Master</h1>
        </div>
        
        <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] font-bold">add</span> Add Type
        </button>
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none" placeholder="Search type..." type="text"/>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="type-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="pl-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox"/></th>
                        <th class="px-4 pb-2">ID</th>
                        <th class="px-4 pb-2">Type Name</th>
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

<div id="floating-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 rounded-2xl px-5 py-2.5 flex items-center gap-5 shadow-2xl transition-all duration-500 translate-y-20 opacity-0 pointer-events-none">
    <div class="flex items-center gap-2 text-white border-r border-slate-700 pr-4">
        <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-black" id="selected-count">0</span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
    </div>
    <div class="flex items-center gap-4">
        <button id="floating-edit" class="hidden flex items-center gap-1.5 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-bold uppercase tracking-widest">
            <span class="material-symbols-outlined text-[18px]">edit_square</span> Edit
        </button>
        <button onclick="handleBulkDelete()" class="flex items-center gap-1.5 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-bold uppercase tracking-widest">
            <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Delete
        </button>
    </div>
</div>

<div id="typeModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/0 backdrop-blur-0 transition-all duration-300 opacity-0 pointer-events-none">
    <div class="modal-content glass-panel w-full max-w-sm rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
        <form id="typeForm">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" id="type_id" name="id">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-slate-800" id="modalTitle">Add Account Type</h3>
                <button type="button" onclick="closeModal('typeModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Type Name</label>
                    <input type="text" name="name" id="type_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all" required>
                </div>
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closeModal('typeModal')" class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        const token = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });

        let editMode = false;
        let selectedIds = [];
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });

        const table = $('#type-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('masters.account_types.index') }}",
            createdRow: (row) => $(row).addClass('glass-card group'),
            columns: [
                { data: 'id', orderable: false, render: (d) => `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${d}"/>` },
                { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                { data: 'name', name: 'name' },
                { 
                    data: 'action', 
                    className: 'text-center',
                    render: (d) => `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest"><span class="w-1 h-1 bg-green-500 rounded-full"></span>Active</span>`
                }
            ],
            dom: 'rtp',
            drawCallback: function(settings) {
                $('#table-info').text(`Total Records: ${settings.json ? settings.json.recordsTotal : 0}`);
                $('#table-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
                updateFloatingBar();
                
                $('#table-pagination .paginate_button').on('click', function(e) {
                    e.preventDefault();
                    if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;
                    if ($(this).hasClass('previous')) table.page('previous').draw('page');
                    else if ($(this).hasClass('next')) table.page('next').draw('page');
                    else table.page(parseInt($(this).text()) - 1).draw('page');
                });
            }
        });

        function updateFloatingBar() {
            const count = selectedIds.length;
            $('#selected-count').text(count);
            if (count > 0) {
                $('#floating-bar').removeClass('translate-y-20 opacity-0 pointer-events-none').addClass('translate-y-0 opacity-100 pointer-events-auto');
                count === 1 ? $('#floating-edit').removeClass('hidden') : $('#floating-edit').addClass('hidden');
            } else {
                $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none').removeClass('translate-y-0 opacity-100 pointer-events-auto');
            }
        }

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        window.openAddModal = () => {
            editMode = false;
            $('#typeForm')[0].reset();
            $('#type_id').val('');
            $('#method').val('POST');
            $('#modalTitle').text('Add Account Type');
            toggleModal('typeModal', true);
        };

        window.closeModal = (id) => toggleModal(id, false);

        function toggleModal(id, show) {
            const el = $('#' + id);
            const content = el.find('.modal-content');
            if(show) {
                el.removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
                content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            } else {
                content.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
                el.addClass('pointer-events-none opacity-0').removeClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
            }
        }

        $('#floating-edit').click(function() {
            const id = selectedIds[0];
            editMode = true;
            
            // FIX: Changed 'account_types' to 'account-types' (hyphen)
            $.get(`{{ url('') }}/masters/account-types/${id}/edit`, function(data) {
                $('#modalTitle').text('Update Account Type');
                $('#type_id').val(data.id);
                $('#type_name').val(data.name);
                $('#method').val('PUT');
                toggleModal('typeModal', true);
            });
        });

        $('#typeForm').submit(function(e) {
            e.preventDefault();
            const id = $('#type_id').val();
            
            // FIX: Changed 'account_types' to 'account-types' (hyphen)
            const url = editMode ? `{{ url('') }}/masters/account-types/${id}` : "{{ route('masters.account_types.store') }}";
            
            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(),
                success: function() {
                    toggleModal('typeModal', false);
                    table.draw(false);
                    Toast.fire({ icon: 'success', title: editMode ? 'Type Updated' : 'Type Created' });
                    selectedIds = [];
                    updateFloatingBar();
                }
            });
        });

        $(document).on('change', '#selectAll, .row-checkbox', function () {
            if ($(this).attr('id') === 'selectAll') {
                $('.row-checkbox').each(function () {
                    const id = $(this).val();
                    if ($('#selectAll').is(':checked')) {
                        $(this).prop('checked', true);
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    } else {
                        $(this).prop('checked', false);
                        selectedIds = selectedIds.filter(x => x != id);
                    }
                });
            } else {
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
                title: 'Deactivate Types?',
                text: `You are about to deactivate ${selectedIds.length} record(s).`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Yes, Deactivate'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('masters.account_types.bulkDelete') }}",
                        type: "POST",
                        data: { ids: selectedIds },
                        success: function() {
                            table.draw(false);
                            selectedIds = [];
                            updateFloatingBar();
                            Toast.fire({ icon: 'success', title: 'Records deactivated' });
                        }
                    });
                }
            });
        };
    });
</script>
@endsection