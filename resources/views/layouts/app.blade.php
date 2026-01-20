<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prominance CRM - @yield('title', 'Dashboard')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body data-bs-theme="light">

    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        @include('partials.header')

        <!-- Page Content -->
        <div class="p-4 overflow-y-auto h-100">
            @yield('content')

            <!-- Footer -->
            @include('partials.footer')
        </div>
    </main>

    <!-- Offcanvas Sidebar (Mobile) -->
    <div class="offcanvas offcanvas-start glass-panel" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white shadow-lg" 
                     style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                    <span class="material-symbols-outlined fs-4">shield</span>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold lh-1 text-body">Prominance</h5>
                    <small class="text-brand-green fw-bold text-uppercase" style="letter-spacing: 0.15em; font-size: 0.6rem;">uPVC Excellence</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <!-- Mobile Menu -->
             @include('partials.sidebar_content')
        </div>
    </div>

    <!-- Reusable Modals -->
    @stack('modals')

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Theme Toggler
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const iconLight = document.querySelector('.light-icon');
        const iconDark = document.querySelector('.dark-icon');

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const isDark = body.getAttribute('data-bs-theme') === 'dark';
                if (isDark) {
                    body.setAttribute('data-bs-theme', 'light');
                    iconLight.classList.remove('d-none');
                    iconDark.classList.add('d-none');
                } else {
                    body.setAttribute('data-bs-theme', 'dark');
                    iconLight.classList.add('d-none');
                    iconDark.classList.remove('d-none');
                }
            });
        }

         // Global Toast Function
    window.Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    </script>



    @stack('scripts')
</body>
</html>
