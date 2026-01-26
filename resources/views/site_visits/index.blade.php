@extends('layouts.app')

@section('title', 'Site Visits')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <style type="text/tailwindcss">
        @layer components {
            .glass-panel { @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm transition-all; }
            .form-input-custom { @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer; }
        }
        #table-pagination .paginate_button { @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100; }
        #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md border-blue-600; }
        table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
        .dataTables_paginate, .dataTables_info { display: none !important; }
        #site-visit-table th, #site-visit-table td { white-space: nowrap; vertical-align: middle; }
        #table-pagination { justify-content: flex-end; }
    </style>

    <div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc] relative z-0">

        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Modules</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">Site Visits</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Site Visits Log</h1>
        </div>

        <div class="glass-panel rounded-2xl p-5 relative z-20">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Zone</label>
                    <select id="filter_zone" class="form-input-custom">
                        <option value="">All Zones</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" {{ Auth::user()->zone_id == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">State</label>
                    <select id="filter_state" class="form-input-custom">
                        <option value="">All States</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" {{ Auth::user()->state_id == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">District</label>
                    <select id="filter_district" class="form-input-custom">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ Auth::user()->district_id == $district->id ? 'selected' : '' }}>{{ $district->district_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">City</label>
                    <select id="filter_city" class="form-input-custom">
                        <option value="">All Cities</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ Auth::user()->city_id == $city->id ? 'selected' : '' }}>{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">User</label>
                    <select id="filter_user_id" class="form-input-custom">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2"> 
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Date Range</label>
                    <div class="flex gap-2">
                        <input type="date" id="filter_from_date" class="form-input-custom" placeholder="From">
                        <input type="date" id="filter_to_date" class="form-input-custom" placeholder="To">
                    </div>
                </div>

                <div class="md:col-span-3 lg:col-span-3 flex items-end justify-end gap-2 pb-[2px]">
                    <button id="btn_reset" class="px-4 h-[38px] bg-white text-slate-500 text-[10px] font-black uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                        Reset
                    </button>
                    <button id="btn_filter" class="px-6 h-[38px] bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2 active:scale-95 shadow-lg shadow-blue-500/20">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                    </button>
                </div>
            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden relative z-10 flex flex-col min-h-[500px]">
            <div class="p-6 flex-1 flex flex-col">
                
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">Visit Records</h2>
                        <p id="table-info" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Records: 0</p>
                    </div>
                    <div class="relative w-full md:w-64">
                        <input id="custom-search" type="text" placeholder="Search..." class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 placeholder:text-slate-300 pl-4 pr-10 py-2 outline-none focus:ring-2 focus:ring-blue-500/20">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[18px]">search</span>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto custom-scrollbar">
                    <table id="site-visit-table" class="w-full min-w-[1000px]">
                        <thead>
                            <tr class="text-left">
                                <th class="pl-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">S.No</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Lead</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Check In</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Check Out</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Remarks</th>
                                <th class="px-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Proof</th>
                                <th class="pr-4 pb-3 text-[10px] font-black text-slate-400 uppercase tracking-wider text-end">Location</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-bold text-slate-700"></tbody>
                    </table>
                </div>

                <div id="table-pagination" class="flex items-center gap-1 mt-4 border-t border-slate-100 pt-4"></div>
            </div>
        </div>
    </div>

    <div id="mapModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm transition-all duration-300 opacity-0">
        <div class="modal-content glass-panel w-full max-w-4xl h-[80vh] rounded-[1.5rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-black text-slate-800">Visit Location</h3>
                <button type="button" onclick="closeModal('mapModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <div id="map" class="flex-1 rounded-xl border border-slate-200 shadow-inner z-10 bg-slate-100"></div>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-900/80 backdrop-blur-md transition-all duration-300 opacity-0" onclick="closeModal('imageModal')">
        <div class="modal-content relative transition-all duration-300 transform scale-95 p-4">
            <img id="modalImage" src="" class="max-w-full max-h-[85vh] rounded-xl shadow-2xl border-4 border-white" alt="Visit Image">
            <button type="button" onclick="closeModal('imageModal')" class="absolute -top-10 right-0 text-white hover:text-rose-400 transition-all">
                <span class="material-symbols-outlined text-[32px]">close</span>
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        $(document).ready(function() {
            let map = null;
            let markers = [];

            // --- Helper: Reset Dropdown ---
            function resetDropdown(selector, placeholder = "All") {
                $(selector).html(`<option value="">${placeholder}</option>`);
            }

            // --- Cascading Location Logic ---
            
            // Zone -> State & Users (Avoiding Telecallers)
            $('#filter_zone').change(function() {
                let zoneId = $(this).val();
                resetDropdown('#filter_state', 'All States'); 
                resetDropdown('#filter_district', 'All Districts'); 
                resetDropdown('#filter_city', 'All Cities');
                resetDropdown('#filter_user_id', 'All Users'); 
                
                if(zoneId) {
                    $.get("{{ route('site-visits.get-locations') }}", { type: 'zone', id: zoneId }, function(data) {
                        // Populate States
                        let states = data.states || [];
                        states.forEach(st => $('#filter_state').append(`<option value="${st.id}">${st.name}</option>`));

                        // Populate Users (Non-Telecallers)
                        let users = data.users || [];
                        users.forEach(u => $('#filter_user_id').append(`<option value="${u.id}">${u.name}</option>`));
                    });
                }
            });

            // State -> District
            $('#filter_state').change(function() {
                let stateId = $(this).val();
                resetDropdown('#filter_district', 'All Districts'); 
                resetDropdown('#filter_city', 'All Cities');
                
                if(stateId) {
                    $.get("{{ route('site-visits.get-locations') }}", { type: 'state', id: stateId }, function(data) {
                        data.forEach(dt => $('#filter_district').append(`<option value="${dt.id}">${dt.name}</option>`));
                    });
                }
            });

            // District -> City
            $('#filter_district').change(function() {
                let districtId = $(this).val();
                resetDropdown('#filter_city', 'All Cities');
                
                if(districtId) {
                    $.get("{{ route('site-visits.get-locations') }}", { type: 'district', id: districtId }, function(data) {
                        data.forEach(city => $('#filter_city').append(`<option value="${city.id}">${city.name}</option>`));
                    });
                }
            });

            // --- DataTable Initialization ---
            var table = $('#site-visit-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rtp',
                pageLength: 10,
                ajax: {
                    url: "{{ route('site-visits.data') }}",
                    data: function (d) {
                        // Standard Filters
                        d.user_id = $('#filter_user_id').val();
                        d.from_date = $('#filter_from_date').val();
                        d.to_date = $('#filter_to_date').val();
                        // Location Filters
                        d.zone_id = $('#filter_zone').val();
                        d.state_id = $('#filter_state').val();
                        d.district_id = $('#filter_district').val();
                        d.city_id = $('#filter_city').val();
                    }
                },
                createdRow: function(row) {
                    $(row).addClass('glass-card hover:bg-blue-50/50 transition-colors group');
                    $(row).find('td').addClass('py-3 px-4 border-b border-slate-100 group-hover:border-blue-100');
                    $(row).find('td:first').addClass('pl-4');
                    $(row).find('td:last').addClass('pr-4');
                },
                columns: [
                    { 
                        data: null, 
                        name: 'id', 
                        orderable: false, 
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'user_name', name: 'user.name' },
                    { data: 'lead_name', name: 'lead.name' },
                    { data: 'visit_date', name: 'visit_date', orderable: false },
                    { data: 'intime_time', name: 'intime_time' },
                    { data: 'out_time', name: 'out_time' },
                    { data: 'remarks', name: 'remarks', 
                      render: function(data) { return data ? `<span class="truncate max-w-[150px] block" title="${data}">${data}</span>` : '-'; }
                    },
                    { 
                        data: 'image', 
                        className: 'text-center',
                        render: function(data) {
                            return data ? `<button onclick="openImageModal('${data}')" class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 shadow-sm hover:scale-110 hover:shadow-md transition-all"><img src="${data}" class="w-full h-full object-cover"></button>` : '<span class="text-slate-300 text-[20px] material-symbols-outlined">image_not_supported</span>';
                        }
                    },
                    { 
                        data: 'map_data', 
                        className: 'text-end',
                        render: function(data) {
                            const dataStr = encodeURIComponent(JSON.stringify(data));
                            return `<button onclick="openMapModal('${dataStr}')" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center gap-1 ml-auto shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> View
                            </button>`;
                        }
                    }
                ],
                order: [[0, 'desc']],
                drawCallback: function(settings) {
                    const total = settings.json ? settings.json.recordsTotal : 0;
                    $('#table-info').text(`Total Records: ${total}`);
                    
                    // Move pagination
                    $('#table-pagination').html($('.dataTables_paginate').html());
                    $('.dataTables_paginate').empty();

                    // Bind pagination clicks
                    $('#table-pagination .paginate_button').on('click', function(e) {
                         e.preventDefault();
                         if (!$(this).hasClass('disabled') && !$(this).hasClass('current')) {
                             const page = $(this).data('dt-idx');
                             table.page(page).draw('page');
                         }
                    });
                }
            });

            // --- Event Listeners ---
            $('#custom-search').on('keyup input', function() { table.search(this.value).draw(); });
            $('#btn_filter').click(function() { table.draw(); });
            
            $('#btn_reset').click(function() {
                $('#filter_zone, #filter_state, #filter_district, #filter_city').val('').trigger('change');
                $('#filter_user_id, #filter_from_date, #filter_to_date').val('');
                $('#custom-search').val('');
                table.search('').draw();
            });

            // --- Modals Logic ---
            window.openMapModal = (dataEncoded) => {
                const data = JSON.parse(decodeURIComponent(dataEncoded));
                $('#mapModal').removeClass('hidden');
                setTimeout(() => {
                    $('#mapModal').removeClass('opacity-0').addClass('opacity-100');
                    $('.modal-content', '#mapModal').removeClass('scale-95').addClass('scale-100');
                }, 10);

                if (!map) {
                    map = L.map('map').setView([0, 0], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                }

                markers.forEach(m => map.removeLayer(m));
                markers = [];
                const bounds = [];

                if (data.lat && data.lng) {
                    const m = L.marker([data.lat, data.lng]).addTo(map).bindPopup("<b>Check In</b>");
                    markers.push(m);
                    bounds.push([data.lat, data.lng]);
                }
                if (data.check_out_lat && data.check_out_lng) {
                    const m = L.marker([data.check_out_lat, data.check_out_lng]).addTo(map).bindPopup("<b>Check Out</b>");
                    markers.push(m);
                    bounds.push([data.check_out_lat, data.check_out_lng]);
                }

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                } else {
                    map.setView([20.5937, 78.9629], 5); // Default to India view if no coords
                }
                setTimeout(() => { map.invalidateSize(); }, 300);
            };

            window.openImageModal = (src) => {
                $('#modalImage').attr('src', src);
                $('#imageModal').removeClass('hidden');
                setTimeout(() => {
                    $('#imageModal').removeClass('opacity-0').addClass('opacity-100');
                    $('.modal-content', '#imageModal').removeClass('scale-95').addClass('scale-100');
                }, 10);
            };

            window.closeModal = (id) => {
                $(`#${id}`).removeClass('opacity-100').addClass('opacity-0');
                $('.modal-content', `#${id}`).removeClass('scale-100').addClass('scale-95');
                setTimeout(() => { $(`#${id}`).addClass('hidden'); }, 300);
            };
        });
    </script>
@endsection