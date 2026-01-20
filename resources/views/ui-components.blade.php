@extends('layouts.app')

@section('title', 'UI Kit')

@section('content')
    <div class="container py-4">
         <div class="row g-5">
            <div class="col-12 text-center mb-3">
                <h1 class="display-5 fw-bold mb-3">UI Components</h1>
                <p class="text-secondary lead">A collection of reusable glassmorphism components for the Prominance System.</p>
            </div>

            <!-- Section: Buttons -->
            <div class="col-12">
                <div class="glass-panel p-5 rounded-5">
                    <h5 class="text-small-caps text-secondary mb-4">Buttons & Actions</h5>
                    
                    <div class="d-flex flex-wrap gap-4 align-items-center mb-5">
                        <!-- Gradient Primary -->
                        <div>
                            <p class="small text-muted mb-2">Primary Gradient</p>
                            <button class="btn btn-gradient-primary rounded-pill px-4 py-2">Primary Action</button>
                        </div>

                        <!-- Outline / Secondary -->
                        <div>
                            <p class="small text-muted mb-2">Secondary Outline</p>
                            <button class="btn btn-outline-primary fw-bold rounded-pill px-4 py-2">Secondary Action</button>
                        </div>
                        
                        <!-- Ghost / Glass -->
                        <div>
                            <p class="small text-muted mb-2">Glass / Ghost</p>
                            <button class="btn btn-glass rounded-pill px-4 py-2">Glass Button</button>
                        </div>

                         <!-- Icon Only -->
                         <div>
                            <p class="small text-muted mb-2">Icon Button</p>
                            <button class="btn btn-glass rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <span class="material-symbols-outlined text-secondary">settings</span>
                            </button>
                        </div>
                    </div>

                     <!-- Sizes -->
                     <div class="d-flex flex-wrap gap-3 align-items-end">
                        <button class="btn btn-gradient-primary rounded-4 px-3 py-1 text-uppercase" style="font-size: 0.7rem;">Small</button>
                        <button class="btn btn-gradient-primary rounded-4 px-4 py-2">Default</button>
                        <button class="btn btn-gradient-primary rounded-4 px-5 py-3 fs-5">Large Button</button>
                    </div>
                </div>
            </div>

            <!-- Section: Cards -->
            <div class="col-12">
                <h5 class="text-small-caps text-secondary mb-4 px-2">Cards & Panels</h5>
                <div class="row g-4">
                    <!-- Stat Card -->
                    <div class="col-md-3">
                        <div class="glass-card p-4 h-100 position-relative overflow-hidden">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="material-symbols-outlined fs-2 text-brand-blue">trending_up</span>
                                <span class="badge bg-success-subtle text-success rounded-pill">+12%</span>
                            </div>
                            <h3 class="fw-bold mb-1">2,450</h3>
                            <p class="text-secondary small fw-bold text-uppercase mb-0">Total Projects</p>
                            
                            <!-- Decor element -->
                            <div class="position-absolute bottom-0 end-0 p-3 opacity-10">
                                <span class="material-symbols-outlined" style="font-size: 80px; transform: rotate(-15deg);">trending_up</span>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Card -->
                    <div class="col-md-4">
                        <div class="glass-card p-4 text-center">
                            <div class="position-relative d-inline-block mb-3">
                                <div class="avatar shadow-lg rounded-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, #1e73be, #60a5fa); font-size: 1.5rem;">AR</div>
                                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-4 border-white" style="width: 20px; height: 20px;"></div>
                            </div>
                            <h5 class="fw-bold mb-1">Alex Rivera</h5>
                            <p class="text-brand-blue small fw-bold text-uppercase mb-3">Sales Director</p>
                            <p class="text-secondary small mb-4 px-3">Managing larger corporate accounts and distribution networks across the region.</p>
                            <button class="btn btn-glass btn-sm w-100 rounded-pill">View Profile</button>
                        </div>
                    </div>

                    <!-- List Card -->
                    <div class="col-md-5">
                        <div class="glass-panel rounded-5 h-100">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">Recent Activity</h6>
                                <button class="btn btn-sm btn-link text-decoration-none small fw-bold">View All</button>
                            </div>
                            <div class="list-group list-group-flush bg-transparent">
                                <a href="#" class="list-group-item bg-transparent border-bottom text-decoration-none d-flex align-items-center gap-3 py-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex"><span class="material-symbols-outlined fs-6">mail</span></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-body small">New Quote Request</div>
                                        <div class="text-secondary" style="font-size: 0.75rem;">From Marcus Knight • 2m ago</div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item bg-transparent border-bottom text-decoration-none d-flex align-items-center gap-3 py-3">
                                    <div class="rounded-circle bg-success-subtle text-success p-2 d-flex"><span class="material-symbols-outlined fs-6">check_circle</span></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-body small">Order Completed</div>
                                        <div class="text-secondary" style="font-size: 0.75rem;">INV-2093 Approved • 1h ago</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Forms -->
            <div class="col-lg-6">
                <div class="glass-panel p-5 rounded-5 h-100">
                    <h5 class="text-small-caps text-secondary mb-4">Form Inputs</h5>
                    <form class="row g-4">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Standard Input</label>
                            <input type="text" class="form-control form-control-glass" placeholder="Type something clean...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Input with Icon</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0 text-secondary ps-0"><span class="material-symbols-outlined">search</span></span>
                                <input type="text" class="form-control form-control-glass" placeholder="Search...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Select Menu</label>
                            <select class="form-select form-select-glass">
                                <option>Option 1</option>
                                <option>Option 2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                             <label class="form-label small fw-bold text-secondary text-uppercase">Date Picker</label>
                             <input type="date" class="form-control form-control-glass">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" checked>
                                <label class="form-check-label small fw-bold text-body" for="flexCheckDefault">
                                  Glass Checkbox Active
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault2">
                                <label class="form-check-label small fw-bold text-body" for="flexCheckDefault2">
                                  Glass Checkbox Inactive
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Section: Modals & Badges -->
            <div class="col-lg-6">
                <div class="glass-panel p-5 rounded-5 h-100">
                    <h5 class="text-small-caps text-secondary mb-4">Interactive & Status</h5>
                    
                    <div class="mb-5">
                        <p class="small text-muted mb-2">Modal Triggers</p>
                        <button class="btn btn-gradient-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#demoModal">
                            Launch Glass Modal
                        </button>
                    </div>

                    <div class="mb-5">
                        <p class="small text-muted mb-2">Status Badges</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-success rounded-circle" style="width: 6px; height: 6px;"></span>
                                ACTIVE
                            </span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-primary rounded-circle" style="width: 6px; height: 6px;"></span>
                                PROCESSING
                            </span>
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-warning rounded-circle" style="width: 6px; height: 6px;"></span>
                                PENDING
                            </span>
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-danger rounded-circle" style="width: 6px; height: 6px;"></span>
                                REJECTED
                            </span>
                        </div>
                    </div>

                    <div>
                        <p class="small text-muted mb-2">Avatar Group</p>
                        <div class="avatar-group">
                            <div class="avatar shadow-sm rounded-circle bg-primary text-white small fw-bold d-flex align-items-center justify-content-center" style="width:40px;height:40px;">AB</div>
                            <div class="avatar shadow-sm rounded-circle bg-success text-white small fw-bold d-flex align-items-center justify-content-center" style="width:40px;height:40px;">CD</div>
                            <div class="avatar shadow-sm rounded-circle bg-info text-white small fw-bold d-flex align-items-center justify-content-center" style="width:40px;height:40px;">EF</div>
                            <div class="avatar shadow-sm rounded-circle bg-white text-secondary small fw-bold d-flex align-items-center justify-content-center border" style="width:40px;height:40px;">+5</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('modals')
    <!-- Demo Modal -->
    <div class="modal fade modal-glass" id="demoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 ps-4 pe-4 pt-4">
                    <h5 class="modal-title fw-bold">Glassmorphism Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary">This modal uses the <code>.modal-glass</code> class. It features a backdrop blur, white transparency, and a smooth "fade up" animation.</p>
                    <input type="text" class="form-control form-control-glass mb-3" placeholder="You can type here...">
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-glass rounded-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-gradient-primary rounded-3 text-white">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
@endpush
