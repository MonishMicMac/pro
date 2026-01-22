@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('content')
    <div class="bg-white rounded-xl p-6">

        <h3 class="font-bold mb-4">Measurement Details</h3>

        <table class="w-full text-sm border">
            <thead class="bg-slate-100">
                <tr>
                    <th>Product</th>
                    <th>Design</th>
                    <th>Width</th>
                    <th>Height</th>
                    <th>Qty</th>
                    <th>Sqft</th>
                    <th>Color</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($measurements as $m)
                    <tr class="border-b">
                        <td>{{ $m->product }}</td>
                        <td>{{ $m->design_code }}</td>
                        <td>{{ $m->width_val }} {{ $m->width_unit }}</td>
                        <td>{{ $m->height_val }} {{ $m->height_unit }}</td>
                        <td>{{ $m->qty }}</td>
                        <td>{{ $m->sqft }}</td>
                        <td>{{ $m->color }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    <form id="fabricationForm" enctype="multipart/form-data" class="mt-6">

        <input type="hidden" name="lead_id" value="{{ $lead->id }}">
        <input type="hidden" name="fabricator_id" value="{{ auth('fabricator')->id() }}">

        <!-- RATE -->
        <div class="mb-4">
            <label class="block text-xs font-bold mb-1">Rate / Sqft</label>
            <input type="number" step="0.01" name="rate_per_sqft" required class="w-full border rounded-lg px-3 py-2">
        </div>

        <!-- PDF -->
        <div class="mb-4">
            <label class="block text-xs font-bold mb-1">Upload Quotation PDF</label>
            <input type="file" name="pdf_file" accept="application/pdf" required
                class="w-full border rounded-lg px-3 py-2">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">
            Upload
        </button>

    </form>

    <div id="uploadMsg" class="mt-3 text-sm"></div>
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
                    $('#uploadMsg').html(
                        `<span class="text-green-600">${res.message}</span>`
                    );
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.reason || 'Upload failed';
                    $('#uploadMsg').html(
                        `<span class="text-red-600">${msg}</span>`
                    );
                }
            });
        });
    </script>
@endsection
