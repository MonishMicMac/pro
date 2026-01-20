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
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Masters</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Branch</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Branch Master</h1>
        </div>
        
        <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] font-bold">add</span> Add Branch
        </button>
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none" placeholder="Search branches..." type="text"/>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="branch-table">
                <thead>
                    <tr class="text-left">
                        <th class="pl-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox"/></th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Branch Name</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Status</th>
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

<div id="BranchModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/0 backdrop-blur-0 transition-all duration-300 opacity-0 pointer-events-none">
    <div class="modal-content glass-panel w-full max-w-sm rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
        <form id="BranchForm">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" id="Branch_id" name="id">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-slate-800" id="modalTitle">Add New Branch</h3>
                <button type="button" onclick="closeModal('BranchModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Full Branch Name</label>
                    <input type="text" name="name" id="branch_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all" placeholder="" required>
                </div>
                
                {{-- <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Platform Status</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="action" id="branch_status" class="sr-only peer" value="0" checked>
                        <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                </div> --}}
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closeModal('BranchModal')" class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-slate-900/20">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        // Robust CSRF Setup
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

        let editMode = false;
        const table = $('#branch-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('masters.branches.index') }}",
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
            },
            columns: [
                { data: 'id', orderable: false, render: (d) => `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${d}"/>` },
                { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                { data: 'name', name: 'name' },
                { 
                    data: 'action', 
                    className: 'text-center',
                    render: (d) => d == 0 
                        ? `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest"><span class="w-1 h-1 bg-green-500 rounded-full"></span>Active</span>`
                        : `<span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase tracking-widest">Inactive</span>`
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
            }
        });

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        // Modal Controls
        window.openAddModal = () => {
            editMode = false;
            $('#BranchForm')[0].reset();
            $('#Branch_id').val('');
            $('#modalTitle').text('Add New Branch');
            $('#BranchModal').removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
            $('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        };

        window.closeModal = (id) => {
            $('.modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            $('#' + id).removeClass('opacity-100 bg-slate-900/30 backdrop-blur-sm').addClass('pointer-events-none opacity-0');
        };

        // Edit via Floating Bar
        $('#floating-edit').click(function() {
            const id = $('.row-checkbox:checked').first().val();
            editMode = true;
            $.get(`{{ url('') }}/masters/branches/${id}/edit`, function(data) {
                $('#modalTitle').text('Update Branch');
                $('#Branch_id').val(data.id);
                $('#branch_name').val(data.name);
                // $('#branch_status').prop('checked', data.action == 0);
                $('#BranchModal').removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
                $('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            });
        });

        // Form Submission (Add/Update)
        $('#BranchForm').submit(function(e) {
            e.preventDefault();

            const id = $('#Branch_id').val();

            if(editMode){
                $('#method').val('PUT');
            }else{
                $('#method').val('POST');
            }

            const url = editMode
                ? `{{ url('') }}/masters/branches/${id}`
                : "{{ route('masters.branches.store') }}";

            $.ajax({
                url: url,
                type: 'POST', // always POST
                data: $(this).serialize(),
                success: function() {
                    closeModal('BranchModal');

                    // REDRAW TABLE
                    table.draw(false);

                    // CLEAR CHECKBOXES
                    $('#selectAll').prop('checked', false);
                    $('.row-checkbox').prop('checked', false);

                    // RESET FLOAT BAR
                    $('#selected-count').text(0);
                    $('#floating-bar')
                        .addClass('translate-y-20 opacity-0 pointer-events-none')
                        .removeClass('translate-y-0 opacity-100 pointer-events-auto');

                    // RESET MODE
                    editMode = false;
                }
            });
        });


        // Bulk Actions & Floating Bar Logic
        $(document).on('change', '#selectAll, .row-checkbox', function() {
            if($(this).attr('id') === 'selectAll') $('.row-checkbox').prop('checked', $(this).prop('checked'));
            const count = $('.row-checkbox:checked').length;
            $('#selected-count').text(count);
            
            if(count > 0) {
                $('#floating-bar').removeClass('translate-y-20 opacity-0 pointer-events-none').addClass('translate-y-0 opacity-100 pointer-events-auto');
                count === 1 ? $('#floating-edit').removeClass('hidden') : $('#floating-edit').addClass('hidden');
            } else {
                $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none').removeClass('translate-y-0 opacity-100 pointer-events-auto');
            }
        });
        

        // Bulk Delete Action
      window.handleBulkDelete = () => {

        const ids = [];

        $('.row-checkbox:checked').each(function () {
            ids.push($(this).val());
        });

        if(ids.length === 0){
            alert('Select at least one record');
            return;
        }
        if(!confirm(`Are you sure you want to delete ${ids.length} selected branch(s)?`)){
            return;
        }

        $.ajax({
            url: "{{ route('masters.branches.bulkDelete') }}",
            type: "POST",
            data: { ids: ids },

            success: function () {

                table.draw(false);

                // RESET CHECKBOXES
                $('#selectAll').prop('checked', false);
                $('.row-checkbox').prop('checked', false);

                // RESET FLOAT BAR
                $('#selected-count').text(0);
                $('#floating-bar')
                    .addClass('translate-y-20 opacity-0 pointer-events-none')
                    .removeClass('translate-y-0 opacity-100 pointer-events-auto');
            },

            error:function(xhr){
                alert(xhr.responseJSON?.error ?? 'Server error');
            }
        });
    };

    });
</script>
@endsection