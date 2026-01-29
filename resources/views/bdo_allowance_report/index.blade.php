@extends('layouts.app')
@section('content')

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel { 
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm transition-all; 
        }
        .form-select-custom { 
            @apply pl-9 pr-3 py-2 bg-transparent border-none text-xs font-bold text-slate-700 outline-none w-48 cursor-pointer focus:ring-0; 
        }
    }
    body { font-family: 'Inter', sans-serif; }
</style>

<div class="flex-1 overflow-y-auto p-5 bg-[#f8fafc] pb-24 font-inter">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Reports</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">BDO Allowance</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daily Allowance Report</h1>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row gap-3 bg-white p-1.5 rounded-2xl shadow-sm border border-slate-200">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">person</span>
                <select name="user_id" class="form-select-custom" onchange="this.form.submit()">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $u->id == $user->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-px bg-slate-100 hidden sm:block"></div>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">calendar_month</span>
                <input type="date" name="date" value="{{ $date }}" 
                       class="pl-9 pr-3 py-2 bg-transparent border-none text-xs font-bold text-slate-700 outline-none cursor-pointer focus:ring-0"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    @if(!$attendance)
        <div class="flex flex-col items-center justify-center py-20 bg-white/60 rounded-[1.5rem] border border-dashed border-slate-300 text-center">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-3xl text-slate-300">event_busy</span>
            </div>
            <h3 class="text-sm font-bold text-slate-600">No Attendance Record</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-xs">
                <span class="font-bold text-slate-800">{{ $user->name }}</span> has no punch-in record for this date.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            
            <div class="bg-white p-5 rounded-[1.25rem] shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-5"><span class="material-symbols-outlined text-6xl">route</span></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Travel</p>
                <div class="flex items-baseline gap-1">
                    <h2 class="text-2xl font-black text-slate-800">{{ $summary['total_travel_km'] }}</h2>
                    <span class="text-xs font-bold text-slate-400">km</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400">Earnings</span>
                    <span class="text-xs font-bold text-green-600">+ ₹{{ $summary['total_travel_allowance'] }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-[1.25rem] shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-5"><span class="material-symbols-outlined text-6xl">restaurant</span></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Food Allowance</p>
                <div class="flex items-baseline gap-1">
                    <h2 class="text-2xl font-black text-slate-800">₹{{ $summary['total_food_allowance'] }}</h2>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400">Type</span>
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded">{{ $summary['food_allowance_type'] }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-[1.25rem] shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-5"><span class="material-symbols-outlined text-6xl">receipt_long</span></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Other Exp.</p>
                <div class="flex items-baseline gap-1">
                    <h2 class="text-2xl font-black text-slate-800">₹{{ $summary['other_expenses_total'] }}</h2>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400">Grand Total</span>
                    <span class="text-xs font-bold text-slate-600">₹{{ $summary['grand_total_allowance'] }}</span>
                </div>
            </div>

            <div class="bg-slate-900 p-5 rounded-[1.25rem] shadow-lg shadow-slate-900/20 relative overflow-hidden flex flex-col justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Payable</p>
                    <h2 class="text-3xl font-black text-white">₹{{ $summary['grand_total_allowance'] }}</h2>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <div class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wide">Approved</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white p-5 rounded-[1.5rem] border border-slate-100 shadow-sm">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide mb-4">Punch Details</h3>
                    <div class="space-y-4 relative">
                        <div class="absolute left-[19px] top-8 bottom-4 w-0.5 bg-slate-100"></div>
                        <div class="relative flex gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center z-10"><span class="material-symbols-outlined text-[20px]">login</span></div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Punch In</p>
                                <p class="text-sm font-bold text-slate-800">{{ $summary['punch_in'] ? \Carbon\Carbon::parse($summary['punch_in'])->format('h:i A') : 'N/A' }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">KM: {{ $summary['odometer_start'] }}</p>
                            </div>
                        </div>
                        <div class="relative flex gap-4">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center z-10"><span class="material-symbols-outlined text-[20px]">logout</span></div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Punch Out</p>
                                <p class="text-sm font-bold text-slate-800">{{ $summary['punch_out'] && $summary['punch_out'] !== 'N/A' ? \Carbon\Carbon::parse($summary['punch_out'])->format('h:i A') : 'Active' }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">KM: {{ $summary['odometer_end'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Visit Activity Log</h3>
                    <div class="flex gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 rounded text-slate-500">{{ count($planned) }} Planned</span>
                        <span class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 rounded text-slate-500">{{ count($unplanned) }} Unplanned</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-3">Site / Client</th>
                                <th class="px-6 py-3">Arrival</th>
                                <th class="px-6 py-3">Depart</th>
                                <th class="px-6 py-3 text-right">Distance</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-bold text-slate-600 divide-y divide-slate-50">
                            @foreach(array_merge($planned, $unplanned) as $visit)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex flex-col">
                                        <span class="text-slate-800">{{ $visit->site_name ?? $visit->account_name ?? $visit->fabricator_name ?? 'Unknown' }}</span>
                                        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wide">{{ $visit->visit_type_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                        {{ $visit->intime ? \Carbon\Carbon::parse($visit->intime)->format('h:i A') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-400"></div>
                                        {{ $visit->outtime ? \Carbon\Carbon::parse($visit->outtime)->format('h:i A') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right"><span class="bg-slate-100 text-slate-600 px-2 py-1 rounded">{{ $visit->travel_km_to_site }} km</span></td>
                            </tr>
                            @endforeach
                            @if(count($planned) + count($unplanned) == 0)
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 italic font-medium">No visits recorded.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection