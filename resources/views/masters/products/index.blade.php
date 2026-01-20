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
                <span class="text-blue-600">Product</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Product Master</h1>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('masters.products.export') }}" class="px-4 py-2 bg-white text-slate-600 border border-slate-200 text-xs font-bold rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">download</span> Export
            </a>
            <a href="{{ route('masters.products.importView') }}" class="px-4 py-2 bg-white text-slate-600 border border-slate-200 text-xs font-bold rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">upload_file</span> Import
            </a>
            <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] font-bold">add</span> Add Product
            </button>
        </div>
    </div>

    <div class="glass-panel rounded-[1.5rem] overflow-hidden">
        <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
            <div class="relative w-full max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="customSearch" class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none" placeholder="Search products..." type="text"/>
            </div>
        </div>

        <div class="px-4 overflow-x-auto">
            <table class="w-full" id="product-table">
                <thead>
                    <tr class="text-left">
                        <th class="pl-4 pb-2 w-10"><input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox"/></th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Item Code</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Name</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Brand</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Item Type</th>
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

<div id="ProductModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/0 backdrop-blur-0 transition-all duration-300 opacity-0 pointer-events-none">
    <div class="modal-content glass-panel w-full max-w-2xl rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0 overflow-y-auto max-h-[90vh]">
        <form id="ProductForm">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" id="Product_id" name="id">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-slate-800" id="modalTitle">Add New Product</h3>
                <button type="button" onclick="closeModal('ProductModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Item Code</label>
                    <input type="text" name="item_code" id="item_code" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all" required>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Item Name</label>
                    <input type="text" name="name" id="name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all" required>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Brand</label>
                    <select name="brand_id" id="brand_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                        <option value="">Select Brand</option>
                        @foreach($brands as $b) <option value="{{$b->id}}">{{$b->name}}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Model</label>
                    <input type="text" name="model_name" id="model_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Property Type</label>
                    <select name="property_type_id" id="property_type_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                        <option value="">Select Property Type</option>
                        @foreach($propertyTypes as $p) <option value="{{$p->id}}">{{$p->name}}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Length</label>
                    <input type="number" step="0.01" name="length" id="length" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Pieces / Packet</label>
                    <input type="number" name="pieces_per_packet" id="pieces_per_packet" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Section Weight</label>
                    <input type="number" step="0.001" name="section_weight" id="section_weight" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Item Type</label>
                    <select name="item_type_id" id="item_type_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                        <option value="">Select Item Type</option>
                        @foreach($itemTypes as $t) <option value="{{$t->id}}">{{$t->name}}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Main Category</label>
                    <select name="category_id" id="category_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700" onchange="filterSubCategories()">
                        <option value="">Select Category</option>
                        @foreach($categories as $c) <option value="{{$c->id}}">{{$c->name}}</option> @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sub Category</label>
                    <select name="sub_category_id" id="sub_category_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                        <option value="">Select Sub Category</option>
                        @foreach($subCategories as $sc) <option value="{{$sc->id}}" data-cat="{{$sc->category_id}}">{{$sc->name}}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rate" id="tax_rate" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">UOM</label>
                    <input type="text" name="uom" id="uom" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700">
                </div>
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closeModal('ProductModal')" class="flex-1 py-2.5 font-black text-slate-400 uppercase text-[10px] tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-slate-900/20">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    const allSubCats = @json($subCategories);

    function filterSubCategories() {
        const catId = $('#category_id').val();
        const subSelect = $('#sub_category_id');
        subSelect.empty().append('<option value="">Select Sub Category</option>');
        
        const filtered = allSubCats.filter(s => s.category_id == catId);
        filtered.forEach(s => {
            subSelect.append(`<option value="${s.id}">${s.name}</option>`);
        });
    }

    $(document).ready(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

        let editMode = false;
        const table = $('#product-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('masters.products.index') }}",
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
            },
            columns: [
                { data: 'id', orderable: false, render: (d) => `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer relative z-10" type="checkbox" value="${d}"/>` },
                { data: 'item_code', name: 'item_code' },
                { data: 'name', name: 'name' },
                { data: 'brand_name', name: 'brand_id' },
                { data: 'category_name', name: 'category_id' },
                { data: 'item_type_name', name: 'item_type_id' },
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
                $('.dataTables_paginate').appendTo('#table-pagination');
            }
        });

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        window.openAddModal = () => {
            editMode = false;
            $('#ProductForm')[0].reset();
            $('#Product_id').val('');
            $('#modalTitle').text('Add New Product');
            
            // SHOW MODAL LOGIC
            $('#ProductModal').removeClass('hidden'); 
            setTimeout(() => {
                $('#ProductModal').removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
                $('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            }, 10);
        };

        window.closeModal = (id) => {
            // HIDE MODAL LOGIC
            $('.modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
            $('#' + id).removeClass('opacity-100 bg-slate-900/30 backdrop-blur-sm').addClass('pointer-events-none opacity-0');
            
            setTimeout(() => {
                $('#' + id).addClass('hidden');
            }, 300); // Wait for transition to finish
        };

        $('#floating-edit').click(function() {
            const id = $('.row-checkbox:checked').first().val();
            editMode = true;
            $.get(`{{ url('') }}/masters/products/${id}/edit`, function(data) {
                $('#modalTitle').text('Update Product');
                $('#Product_id').val(data.id);
                
                $('#item_code').val(data.item_code);
                $('#name').val(data.name);
                $('#brand_id').val(data.brand_id);
                $('#model_name').val(data.model_name);
                $('#property_type_id').val(data.property_type_id);
                $('#length').val(data.length);
                $('#pieces_per_packet').val(data.pieces_per_packet);
                $('#section_weight').val(data.section_weight);
                $('#item_type_id').val(data.item_type_id);
                $('#category_id').val(data.category_id).trigger('change');
                
                setTimeout(() => $('#sub_category_id').val(data.sub_category_id), 100);

                $('#tax_rate').val(data.tax_rate);
                $('#uom').val(data.uom);

                // SHOW MODAL LOGIC
                $('#ProductModal').removeClass('hidden');
                setTimeout(() => {
                    $('#ProductModal').removeClass('pointer-events-none opacity-0').addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
                    $('.modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
                }, 10);
            });
        });

        $('#ProductForm').submit(function(e) {
            e.preventDefault();
            const id = $('#Product_id').val();
            if(editMode) $('#method').val('PUT'); else $('#method').val('POST');
            
            const url = editMode
                ? `{{ url('') }}/masters/products/${id}`
                : "{{ route('masters.products.store') }}";

            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(),
                success: function() {
                    closeModal('ProductModal');
                    table.draw(false);
                    resetSelection();
                }
            });
        });

        function resetSelection() {
            $('#selectAll').prop('checked', false);
            $('.row-checkbox').prop('checked', false);
            $('#selected-count').text(0);
            $('#floating-bar')
                .addClass('translate-y-20 opacity-0 pointer-events-none')
                .removeClass('translate-y-0 opacity-100 pointer-events-auto');
        }

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

        window.handleBulkDelete = () => {
            const ids = [];
            $('.row-checkbox:checked').each(function () { ids.push($(this).val()); });
            if(ids.length === 0){ alert('Select at least one record'); return; }
            if(!confirm(`Are you sure you want to delete ${ids.length} selected product(s)?`)){ return; }

            $.ajax({
                url: "{{ route('masters.products.bulkDelete') }}",
                type: "POST",
                data: { ids: ids },
                success: function () {
                    table.draw(false);
                    resetSelection();
                },
                error:function(xhr){ alert(xhr.responseJSON?.error ?? 'Server error'); }
            });
        };
    });
</script>
@endsection