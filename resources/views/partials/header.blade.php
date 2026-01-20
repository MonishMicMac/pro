<header class="glass-panel sticky-top px-4 py-3 d-flex align-items-center justify-content-between border-bottom z-3">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="flex-grow-1 d-flex align-items-center gap-4">
        <button class="btn btn-link text-body d-lg-none p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <span class="material-symbols-outlined">menu</span>
        </button>
        
        <div class="position-relative w-100" style="max-width: 400px;">
            <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary">search</span>
            <input type="text" class="form-control rounded-4 border-0 bg-white bg-opacity-50 ps-5" placeholder="Search leads, systems, or orders..."
                   style="padding-top: 0.6rem; padding-bottom: 0.6rem;">
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="d-flex gap-2 border-end pe-3 border-secondary-subtle">
            <button class="btn btn-light bg-opacity-50 rounded-4 p-2 d-flex position-relative">
                <span class="material-symbols-outlined text-secondary">notifications</span>
                <span class="position-absolute top-0 end-0 mt-2 me-2 bg-brand-green rounded-circle border border-2 border-white" 
                      style="width: 8px; height: 8px;"></span>
            </button>
            <button class="btn btn-light bg-opacity-50 rounded-4 p-2 d-flex">
                <span class="material-symbols-outlined text-secondary">help_outline</span>
            </button>
        </div>

        <div class="dropdown">
            <div class="d-flex align-items-center gap-3 cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                <div class="text-end d-none d-sm-block">
                    <p class="mb-0 fw-bold small lh-1">{{ Auth::user()->name }}</p>
                    
                    <p class="mb-0 text-brand-blue fw-bold text-uppercase mt-1" style="font-size: 0.6rem; letter-spacing: 0.05em;">
                        @if(Auth::user()->roles->isNotEmpty())
                            {{ Auth::user()->roles->pluck('name')->first() }}
                        @else
                            User
                        @endif
                    </p>
                </div>
                
                <div class="position-relative">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0D6EFD&color=fff&rounded=true" 
                         alt="Profile" class="rounded-4 object-fit-cover shadow-sm" style="width: 44px; height: 44px;">
                    <div class="position-absolute bottom-0 end-0 bg-brand-green rounded-circle border border-2 border-white" 
                         style="width: 14px; height: 14px; transform: translate(10%, 10%);"></div>
                </div>
            </div>

            <ul class="dropdown-menu dropdown-menu-end border-0 rounded-4 mt-2 p-2 shadow-xl" 
                style="min-width: 220px; background-color: #ffffff; opacity: 1 !important; backdrop-filter: none !important;">
                
                <li>
                    <div class="px-3 py-2">
                        <p class="mb-0 small fw-black text-muted text-uppercase tracking-widest" style="font-size: 10px;">Account Management</p>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2.5 rounded-3 hover:bg-light" href="#">
                        <span class="material-symbols-outlined text-secondary">person</span> 
                        <span class="small fw-bold text-dark">My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2.5 rounded-3 hover:bg-light" href="#">
                        <span class="material-symbols-outlined text-secondary">settings</span> 
                        <span class="small fw-bold text-dark">Account Settings</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-2 opacity-10"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2.5 rounded-3 text-danger hover:bg-danger-subtle" href="#" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <span class="material-symbols-outlined">logout</span> 
                        <span class="small fw-bold">Sign Out</span>
                    </a>
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>