@extends('layouts.app')

@section('title', 'Fabricator Projections')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
    }
    #table-pagination .paginate_button {
        @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100;
    }
    #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md border-blue-600; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
    <!-- Header -->
    <div class="flex items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Modules</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Fabricator Projections</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fabricator Projections</h1>
        </div>
        <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">add</span> Add Projection
        </button>
    </div>

    <!-- Filters -->
    <div class="glass-panel rounded-[1.5rem] p-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                <select id="filter_zone_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">ZSM</label>
                <select id="filter_zsm_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All ZSMs</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager</label>
                <select id="filter_manager_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Managers</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO / Assigned To</label>
                <select id="filter_user_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Users</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Fabricator</label>
                <select id="filter_fabricator_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Fabricators</option>
                    @foreach($fabricators as $f)
                        <option value="{{ $f->id }}">{{ $f->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Month</label>
                <input type="month" id="filter_month" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
            </div>
            <div class="md:col-span-6 flex justify-end gap-2">
                <button onclick="refreshTable()" class="px-6 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                </button>
                <button onclick="resetFilters()" class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="overflow-x-auto p-4">
            <table id="projectionsTable" class="w-full">
                <thead>
                    <tr class="text-left border-b border-slate-100">
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">#</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Fabricator</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Created By</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Month</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Projection (Ton)</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Collection</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700">
                    <!-- DataTables -->
                </tbody>
            </table>
        </div>
        
        <div class="p-4 bg-white/40 border-t border-white/60 flex items-center justify-between">
            <p id="table-info" class="text-[9px] font-black text-slate-400 uppercase tracking-widest"></p>
            <div id="table-pagination" class="flex items-center gap-0.5"></div>
        </div>
    </div>
</div>

<!-- Modal Container -->
<div id="projectionModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    
    <div class="relative bg-white w-full max-w-[380px] rounded-2xl shadow-2xl transform scale-95 transition-all duration-300 p-6" id="modalContent">
        
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 id="modalTitle" class="text-lg font-bold text-slate-800">Add Projection</h2>
                <p class="text-[10px] font-medium text-slate-400 mt-0.5">Enter details for the new projection</p>
            </div>
            <button onclick="closeModal()" class="w-6 h-6 rounded-full flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-500 transition-colors">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>

        <form id="projectionForm" class="space-y-4">
            @csrf
            <input type="hidden" id="projection_id" name="id">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fabricator</label>
                <div class="relative">
                    <select name="fabricator_id" id="fabricator_id" class="w-full pl-3 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all appearance-none cursor-pointer" required>
                        <option value="">Select Fabricator</option>
                        @foreach($fabricators as $f)
                            <option value="{{ $f->id }}">{{ $f->shop_name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Month</label>
                    <input type="month" name="projection_month" id="projection_month" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" required>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tonnage</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="sale_projection_tonnage" id="sale_projection_tonnage" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" required placeholder="0.00">
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <span class="text-[10px] font-bold text-slate-400">MT</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Collection Amount</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs font-bold">₹</span>
                    <input type="number" step="0.01" name="fabricator_collection" id="fabricator_collection" class="w-full pl-6 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" required placeholder="0.00">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider rounded-lg hover:bg-slate-50 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 text-white text-[11px] font-bold uppercase tracking-wider rounded-lg shadow-md shadow-blue-500/20 hover:bg-blue-700 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    let table;
    $(document).ready(function() {
        table = $('#projectionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('fabricator-projections.data') }}",
                data: function (d) {
                    d.zone_id = $('#filter_zone_id').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.manager_id = $('#filter_manager_id').val();
                    d.user_id = $('#filter_user_id').val();
                    d.fabricator_id = $('#filter_fabricator_id').val();
                    d.month = $('#filter_month').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'fabricator_name', name: 'fabricator_name', className: 'px-4' },
                { data: 'user_name', name: 'user_name', className: 'px-4' },
                { data: 'projection_month', name: 'projection_month', className: 'px-4' },
                { data: 'sale_projection_tonnage', name: 'sale_projection_tonnage', className: 'px-4' },
                { data: 'fabricator_collection', name: 'fabricator_collection', className: 'px-4' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            dom: 'rt',
            drawCallback: function(settings) {
                const api = this.api();
                const info = api.page.info();
                
                $('#table-info').text(`Showing ${info.start + 1} to ${info.end} of ${info.recordsTotal} projections`);
                
                let paginationHtml = '';
                const pages = api.pagination.get();
                pages.forEach(item => {
                    const activeClass = item.current ? 'current' : '';
                    paginationHtml += `<span class="paginate_button ${activeClass}" onclick="goToPage('${item.page}')">${item.display}</span>`;
                });
                $('#table-pagination').html(paginationHtml);
                
                // Styling rows
                $('#projectionsTable tbody tr').addClass('glass-card bg-white/50 mb-2 hover:bg-white/90 transition-all duration-200 cursor-pointer');
                $('#projectionsTable tbody td').first().addClass('rounded-l-2xl');
                $('#projectionsTable tbody td').last().addClass('rounded-r-2xl');
            }
        });

        // Hierarchical Filters
        $('#filter_zone_id').on('change', function() {
            const zoneId = $(this).val();
            $('#filter_zsm_id, #filter_manager_id, #filter_user_id').html('<option value="">Loading...</option>');
            if(zoneId) {
                $.get("{{ route('get.location.data') }}", { type: 'zone', id: zoneId }, function(res) {
                    let zsmHtml = '<option value="">All ZSMs</option>';
                    res.zsms.forEach(z => zsmHtml += `<option value="${z.id}">${z.name}</option>`);
                    $('#filter_zsm_id').html(zsmHtml);
                    
                    let bdmHtml = '<option value="">All Managers</option>';
                    res.bdms.forEach(m => bdmHtml += `<option value="${m.id}">${m.name}</option>`);
                    $('#filter_manager_id').html(bdmHtml);
                    
                    let bdoHtml = '<option value="">All Users</option>';
                    res.bdos.forEach(b => bdoHtml += `<option value="${b.id}">${b.name}</option>`);
                    $('#filter_user_id').html(bdoHtml);
                });
            } else {
                $('#filter_zsm_id').html('<option value="">All ZSMs</option>');
                $('#filter_manager_id').html('<option value="">All Managers</option>');
                $('#filter_user_id').html('<option value="">All Users</option>');
            }
        });

        $('#filter_zsm_id').on('change', function() {
            const zsmId = $(this).val();
            if(zsmId) {
                $.get("{{ route('get.location.data') }}", { type: 'bdm', id: zsmId }, function(res) {
                    let html = '<option value="">All Managers</option>';
                    res.forEach(m => html += `<option value="${m.id}">${m.name}</option>`);
                    $('#filter_manager_id').html(html);
                    $('#filter_user_id').html('<option value="">All Users</option>');
                });
            }
        });

        $('#filter_manager_id').on('change', function() {
            const bdmId = $(this).val();
            if(bdmId) {
                $.get("{{ route('get.location.data') }}", { type: 'bdo', id: bdmId }, function(res) {
                    let html = '<option value="">All Users</option>';
                    res.forEach(b => html += `<option value="${b.id}">${b.name}</option>`);
                    $('#filter_user_id').html(html);
                });
            }
        });
    });

    function goToPage(page) {
        if(page === 'previous') table.page('previous').draw('page');
        else if(page === 'next') table.page('next').draw('page');
        else table.page(parseInt(page)).draw('page');
    }

    function refreshTable() { table.draw(); }
    function resetFilters() {
        $('#filter_fabricator_id, #filter_user_id, #filter_month').val('');
        refreshTable();
    }

    function openModalUI() {
        const modal = $('#projectionModal');
        const content = $('#modalContent');
        modal.removeClass('opacity-0 pointer-events-none').addClass('opacity-100 pointer-events-auto');
        content.removeClass('scale-95').addClass('scale-100');
    }

    function openAddModal() {
        $('#projectionForm')[0].reset();
        $('#projection_id').val('');
        $('#modalTitle').text('Add Projection');
        openModalUI();
    }

    function closeModal() {
        const modal = $('#projectionModal');
        const content = $('#modalContent');
        modal.addClass('opacity-0 pointer-events-none').removeClass('opacity-100 pointer-events-auto');
        content.addClass('scale-95').removeClass('scale-100');
    }

    $('#projectionForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#projection_id').val();
        const url = id ? `{{ url('fabricator-projections') }}/${id}` : "{{ route('fabricator-projections.store') }}";
        
        let formData = $(this).serialize();
        if(id) formData += '&_method=PUT';

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            success: function(res) {
                closeModal();
                refreshTable();
                Toast.fire({ icon: 'success', title: res.success });
            },
            error: function(err) {
                Toast.fire({ icon: 'error', title: 'Error processing request' });
            }
        });
    });

    function editProjection(id) {
        $.get(`{{ url('fabricator-projections') }}/${id}/edit`, function(data) {
            console.log("Edit Data:", data);
            // Fill form
            $('#projection_id').val(data.id);
            $('#fabricator_id').val(data.fabricator_id);
            $('#projection_month').val(data.projection_month);
            $('#sale_projection_tonnage').val(data.sale_projection_tonnage);
            $('#fabricator_collection').val(data.fabricator_collection);
            
            $('#modalTitle').text('Edit Projection');
            openModalUI(); // Open UI ONLY, do not reset
        }).fail(function() {
            Toast.fire({ icon: 'error', title: 'Could not fetch projection data' });
        });
    }

    function deleteProjection(id) {
        Swal.fire({
            title: 'Delete Projection?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#f43f5e',
            confirmButtonText: 'Yes, Delete',
            customClass: {
                popup: 'rounded-[2rem]',
                confirmButton: 'rounded-xl px-6 py-2',
                cancelButton: 'rounded-xl px-6 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('fabricator-projections') }}/${id}`,
                    method: 'DELETE',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(res) {
                        refreshTable();
                        Toast.fire({ icon: 'success', title: res.success });
                    }
                });
            }
        });
    }
</script>
@endpush
@endsection
