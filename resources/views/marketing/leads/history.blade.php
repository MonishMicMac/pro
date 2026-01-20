@extends('layouts.app')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>

<div class="h-screen flex flex-col bg-[#f8fafc] p-5 overflow-hidden">
    <div class="mb-5 flex-none">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Lead History: {{ $lead->name }}</h1>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Phone: {{ $lead->phone_number }}</p>
    </div>

    {{-- Added overflow-x-auto to the container --}}
    <div class="overflow-hidden glass-panel rounded-[1.5rem] flex flex-col border border-white/40 bg-white/70 backdrop-blur-xl shadow-xl">
        <div class="flex-1 overflow-auto custom-scrollbar p-4">
            {{-- Set min-width to table to force scroll --}}
            <table class="min-w-[1500px] w-full text-left border-separate border-spacing-y-2">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">
                        <th class="px-4 py-3 rounded-l-xl w-[150px]">Modified Date</th>
                        <th class="px-4 py-3 w-[120px]">Updated By</th>
                        <th class="px-4 py-3 w-[120px]">Stage</th>
                        <th class="px-4 py-3 w-[150px]">Reason</th>
                        <th class="px-4 py-3 w-[160px]">Future Follow Up</th>
                        <th class="px-4 py-3 w-[160px]">Potential Follow Up</th>
                        <th class="px-4 py-3 w-[100px]">Type</th>
                        <th class="px-4 py-3 w-[100px]">Colour</th>
                        <th class="px-4 py-3 w-[80px]">Sqft</th>
                        <th class="px-4 py-3 w-[120px]">Bld Status</th>
                        <th class="px-4 py-3 w-[120px]">Bld Type</th>
                        <th class="px-4 py-3 w-[120px]">Assigned To</th>
                        <th class="px-4 py-3 w-[100px]">Zone</th>
                        <th class="px-4 py-3 rounded-r-xl min-w-[250px]">Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] font-bold text-slate-700">
                    @foreach($history as $log)
                    <tr class="bg-white/50 hover:bg-white transition-colors group shadow-sm">
                        <td class="px-4 py-3 rounded-l-xl border-y border-l border-slate-100 whitespace-nowrap">{{ $log->created_at->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 text-blue-600 whitespace-nowrap">{{ $log->user->name ?? 'System' }}</td>

                        {{-- Stage Column --}}
                        <td class="px-4 py-3 border-y border-slate-100">
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-500 whitespace-nowrap">
                                {{ $leadStages[$log->stage] ?? $log->stage }}
                            </span>
                        </td>

                        {{-- Reason Column --}}
                        <td class="px-4 py-3 border-y border-slate-100">
                             @if($log->disqualified_reason)
                                <span class="text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded">{{ $log->disqualified_reason }}</span>
                             @elseif($log->rnr_reason)
                                <span class="text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded">{{ $log->rnr_reason }}</span>
                             @else
                                <span class="text-slate-300">-</span>
                             @endif
                        </td>

                        {{-- Future Follow Up Column --}}
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">
                            @if($log->future_follow_up_date)
                                {{ date('d-m-Y', strtotime($log->future_follow_up_date)) }}
                                @if($log->future_follow_up_time)
                                    <span class="text-slate-400 ml-1 text-[10px] tracking-tighter">
                                        {{ date('h:i A', strtotime($log->future_follow_up_time)) }}
                                    </span>
                                @endif
                            @else
                                -
                            @endif
                        </td>

                        {{-- Potential Follow Up Column --}}
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">
                            @if($log->potential_follow_up_date)
                                {{ date('d-m-Y', strtotime($log->potential_follow_up_date)) }}
                                @if($log->potential_follow_up_time)
                                    <span class="text-slate-400 ml-1 text-[10px] tracking-tighter">
                                        {{ date('h:i A', strtotime($log->potential_follow_up_time)) }}
                                    </span>
                                @endif
                            @else
                                -
                            @endif
                        </td>

                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $customerTypes[$log->customer_type] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $log->colour ?: '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $log->total_order_sqft ?: '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $buildingStatuses[$log->building_status] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $buildingTypes[$log->building_type] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $users[$log->assigned_to] ?? '-' }}</td>
                        <td class="px-4 py-3 border-y border-slate-100 whitespace-nowrap">{{ $zones[$log->zone] ?? '-' }}</td>
                        <td class="px-4 py-3 rounded-r-xl border-y border-r border-slate-100 italic text-slate-500 min-w-[250px]">{{ $log->remarks ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); }
    /* Horizontal scrollbar styling */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection
