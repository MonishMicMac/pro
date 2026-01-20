<aside class="sidebar glass-panel border-end d-none d-lg-flex">
    <div class="p-4 d-flex align-items-center gap-3">
        <div class="rounded-3 d-flex align-items-center justify-content-center text-white shadow-lg" 
             style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
            <span class="material-symbols-outlined fs-4">shield</span>
        </div>
        <div>
            <h5 class="mb-0 fw-bold lh-1 text-body">Prominance</h5>
            <small class="text-brand-green fw-bold text-uppercase" style="letter-spacing: 0.15em; font-size: 0.6rem;">uPVC Excellence</small>
        </div>
    </div>

    @include('partials.sidebar_content')

    <div class="p-3 border-top border-secondary-subtle">
        <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2 border-0 bg-opacity-10 bg-white" 
                id="themeToggle">
            <span class="material-symbols-outlined fs-5 dark-icon d-none">light_mode</span>
            <span class="material-symbols-outlined fs-5 light-icon">dark_mode</span>
            <span class="fw-semibold text-body" style="font-size: 0.875rem;">Appearance</span>
        </button>
    </div>
</aside>
