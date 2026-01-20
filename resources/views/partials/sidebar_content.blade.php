<nav class="flex-grow-1 overflow-auto px-3 py-2">
    @php
        $sidebarmenus = \App\Models\MainMenu::with(['submenus' => function($query) {
            $query->orderBy('position', 'asc');
        }])->orderBy('position', 'asc')->get();
    @endphp

    @foreach($sidebarmenus as $main)
        <div class="mb-4">
            <p class="px-3 mb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
                {{ $main->mainmenu_name }}
            </p>
            
            <div class="d-flex flex-column gap-1">
                {{-- Case 1: The Main Menu IS the link (e.g., Dashboard) --}}
                @if($main->submenus->count() == 0)
                    @php 
                        $isActive = request()->is($main->path) || ($main->path == 'dashboard' && request()->is('/')); 
                    @endphp
                    
                    <a href="{{ url($main->path) }}" class="sidebar-link {{ $isActive ? 'active' : '' }}">
                        <span class="material-symbols-outlined {{ $isActive ? 'filled text-brand-green' : '' }}">
                            {{ $main->icon }}
                        </span>
                        {{ $main->mainmenu_name }}
                        @if($isActive)
                            <div class="ms-auto bg-brand-green rounded-circle glow-indicator" style="width: 6px; height: 6px;"></div>
                        @endif
                    </a>
                @else
                    {{-- Case 2: The Main Menu is a Header, Submenus are the actual links --}}
@foreach($main->submenus as $sub)
    @php
        // 1. Get the last part of the path (e.g., 'masters/lead-types' -> 'lead-types')
        $pathParts = explode('/', trim($sub->path, '/'));
        $resourceName = end($pathParts); 
        
        // 2. STOP converting hyphens. Keep them as they are in the URL.
        // We only check for dot replacement if necessary.
        $permissionName = $resourceName . '.view'; // Becomes 'lead-types.view'
        
        $isActive = request()->is($sub->path) || request()->is($sub->path . '/*');
    @endphp

    @can($permissionName)
        <a href="{{ url($sub->path) }}" class="sidebar-link {{ $isActive ? 'active' : '' }}">
            <span class="material-symbols-outlined {{ $isActive ? 'filled text-brand-green' : '' }}">
                {{ $sub->icon ?? $main->icon }}
            </span>
            {{ $sub->submenu_name }}
        </a>
    @endcan
@endforeach
                @endif
            </div>
        </div>
    @endforeach
</nav>