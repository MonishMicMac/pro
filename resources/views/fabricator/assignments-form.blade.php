@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('content')
    <div class="max-w-xl mx-auto p-6 bg-white shadow rounded">

        <h2 class="text-xl font-bold mb-4">
            Search Fabricator Assignments
        </h2>

        <form method="POST" action="{{ route('fabricator.assignments') }}">
            @csrf

            <label class="block mb-2 font-medium">
                Fabricator ID
            </label>

            <input type="number" name="fabricator_id" class="w-full border p-2 rounded" placeholder="Enter Fabricator ID"
                required>

            @error('fabricator_id')
                <p class="text-red-500 text-sm mt-1">
                    {{ $message }}
                </p>
            @enderror

            <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
                Get Assignments
            </button>

        </form>
    </div>
@endsection
