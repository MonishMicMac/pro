@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('content')
    <div class="flex-1 min-h-screen p-6 space-y-8 bg-[#f8fafc] pb-24">

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-5">
                <a href="{{ route('masters.fabricators.index') }}"
                    class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white text-slate-400 hover:text-blue-600 shadow-sm hover:shadow-md transition-all border border-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Fabricator Details</h1>
                    <p class="text-sm text-slate-500 font-medium">Viewing master record for <span
                            class="text-blue-600">{{ $fabricator->shop_name }}</span></p>
                </div>
            </div>

            <div class="hidden md:block">
                <span
                    class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold tracking-widest
        {{ $fabricator->is_existing == 1 ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">

                    {{ $fabricator->is_existing == 1 ? '● EXISTING' : '● NEW' }}
                </span>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">General Information</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-8 gap-x-4">
                        @php
                            $info = [
                                'Shop Name' => $fabricator->shop_name,
                                'Mobile Number' => $fabricator->mobile,
                                'Email Address' => $fabricator->email,
                                'Division' => $fabricator->division,
                                'Category' => $fabricator->category,
                                'Segment' => $fabricator->segment,
                                'Sub Segment' => $fabricator->sub_segment,
                            ];
                        @endphp

                        @foreach ($info as $label => $value)
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tight">{{ $label }}
                                </p>
                                <p class="text-sm font-semibold text-slate-700 break-words">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1.5 h-6 bg-indigo-600 rounded-full"></div>
                        <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">Location & Logistics</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-8 gap-x-4">
                        @php
                            $location = [
                                'State' => $fabricator->state->name ?? null,
                                'District' => $fabricator->district->district_name ?? null,
                                'City' => $fabricator->city->city_name ?? null,
                                'Pincode' => $fabricator->pincode->pincode ?? null,
                            ];
                        @endphp

                        @foreach ($location as $label => $value)
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tight">{{ $label }}
                                </p>
                                <p class="text-sm font-semibold text-slate-700">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach

                        <div class="md:col-span-3 space-y-1 pt-4 border-t border-slate-50">
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Primary Address</p>
                            <p class="text-sm font-semibold text-slate-700 leading-relaxed">
                                {{ $fabricator->address ?? '—' }}</p>
                        </div>

                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-50">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Shipping Address</p>
                                <p class="text-sm font-semibold text-slate-700">{{ $fabricator->shipping_address ?? '—' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Billing Address</p>
                                <p class="text-sm font-semibold text-slate-700">{{ $fabricator->billing_address ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">Bank Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-8 gap-x-4">
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Bank Name</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $fabricator->bank_name ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase">IFSC Code</p>
                            <p class="text-sm font-mono font-bold text-blue-600">{{ $fabricator->ifsc_code ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Account Number</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $fabricator->account_number ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">
                            Fabricator Requests
                        </h3>

                        <!-- FILTER -->
                        <div class="flex gap-2">
                            <button class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-slate-100"
                                data-status="all">All</button>

                            <button class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700"
                                data-status="0">Pending</button>

                            <button
                                class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700"
                                data-status="1">Completed</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-3">Lead</th>
                                    <th>Approx Sq.ft</th>
                                    <th>Status</th>
                                    <th>Rate</th>
                                    <th>PDF</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody id="requestTable">
                                @forelse($fabricator->requests as $req)
                                    <tr class="border-b request-row" data-status="{{ $req->status }}">

                                        <td class="py-3 font-semibold">
                                            #{{ $req->lead_id }}
                                        </td>

                                        <td>{{ $req->approx_sqft }}</td>

                                        <td>
                                            <span
                                                class="px-3 py-1 rounded-full text-xs font-bold
                        {{ $req->status == 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">

                                                {{ $req->status == 0 ? 'Pending' : 'Completed' }}
                                            </span>
                                        </td>

                                        <td>
                                            ₹ {{ $req->rate_per_sqft ?? '-' }}
                                        </td>

                                        <td>
                                            @if ($req->fabrication_pdf)
                                                <a href="{{ asset('storage/' . $req->fabrication_pdf) }}" target="_blank"
                                                    class="text-red-500 font-bold">
                                                    PDF
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="text-slate-500">
                                            {{ $req->created_at->format('d M Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-slate-400 text-sm">
                                            No requests found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="space-y-8">

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                    <h3 class="text-base font-bold text-slate-800 uppercase mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Contact Info
                    </h3>

                    <div class="space-y-5">
                        @php
                            $contact = [
                                'Contact Person' => $fabricator->contact_person,
                                'Sales Person' => $fabricator->sales_person,
                                'Contact Type' => $fabricator->contact_type,
                                'GST' => $fabricator->gst,
                                'Credit Terms' => $fabricator->payment_credit_terms,
                                'Credit Limit' => $fabricator->credit_limit,
                            ];
                        @endphp

                        @foreach ($contact as $label => $value)
                            <div class="flex justify-between items-start border-b border-slate-50 pb-3 last:border-0">
                                <p class="text-[11px] font-bold text-slate-400 uppercase leading-relaxed">
                                    {{ $label }}</p>
                                <p class="text-xs font-bold text-slate-700 text-right">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- <div class="bg-slate-900 rounded-[2rem] p-8 text-white shadow-xl shadow-slate-200">
                    <div class="space-y-6">
                        <div class="text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Registration
                                Status</p>
                            <div
                                class="inline-block px-6 py-2 rounded-xl text-xs font-black tracking-widest {{ $fabricator->status == 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }} border border-white/5">
                                {{ $fabricator->status == 0 ? 'VERIFIED' : 'DECLINED' }}
                            </div>
                        </div>

                        <div class="h-px bg-white/10"></div>

                        <div class="text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer Type
                            </p>
                            <p class="text-lg font-bold text-blue-400 italic">
                                {{ $fabricator->is_existing == 1 ? 'Existing Client' : 'New Prospect' }}
                            </p>
                        </div>
                    </div>
                </div> --}}

            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {

                let status = this.dataset.status;

                document.querySelectorAll('.request-row')
                    .forEach(row => {

                        if (status === 'all') {
                            row.classList.remove('hidden');
                        } else {
                            row.dataset.status === status ?
                                row.classList.remove('hidden') :
                                row.classList.add('hidden');
                        }
                    });

                // active style
                document.querySelectorAll('.filter-btn')
                    .forEach(b => b.classList.remove('ring', 'ring-blue-400'));

                this.classList.add('ring', 'ring-blue-400');
            });
        });
    </script>
@endsection
