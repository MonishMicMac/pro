@extends('layouts.app')
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
                transform: translateY(-1px);
            }
        }

        #table-pagination .paginate_button {
            @apply px-2 py-1 mx-0.5 rounded-md border-none bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px];
        }

        #table-pagination .paginate_button.current {
            @apply bg-blue-600 text-white shadow-md shadow-blue-500/30;
        }

        table.dataTable {
            border-collapse: collapse !important;
        }


        .label {
            @apply block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5;
        }

        .input {
            @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold;
        }

        .select2-container--default .select2-selection--multiple {
            border-radius: 6px !important;
        }

        #fabricator-table {

            width: 100%;
        }

        #fabricator-table {
            table-layout: auto;
        }

        #fabricator-table th,
        #fabricator-table td {
            padding: 12px 16px;
            white-space: nowrap;
        }

        #fabricator-table thead {
            background: #f8fafc;
        }


        #fabricator-table th:nth-child(1),
        #fabricator-table td:nth-child(1) {
            width: 50px;
        }

        #fabricator-table th:nth-child(10),
        #fabricator-table td:nth-child(10) {
            width: 120px;
            text-align: center;
        }

        #fabricator-table thead th {
            padding-top: 12px;
            padding-bottom: 12px;
            position: relative;
            z-index: 10;
            background: transparent;
        }

        /* Force correct header height */
        #fabricator-table thead th {
            padding: 14px 16px !important;
            vertical-align: middle;
        }

        /* Fix width sync */
        .dataTables_wrapper table {
            width: 100% !important;
        }

        /* Prevent header clipping */
        .dataTables_scrollHead {
            overflow: visible !important;
        }

        /* Align body + header */
        #fabricator-table th,
        #fabricator-table td {
            text-align: left;
        }

        <style>.modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        #table-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        #table-pagination .paginate_button {
            min-width: 28px;
            height: 28px;
        }
    </style>

    </style>

    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc]">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                    <span>Masters</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-blue-600">Fabricator</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fabricator Master</h1>
            </div>
            @can('fabricators.create')
                <button onclick="openAddModal()"
                    class="px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] font-bold">add</span> Add Fabricator
                </button>
            @endcan
        </div>

        <div class="glass-panel rounded-[1.5rem]">
            <div class="p-4 bg-white/30 border-b border-white/50 flex flex-wrap items-center gap-4">
                <div class="relative w-full max-w-xs">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input id="customSearch"
                        class="w-full pl-9 pr-3 py-1.5 bg-white/80 border border-slate-200 rounded-xl text-xs outline-none"
                        placeholder="Search fabricators..." type="text" />
                </div>


            </div>

            <div class="px-4 overflow-x-auto">
                <table class="w-full" id="fabricator-table">
                    <thead>
                        <tr class="text-left">
                            <th class="pl-4 pb-2 w-10">
                                <input id="selectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer"
                                    type="checkbox" />
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Fabricator
                                Name</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Contact
                                Person</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Sales
                                Person</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Contact
                                Type</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Payment
                                Credit
                                Terms</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Credit
                                Limit</th>

                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Mobile</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Zone</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">State</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">District
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Area</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Pincode
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">GST</th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Address
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Existing
                            </th>
                            <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Created By
                            </th>
                            {{-- <th
                                class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">
                                Status</th> --}}
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
        <div id="floating-selected" class="flex items-center gap-2 text-white border-r border-slate-700 pr-4">
            <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-black"
                id="selected-count">0</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Selected</span>
        </div>
        <div class="flex items-center gap-4">
            <button id="floating-view"
                class="hidden flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 text-[10px] font-bold uppercase">
                <span class="material-symbols-outlined text-[18px]">visibility</span> View
            </button>

            @can('fabricators.edit')
                <button id="floating-edit"
                    class="hidden flex items-center gap-1.5 text-blue-400 hover:text-blue-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span> Edit
                </button>
            @endcan
            @can('fabricators.delete')
                <button id="floating-delete" onclick="handleBulkDelete()"
                    class="flex items-center gap-1.5 text-rose-400 hover:text-rose-300 transition-all text-[10px] font-bold uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Delete
                </button>
            @endcan
            {{-- <button id="bulk-approve" class="text-green-400">Approve</button>
            <button id="bulk-decline" class="text-rose-400">Decline</button> --}}

        </div>
    </div>

    <div id="fabricatorModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm hidden  p-4">
        <div
            class="modal-content glass-panel w-full max-w-3xl mx-auto rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 opacity-0
        max-h-[90vh] overflow-y-auto">

            <form id="fabricatorForm">
                @csrf
                <input type="hidden" name="_method" id="method">
                <input type="hidden" id="fabricator_id" name="id">

                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-slate-800" id="modalTitle">Add New Fabricator</h3>
                    <button type="button" onclick="closeModal('fabricatorModal')"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex gap-6 mb-6 text-xs font-black">
                    <button type="button" class="step-btn text-blue-600 border-b-2 border-blue-600 pb-1"
                        data-step="1">Information</button>

                    <button type="button" class="step-btn text-slate-400" data-step="2">Location</button>

                    <button type="button" class="step-btn text-slate-400" data-step="3">Bank Details</button>

                    <button type="button" class="step-btn text-slate-400" data-step="4">Contact</button>
                </div>

                <!-- ================= STEP 1 ================= -->
                <div class="step step-1 grid grid-cols-2 gap-4">

                    <div class="col-span-2">
                        <input type="checkbox" id="is_existing" name="is_existing" value="1"
                            class="w-4 h-4 text-blue-600 border-slate-300 rounded">

                        <label for="is_existing" class="text-[10px] font-black text-slate-600 uppercase tracking-widest">
                            Existing Fabricator
                        </label>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Fabricator Shop Name
                        </label>
                        <input type="text" name="shop_name" id="fabricator_name"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold">
                    </div>

                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Division</label>
                        <select name="division"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Division</option>
                            <option value="Retail">Retail</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="Project">Project</option>
                            <option value="Export">Export</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Category</label>
                        <select name="category"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Category</option>
                            <option value="Aluminium">Aluminium</option>
                            <option value="Glass">Glass</option>
                            <option value="UPVC">UPVC</option>
                            <option value="Steel">Steel</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Segment</label>
                        <select name="segment"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Segment</option>
                            <option value="Residential">Residential</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Industrial">Industrial</option>

                        </select>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sub
                            Segment</label>
                        <select name="sub_segment"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Sub Segment</option>
                            <option value="Windows">Windows</option>
                            <option value="Doors">Doors</option>
                            <option value="Curtain Wall">Curtain Wall</option>
                            <option value="Partitions">Partitions</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Brands</label>
                        <select name="brands[]" id="brands" multiple
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>


                </div>

                <!-- ================= STEP 2 ================= -->
                <div class="step step-2 hidden grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                        <select name="zone_id"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"
                            required>
                            <option value="">Select Zone</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}">
                                    {{ $zone->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State -->
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            State
                        </label>
                        <select name="state_id" id="state"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select State</option>
                            @foreach ($states as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- District -->
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            District
                        </label>
                        <select name="district_id" id="district" disabled
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select District</option>
                        </select>
                    </div>

                    <!-- City -->
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            City
                        </label>
                        <select name="city_id" id="city" disabled
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select City</option>
                        </select>
                    </div>

                    <!-- Area -->
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Area
                        </label>
                        <select name="area_id" id="area" disabled
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Area</option>
                        </select>
                    </div>

                    <!-- Pincode -->
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Pincode
                        </label>
                        <select name="pincode_id" id="pincode" disabled
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Pincode</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <textarea name="address" id="fabricator_address" rows="3" class="input"></textarea>
                    </div>

                    <!-- Shipping -->
                    <div>
                        <label class="label flex items-center gap-2">
                            <input type="checkbox" id="sameShipping" class="w-4 h-4">
                            Same as address (Shipping)
                        </label>
                        <textarea name="shipping_address" id="shipping_address" rows="2" class="input"></textarea>
                    </div>

                    <!-- Billing -->
                    <div>
                        <label class="label flex items-center gap-2">
                            <input type="checkbox" id="sameBilling" class="w-4 h-4">
                            Same as address (Billing)
                        </label>
                        <textarea name="billing_address" id="billing_address" rows="2" class="input"></textarea>
                    </div>

                </div>


                <!-- ================= STEP 3 ================= -->
                <div class="step step-3 hidden grid grid-cols-2 gap-4">

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Bank
                            Name</label>
                        <input name="bank_name"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">IFSC
                            Code</label>
                        <input name="ifsc_code"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Account
                            Number</label>
                        <input name="account_number"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Branch</label>
                        <input name="branch"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Payment
                            Credit Terms</label>
                        <input name="credit_terms"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Credit
                            Limit</label>
                        <input name="credit_limit"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">GST</label>
                        <input type="text" name="gst" id="fabricator_gst"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                </div>
                <!-- ================= STEP 4 ================= -->
                <div class="step step-4 hidden grid grid-cols-2 gap-4">


                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Contact
                            Person</label>
                        <input name="contact_person"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Sales
                            Person</label>
                        <input name="sales_person"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Password</label>
                        <input type="password" name="password"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Mobile</label>
                        <input name="mobile" id="mobile"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label
                            class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Email</label>
                        <input name="email"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Contact
                            Type</label>
                        <select name="contact_type"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Type</option>
                            <option>Owner</option>
                            <option>Manager</option>
                            <option>Accounts</option>
                            <option>Staff</option>
                        </select>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="mt-8 flex gap-3">

                    <button type="button" id="prevBtn" class="flex-1 py-2.5 font-black text-slate-400 text-[10px]">
                        Previous
                    </button>

                    <button type="button" id="nextBtn"
                        class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-black text-[10px]">
                        Next
                    </button>

                    <button type="submit"
                        class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl font-black text-[10px]">
                        Save
                    </button>

                </div>


            </form>
        </div>
    </div>
    <div id="viewModal"
        class="fixed inset-0 z-[9999] hidden bg-black/40 backdrop-blur-sm
flex items-center justify-center p-4">

        <div
            class="modal-content glass-panel w-full max-w-4xl
rounded-[1.25rem] p-6 shadow-2xl
max-h-[90vh] overflow-y-auto">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-black text-slate-800">
                        Fabricator Details
                    </h3>
                    <p class="text-xs text-slate-400 font-bold">
                        Complete information
                    </p>
                </div>

                <button onclick="closeView()"
                    class="w-8 h-8 flex items-center justify-center
    rounded-full hover:bg-rose-50
    text-slate-400 hover:text-rose-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Content -->
            <div id="viewContent"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3
gap-4 text-xs font-bold text-slate-700">
            </div>

        </div>
    </div>




    {{-- <div id="remarkModal" class="fixed inset-0 bg-black/40 hidden flex items-center justify-center">
        <div class="bg-white p-5 rounded-xl w-96">
            <h3 class="font-bold mb-3">Enter Remark</h3>
            <textarea id="status_remark" class="w-full border p-2 rounded" rows="3"></textarea>

            <div class="flex justify-end gap-2 mt-3">
                <button onclick="closeRemark()" class="px-3 py-1">Cancel</button>
                <button id="confirmStatus" class="bg-blue-600 text-white px-3 py-1 rounded">
                    Submit
                </button>
            </div>
        </div>
    </div> --}}

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            let editMode = false;

            const table = $('#fabricator-table').DataTable({
                autoWidth: false,
                scrollX: false,
                responsive: false,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('masters.fabricators.index') }}",
                    data: function(d) {
                        d.state_id = $('#filter-state').val();
                        d.district_id = $('#filter-district').val();

                    }
                },
                createdRow: function(row) {
                    $(row).addClass('glass-card group');
                    $(row).find('td:first').addClass('pl-4 py-2 rounded-l-xl');
                    $(row).find('td:last').addClass('pr-4 py-2 rounded-r-xl');
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: (d) =>
                            `<input class="row-checkbox w-4 h-4 rounded border-slate-300 text-blue-600 cursor-pointer" type="checkbox" value="${d}"/>`
                    },
                    {
                        data: 'shop_name'
                    },
                    {
                        data: 'contact_person'
                    },
                    {
                        data: 'sales_person'
                    },
                    {
                        data: 'contact_type'
                    },
                    {
                        data: 'payment_credit_terms'
                    },
                    {
                        data: 'credit_limit'
                    },
                    {
                        data: 'mobile'
                    },
                    {
                        data: 'zone',
                        name: 'zone'
                    },
                    {
                        data: 'state',
                        name: 'state'
                    },
                    {
                        data: 'district',
                        name: 'district'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'pincode',
                        name: 'pincode'
                    },

                    {
                        data: 'gst'
                    },
                    {
                        data: 'address'
                    },
                    {
                        data: 'is_existing',
                        render: function(d) {
                            if (d == 0) {
                                return `<span class="px-2 py-1 bg-blue-100 text-blue-600 rounded-full text-[9px] font-black">NEW</span>`;
                            } else {
                                return `<span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-[9px] font-black">EXISTING</span>`;
                            }
                        }
                    },
                    {
                        data: 'created_by'
                    },
                    // {
                    //     data: 'status',
                    //     className: 'text-center',
                    //     render: (d) => d == 0 ?
                    //         `<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/10 text-green-600 rounded-full text-[9px] font-black uppercase tracking-widest"><span class="w-1 h-1 bg-green-500 rounded-full"></span>Approved</span>` :
                    //         `<span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase tracking-widest">Unapproved</span>`
                    // }
                ],
                dom: 'rtp',
                language: {
                    paginate: {
                        previous: '<span class="material-symbols-outlined text-[14px]">arrow_back_ios</span>',
                        next: '<span class="material-symbols-outlined text-[14px]">arrow_forward_ios</span>'
                    }
                },
                drawCallback: function() {

                    let info = this.api().page.info();

                    $('#table-info').text(
                        `Total Records: ${info.recordsTotal}`
                    );

                    // Proper move with event preservation
                    let paginate = $('.dataTables_paginate').detach();

                    paginate.addClass('flex items-center gap-1');

                    $('#table-pagination')
                        .empty()
                        .append(paginate);
                }

            });

            // Trigger Table Redraw on Filter Change
            $('#filter-state, #filter-district').on('change', function() {
                table.draw();
            });

            $('#reset-filters').on('click', function() {
                $('#filter-state, #filter-district').val('');
                $('#customSearch').val('');
                table.search('').draw();
            });

            $('#customSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#brands').select2({
                placeholder: "Select Brands",
                allowClear: true,
                closeOnSelect: false,
                width: '100%'
            });

            // Modal Controls
            window.openAddModal = () => {
                editMode = false;
                $('#fabricatorForm')[0].reset();
                $('#fabricator_id').val('');
                $('#modalTitle').text('Add New Fabricator');
                $('#state').val('').trigger('change');
                $('#brands').val(null).trigger('change');



                // 1. Remove hidden first so it exists in the DOM
                $('#fabricatorModal').removeClass('hidden');

                // 2. Use a tiny timeout so the browser registers the display change 
                // before starting the opacity/transform animation
                setTimeout(() => {
                    $('#fabricatorModal')
                        .removeClass('pointer-events-none opacity-0')
                        .addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');

                    $('.modal-content')
                        .removeClass('scale-95 opacity-0')
                        .addClass('scale-100 opacity-100');
                }, 10);
            };

            let pendingStatus = null;

            $('#bulk-approve').click(function() {
                handleStatusClick(0);
            });

            $('#bulk-decline').click(function() {
                handleStatusClick(1);
            });

            function handleStatusClick(status) {

                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) {
                    alert("Please select records first");
                    return;
                }

                // Check same status
                let statuses = [];
                $('.row-checkbox:checked').each(function() {
                    let text = $(this).closest('tr').find('td:last').text().trim();
                    statuses.push(text);
                });

                let unique = [...new Set(statuses)];
                if (unique.length > 1) {
                    alert("Please select same status records only");
                    return;
                }

                pendingStatus = status;
                $('#status_remark').val('');
                $('#remarkModal').removeClass('hidden');
            }

            /* Submit remark */
            $('#confirmStatus').click(function() {

                let remark = $('#status_remark').val();

                if (!remark) {
                    alert("Remark required");
                    return;
                }

                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                $.post("{{ route('masters.fabricators.bulkStatus') }}", {
                    ids: ids,
                    status: pendingStatus,
                    remark: remark
                }, function() {

                    table.draw(false);

                    // Reset UI
                    $('#selectAll, .row-checkbox').prop('checked', false);
                    $('#selected-count').text(0);
                    $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none');

                    $('#remarkModal').addClass('hidden');
                });
            });

            function closeRemark() {
                $('#remarkModal').addClass('hidden');
            }



            let step = 1;

            function showStep(s) {
                $('.step').addClass('hidden');
                $('.step-' + s).removeClass('hidden');

                $('.step-btn')
                    .removeClass('text-blue-600 border-b-2 border-blue-600')
                    .addClass('text-slate-400');

                $('.step-btn[data-step="' + s + '"]')
                    .addClass('text-blue-600 border-b-2 border-blue-600')
                    .removeClass('text-slate-400');

                $('#prevBtn').toggle(s > 1);
                $('#nextBtn').toggle(s < 4);
            }

            $('.step-btn').click(function() {
                step = $(this).data('step');
                showStep(step);
            });

            $('#nextBtn').click(() => {
                step++;
                showStep(step);
            });
            $('#prevBtn').click(() => {
                step--;
                showStep(step);
            });

            showStep(1);


            window.closeModal = (id) => {

                const modal = $('#' + id);

                $('.modal-content')
                    .removeClass('scale-100 opacity-100')
                    .addClass('scale-95 opacity-0');

                modal
                    .removeClass('opacity-100 bg-slate-900/30 backdrop-blur-sm')
                    .addClass('opacity-0 pointer-events-none');

                setTimeout(() => {
                    modal.addClass('hidden');
                }, 300);
            };

            // ================= DEPENDENT DROPDOWNS =================
            const dropIds = ['district', 'city', 'area', 'pincode'];
            const clearAfter = (id) => {
                let start = dropIds.indexOf(id) + 1;
                for (let i = start; i < dropIds.length; i++) {
                    $('#' + dropIds[i]).html(
                        `<option value="">Select ${dropIds[i].charAt(0).toUpperCase() + dropIds[i].slice(1)}</option>`
                    ).prop('disabled', true);
                    setTimeout(() => {
                        modal.addClass('hidden pointer-events-none');
                    }, 300);
                }
            };

            // State -> District
            $('#state').change(function() {
                let id = $(this).val();
                clearAfter('state');
                if (!id) return;
                $.get("{{ url('get-districts') }}/" + id, function(res) {
                    let html = '<option value="">Select District</option>';
                    res.forEach(r => {
                        html += `<option value="${r.id}">${r.district_name}</option>`;
                    });
                    $('#district').html(html).prop('disabled', false);
                });
            });

            $('#district').change(function() {
                let id = $(this).val();
                clearAfter('district');
                if (!id) return;
                $.get("{{ url('get-cities') }}/" + id, function(res) {
                    let html = '<option value="">Select City</option>';
                    res.forEach(r => {
                        html += `<option value="${r.id}">${r.city_name}</option>`;
                    });
                    $('#city').html(html).prop('disabled', false);
                });
            });

            // City -> Area
            $('#city').change(function() {
                let id = $(this).val();
                clearAfter('city');
                if (!id) return;
                $.get("{{ url('get-areas') }}/" + id, function(res) {
                    let html = '<option value="">Select Area</option>';
                    res.forEach(r => {
                        html += `<option value="${r.id}">${r.area_name}</option>`;
                    });
                    $('#area').html(html).prop('disabled', false);
                });
            });

            // Area -> Pincode
            $('#area').change(function() {
                let id = $(this).val();
                clearAfter('area');
                if (!id) return;
                $.get("{{ url('get-pincodes') }}/" + id, function(res) {
                    let html = '<option value="">Select Pincode</option>';
                    res.forEach(r => {
                        html += `<option value="${r.id}">${r.pincode}</option>`;
                    });
                    $('#pincode').html(html).prop('disabled', false);
                });
            });
            $('#sameShipping').change(function() {
                if (this.checked) {
                    $('#shipping_address').val($('#fabricator_address').val());
                } else {
                    $('#shipping_address').val('');
                }
            });

            $('#sameBilling').change(function() {
                if (this.checked) {
                    $('#billing_address').val($('#fabricator_address').val());
                } else {
                    $('#billing_address').val('');
                }
            });


            // Edit via Floating Bar
            $('#floating-edit').click(function() {
                const id = $('.row-checkbox:checked').first().val();
                if (!id) return;

                editMode = true;
                let editUrl = "{{ route('masters.fabricators.edit', ':id') }}".replace(':id', id);

                $.get(editUrl, function(res) {
                    $('#fabricator_id').val(res.id);
                    $('#fabricator_name').val(res.shop_name);
                    // Existing checkbox
                    if (res.is_existing == 1) {
                        $('#is_existing').prop('checked', true);
                    } else {
                        $('#is_existing').prop('checked', false);
                    }
                    $('#mobile').val(res.mobile);
                    $('select[name="division"]').val(res.division);
                    $('select[name="category"]').val(res.category);
                    $('select[name="segment"]').val(res.segment);
                    $('select[name="sub_segment"]').val(res.sub_segment);

                    $('input[name="contact_person"]').val(res.contact_person);
                    $('input[name="sales_person"]').val(res.sales_person);
                    $('input[name="contact_mobile"]').val(res.contact_mobile);
                    $('input[name="email"]').val(res.email);
                    $('select[name="contact_type"]').val(res.contact_type);

                    $('input[name="credit_terms"]').val(res.payment_credit_terms);
                    $('input[name="credit_limit"]').val(res.credit_limit);
                    $('select[name="zone_id"]').val(res.zone_id);


                    // Set State and trigger chain
                    $('#state').val(res.state_id).trigger('change');
                    $('#brands').val(res.brands.map(b => b.id)).trigger('change');


                    const setDelayed = (selector, val, nextTrigger) => {
                        let interval = setInterval(() => {
                            if ($(selector + " option[value='" + val + "']").length >
                                0) {
                                $(selector).val(val);
                                if (nextTrigger) $(selector).trigger('change');
                                clearInterval(interval);
                            }
                        }, 50);
                        setTimeout(() => clearInterval(interval), 5000);
                    };

                    if (res.district_id) setDelayed('#district', res.district_id, true);
                    if (res.city_id) setDelayed('#city', res.city_id, true);
                    if (res.area_id) setDelayed('#area', res.area_id, true);
                    if (res.pincode_id) setDelayed('#pincode', res.pincode_id, false);

                    $('#fabricator_gst').val(res.gst);
                    $('#fabricator_address').val(res.address);
                    $('#modalTitle').text('Edit Fabricator');
                    $('#fabricatorModal').removeClass('pointer-events-none opacity-0 hidden')
                        .addClass('opacity-100 bg-slate-900/30 backdrop-blur-sm');
                    $('.modal-content').removeClass('scale-95 opacity-0').addClass(
                        'scale-100 opacity-100');
                }).fail(function() {
                    alert("Could not fetch data.");
                });
            });

            // Form Submission
            $('#fabricatorForm').submit(function(e) {
                e.preventDefault();

                const id = $('#fabricator_id').val();

                let url = "{{ route('masters.fabricators.store') }}";

                if (editMode) {
                    url = "{{ url('masters/fabricators') }}/" + id;
                    $('#method').val('PUT');
                } else {
                    $('#method').val('POST');
                }

                $.ajax({
                    url: url,
                    type: 'POST', // Laravel will detect PUT via _method
                    data: $(this).serialize(),
                    success: function() {
                        closeModal('fabricatorModal');
                        table.draw(false);
                        // Reset Checkboxes and Floating Bar
                        $('#selectAll, .row-checkbox').prop('checked', false);
                        $('#selected-count').text(0);
                        $('#floating-bar').addClass(
                            'translate-y-20 opacity-0 pointer-events-none');
                        editMode = false;
                    },
                    error: function(xhr) {
                        // This will show you exactly what failed in an alert if validation fails
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMsg = "";
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + "\n";
                            });
                            alert(errorMsg);
                        } else {
                            alert("Something went wrong!");
                        }
                    }
                });
            });

            // Bulk Actions Logic
            $(document).on('change', '#selectAll, .row-checkbox', function() {

                if ($(this).attr('id') === 'selectAll') {
                    $('.row-checkbox').prop('checked', $(this).prop('checked'));
                }

                const count = $('.row-checkbox:checked').length;
                $('#selected-count').text(count);

                if (count > 0) {

                    $('#floating-bar')
                        .removeClass('translate-y-20 opacity-0 pointer-events-none')
                        .addClass('translate-y-0 opacity-100 pointer-events-auto');

                    if (count === 1) {
                        $('#floating-edit').removeClass('hidden');
                        $('#floating-view').removeClass('hidden');
                    } else {
                        $('#floating-edit').addClass('hidden');
                        $('#floating-view').addClass('hidden');
                    }

                } else {
                    $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none');
                    $('#floating-edit').addClass('hidden');
                    $('#floating-view').addClass('hidden');
                }
            });

            $('#floating-view').click(function() {

                let id = $('.row-checkbox:checked').first().val();

                window.location.href =
                    "{{ route('masters.fabricators.show', ':id') }}"
                    .replace(':id', id);
            });



            window.closeView = function() {
                $('#viewModal').addClass('hidden');
            };


            // Bulk Delete Action
            window.handleBulkDelete = () => {
                const ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return;
                if (!confirm(`Are you sure you want to delete ${ids.length} selected record(s)?`)) return;

                $.ajax({
                    url: "{{ route('masters.fabricators.bulkDelete') }}",
                    type: "POST",
                    data: {
                        ids: ids
                    },
                    success: function() {
                        table.draw(false);
                        $('#selectAll, .row-checkbox').prop('checked', false);
                        $('#selected-count').text(0);
                        $('#floating-bar').addClass('translate-y-20 opacity-0 pointer-events-none');
                    }
                });
            };
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
