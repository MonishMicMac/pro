@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700" rel="stylesheet"/>

<style>
    /* Base Font */
    body { font-family: 'Inter', sans-serif; }

    /* Glassmorphism Styles */
    .glass-panel { background: rgba(255, 255, 255, 0.70); backdrop-filter: blur(12px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); }
    
    /* Table Styles to match reference */
    table { border-collapse: separate !important; border-spacing: 0 0.5rem !important; width: 100%; }
    table thead th { padding: 12px 16px; }
    table tbody tr { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); transition: all 0.2s ease; }
    table tbody tr:hover { background: rgba(255, 255, 255, 0.9); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    table tbody tr td { padding: 16px; vertical-align: middle; border-top: 1px solid rgba(255,255,255,0.5); border-bottom: 1px solid rgba(255,255,255,0.5); }
    table tbody tr td:first-child { border-left: 1px solid rgba(255,255,255,0.5); border-radius: 12px 0 0 12px; }
    table tbody tr td:last-child { border-right: 1px solid rgba(255,255,255,0.5); border-radius: 0 12px 12px 0; }

    /* Sticky First Column for Matrix Table */
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: rgba(255, 255, 255, 0.95); /* Slightly more opaque for readability */
        backdrop-filter: blur(4px);
    }
</style>

<div class="relative flex-1 p-6 space-y-4 pb-24 bg-[#f8fafc] min-h-screen">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span> <span class="material-symbols-outlined text-[12px]">chevron_right</span> <span class="text-blue-600">Telecaller Performance</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Consolidated Report</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Breakdown of lead stages per telecaller.</p>
        </div>
    </div>

    {{-- CARD 1: FILTERS --}}
    <div class="glass-panel rounded-[1.5rem] p-6 mb-6">
        <form action="{{ route('telecaller.report') }}" method="GET">
            <div class="flex flex-wrap gap-4 items-end">
                
                {{-- Telecaller Filter --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Telecaller</label>
                    <select name="telecaller_id" class="w-48 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <option value="">All Telecallers</option>
                        @foreach($allTelecallers as $tc)
                            <option value="{{ $tc->id }}" {{ request('telecaller_id') == $tc->id ? 'selected' : '' }}>
                                {{ $tc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date Range</label>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase pl-2">From</span>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="py-2 bg-transparent border-0 text-xs font-bold text-slate-600 outline-none focus:ring-0">
                        </div>
                        <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase pl-2">To</span>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="py-2 bg-transparent border-0 text-xs font-bold text-slate-600 outline-none focus:ring-0">
                        </div>
                    </div>
                </div>

                {{-- Filter Button --}}
                <button type="submit" class="mb-[2px] px-6 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply Filters
                </button>
                
                {{-- Clear Filter --}}
                <a href="{{ route('telecaller.report') }}" class="mb-[2px] px-4 py-2.5 bg-white text-slate-500 border border-slate-200 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span> Reset
                </a>
            </div>
        </form>
    </div>

    {{-- CARD 2: DATA TABLE --}}
    <div class="glass-panel rounded-[1.5rem] overflow-hidden p-1">
        
        {{-- Table Container --}}
        <div class="overflow-x-auto px-4 pb-4">
            <table class="w-full text-center">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-200/60">
                        <th class="text-left sticky-col">Telecaller Name</th>
                        <th>Total</th>
                        @foreach($stages as $id => $name)
                            <th class="whitespace-nowrap">{{ $name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="text-xs font-bold text-slate-700">
                    @forelse($reportData as $data)
                        <tr>
                            <td class="text-left sticky-col text-slate-900 font-extrabold">
                                {{ $data['name'] }}
                            </td>

                            <td>
                                <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg shadow-sm border border-blue-100">
                                    {{ $data['total_assigned'] }}
                                </span>
                            </td>

                            @foreach($stages as $stageId => $stageName)
                                @php
                                    $count = $data['counts'][$stageId] ?? 0;
                                    $hasCount = $count > 0;
                                @endphp
                                <td>
                                    @if($hasCount)
                                        <span class="text-slate-800">{{ $count }}</span>
                                    @else
                                        <span class="text-slate-300 font-normal">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($stages) + 2 }}" class="text-center py-10">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-[48px] mb-2 opacity-20">dataset</span>
                                    <p class="text-xs font-bold uppercase tracking-widest">No Data Found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                {{-- Footer Totals --}}
                @if(count($reportData) > 0)
                <tfoot class="text-xs font-black text-slate-800 uppercase bg-slate-50/50">
                    <tr>
                        <td class="text-left p-4 rounded-l-xl">TOTAL</td>
                        <td class="p-4">{{ collect($reportData)->sum('total_assigned') }}</td>
                        @foreach($stages as $stageId => $stageName)
                            <td class="p-4 {{ $loop->last ? 'rounded-r-xl' : '' }}">
                                {{ collect($reportData)->sum(fn($item) => $item['counts'][$stageId] ?? 0) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection