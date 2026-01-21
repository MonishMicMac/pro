@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('content')
    <div class="max-w-7xl mx-auto p-6">

        <h2 class="text-xl font-bold mb-4">
            Assignments List
        </h2>

        <a href="/fabricator/dashboard" class="text-blue-600 underline">
            ← Back
        </a>

        @forelse($assignments as $row)
            <div class="bg-white p-5 shadow rounded mb-5">

                <p><b>Customer:</b>
                    {{ $row->lead->name ?? '-' }}
                </p>

                <p><b>Sales Person:</b>
                    {{ $row->lead->assignedUser->name ?? '-' }}
                </p>

                <p><b>Status:</b>
                    {{ $row->status }}
                </p>

                <hr class="my-3">

                <h4 class="font-bold">Measurements</h4>

                <table class="w-full border mt-2">
                    <tr class="bg-gray-100">
                        <th>Product</th>
                        <th>Width</th>
                        <th>Height</th>
                        <th>Qty</th>
                    </tr>

                    @foreach ($row->measurements as $m)
                        <tr>
                            <td>{{ $m->product }}</td>
                            <td>{{ $m->width_val }}</td>
                            <td>{{ $m->height_val }}</td>
                            <td>{{ $m->qty }}</td>
                        </tr>
                    @endforeach
                </table>

            </div>

        @empty
            <p>No records found</p>
        @endforelse

    </div>
@endsection
