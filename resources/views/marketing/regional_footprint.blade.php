@extends('layouts.app')

@section('title', 'Regional Foot Print')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        #map { height: calc(100vh - 350px); width: 100%; border-radius: 1rem; }
        .legend {
            background: white;
            padding: 10px;
            line-height: 18px;
            color: #555;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            max-height: 300px;
            overflow-y: auto;
        }
        .legend i {
            width: 15px;
            height: 15px;
            float: left;
            margin-right: 8px;
            opacity: 0.7;
            border-radius: 50%;
            border: 1px solid #ccc;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
    </style>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-black text-slate-900 mb-1">REGIONAL FOOT PRINT</h3>
            <p class="text-secondary small mb-0">Geographic distribution of Leads by stage</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="loadMarkers()" class="btn btn-white border-0 shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined fs-5">refresh</span>
                Refresh Map
            </button>
        </div>
    </div>

    <div class="glass-panel p-4 rounded-[1.5rem] mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Stage</label>
                <select id="filter_lead_stage"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                    <option value="">All Stages</option>
                    <option value="0">Site Identification</option>
                    <option value="1">Intro</option>
                    <option value="2">FollowUp</option>
                    <option value="3">Quote Pending</option>
                    <option value="4">Quote Sent</option>
                    <option value="5">Won</option>
                    <option value="6">Site Handed Over</option>
                    <option value="7">Lost</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone</label>
                <select id="filter_zone_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Zones</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Zone Manager</label>
                <select id="filter_zsm_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All ZSMs</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Manager</label>
                <select id="filter_manager_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Managers</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">BDO / Assigned To</label>
                <select id="filter_bdo_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All BDOs</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">State</label>
                <select id="filter_state_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All States</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">District</label>
                <select id="filter_district_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Districts</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">City</label>
                <select id="filter_city_id"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
                    <option value="">All Cities</option>
                </select>
            </div>


            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">From Date</label>
                <input type="date" id="filter_from_date"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">To Date</label>
                <input type="date" id="filter_to_date"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold text-slate-700 focus:border-blue-500 transition-all">
            </div>

            <div class="flex items-end gap-2 lg:col-span-2">
                <button id="btn_filter"
                    class="flex-1 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> Apply Filters
                </button>
                <button id="btn_reset"
                    class="px-3 py-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                </button>
            </div>
        </div>
    </div>

    <div class="glass-panel p-3 rounded-4 shadow-sm">
        <div id="map" class="h-72"></div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        let map;
        let markersLayer;

        // Custom Icon Function
        function createCustomIcon(color) {
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color:${color}; width:15px; height:15px; border-radius:50%; border:2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [15, 15],
                iconAnchor: [7, 7]
            });
        }

        // Initialize Map
        function initMap() {
            map = L.map('map').setView([20.5937, 78.9629], 5); // Default center of India

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            // Add Legend
            const legend = L.control({position: 'bottomright'});
            legend.onAdd = function (map) {
                const div = L.DomUtil.create('div', 'legend');
                const data = [
                    { color: '#6b7280', label: 'Site Identification' },
                    { color: '#2563eb', label: 'Intro' },
                    { color: '#4f46e5', label: 'FollowUp' },
                    { color: '#d97706', label: 'Quote Pending' },
                    { color: '#9333ea', label: 'Quote Sent' },
                    { color: '#16a34a', label: 'Won' },
                    { color: '#059669', label: 'Site Handed Over' },
                    { color: '#e11d48', label: 'Lost' }
                ];

                div.innerHTML = '<h6 class="mb-2 fw-bold small">Lead Stages</h6>';
                data.forEach(item => {
                    div.innerHTML += `<div><i style="background: ${item.color}"></i> <span class="small">${item.label}</span></div>`;
                });
                return div;
            };
            legend.addTo(map);

            loadMarkers();
        }

        // Load Markers from Backend
        function loadMarkers() {
            markersLayer.clearLayers();
            
            const params = {
                lead_stage: $('#filter_lead_stage').val(),
                zone_id: $('#filter_zone_id').val(),
                zsm_id: $('#filter_zsm_id').val(),
                manager_id: $('#filter_manager_id').val(),
                bdo_id: $('#filter_bdo_id').val(),
                state_id: $('#filter_state_id').val(),
                district_id: $('#filter_district_id').val(),
                city_id: $('#filter_city_id').val(),
                from_date: $('#filter_from_date').val(),
                to_date: $('#filter_to_date').val()
            };

            $.get("{{ route('marketing.regional.footprint.data') }}", params, function(response) {
                if (response.success) {
                    const data = response.data;
                    const bounds = [];

                    data.forEach(item => {
                        const marker = L.marker([item.lat, item.lng], {
                            icon: createCustomIcon(item.color)
                        }).bindPopup(`<strong>${item.name}</strong><br>${item.details}`);
                        
                        markersLayer.addLayer(marker);
                        bounds.push([item.lat, item.lng]);
                    });

                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    } else {
                        map.setView([20.5937, 78.9629], 5);
                    }
                }
            }).fail(function(err) {
                console.error("Error loading map data:", err);
                Swal.fire('Error', 'Failed to load map data', 'error');
            });
        }

        $(document).ready(function() {
            const authGeo = {
                zone: "{{ Auth::user()->zone_id }}",
                state: "{{ Auth::user()->state_id }}",
                district: "{{ Auth::user()->district_id }}",
                city: "{{ Auth::user()->city_id }}"
            };

            function clearDropdown(id, placeholder) {
                $(id).empty().append(`<option value="">All ${placeholder}</option>`);
            }

            // Chained Dropdown Logic
            function getZoneData(zoneId, callback) {
                if (!zoneId) {
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    return;
                }
                $.get("{{ route('get.location.data') }}", { type: 'zone', id: zoneId }, function(data) {
                    clearDropdown('#filter_state_id', 'States');
                    $.each(data.states, function(i, item) { $('#filter_state_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    $.each(data.zsms, function(i, item) { $('#filter_zsm_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    clearDropdown('#filter_manager_id', 'Managers');
                    $.each(data.bdms, function(i, item) { $('#filter_manager_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    $.each(data.bdos, function(i, item) { $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    if (callback) callback();
                });
            }

            function getDistricts(stateId, callback) {
                if (!stateId) { clearDropdown('#filter_district_id', 'Districts'); return; }
                $.get("{{ route('get.location.data') }}", { type: 'state', id: stateId }, function(data) {
                    clearDropdown('#filter_district_id', 'Districts');
                    $.each(data, function(i, item) { $('#filter_district_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    if (callback) callback();
                });
            }

            function getCities(districtId, callback) {
                if (!districtId) { clearDropdown('#filter_city_id', 'Cities'); return; }
                $.get("{{ route('get.location.data') }}", { type: 'district', id: districtId }, function(data) {
                    clearDropdown('#filter_city_id', 'Cities');
                    $.each(data, function(i, item) { $('#filter_city_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    if (callback) callback();
                });
            }

            function getManagers(zsmId, callback) {
                if (!zsmId) { clearDropdown('#filter_manager_id', 'Managers'); clearDropdown('#filter_bdo_id', 'BDOs'); return; }
                $.get("{{ route('get.location.data') }}", { type: 'bdm', id: zsmId }, function(data) {
                    clearDropdown('#filter_manager_id', 'Managers');
                    $.each(data, function(i, item) { $('#filter_manager_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    if (callback) callback();
                });
            }

            function getBdos(bdmId, callback) {
                if (!bdmId) { clearDropdown('#filter_bdo_id', 'BDOs'); return; }
                $.get("{{ route('get.location.data') }}", { type: 'bdo', id: bdmId }, function(data) {
                    clearDropdown('#filter_bdo_id', 'BDOs');
                    $.each(data, function(i, item) { $('#filter_bdo_id').append(`<option value="${item.id}">${item.name}</option>`); });
                    if (callback) callback();
                });
            }

            $('#filter_zone_id').on('change', function() { getZoneData($(this).val()); clearDropdown('#filter_district_id', 'Districts'); clearDropdown('#filter_city_id', 'Cities'); });
            $('#filter_zsm_id').on('change', function() { getManagers($(this).val()); });
            $('#filter_manager_id').on('change', function() { getBdos($(this).val()); });
            $('#filter_state_id').on('change', function() { getDistricts($(this).val()); clearDropdown('#filter_city_id', 'Cities'); });
            $('#filter_district_id').on('change', function() { getCities($(this).val()); });

            if (authGeo.zone) {
                $('#filter_zone_id').val(authGeo.zone).prop('disabled', true);
                getZoneData(authGeo.zone, function() {
                    if (authGeo.state) {
                        $('#filter_state_id').val(authGeo.state).prop('disabled', true);
                        getDistricts(authGeo.state, function() {
                            if (authGeo.district) {
                                $('#filter_district_id').val(authGeo.district).prop('disabled', true);
                                getCities(authGeo.district);
                            }
                        });
                    }
                });
            }

            $('#btn_filter').click(function() { loadMarkers(); });
            $('#btn_reset').click(function() {
                $('#filter_lead_stage, #filter_from_date, #filter_to_date').val('');
                if (!authGeo.zone) {
                    $('select:not([disabled])').val('');
                    clearDropdown('#filter_state_id', 'States');
                    clearDropdown('#filter_district_id', 'Districts');
                    clearDropdown('#filter_city_id', 'Cities');
                    clearDropdown('#filter_zsm_id', 'ZSMs');
                    clearDropdown('#filter_manager_id', 'Managers');
                    clearDropdown('#filter_bdo_id', 'BDOs');
                } else {
                    $('select:not([disabled])').val('');
                }
                loadMarkers();
            });

            initMap();
        });
    </script>
    @endpush
@endsection
