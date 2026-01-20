@extends('layouts.app')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>

<div class="h-screen flex flex-col bg-[#f8fafc] p-5">
    <div class="mb-5">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Lead History: {{ $lead->name }}</h1>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Phone: {{ $lead->phone_number }}</p>
    </div>

    <div class="flex-1 overflow-hidden glass-panel rounded-[1.5rem] flex flex-col border border-white/40 bg-white/70 backdrop-blur-xl shadow-xl">
        <div class="overflow-x-auto overflow-y-auto custom-scrollbar p-4">
            <table class="w-full text-left border-separate border-spacing-y-2">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">
                        <th class="px-4 py-3 rounded-l-xl">Modified Date</th>
                        <th class="px-4 py-3">Updated By</th>
                        <th class="px-4 py-3">Stage</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Colour</th>
                        <th class="px-4 py-3">Sqft</th>
                        <th class="px-4 py-3">Bld Status</th>
                        <th class="px-4 py-3">Bld Type</th>
                        <th class="px-4 py-3">Assigned To</th>
                        <th class="px-4 py-3">Zone</th>
                        <th class="px-4 py-3 rounded-r-xl">Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] font-bold text-slate-700">
                    @foreach($history as $log)
                    <tr class="bg-white/50 hover:bg-white transition-colors group shadow-sm">
                        <td class="px-4 py-3 rounded-l-xl border-y border-l border-slate-100">{{ $log->created_at->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 text-blue-600">{{ $log->user->name ?? 'System' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100"><span class="px-2 py-0.5 bg-slate-100 rounded text-slate-500">{{ $leadStages[$log->stage] ?? $log->stage }}</span></td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $customerTypes[$log->customer_type] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $log->colour ?: '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $log->total_order_sqft ?: '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $buildingStatuses[$log->building_status] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $buildingTypes[$log->building_type] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $users[$log->assigned_to] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100">{{ $zones[$log->zone] ?? '-' }}</td>
                        <td class="px-4 py-3 rounded-r-xl border-y border-r border-slate-100 italic text-slate-500">{{ $log->remarks ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
@endsection
