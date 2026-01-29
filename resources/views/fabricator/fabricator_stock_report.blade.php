@extends('layouts.fabricator')
@section('title', 'Fabricator Stock')
@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
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

        #table-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md shadow-blue-500/30;
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 0.4rem !important;
        }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                    <span>Reports</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-blue-600">Fabricator Stock</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fabricator Stock Report</h1>
            </div>
            <div class="flex items-center gap-2">
                <button id="reset-filters"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-500 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span> Reset
                </button>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="bg-white rounded-[1.5rem] shadow-sm border border-slate-100 p-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Date Range -->
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">From
                        Date</label>
                    <input type="date" id="from_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500 transition-all"
                        value="{{ date('Y-m-01') }}">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">To
                        Date</label>
                    <input type="date" id="to_date"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500 transition-all"
                        value="{{ date('Y-m-t') }}">
                </div>



                <!-- Product Filters -->
                <div>
                    <label
                        class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                    <select id="category_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500 transition-all">
                        <option value="">All Categories</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sub
                        Category</label>
                    <select id="sub_category_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500 transition-all">
                        <option value="">All Sub Categories</option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Product</label>
                    <select id="product_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500 transition-all">
                        <option value="">All Products</option>
                    </select>
                </div>


            </div>
        </div>

        <!-- DATA TABLE -->
        <div class="glass-panel rounded-[1.5rem] overflow-hidden">
            <div class="p-4 bg-white/30 border-b border-white/50 flex justify-between items-center">
                <!-- Search handled via filters, keeping empty header for consistent look if desired, or remove -->
                <div class="relative w-full max-w-xs">
                    <!-- Optional Text Search could go here -->
                </div>
            </div>

            <div class="px-3 overflow-x-auto">
                <table class="w-full" id="report-table">
                    <thead>
                        <tr class="text-left">
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider pl-4 whitespace-nowrap w-24">
                                Last Updated</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                Zone</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                Fabricator</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                Category</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                Sub Category</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider w-64">
                                Product</th>
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center whitespace-nowrap">
                                Current Stock</th>
                            <!-- Removed Opening/Closing as per request column focus -->
                            <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider pr-4 whitespace-nowrap">
                                Updated By</th>
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const table = $('#report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('fabricator.stock.report.data') }}",
                    data: function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();


                        d.category_id = $('#category_id').val();
                        d.sub_category_id = $('#sub_category_id').val();
                        d.product_id = $('#product_id').val();
                    }
                },
                columns: [{
                        data: 'date',
                        name: 'created_at',
                        className: 'whitespace-nowrap'
                    },
                    {
                        data: 'fabricator_zone',
                        name: 'fabricator.zone.name',
                        orderable: false,
                        className: 'whitespace-nowrap'
                    },
                    {
                        data: 'fabricator_name',
                        name: 'fabricator.shop_name',
                        className: 'whitespace-nowrap'
                    },

                    {
                        data: 'category_name',
                        name: 'product.category.name',
                        defaultContent: '-',
                        className: 'whitespace-nowrap'
                    },
                    {
                        data: 'sub_category_name',
                        name: 'product.subCategory.name',
                        defaultContent: '-',
                        className: 'whitespace-nowrap'
                    },
                    {
                        data: 'product_name',
                        name: 'product.name'
                    }, // Allow wrapping for product

                    {
                        data: 'current_stock',
                        name: 'current_stock',
                        className: 'text-center'
                    },
                    {
                        data: 'updated_by',
                        name: 'updated_by',
                        className: 'whitespace-nowrap'
                    },
                ],


                order: [
                    [0, 'desc']
                ],
                dom: 'rtp', // Using rtp to match product table (bottom pagination)
                createdRow: function(row) {
                    $(row).addClass('glass-card group');
                    $(row).find('td:first').addClass('pl-4 py-3 rounded-l-xl');
                    $(row).find('td:last').addClass('pr-4 py-3 rounded-r-xl');
                    // Use align-top for better look when product name wraps multiple lines
                    $(row).find('td').addClass('border-b border-slate-100/50 align-top py-3');
                },

                drawCallback: function(settings) {
                    const total = settings.json ? settings.json.recordsTotal : 0;
                    $('#table-info').text(`Total Records: ${total}`);
                    $('.dataTables_paginate').appendTo('#table-pagination');
                }
            });

            $('#from_date, #to_date, #zone_id, #fabricator_id, #category_id, #sub_category_id, #product_id').change(
                function() {
                    table.draw();
                });

            $('#reset-filters').click(function() {
                $('#from_date').val("{{ date('Y-m-01') }}");
                $('#to_date').val("{{ date('Y-m-t') }}");

                $('#category_id').val('');
                $('#sub_category_id').val('');
                $('#product_id').val('');


                table.draw();
            });

            // API Data Loading
            // 1. Categories
            $.get("{{ route('fabricator.stock.report.categories') }}", function(data) {
                // Internal API returns direct collection/array (via response()->json($data))
                // so data is likely the array itself.
                let cats = data;

                let html = '<option value="">All Categories</option>';
                cats.forEach(c => {
                    html += `<option value="${c.id}">${c.name}</option>`;
                });
                $('#category_id').html(html);
            });

            // 2. Sub Categories (Dependent on Category)
            $('#category_id').change(function() {
                let catId = $(this).val();
                let subSelect = $('#sub_category_id');

                // Reset downstreams
                subSelect.html('<option value="">All Sub Categories</option>');
                $('#product_id').html('<option value="">All Products</option>');

                if (catId) {
                    $.get("{{ route('fabricator.stock.report.sub-categories') }}", {
                        category_id: catId
                    }, function(data) {
                        let subs = data;
                        let html = '<option value="">All Sub Categories</option>';
                        subs.forEach(s => {
                            html += `<option value="${s.id}">${s.name}</option>`;
                        });
                        subSelect.html(html);
                    });
                }
            });


            // 3. Products (Dependent on Sub Category AND Category)
            // User requested: api/products?category_id=13&sub_category_id=40
            $('#sub_category_id, #category_id').change(function() {
                let catId = $('#category_id').val();
                let subId = $('#sub_category_id').val();
                let prodSelect = $('#product_id');

                // Only fetch if both are present? Or allow fetching by category only if sub is empty?
                // User example: api/products?category_id=13&sub_category_id=40
                // Assuming strict dependency based on typical flows, but flexibility is good.
                // If user only selects Category, do we show all products in that category?
                // The API `api/products` likely filters by whatever is passed.

                // If I change Category, I reset Product (handled above).
                // If I change SubCategory, I fetch Products.
                if ($(this).attr('id') === 'category_id')
                    return; // Handled by category change resetting product

                if (catId && subId) {
                    prodSelect.html('<option value="">Loading...</option>');
                    $.get("{{ route('fabricator.stock.report.products') }}", {
                        category_id: catId,
                        sub_category_id: subId
                    }, function(data) {
                        let prods = data;
                        let html = '<option value="">All Products</option>';
                        prods.forEach(p => {
                            // Assuming product object has 'item_code' or 'name'
                            html +=
                                `<option value="${p.id}">${p.name} (${p.item_code})</option>`;
                        });
                        prodSelect.html(html);
                    });
                } else {
                    prodSelect.html('<option value="">All Products</option>');
                }

            });



        });
    </script>
@endsection
