@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('title', 'Fabricator Dashboard')

@section('content')

    <a href="{{ route('fabricator.profile') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">
        View Profile
    </a>
    <div class="max-w-6xl mx-auto mt-10 space-y-6">

        <h2 class="text-xl font-bold">
            Welcome, {{ $fabricator->shop_name }}
        </h2>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- TOTAL -->
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm">Total Requests</p>
                <p class="text-3xl font-black text-blue-600">
                    {{ $total }}
                </p>
            </div>

            <!-- PENDING -->
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                <p class="text-gray-500 text-sm">Pending Requests</p>
                <p class="text-3xl font-black text-yellow-600">
                    {{ $pending }}
                </p>
            </div>

            <!-- COMPLETED -->
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                <p class="text-gray-500 text-sm">Completed Requests</p>
                <p class="text-3xl font-black text-green-600">
                    {{ $completed }}
                </p>
            </div>

        </div>





    </div>

    </div>

@endsection
