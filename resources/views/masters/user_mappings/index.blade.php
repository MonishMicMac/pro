@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700" rel="stylesheet"/>

<style>
    .glass-panel { 
        background: rgba(255, 255, 255, 0.70); 
        backdrop-filter: blur(12px) saturate(180%); 
        border: 1px solid rgba(255, 255, 255, 0.4); 
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); 
    }
    .glass-card { 
        background: rgba(255, 255, 255, 0.4); 
        backdrop-filter: blur(4px); 
        border: 1px solid rgba(255, 255, 255, 0.2); 
        transition: all 0.2s ease;
    }
    .glass-card:hover { 
        background: rgba(255, 255, 255, 0.9); 
        transform: translateY(-2px); 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .user-badge { 
        padding: 2px 8px; border-radius: 6px; font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.05em; display: inline-block;
    }
    .cell-actions { display: flex; align-items: center; gap: 4px; opacity: 0; transition: opacity 0.2s ease; }
    tr:hover .cell-actions { opacity: 1; }

    #table-pagination .paginate_button { 
        padding: 4px 10px; margin: 0 2px; border-radius: 8px; background: white;
        color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; min-width: 28px;
        transition: all 0.2s;
    }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; }
    
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    table.dataTable tbody tr td { padding: 12px 16px; }
</style>

{{-- Main Container: Set to 'relative' so absolute modal stays inside --}}
<div class="relative flex-1 p-6 space-y-4 pb-24 bg-[#f8fafc] min-h-screen">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Masters</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Hierarchy Mapping</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Organization Hierarchy</h1>
        </div>
        @can('user-mappings.create')
        <button onclick="openAddModal()" class="px-5 py-2.5 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] font-bold">group_add</span> Bulk Map BDOs
        </button>
        @endcan
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-10 pr-4 py-2 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Quick Search..." type="text"/>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="mapping-table">
                <thead>
                    <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer focus:ring-0" type="checkbox"/></th>
                        <th class="px-4 pb-2">MD</th>
                        <th class="px-4 pb-2">VP</th>
                        <th class="px-4 pb-2">ISM</th>
                        <th class="px-4 pb-2">ZSM</th>
                        <th class="px-4 pb-2">BDM</th>
                        <th class="px-4 pb-2">BDO</th>
                        <th class="px-4 pb-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700"></tbody>
            </table>
        </div>
        
        <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
            <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest"></p>
            <div id="table-pagination" class="flex items-center gap-1"></div>
        </div>
    </div>

    {{-- 1. FULL SCREEN BACKDROP: Covers sidebar and entire browser window --}}
    <div id="modalBackdrop" class="fixed inset-0 z-[998] hidden bg-slate-950/60 backdrop-blur-sm transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    {{-- 2. MODAL WRAPPER: Centers modal box ONLY within the white content area --}}
    <div id="mappingModal" class="absolute inset-0 z-[999] hidden opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-6" style="position: absolute; top: -125px;">
        <div class="modal-content glass-panel relative z-[1000] w-full max-w-3xl rounded-[1.5rem] p-5 md:p-8 shadow-2xl transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto">
            <form id="mappingForm">
                @csrf
                <input type="hidden" name="id" id="mapping_id">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-slate-800" id="modalTitle">Hierarchy Assignment</h3>
                    <button type="button" onclick="closeModal()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                        <span class="material-symbols-outlined text-[22px]">close</span>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 p-5 bg-blue-50/50 rounded-2xl border border-blue-100/50">
                    @foreach(['md_id', 'vp_id', 'ism_id', 'zsm_id', 'bdm_id'] as $field)
                        @php $varName = str_replace('_id', '', $field) . 's'; @endphp
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase mb-1.5 ml-1">{{ strtoupper(str_replace('_id', '', $field)) }}</label>
                            <select name="{{ $field }}" class="w-full text-xs font-bold rounded-xl border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" required>
                                <option value="">Select Manager</option>
                                @if(isset($$varName))
                                    @foreach($$varName as $user) 
                                        <option value="{{ $user->id }}">{{ $user->name }}</option> 
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center ml-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Target BDO Selection</label>
                        <button type="button" onclick="$('.bdo-chk').prop('checked', true)" class="text-[10px] font-black text-blue-600 uppercase hover:underline">Select All</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-4 bg-slate-50/50 rounded-2xl border border-slate-200/60">
                        @foreach($bdos as $bdo)
                        <label class="flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
                            <input type="checkbox" name="bdo_ids[]" value="{{ $bdo->id }}" class="bdo-chk w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-0">
                            <span class="text-[11px] font-bold text-slate-600 group-hover:text-blue-600 transition-colors">{{ $bdo->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3.5 text-slate-400 uppercase text-[11px] font-black tracking-widest hover:text-slate-600 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-3.5 bg-slate-900 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-xl shadow-slate-900/20 hover:bg-black transition-all">Apply Mapping</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Floating bar --}}
<div id="floating-bar" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[900] bg-slate-900 rounded-2xl px-6 py-3 flex items-center gap-6 shadow-2xl transition-all duration-500 translate-y-32 opacity-0 pointer-events-none">
    <div class="flex items-center gap-3 text-white border-r border-slate-700 pr-6">
        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-500 text-[11px] font-black shadow-lg shadow-blue-500/30" id="selected-count">0</span>
        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Rows Selected</span>
    </div>
    <div class="flex items-center gap-5">
        @can('user-mappings.edit')
        <button id="floating-edit" class="hidden flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-black uppercase tracking-widest">
            <span class="material-symbols-outlined text-[20px]">edit_square</span> Edit
        </button>
        @endcan
        @can('user-mappings.delete')
        <button onclick="handleBulkDelete()" class="flex items-center gap-2 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-black uppercase tracking-widest">
            <span class="material-symbols-outlined text-[20px]">delete_sweep</span> Delete
        </button>
        @endcan
    </div>
</div>

<script>
let roleLists = {};

function toggleModal(show) {
    const modal = $('#mappingModal');
    const backdrop = $('#modalBackdrop');
    if(show) {
        modal.removeClass('hidden');
        backdrop.removeClass('hidden');
        setTimeout(() => {
            backdrop.removeClass('pointer-events-none opacity-0').addClass('opacity-100');
            modal.removeClass('pointer-events-none opacity-0').addClass('opacity-100');
            modal.find('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    } else {
        modal.find('.modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        backdrop.addClass('pointer-events-none opacity-0').removeClass('opacity-100');
        modal.addClass('pointer-events-none opacity-0').removeClass('opacity-100');
        setTimeout(() => {
            modal.addClass('hidden');
            backdrop.addClass('hidden');
        }, 200);
    }
}

window.openAddModal = function() {
    $('#mappingForm')[0].reset();
    $('#mapping_id').val('');
    $('#modalTitle').text('Bulk Hierarchy Assignment');
    $('.bdo-chk').prop('checked', false);
    toggleModal(true);
};

window.openEditModal = function(id) {
    $.get(`{{ url('masters/user-mappings') }}/${id}/edit`, function(data) {
        $('#mappingForm')[0].reset();
        $('#mapping_id').val(data.id);
        $('#modalTitle').text('Edit Hierarchy Mapping');
        ['md_id', 'vp_id', 'ism_id', 'zsm_id', 'bdm_id'].forEach(f => $(`select[name="${f}"]`).val(data[f]));
        $('.bdo-chk').prop('checked', false);
        $(`.bdo-chk[value="${data.bdo_id}"]`).prop('checked', true);
        toggleModal(true);
    });
};

window.inlineEdit = async (id, field, label) => {
    const users = roleLists[field];
    if(!users) return;
    let options = {};
    users.forEach(u => options[u.id] = u.name);

    const { value: selectedId } = await Swal.fire({
        title: `Assign ${label}`,
        input: 'select',
        inputOptions: options,
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
    });

    if (selectedId) {
        $.post("{{ route('masters.user-mappings.updateField') }}", {
            _token: "{{ csrf_token() }}", id: id, field: field, value: selectedId
        }, () => {
            $('#mapping-table').DataTable().draw(false);
            Swal.fire({ icon: 'success', title: 'Updated', toast: true, position: 'top-end', timer: 1500 });
        }).fail((xhr) => {
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.error || 'Update failed' });
        });
    }
};

$(document).ready(function() {
    const table = $('#mapping-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('masters.user-mappings.data') }}",
        createdRow: (row) => $(row).addClass('glass-card group'),
        columns: [
            { data: 'id', orderable: false, render: (d) => `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer focus:ring-0" type="checkbox" value="${d}"/>` },
            { data: 'md.name', render: (d, t, r) => renderCell(d, r, 'md_id', 'MD', 'bg-slate-900 text-white') },
            { data: 'vp.name', render: (d, t, r) => renderCell(d, r, 'vp_id', 'VP', 'bg-blue-50 text-blue-600 border border-blue-100') },
            { data: 'ism.name', render: (d, t, r) => renderCell(d, r, 'ism_id', 'ISM', 'bg-indigo-50 text-indigo-600 border border-indigo-100') },
            { data: 'zsm.name', render: (d, t, r) => renderCell(d, r, 'zsm_id', 'ZSM', 'bg-purple-50 text-purple-600 border border-purple-100') },
            { data: 'bdm.name', render: (d, t, r) => renderCell(d, r, 'bdm_id', 'BDM', 'bg-emerald-50 text-emerald-600 border border-emerald-100') },
            { data: 'bdo.name', render: (d, t, r) => renderCell(d, r, 'bdo_id', 'BDO', 'bg-amber-50 text-amber-700 border border-amber-100') },
            { data: 'id', className: 'text-center', render: (id, t, row) => {
                if(!row.can_delete) return '';
                return `<button onclick="deleteRow(${id})" class="text-rose-400 hover:text-rose-600 hover:scale-125 transition-all"><span class="material-symbols-outlined text-[20px]">delete</span></button>`;
            }}
        ],
        dom: 'rtp',
        language: {
            paginate: { 
                previous: '<span class="material-symbols-outlined">arrow_back_ios</span>', 
                next: '<span class="material-symbols-outlined">arrow_forward_ios</span>' 
            }
        },
        drawCallback: function(settings) {
            roleLists = settings.json.role_users;
            $('#table-info').text(`Total Hierarchies: ${settings.json.recordsTotal}`);
            $('#table-pagination').html($('.dataTables_paginate').html());
            $('.dataTables_paginate').empty();
            
            $('#table-pagination .paginate_button').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled') || $(this).hasClass('current')) return;
                if ($(this).hasClass('previous')) table.page('previous').draw('page');
                else if ($(this).hasClass('next')) table.page('next').draw('page');
                else table.page(parseInt($(this).text()) - 1).draw('page');
            });
            updateFloatingBar();
        }
    });

    // function renderCell(name, row, field, label, badgeClass) {
    //     return `<div class="flex items-center justify-between gap-2 min-w-[140px]">
    //                 <span class="user-badge ${name ? badgeClass : 'bg-slate-50 text-slate-300 italic font-normal border border-slate-100'}">${name || 'Unassigned'}</span>
    //                 <div class="cell-actions">
    //                     ${row.can_edit ? `
    //                         <button onclick="inlineEdit(${row.id}, '${field}', '${label}')" class="text-slate-400 hover:text-blue-500 transition-colors" title="Quick Change"><span class="material-symbols-outlined text-[16px] font-bold">sync</span></button>
    //                     ` : ''}
    //                 </div>
    //             </div>`;
    // }
    function renderCell(name, row, field, label, badgeClass) {
        // CHANGED: 'justify-between' -> 'justify-start'
        return `<div class="flex items-center justify-start gap-2 min-w-[140px]">
                    <span class="user-badge ${name ? badgeClass : 'bg-slate-50 text-slate-300 italic font-normal border border-slate-100'}">${name || 'Unassigned'}</span>
                    <div class="cell-actions">
                        ${row.can_edit ? `
                            <button onclick="inlineEdit(${row.id}, '${field}', '${label}')" class="text-slate-400 hover:text-blue-500 transition-colors" title="Quick Change"><span class="material-symbols-outlined text-[16px] font-bold">sync</span></button>
                        ` : ''}
                    </div>
                </div>`;
    }

    $(document).on('change', '#selectAll, .row-checkbox', function() {
        if($(this).attr('id') === 'selectAll') $('.row-checkbox').prop('checked', $(this).prop('checked'));
        updateFloatingBar();
    });

    function updateFloatingBar() {
        const checked = $('.row-checkbox:checked');
        const count = checked.length;
        $('#selected-count').text(count);

        if(count > 0) {
            $('#floating-bar').removeClass('translate-y-32 opacity-0 pointer-events-none').addClass('translate-y-0 opacity-100 pointer-events-auto');
            if(count === 1) $('#floating-edit').removeClass('hidden');
            else $('#floating-edit').addClass('hidden');
        } else {
            $('#floating-bar').addClass('translate-y-32 opacity-0 pointer-events-none').removeClass('translate-y-0 opacity-100 pointer-events-auto');
        }
    }

    $('#floating-edit').click(function() {
        const id = $('.row-checkbox:checked').first().val();
        openEditModal(id);
    });

    $('#mappingForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('masters.user-mappings.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() {
                table.draw(false); closeModal();
                Swal.fire({ icon: 'success', title: 'Hierarchy Saved', toast: true, position: 'top-end', timer: 2000 });
            },
            error: function(xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message || "An error occurred" });
            }
        });
    });

    window.deleteRow = (id) => {
        Swal.fire({ title: 'Delete Row?', text: "This mapping will be removed permanently.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48' }).then(res => {
            if(res.isConfirmed) $.ajax({ url: `{{ url('masters/user-mappings') }}/${id}`, type: 'DELETE', data: { _token: "{{ csrf_token() }}" }, success: () => table.draw(false) });
        });
    };

    window.handleBulkDelete = () => {
        const ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
        Swal.fire({ title: `Delete ${ids.length} mappings?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48' }).then(res => {
            if(res.isConfirmed) $.post("{{ route('masters.user-mappings.bulkDelete') }}", { _token: "{{ csrf_token() }}", ids: ids }, () => {
                table.draw(false);
                $('#selectAll').prop('checked', false);
            });
        });
    };

    window.closeModal = () => toggleModal(false);
    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
});
</script>
@endsection