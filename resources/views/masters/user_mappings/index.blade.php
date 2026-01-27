@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700" rel="stylesheet"/>

<style>
    /* Glassmorphism & UI Utilities */
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
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    
    /* DataTable Overrides */
    #table-pagination .paginate_button { 
        padding: 4px 10px; margin: 0 2px; border-radius: 8px; background: white;
        color: #64748b; font-weight: 700; font-size: 11px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; min-width: 28px;
        transition: all 0.2s;
    }
    #table-pagination .paginate_button.current { background: #2563eb; color: white; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.65rem !important; }
    table.dataTable tbody tr td { padding: 12px 16px; }

    /* Mind Map Styles */
    #mindmap-container {
        display: none;
        background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
        min-height: 700px;
        position: relative;
        overflow: hidden;
    }
    .node circle { stroke-width: 2px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .node text { font-family: 'Inter', sans-serif; font-size: 10px; font-weight: 700; fill: #475569; }
    .link { fill: none; stroke: #cbd5e1; stroke-width: 1.5px; opacity: 0.6; }
    .node:hover circle { filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.4)); stroke-width: 3px; }
    .node--md circle { fill: #0f172a; stroke: #0f172a; }
    .node--vp circle { fill: #2563eb; stroke: #2563eb; }
    .node--ism circle { fill: #4f46e5; stroke: #4f46e5; }
    .node--zsm circle { fill: #9333ea; stroke: #9333ea; }
    .node--bdm circle { fill: #059669; stroke: #059669; }
    .node--bdo circle { fill: #d97706; stroke: #d97706; }
    .node--root circle { fill: #64748b; stroke: #64748b; }
    
    .map-controls {
        position: absolute; bottom: 24px; right: 24px; display: flex; gap: 8px; z-index: 10;
    }
    .control-btn {
        width: 40px; height: 40px; border-radius: 12px;
        background: white; border: 1px solid rgba(0,0,0,0.05);
        color: #64748b; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.2s;
    }
    .control-btn:hover { background: #f8fafc; color: #2563eb; transform: translateY(-1px); }
</style>

<div class="relative flex-1 p-6 lg:p-10 space-y-8 pb-24 bg-[#f8fafc] min-h-screen">
    
    {{-- Header Section --}}
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1">
                <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>Masters</span>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    <span class="text-blue-600">Hierarchy Mapping</span>
                </nav>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Organization Hierarchy</h1>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                {{-- View Toggle --}}
                <div class="flex bg-slate-200/50 p-1 rounded-xl gap-1">
                    <button onclick="switchView('table')" id="view-table-btn" class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg bg-white shadow-sm text-blue-600 transition-all">Table</button>
                    <button onclick="switchView('mindmap')" id="view-mindmap-btn" class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg text-slate-500 hover:text-slate-700 transition-all">Mind Map</button>
                </div>

                @can('user-mappings.create')
                <div class="flex items-center gap-2">
                    <button onclick="openAddModal('ism_zsm')" class="h-10 px-4 bg-indigo-600 text-white text-[10px] font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">account_tree</span> ISM
                    </button>
                    <button onclick="openAddModal('zsm_bdm')" class="h-10 px-4 bg-purple-600 text-white text-[10px] font-bold rounded-xl shadow-lg shadow-purple-200 hover:bg-purple-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">hub</span> ZSM
                    </button>
                    <button onclick="openAddModal('bdm_bdo')" class="h-10 px-4 bg-blue-600 text-white text-[10px] font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">group_add</span> BDM
                    </button>
                    <div class="w-px h-6 bg-slate-200 mx-1"></div>
                    <button onclick="openAddModal('full')" class="h-10 px-4 bg-slate-800 text-white text-[10px] font-bold rounded-xl shadow-lg shadow-slate-200 hover:bg-slate-950 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">segment</span> Full Map
                    </button>
                </div>
                @endcan
            </div>
        </div>

        {{-- Table Container --}}
        <div class="glass-panel rounded-[2rem] overflow-hidden border border-white/40 shadow-2xl shadow-slate-200/50">
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

        {{-- Mind Map Section --}}
        <div id="mindmap-container" class="glass-panel rounded-[2rem] overflow-hidden min-h-[700px] border border-white/40 shadow-2xl">
            <div id="hierarchy-map" class="w-full h-full min-h-[700px]"></div>
            <div class="map-controls">
                <button onclick="resetZoom()" class="control-btn" title="Reset View"><span class="material-symbols-outlined">restart_alt</span></button>
                <button onclick="zoomIn()" class="control-btn"><span class="material-symbols-outlined">add</span></button>
                <button onclick="zoomOut()" class="control-btn"><span class="material-symbols-outlined">remove</span></button>
            </div>
        </div>
    </div>

    {{-- 1. FULL SCREEN BACKDROP --}}
    <div id="modalBackdrop" class="fixed inset-0 z-[9998] hidden bg-slate-950/70 backdrop-blur-[2px] transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    {{-- 2. MODAL WRAPPER --}}
    <div id="mappingModal" class="fixed inset-0 z-[9999] hidden opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
        
        {{-- Modal Content: Compact Width (max-w-xl) --}}
        <div class="modal-content glass-panel relative w-full max-w-xl rounded-[1.5rem] p-6 shadow-2xl transform scale-95 transition-all duration-300 max-h-[85vh] overflow-y-auto border border-white/60 bg-white/90">
            
            <form id="mappingForm">
                @csrf
                <input type="hidden" name="id" id="mapping_id">
                
                {{-- Compact Header --}}
                <div class="flex justify-between items-start mb-6 pb-4 border-b border-slate-100/80">
                    <div>
                        <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Configuration</span>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight mt-0.5" id="modalTitle">Hierarchy Assignment</h3>
                    </div>
                    <button type="button" onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                
                <input type="hidden" name="mapping_type" id="mapping_type" value="bdm_bdo">
                
                {{-- Compact Form Grid --}}
                <div class="space-y-4 mb-6">
                    @foreach(['md_id', 'vp_id', 'ism_id', 'zsm_id', 'bdm_id'] as $field)
                        @php 
                            $varName = str_replace('_id', '', $field) . 's'; 
                            $label = strtoupper(str_replace('_id', '', $field));
                        @endphp
                        <div id="field-{{ $field }}" class="space-y-1.5 group hidden">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider ml-1">{{ $label }}</label>
                            <div class="relative">
                                {{-- Added 'bg-none' to remove default arrow, and 'disabled selected' to placeholder --}}
                                <select name="{{ $field }}" class="w-full appearance-none bg-none bg-slate-50 text-xs font-bold text-slate-700 rounded-xl border border-slate-200 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all py-3 px-4 pr-10 cursor-pointer hover:border-blue-300">
                                    <option value="" disabled selected>Select {{ $label }}</option>
                                    @if(isset($$varName))
                                        @foreach($$varName as $user) 
                                            <option value="{{ $user->id }}">{{ $user->name }}</option> 
                                        @endforeach
                                    @endif
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                            </div>
                        </div>
                    @endforeach

                    {{-- 1. ZSM Checkbox Grid (Compact) --}}
                    <div id="field-zsm_chk" class="hidden space-y-2 pt-2">
                        <div class="flex justify-between items-end px-1 border-b border-slate-100 pb-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select ZSMs</label>
                            <div class="flex gap-2">
                                <button type="button" onclick="$('.zsm-chk').prop('checked', false)" class="text-[9px] font-bold text-slate-400 hover:text-slate-600 uppercase">Clear</button>
                                <button type="button" onclick="$('.zsm-chk').prop('checked', true)" class="text-[9px] font-black text-blue-600 hover:text-blue-700 uppercase">Select All</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
                            @if(isset($zsms))
                                @foreach($zsms as $user) 
                                    <label class="relative flex items-center gap-3 p-2.5 bg-white border border-slate-100 rounded-lg cursor-pointer hover:border-blue-400 hover:shadow-sm transition-all group select-none">
                                        <input type="checkbox" name="zsm_id[]" value="{{ $user->id }}" class="zsm-chk peer w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-0">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-[10px] font-bold text-slate-600 peer-checked:text-blue-700 transition-colors truncate">{{ $user->name }}</span>
                                            <span class="text-[8px] font-semibold text-slate-300 peer-checked:text-blue-400 uppercase tracking-wider">ZSM</span>
                                        </div>
                                        <div class="absolute inset-0 rounded-lg border border-transparent peer-checked:border-blue-500/20 pointer-events-none"></div>
                                    </label>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- 2. BDM Checkbox Grid (Compact) --}}
                    <div id="field-bdm_ids" class="hidden space-y-2 pt-2">
                        <div class="flex justify-between items-end px-1 border-b border-slate-100 pb-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select BDMs</label>
                            <button type="button" onclick="$('.bdm-chk').prop('checked', true)" class="text-[9px] font-black text-blue-600 hover:text-blue-700 uppercase">Select All</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1">
                            @if(isset($bdms))
                                @foreach($bdms as $user) 
                                    <label class="relative flex items-center gap-3 p-2.5 bg-white border border-slate-100 rounded-lg cursor-pointer hover:border-emerald-400 hover:shadow-sm transition-all group select-none">
                                        <input type="checkbox" name="bdm_ids[]" value="{{ $user->id }}" class="bdm-chk peer w-3.5 h-3.5 rounded border-slate-300 text-emerald-600 focus:ring-0">
                                        <span class="text-[10px] font-bold text-slate-600 peer-checked:text-emerald-700 transition-colors truncate">{{ $user->name }}</span>
                                        <div class="absolute inset-0 rounded-lg border border-transparent peer-checked:border-emerald-500/20 pointer-events-none"></div>
                                    </label>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- BDO Selection (Compact) --}}
                    <div class="space-y-2 pt-2 hidden" id="field-bdo_ids">
                        <div class="flex justify-between items-end px-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target BDOs</label>
                            <button type="button" onclick="$('.bdo-chk').prop('checked', true)" class="text-[9px] font-black text-blue-600 hover:text-blue-700 uppercase">Select All</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-3 bg-slate-50/50 rounded-xl border border-slate-100">
                            @foreach($bdos as $bdo)
                            <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-blue-400 transition-all group">
                                <input type="checkbox" name="bdo_ids[]" value="{{ $bdo->id }}" class="bdo-chk w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-0">
                                <span class="text-[10px] font-bold text-slate-600 group-hover:text-blue-600 transition-colors truncate">{{ $bdo->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-slate-100 text-slate-500 rounded-xl uppercase text-[10px] font-black tracking-widest hover:bg-slate-200 hover:text-slate-800 transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="flex-[1.5] py-3 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Floating bar --}}
<div id="floating-bar" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[900] bg-slate-900 rounded-2xl px-6 py-3 flex items-center gap-6 shadow-2xl transition-all duration-500 translate-y-32 opacity-0 pointer-events-none">
    <div class="flex items-center gap-3 text-white border-r border-slate-700 pr-6">
        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-500 text-[11px] font-black shadow-lg shadow-blue-500/30" id="selected-count">0</span>
        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Selected</span>
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

window.openAddModal = function(mode = 'full') {
    $('#mappingForm')[0].reset();
    $('#mapping_id').val('');
    $('#mapping_type').val(mode);
    $('.bdo-chk').prop('checked', false);
    $('.zsm-chk').prop('checked', false);
    $('.bdm-chk').prop('checked', false);
    
    // Hide all fields first
    $('[id^="field-"]').addClass('hidden');
    $('select').prop('required', false);

    if (mode === 'ism_zsm') {
        $('#modalTitle').text('Map ISM to ZSMs');
        $('#field-ism_id').removeClass('hidden'); // Show ISM Dropdown
        $('#field-zsm_chk').removeClass('hidden'); // Show ZSM Checkbox Grid
        $('select[name="ism_id"]').prop('required', true);

    } else if (mode === 'zsm_bdm') {
        $('#modalTitle').text('Map ZSM to BDMs');
        $('#field-zsm_id').removeClass('hidden'); // Show ZSM Dropdown (Single)
        $('#field-bdm_ids').removeClass('hidden'); // Show BDM Checkbox Grid
        $('select[name="zsm_id"]').prop('required', true);
    
    } else if (mode === 'bdm_bdo') {
        $('#modalTitle').text('Map BDM to BDOs');
        $('#field-bdm_id').removeClass('hidden');
        $('#field-bdo_ids').removeClass('hidden');
        $('select[name="bdm_id"]').prop('required', true);
    
    } else {
        // Full Hierarchy Mode
        $('#modalTitle').text('Full Hierarchy Assignment');
        $('#field-md_id, #field-vp_id, #field-ism_id, #field-zsm_id, #field-bdm_id').removeClass('hidden');
        $('#field-bdo_ids').removeClass('hidden');
        $('#field-md_id select, #field-vp_id select, #field-ism_id select, #field-zsm_id select, #field-bdm_id select').prop('required', true);
        if(mode === 'undefined') $('#mapping_type').val('full');
    }

    toggleModal(true);
};

window.openEditModal = function(id) {
    $.get(`{{ url('masters/user-mappings') }}/${id}/edit`, function(data) {
        $('#mappingForm')[0].reset();
        $('#mapping_id').val(data.id);
        $('#modalTitle').text('Edit Hierarchy Mapping');
        
        // Populate fields
        ['md_id', 'vp_id', 'ism_id', 'zsm_id', 'bdm_id'].forEach(f => {
            $(`select[name="${f}"]`).val(data[f]).trigger('change');
        });
        
        // Handle Target BDO
        $('.bdo-chk').prop('checked', false);
        $(`.bdo-chk[value="${data.bdo_id}"]`).prop('checked', true);
        
        // Set to full mode for edit
        $('#mapping_type').val('full');
        $('[id^="field-"]').addClass('hidden');
        $('#field-md_id, #field-vp_id, #field-ism_id, #field-zsm_id, #field-bdm_id, #field-bdo_ids').removeClass('hidden');

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
            { data: 'ism.name', render: (d, t, r) => renderCell(d, r, 'ism_id', 'ISM', 'bg-indigo-50 text-indigo-600 border border-indigo-100') },
            { data: 'zsm_names', render: (d, t, r) => renderCell(d, r, 'zsm_id', 'ZSM', 'bg-purple-50 text-purple-600 border border-purple-100') },
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

    function renderCell(name, row, field, label, badgeClass) {
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
        
        // Manual Checkbox Validation
        const type = $('#mapping_type').val();
        if(type === 'ism_zsm' && $('.zsm-chk:checked').length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select at least one ZSM.' });
            return;
        }
        if(type === 'zsm_bdm' && $('.bdm-chk:checked').length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select at least one BDM.' });
            return;
        }
        if((type === 'bdm_bdo' || type === 'full') && $('.bdo-chk:checked').length === 0 && !$('#mapping_id').val()) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select at least one BDO.' });
            return;
        }

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

    // --- Mind Map Logic (D3.js) ---
    let svg, g, zoom, root;
    let width, height;
    let mapInitialized = false;

    window.switchView = function(view) {
        if(view === 'table') {
            $('#view-table-btn').addClass('bg-white shadow-sm text-blue-600').removeClass('text-slate-500');
            $('#view-mindmap-btn').removeClass('bg-white shadow-sm text-blue-600').addClass('text-slate-500');
            $('.glass-panel:first').show();
            $('#mindmap-container').hide();
        } else {
            $('#view-mindmap-btn').addClass('bg-white shadow-sm text-blue-600').removeClass('text-slate-500');
            $('#view-table-btn').removeClass('bg-white shadow-sm text-blue-600').addClass('text-slate-500');
            $('.glass-panel:first').hide();
            $('#mindmap-container').show();
            if(!mapInitialized) initMindMap();
        }
    };

    function initMindMap() {
        width = $('#mindmap-container').width();
        height = 700;

        const margin = {top: 20, right: 120, bottom: 20, left: 120};

        svg = d3.select("#hierarchy-map")
            .append("svg")
            .attr("width", width)
            .attr("height", height)
            .attr("class", "cursor-grab");

        g = svg.append("g");

        zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on("zoom", (event) => {
                g.attr("transform", event.transform);
            });

        svg.call(zoom);

        d3.json("{{ route('masters.user-mappings.treeData') }}").then(data => {
            root = d3.hierarchy(data);
            root.x0 = height / 2;
            root.y0 = 0;
            if (root.children) root.children.forEach(collapse);
            updateMap(root);
            mapInitialized = true;
            resetZoom();
        });

        function collapse(d) {
            if (d.children) {
                d._children = d.children;
                d._children.forEach(collapse);
                d.children = null;
            }
        }
    }

    let i = 0;
    const duration = 750;

    function updateMap(source) {
        const tree = d3.tree().nodeSize([40, 200]);
        const treeData = tree(root);
        const nodes = treeData.descendants();
        const links = treeData.links();
        nodes.forEach(d => d.y = d.depth * 250);

        const node = g.selectAll('g.node').data(nodes, d => d.id || (d.id = ++i));

        const nodeEnter = node.enter().append('g')
            .attr('class', d => `node node--${d.data.role.toLowerCase()}`)
            .attr('transform', d => `translate(${source.y0},${source.x0})`)
            .on('click', (event, d) => {
                if (d.children) { d._children = d.children; d.children = null; } 
                else { d.children = d._children; d._children = null; }
                updateMap(d);
            })
            .style('cursor', 'pointer');

        nodeEnter.append('circle').attr('r', 6).style("fill", d => d._children ? null : "#fff");
        nodeEnter.append('text').attr("dy", ".35em").attr("x", d => d.children || d._children ? -13 : 13)
            .attr("text-anchor", d => d.children || d._children ? "end" : "start")
            .text(d => d.data.name).clone(true).lower()
            .attr("stroke-linejoin", "round").attr("stroke-width", 3).attr("stroke", "white");

        const nodeUpdate = nodeEnter.merge(node);
        nodeUpdate.transition().duration(duration).attr('transform', d => `translate(${d.y},${d.x})`);
        nodeUpdate.select('circle').attr('r', 6).style("fill", d => d._children ? null : "#fff");

        const nodeExit = node.exit().transition().duration(duration).attr('transform', d => `translate(${source.y},${source.x})`).remove();
        nodeExit.select('circle').attr('r', 0);
        nodeExit.select('text').style('fill-opacity', 0);

        const link = g.selectAll('path.link').data(links, d => d.target.id);
        const linkEnter = link.enter().insert('path', "g").attr('class', 'link').attr('d', d => {
            const o = {x: source.x0, y: source.y0}; return diagonal(o, o);
        });

        const linkUpdate = linkEnter.merge(link);
        linkUpdate.transition().duration(duration).attr('d', d => diagonal(d.source, d.target));
        const linkExit = link.exit().transition().duration(duration).attr('d', d => {
            const o = {x: source.x, y: source.y}; return diagonal(o, o);
        }).remove();

        nodes.forEach(d => { d.x0 = d.x; d.y0 = d.y; });
        function diagonal(s, t) { return `M ${s.y} ${s.x} C ${(s.y + t.y) / 2} ${s.x}, ${(s.y + t.y) / 2} ${t.x}, ${t.y} ${t.x}`; }
    }

    window.resetZoom = function() {
        if(!svg || !zoom || !root) return;
        const transform = d3.zoomIdentity.translate(150, height/2).scale(0.8);
        svg.transition().duration(750).call(zoom.transform, transform);
    };

    window.zoomIn = () => svg.transition().call(zoom.scaleBy, 1.3);
    window.zoomOut = () => svg.transition().call(zoom.scaleBy, 0.7);

    // --- Auto Selection Logic ---
    function fetchAndCheck(type, id, checkboxClass) {
        if (!id) { $(`.${checkboxClass}`).prop('checked', false); return; }
        $.get("{{ route('masters.user-mappings.getMappedUsers') }}", { type: type, id: id }, function(ids) {
            $(`.${checkboxClass}`).prop('checked', false);
            ids.forEach(childId => $(`.${checkboxClass}[value="${childId}"]`).prop('checked', true));
        });
    }
    
    // Auto-select children when a parent is selected from dropdown
    $('select[name="ism_id"]').on('change', function() { if ($('#mapping_type').val() === 'ism_zsm') fetchAndCheck('ism_zsm', $(this).val(), 'zsm-chk'); });
    $('select[name="zsm_id"]').on('change', function() { if ($('#mapping_type').val() === 'zsm_bdm') fetchAndCheck('zsm_bdm', $(this).val(), 'bdm-chk'); });
    $('select[name="bdm_id"]').on('change', function() { if ($('#mapping_type').val() === 'bdm_bdo') fetchAndCheck('bdm_bdo', $(this).val(), 'bdo-chk'); });
});
</script>
@endsection