@extends('layouts.app')

@section('title', 'Regional Foot Print')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: calc(100vh - 180px); width: 100%; border-radius: 1rem; }
    .legend {
        background: white;
        padding: 10px;
        line-height: 18px;
        color: #555;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .legend i {
        width: 18px;
        height: 18px;
        float: left;
        margin-right: 8px;
        opacity: 0.7;
        border-radius: 50%;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-black text-slate-900 mb-1">REGIONAL FOOT PRINT</h3>
        <p class="text-secondary small mb-0">Geographic distribution of Leads by status</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="loadMarkers()" class="btn btn-white border-0 shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2">
            <span class="material-symbols-outlined fs-5">refresh</span>
            Refresh Map
        </button>
    </div>
</div>

<div class="glass-panel p-3 rounded-4 shadow-sm">
    <div id="map"></div>
</div>

@push('scripts')
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
                { color: 'purple', label: 'Quote Sent' },
                { color: 'gray', label: 'Unidentified Sites' },
                { color: 'green', label: 'Converted Sites' }
            ];

            div.innerHTML = '<h6 class="mb-2 fw-bold small">Map Legend</h6>';
            data.forEach(item => {
                div.innerHTML += `<i style="background: ${item.color}"></i> ${item.label}<br>`;
            });
            return div;
        };
        legend.addTo(map);

        loadMarkers();
    }

    // Load Markers from Backend
    function loadMarkers() {
        markersLayer.clearLayers();
        
        fetch("{{ route('marketing.regional.footprint.data') }}")
            .then(res => res.json())
            .then(response => {
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
                    }
                }
            })
            .catch(err => {
                console.error("Error loading map data:", err);
                Swal.fire('Error', 'Failed to load map data', 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', initMap);
</script>
@endpush
@endsection
