@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
        .glass-card {
            @apply bg-white/50 backdrop-blur-sm border border-white/20 transition-all duration-200;
        }
        .glass-card:hover {
            @apply bg-white/90 -translate-y-0.5 shadow-md;
        }
        .form-input-custom {
            @apply w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer;
        }
    }
    
    #table-pagination .paginate_button {
        @apply px-2 py-1 mx-0.5 rounded-md bg-white text-slate-600 font-bold text-[10px] cursor-pointer transition-all inline-flex items-center justify-center min-w-[24px] border border-slate-100;
    }
    #table-pagination .paginate_button.current { @apply bg-blue-600 text-white shadow-md border-blue-600; }
    table.dataTable { border-collapse: separate !important; border-spacing: 0 0.4rem !important; }
    
    .force-click {
        position: relative;
        z-index: 50;
    }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-4 pb-20 bg-[#f8fafc] relative z-0">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Attendance</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Attendance Report</h1>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="glass-panel rounded-[1.5rem] p-4 relative z-20 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
            
            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Search</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[16px]">search</span>
                    <input id="customSearch" class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all" placeholder="Search..." type="text"/>
                </div>
            </div>

            @if(!Auth::user()->hasRole(['ZSM', 'BDM', 'BDO']))
            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                <select id="filter_zone_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(!Auth::user()->hasRole(['ZSM', 'BDM', 'BDO']))
            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone Manager</label>
                <select id="filter_zsm_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All ZSMs</option>
                </select>
            </div>
            @endif

            @if(!Auth::user()->hasRole(['BDM', 'BDO']))
            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager</label>
                <select id="filter_manager_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Managers</option>
                </select>
            </div>
            @endif

            @if(!Auth::user()->hasRole('BDO'))
            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO</label>
                <select id="filter_bdo_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All BDOs</option>
                </select>
            </div>
            @endif

             <div class="col-span-1 md:col-span-1 hidden">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">User</label>
                <select id="filter_user_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">From Date</label>
                <input type="date" id="filter_from_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
            </div>
            
             <div class="col-span-1 md:col-span-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">To Date</label>
                <input type="date" id="filter_to_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
            </div>

            <div class="flex items-end gap-2 col-span-1 md:col-span-1">
                <button id="btn_filter" class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
                </button>
                <button id="btn_reset" class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-[1.5rem] overflow-hidden relative z-10">
                <div class="px-4 overflow-x-auto pt-4">
            <table class="w-full" id="attendance-table">
                <thead>
                    <tr class="text-left">
                        <th class="pl-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Role</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Punch In</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Punch Out</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Spent Time</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Traveled Time</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Start KM</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">End KM</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Traveled KM</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Start Ph.</th>
                        <th class="px-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">End Ph.</th>
                        <th class="pr-4 pb-2 text-[10px] font-black text-slate-400 uppercase tracking-wider text-end">Map</th>
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

<!-- Map Modal -->
<div id="mapModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm transition-all duration-300 opacity-0">
    <div class="modal-content glass-panel w-full max-w-4xl h-[80vh] rounded-[1.25rem] p-6 shadow-2xl transition-all duration-300 transform scale-95 flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-black text-slate-800">Attendance Location</h3>
            <button type="button" onclick="closeModal('mapModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-all">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div id="map" class="flex-1 rounded-xl border border-slate-200 shadow-inner z-10"></div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-300 opacity-0" onclick="closeModal('imageModal')">
    <div class="modal-content relative transition-all duration-300 transform scale-95 flex justify-center items-center w-full h-full p-10">
        <img id="modalImage" src="" class="max-w-full max-h-full object-contain rounded-xl shadow-2xl border-4 border-white" alt="Attendance Photo">
        <button type="button" onclick="closeModal('imageModal')" class="absolute top-5 right-5 text-white hover:text-rose-400 transition-all z-50">
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

        // Auth User Info
        const authUser = {
            id: "{{ Auth::id() }}",
            zone_id: "{{ Auth::user()->zone_id }}",
            // Check roles safely. Assuming Spatie logic works in Blade
            is_zsm: {{ Auth::user()->hasRole('ZSM') ? 'true' : 'false' }},
            is_bdm: {{ Auth::user()->hasRole('BDM') ? 'true' : 'false' }},
            is_bdo: {{ Auth::user()->hasRole('BDO') ? 'true' : 'false' }}
        };

        function clearDropdown(id, placeholder) {
            $(id).empty().append(`<option value="">All ${placeholder}</option>`);
        }

        // --- Dependent Dropdown Logic (Replicated from Leads) ---
        function getZoneData(zoneId, callback) {
            if (!zoneId) {
                clearDropdown('#filter_zsm_id', 'ZSMs');
                clearDropdown('#filter_manager_id', 'Managers');
                clearDropdown('#filter_bdo_id', 'BDOs');
                return;
            }
            $.get("{{ route('get.location.data') }}", { type: 'zone', id: zoneId }, function(data) {
                // Populate ZSMs
                clearDropdown('#filter_zsm_id', 'ZSMs');
                $.each(data.zsms, function(i, item) {
                    $('#filter_zsm_id').append(`<option value="${item.id}">${item.name}</option>`);
                });
                
                // Populate Managers (BDM)
                clearDropdown('#filter_manager_id', 'Managers');
                $.each(data.bdms, function(i, item) {
                    $('#filter_manager_id').append(`<option value="${item.id}">${item.name}</option>`);
                });

                // Populate BDOs
                clearDropdown('#filter_bdo_id', 'BDOs');
                $.each(data.bdos, function(i, item) {
                    $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`);
                });

                if (callback) callback();
            });
        }

        function getManagers(zsmId, callback) {
            if (!zsmId) {
                clearDropdown('#filter_manager_id', 'Managers');
                clearDropdown('#filter_bdo_id', 'BDOs');
                return;
            }
            $.get("{{ route('get.location.data') }}", { type: 'bdm', id: zsmId }, function(data) {
                clearDropdown('#filter_manager_id', 'Managers');
                $.each(data, function(i, item) {
                    $('#filter_manager_id').append(`<option value="${item.id}">${item.name}</option>`);
                });
                if (callback) callback();
            });
        }

        function getBdos(bdmId, callback) {
            if (!bdmId) {
                clearDropdown('#filter_bdo_id', 'BDOs');
                return;
            }
             $.get("{{ route('get.location.data') }}", { type: 'bdo', id: bdmId }, function(data) {
                clearDropdown('#filter_bdo_id', 'BDOs');
                $.each(data, function(i, item) {
                    $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`);
                });
                if (callback) callback();
            });
        }

        // Change Handlers
        $('#filter_zone_id').change(function() { getZoneData($(this).val()); });
        $('#filter_zsm_id').change(function() { getManagers($(this).val()); });
        $('#filter_manager_id').change(function() { getBdos($(this).val()); });

        // --- Initialization Logic ---
        if (authUser.zone_id) {
            $('#filter_zone_id').val(authUser.zone_id).prop('disabled', true);
            getZoneData(authUser.zone_id, function() {
                // Auto-select based on Role
                if (authUser.is_zsm) {
                    $('#filter_zsm_id').val(authUser.id).prop('disabled', true);
                    getManagers(authUser.id);
                } else if (authUser.is_bdm) {
                    // If BDM, they might not be in the ZSM list if ZSM not selected?
                    // Actually, getZoneData loads ALL BDMs in that Zone.
                    // So we can select safely.
                    $('#filter_manager_id').val(authUser.id).prop('disabled', true);
                    getBdos(authUser.id);
                } else if (authUser.is_bdo) {
                     $('#filter_bdo_id').val(authUser.id).prop('disabled', true);
                }
            });
        } else {
             // Admin or no Zone assigned: Check roles anyway (e.g. if ZSM but no zone_id? Unlikely)
             // But if they are ZSM, they usually have zone_id.
        }

        var table = $('#attendance-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('attendance.data') }}",
                data: function (d) {
                    d.user_id = $('#filter_user_id').val();
                    d.zone_id = $('#filter_zone_id').val();
                    d.zsm_id = $('#filter_zsm_id').val();
                    d.manager_id = $('#filter_manager_id').val();
                    d.bdo_id = $('#filter_bdo_id').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                }
            },
            createdRow: function(row) {
                $(row).addClass('glass-card group');
                $(row).find('td:first').addClass('pl-4 py-3 rounded-l-xl');
                $(row).find('td:last').addClass('pr-4 py-3 rounded-r-xl');
                $(row).find('td').addClass('py-3 px-4');
            },
            columns: [
                { data: 'formatted_date', name: 'date' },
                { data: 'user_name', name: 'users.name' },
                { data: 'user_role', name: 'user_role' },
                { data: 'punch_in_time', name: 'punch_in_time' },
                { data: 'punch_out_time', name: 'punch_out_time' },
                { data: 'spend_time', name: 'spend_time', orderable: false },
                { data: 'travel_time', name: 'travel_time', orderable: false },
                { 
                    data: 'start_km', 
                    name: 'start_km',
                    render: function(data) { return data ? `<span class="font-bold text-slate-700">${data}</span>` : '-'; }
                },
                { 
                    data: 'end_km', 
                    name: 'end_km',
                    render: function(data) { return data ? `<span class="font-bold text-slate-700">${data}</span>` : '-'; }
                },
                { 
                    data: 'traveled_km', 
                    name: 'traveled_km',
                    render: function(data) { return data ? `<span class="text-blue-600 font-bold">${data} KM</span>` : '-'; }
                },
                { 
                    data: 'status', 
                    render: function(data) {
                        if (data === '0') return '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-[10px] uppercase font-bold tracking-wider">In Progress</span>';
                        if (data === '1') return '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] uppercase font-bold tracking-wider">Completed</span>';
                        return data;
                    }
                },
                { 
                    data: null, 
                    className: 'text-center',
                    render: function(data) {
                        if (data.start_km_photo_url) {
                            return `<button onclick="openImageModal('${data.start_km_photo_url}')" class="w-8 h-8 rounded-lg border border-slate-200 hover:scale-110 active:scale-95 transition-all shadow-sm overflow-hidden" title="Punch In Photo"><img src="${data.start_km_photo_url}" class="w-full h-full object-cover"></button>`;
                        }
                        return '-';
                    }
                },
                { 
                    data: null, 
                    className: 'text-center',
                    render: function(data) {
                        if (data.end_km_photo_url) {
                            return `<button onclick="openImageModal('${data.end_km_photo_url}')" class="w-8 h-8 rounded-lg border border-slate-200 hover:scale-110 active:scale-95 transition-all shadow-sm overflow-hidden" title="Punch Out Photo"><img src="${data.end_km_photo_url}" class="w-full h-full object-cover"></button>`;
                        }
                        return '-';
                    }
                },
                { 
                    data: 'map_data', 
                    className: 'text-end',
                    render: function(data) {
                        const dataStr = encodeURIComponent(JSON.stringify(data));
                        return `<button onclick="openMapModal('${dataStr}')" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center gap-1 ml-auto shadow-sm"><span class="material-symbols-outlined text-[14px]">map</span> Map</button>`;
                    }
                }
            ],
            order: [[0, 'desc']],
            dom: 'rtp',
            drawCallback: function(settings) {
                const total = settings.json ? settings.json.recordsTotal : 0;
                $('#table-info').text(`Total Records: ${total}`);
                $('#table-pagination').html($('.dataTables_paginate').html());
                $('.dataTables_paginate').empty();
            }
        });

        $('#btn_filter').click(function() { table.draw(); });
        $('#btn_reset').click(function() {
            $('#filter_user_id, #filter_from_date, #filter_to_date, #filter_zone_id, #filter_zsm_id, #filter_manager_id, #filter_bdo_id').val('');
            $('#customSearch').val(''); // Clear custom search too
            table.search('').draw(); // Clear datatable search
        });
        
        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });

        window.openMapModal = (dataEncoded) => {
            const data = JSON.parse(decodeURIComponent(dataEncoded));
            $('#mapModal').removeClass('hidden');
            setTimeout(() => {
                $('#mapModal').removeClass('opacity-0').addClass('opacity-100');
                $('.modal-content', '#mapModal').removeClass('scale-95').addClass('scale-100');
            }, 10);

            if (!map) {
                map = L.map('map').setView([0, 0], 2);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            }

            markers.forEach(m => map.removeLayer(m));
            markers = [];
            const bounds = [];

            if (data.in_lat && data.in_long) {
                const m = L.marker([data.in_lat, data.in_long]).addTo(map).bindPopup("Punch In");
                markers.push(m);
                bounds.push([data.in_lat, data.in_long]);
            }
            if (data.out_lat && data.out_long) {
                const m = L.marker([data.out_lat, data.out_long]).addTo(map).bindPopup("Punch Out");
                markers.push(m);
                bounds.push([data.out_lat, data.out_long]);
            }

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
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
            setTimeout(() => {
                $(`#${id}`).addClass('hidden');
            }, 300);
        };
    });
</script>
@endsection
