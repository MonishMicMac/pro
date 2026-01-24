<nav class="flex-grow-1 overflow-auto px-3 py-2">

    <!-- MAIN -->
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

    <!-- WORK -->
    <div class="mb-4">
        <p class="px-3 mb-2 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
            Work
        </p>

        <a href="{{ route('fabricator.assignments') }}"
            class="sidebar-link {{ request()->routeIs('fabricator.assignments*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">assignment</span>
            Assignments
        </a>
    </div>

    <!-- ACCOUNT -->
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
