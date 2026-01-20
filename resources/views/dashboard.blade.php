@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Title Section -->
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-5">
        <div>
            <h1 class="display-6 fw-bold mb-1">System Overview</h1>
            <p class="text-secondary mb-0 fw-medium">Real-time performance metrics for uPVC distribution networks.</p>
        </div>
        <button class="btn text-white fw-bold rounded-4 px-4 py-2 d-flex align-items-center gap-2 shadow-sm"
                style="background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); border: none;"
                data-bs-toggle="modal" data-bs-target="#inquiryModal">
            <span class="material-symbols-outlined">add_circle</span>
            New Inquiry Entry
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <!-- Stat Card 1 -->
        <div class="col-sm-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; background: rgba(30, 115, 190, 0.1); color: var(--primary-color);">
                        <span class="material-symbols-outlined fs-3">trending_up</span>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">+12.4%</span>
                </div>
                <p class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Monthly Revenue</p>
                <h3 class="fw-bold mb-0">₹8,45,200</h3>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="col-sm-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; background: rgba(76, 175, 80, 0.1); color: var(--secondary-color);">
                        <span class="material-symbols-outlined fs-3">leaderboard</span>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">+5.8%</span>
                </div>
                <p class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Active Leads</p>
                <h3 class="fw-bold mb-0">1,428</h3>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="col-sm-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; background: rgba(30, 115, 190, 0.1); color: var(--primary-color);">
                        <span class="material-symbols-outlined fs-3">pie_chart</span>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">-2.1%</span>
                </div>
                <p class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Conversion Rate</p>
                <h3 class="fw-bold mb-0">28.4%</h3>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="col-sm-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; background: rgba(76, 175, 80, 0.1); color: var(--secondary-color);">
                        <span class="material-symbols-outlined fs-3">schedule</span>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Stable</span>
                </div>
                <p class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Average Lead Cycle</p>
                <h3 class="fw-bold mb-0">12 Days</h3>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="glass-panel rounded-5 overflow-hidden shadow-sm">
        <div class="p-4 border-bottom d-flex align-items-center justify-content-between bg-white bg-opacity-10">
            <div>
                <h4 class="fw-bold mb-1">Recent Inquiries</h4>
                <p class="text-secondary small fw-medium mb-0">Managing your latest project leads</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light border bg-opacity-50 fw-bold d-flex align-items-center gap-2 rounded-3">
                    <span class="material-symbols-outlined fs-6">filter_list</span>
                    Filter
                </button>
                <button class="btn btn-sm btn-light border bg-opacity-50 fw-bold d-flex align-items-center gap-2 rounded-3">
                    <span class="material-symbols-outlined fs-6">download</span>
                    Export
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="bg-light bg-opacity-50">
                    <tr>
                        <th class="px-4 py-3 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Customer Profile</th>
                        <th class="px-4 py-3 text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">System Type</th>
                        <th class="px-4 py-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.65rem; letter-spacing: 1px;">Inquiry Date</th>
                        <th class="px-4 py-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.65rem; letter-spacing: 1px;">Status</th>
                        <th class="px-4 py-3 text-uppercase text-secondary fw-bold text-end" style="font-size: 0.65rem; letter-spacing: 1px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Row 1 -->
                    <tr class="hover-bg-primary-subtle transition-all">
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar shadow-sm" style="background: linear-gradient(135deg, #1e73be, #60a5fa);">JS</div>
                                <div>
                                    <div class="fw-bold text-body">Julianne Smith</div>
                                    <div class="small text-secondary">j.smith@corporate.com</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge bg-light text-secondary border fw-bold p-2 text-dark">Casement System</span>
                        </td>
                        <td class="px-4 py-3 text-center text-secondary fw-medium">Oct 24, 2023</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-success rounded-circle" style="width: 6px; height: 6px;"></span>
                                ACTIVE INQUIRY
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button class="btn btn-sm btn-link text-secondary"><span class="material-symbols-outlined">more_horiz</span></button>
                        </td>
                    </tr>
                    
                    <!-- Row 2 -->
                    <tr class="hover-bg-primary-subtle transition-all">
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar shadow-sm" style="background: linear-gradient(135deg, #4caf50, #34d399);">MK</div>
                                <div>
                                    <div class="fw-bold text-body">Marcus Knight</div>
                                    <div class="small text-secondary">marcus.k@arch-labs.io</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge bg-light text-secondary border fw-bold p-2 text-dark">Villa Window Series</span>
                        </td>
                        <td class="px-4 py-3 text-center text-secondary fw-medium">Oct 23, 2023</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-primary rounded-circle" style="width: 6px; height: 6px;"></span>
                                PENDING REVIEW
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button class="btn btn-sm btn-link text-secondary"><span class="material-symbols-outlined">more_horiz</span></button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover-bg-primary-subtle transition-all">
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar shadow-sm" style="background: linear-gradient(135deg, #94a3b8, #475569);">DL</div>
                                <div>
                                    <div class="fw-bold text-body">Diana Loft</div>
                                    <div class="small text-secondary">diana.l@luxuryapartments.com</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge bg-light text-secondary border fw-bold p-2 text-dark">Sliding System</span>
                        </td>
                        <td class="px-4 py-3 text-center text-secondary fw-medium">Oct 22, 2023</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bg-warning rounded-circle" style="width: 6px; height: 6px;"></span>
                                ON HOLD
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button class="btn btn-sm btn-link text-secondary"><span class="material-symbols-outlined">more_horiz</span></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-top bg-light bg-opacity-25 d-flex align-items-center justify-content-between">
            <small class="fw-bold text-secondary">Showing 3 of 124 leads</small>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary bg-white border"><span class="material-symbols-outlined fs-6 align-middle">chevron_left</span></button>
                <button class="btn btn-sm btn-outline-secondary bg-light text-primary border fw-bold">1</button>
                <button class="btn btn-sm btn-outline-secondary bg-white border fw-bold">2</button>
                <button class="btn btn-sm btn-outline-secondary bg-white border"><span class="material-symbols-outlined fs-6 align-middle">chevron_right</span></button>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Reusable Glass Modal: New Inquiry -->
    <div class="modal fade modal-glass" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 ps-4 pe-4 pt-4">
                    <div>
                        <h5 class="modal-title fw-bold" id="inquiryModalLabel">New Inquiry Entry</h5>
                        <p class="text-secondary small mb-0">Enter details for the new potential lead</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Customer Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-secondary"><span class="material-symbols-outlined fs-5">person</span></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="e.g. John Doe">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-secondary"><span class="material-symbols-outlined fs-5">mail</span></span>
                                <input type="email" class="form-control border-start-0 ps-0" placeholder="name@example.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">System Interest</label>
                            <select class="form-select">
                                <option selected>Choose system type...</option>
                                <option value="1">Casement Windows</option>
                                <option value="2">Sliding Systems</option>
                                <option value="3">Villa Windows</option>
                                <option value="4">Tilt & Turn</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;">Project Details</label>
                            <textarea class="form-control" rows="3" placeholder="Additional requirements or notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light text-secondary fw-bold rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn text-white fw-bold rounded-3 px-4 shadow-sm" style="background: var(--primary-color);">Create Inquiry</button>
                </div>
            </div>
        </div>
    </div>
@endpush
