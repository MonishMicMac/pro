@extends('layouts.app')

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 font-[Inter]">

        {{-- ================= MEASUREMENT TABLE ================= --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">
                    Measurement Details
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Design</th>
                            <th class="px-4 py-3 text-left">Area</th>
                            <th class="px-4 py-3">Width</th>
                            <th class="px-4 py-3">Height</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Sqft</th>
                            <th class="px-4 py-3">Color</th>
                            <th class="px-4 py-3">Note</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @php $totalSqft = 0; @endphp
                        @foreach ($measurements as $m)
                            @php $totalSqft += $m->sqft; @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">{{ $m->product }}</td>
                                <td class="px-4 py-3">{{ $m->design_code }}</td>
                                <td class="px-4 py-3">{{ $m->area }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->width_val }} {{ $m->width_unit }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->height_val }} {{ $m->height_unit }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->qty }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ $m->sqft }}</td>
                                <td class="px-4 py-3">{{ $m->color }}</td>
                                <td class="px-4 py-3">{{ $m->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- TOTAL SQFT BAR --}}
            <div class="flex justify-end px-6 py-4 bg-slate-50 border-t">
                <div class="text-sm font-semibold text-slate-700">
                    Total Sqft :
                    <span id="totalSqft" class="text-blue-600">{{ number_format($totalSqft, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- ================= QUOTATION FORM ================= --}}
        <form id="fabricationForm" enctype="multipart/form-data"
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">

            @php
                $hasInitial = !empty($fabricatorRequest?->fabrication_pdf);
                $hasFinal = !empty($lead?->final_quotation_pdf);
            @endphp

            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
            <input type="hidden" name="fabricator_id" value="{{ auth('fabricator')->id() }}">

            <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">
                {{ !$hasInitial ? 'Upload Initial Quotation' : (!$hasFinal ? 'Upload Final Quotation' : 'Quotation Submitted') }}
            </h3>

            {{-- ================= INITIAL QUOTATION (READ ONLY) ================= --}}
            @if ($hasInitial)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 space-y-4">
                    <h4 class="text-sm font-semibold text-emerald-800 uppercase">
                        Initial Quotation
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-600">Rate per Sqft (₹)</label>
                            <input type="number" value="{{ $fabricatorRequest->rate_per_sqft }}" readonly
                                class="w-full rounded-xl border-slate-300 bg-slate-100">
                        </div>

                        <div>
                            <label class="text-xs text-slate-600">Total Amount (₹)</label>
                            <input type="number" value="{{ $fabricatorRequest->total_value }}" readonly
                                class="w-full rounded-xl border-slate-300 bg-slate-100">
                        </div>
                    </div>

                    <div class="flex justify-between items-center bg-white border rounded-lg p-4">
                        <div>
                            <p class="text-sm font-medium text-emerald-700">Initial Quotation PDF</p>
                            <p class="text-xs text-emerald-600">Uploaded by you</p>
                        </div>
                        <a href="{{ asset('storage/' . $fabricatorRequest->fabrication_pdf) }}" target="_blank"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm">
                            View PDF
                        </a>
                    </div>
                </div>
            @endif

            {{-- ================= FINAL QUOTATION (EDITABLE) ================= --}}
            @if (!$hasFinal)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 space-y-4">
                    <h4 class="text-sm font-semibold text-blue-800 uppercase">
                        Final Quotation
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-600">Final Rate per Sqft (₹)</label>
                            <input type="number" step="0.01" name="rate_per_sqft"
                                value="{{ $lead->final_rate_per_sqft ?? '' }}" required
                                class="w-full rounded-xl border-slate-300">
                        </div>

                        <div>
                            <label class="text-xs text-slate-600">Final Total Amount (₹)</label>
                            <input type="number" step="0.01" name="total_value" value="{{ $lead->total_value ?? '' }}"
                                required class="w-full rounded-xl border-slate-300">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-slate-600 block mb-1">
                            Upload Final Quotation PDF
                        </label>
                        <input type="file" name="pdf_file" accept="application/pdf" required
                            class="w-full rounded-xl border-slate-300
                    file:bg-blue-50 file:text-blue-700
                    file:border-0 file:px-4 file:py-2 file:rounded-lg">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl text-sm font-medium">
                            Upload Final Quotation
                        </button>
                    </div>
                </div>
            @else
                {{-- ================= FINAL LOCKED ================= --}}
                <div class="bg-slate-100 border rounded-xl p-4 text-sm text-slate-600 text-center">
                    Final quotation already submitted. No further changes allowed.
                </div>
            @endif

            <div id="uploadMsg" class="text-sm"></div>
        </form>


    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $('#fabricationForm').submit(function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('fabricator.upload.pdf') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(res) {
                    $('#uploadMsg').html(`<span class="text-green-600">${res.message}</span>`);
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.reason || 'Upload failed';
                    $('#uploadMsg').html(`<span class="text-red-600">${msg}</span>`);
                }
            });
        });
    </script>
@endsection
