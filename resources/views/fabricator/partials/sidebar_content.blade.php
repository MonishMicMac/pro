<style>
    /* Submenu */
    .sidebar-submenu-wrapper .submenu-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        background: none;
        border: none;
        padding: 10px 16px;
        font-weight: 600;
    }

    .sidebar-submenu {
        display: none;
        padding-left: 48px;
    }

    .sidebar-submenu-wrapper.open .sidebar-submenu {
        display: block;
    }

    .sidebar-sublink {
        display: block;
        padding: 8px 0;
        font-size: 0.75rem;
        color: #64748b;
        text-decoration: none;
    }

    .sidebar-sublink.active {
        color: #2563eb;
        font-weight: 700;
    }

    .submenu-arrow {
        margin-left: auto;
        font-size: 18px;
        transition: transform .2s ease;
    }

    .sidebar-submenu-wrapper.open .submenu-arrow {
        transform: rotate(180deg);
    }
</style>

<nav class="flex-grow-1 overflow-auto px-3 py-2">

    <div class="mb-4">
        <p class="px-3 mb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
            Main
        </p>

        <a href="{{ route('fabricator.dashboard') }}"
            class="sidebar-link {{ request()->routeIs('fabricator.dashboard') ? 'active' : '' }}">
            <span class="material-symbols-outlined">dashboard</span>
            Dashboard
        </a>
    </div>

    <div class="mb-4">
        <p class="px-3 mb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
            Work
        </p>

        <a href="{{ route('fabricator.assignments') }}"
            class="sidebar-link {{ request()->routeIs('fabricator.assignments*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">assignment</span>
            Assignments
        </a>

        <a href="{{ route('fabricator-stock.report') }}"
            class="sidebar-link {{ request()->routeIs('fabricator-stock.report*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">inventory</span>
            Stock Report
        </a>
    </div>



    <div class="mb-4">
        <p class="px-3 mb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
            Account
        </p>

        <a href="{{ route('fabricator.profile') }}"
            class="sidebar-link {{ request()->routeIs('fabricator.profile') ? 'active' : '' }}">
            <span class="material-symbols-outlined">person</span>
            Profile
        </a>
    </div>

</nav>
