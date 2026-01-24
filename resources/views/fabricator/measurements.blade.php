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
                            <th class="px-4 py-3">Width</th>
                            <th class="px-4 py-3">Height</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Sqft</th>
                            <th class="px-4 py-3">Color</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @php $totalSqft = 0; @endphp
                        @foreach ($measurements as $m)
                            @php $totalSqft += $m->sqft; @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">{{ $m->product }}</td>
                                <td class="px-4 py-3">{{ $m->design_code }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->width_val }} {{ $m->width_unit }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->height_val }} {{ $m->height_unit }}</td>
                                <td class="px-4 py-3 text-center">{{ $m->qty }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ $m->sqft }}</td>
                                <td class="px-4 py-3">{{ $m->color }}</td>
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
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">

            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
            <input type="hidden" name="fabricator_id" value="{{ auth('fabricator')->id() }}">

            <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">
                Upload Quotation
            </h3>

            {{-- RATE INPUT --}}
            <div class="bg-slate-50 border rounded-xl p-4 flex items-center justify-between gap-4">
                <span class="text-sm font-medium text-slate-600 whitespace-nowrap">
                    Rate per Sqft (₹)
                </span>
                <input type="number" step="0.01" name="rate_per_sqft" id="ratePerSqft" required
                    value="{{ $fabricatorRequest->rate_per_sqft ?? '' }}"
                    {{ isset($fabricatorRequest?->fabrication_pdf) ? 'readonly' : '' }}
                    class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Enter rate per sqft">
            </div>

            {{-- TOTAL RATE BAR --}}
            <div class="bg-slate-50 border rounded-xl p-4 flex items-center justify-between gap-4">
                <span class="text-sm font-medium text-slate-600 whitespace-nowrap">
                    Total Quotation Amount (₹)
                </span>

                <input type="number" step="0.01" name="total_quotation_amount" id="totalAmount" required
                    value="{{ $fabricatorRequest->total_quotation_amount ?? '' }}"
                    {{ isset($fabricatorRequest?->fabrication_pdf) ? 'readonly' : '' }}
                    class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Enter total quotation amount" />
            </div>


            {{-- PDF UPLOAD --}}
            @if (isset($fabricatorRequest?->fabrication_pdf))
                {{-- SHOW EXISTING PDF --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-700">
                            Quotation Already Uploaded
                        </p>
                        <p class="text-xs text-emerald-600">
                            You can view the previously uploaded quotation
                        </p>
                    </div>

                    <a href="{{ asset('storage/' . $fabricatorRequest->fabrication_pdf) }}" target="_blank"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">
                        Open PDF
                    </a>
                </div>
            @else
                {{-- UPLOAD PDF --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Upload Quotation PDF
                    </label>
                    <input type="file" name="pdf_file" accept="application/pdf" required
                        class="w-full rounded-xl border-slate-300 file:bg-blue-50 file:text-blue-700
                   file:border-0 file:px-4 file:py-2 file:rounded-lg">
                </div>
            @endif


            @if (!isset($fabricatorRequest?->fabrication_pdf))
                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl text-sm font-medium transition">
                        Upload Quotation
                    </button>
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
