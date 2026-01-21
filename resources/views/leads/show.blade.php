@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            @apply bg-white/75 backdrop-blur-xl border border-white/40 shadow-sm;
        }
    }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 pb-20 bg-[#f8fafc]">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('leads.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white text-slate-500 hover:text-blue-600 hover:shadow-lg transition-all">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Leads</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Details</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $lead->name }} <span class="text-slate-400 font-medium">#{{ $lead->id }}</span></h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Basic Info Card -->
            <div class="glass-panel rounded-[1.5rem] p-6 space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-blue-500">info</span>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Lead Information</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Phone</label>
                        <p class="text-sm font-bold text-slate-700">{{ $lead->phone_number }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
                        <p class="text-sm font-bold text-slate-700">{{ $lead->email ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">City</label>
                        <p class="text-sm font-bold text-slate-700">{{ $lead->city }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Address</label>
                        <p class="text-sm font-bold text-slate-700">{{ $lead->site_address ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Building Stage</label>
                        <span class="inline-flex px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-black uppercase tracking-wider">
                            {{ $lead->building_status ?? 'N/A' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Required Area</label>
                        <p class="text-sm font-bold text-slate-700">{{ $lead->total_required_area_sqft }} Sq.ft</p>
                    </div>
                    @if(!in_array((int)$lead->lead_stage, [5, 6, 7]))
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Priority</label>
                        @php
                            $priorityStyle = match((int)$lead->priority) {
                                1 => 'bg-red-100 text-red-600',
                                2 => 'bg-amber-100 text-amber-600',
                                3 => 'bg-blue-100 text-blue-600',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $priorityLabel = match((int)$lead->priority) {
                                1 => 'High',
                                2 => 'Medium',
                                3 => 'Low',
                                default => 'N/A',
                            };
                        @endphp
                        <span class="inline-flex px-2 py-1 {{ $priorityStyle }} rounded-lg text-[10px] font-black uppercase tracking-wider">
                            {{ $priorityLabel }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Site Images (New Section) -->
            @if($lead->images->count() > 0)
            <div class="glass-panel rounded-[1.5rem] p-6 space-y-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-blue-500">image</span>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Site Images</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($lead->images as $img)
                    <a href="{{ asset('storage/' . $img->img_path) }}" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 hover:shadow-md transition-all group relative">
                        <img src="{{ asset('storage/' . $img->img_path) }}" alt="Site Image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Measurements Table -->
             <div class="glass-panel rounded-[1.5rem] overflow-hidden">
                <div class="p-4 bg-white/40 border-b border-white/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-purple-500">straighten</span>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Measurement Details</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/50">
                            <tr class="text-left">
                                <th class="pl-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">S.No</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Product</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Dimensions</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Area</th>
                                <th class="pr-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Sq.ft</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lead->measurements as $item)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="pl-6 py-3 text-xs font-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-bold text-slate-700">{{ $item->product }}</p>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $item->design_code }} • {{ $item->color }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs font-bold text-slate-600">
                                    {{ $item->width_val }}{{ $item->width_unit }} x {{ $item->height_val }}{{ $item->height_unit }}
                                </td>
                                <td class="px-4 py-3 text-xs font-bold text-slate-600">{{ $item->qty }}</td>
                                <td class="px-4 py-3 text-xs font-bold text-slate-600">{{ $item->area }}</td>
                                <td class="pr-6 py-3 text-xs font-black text-slate-800 text-right">{{ $item->sqft }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-slate-400 font-bold italic">No measurements recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($lead->measurements->count() > 0)
                        <tfoot class="bg-slate-50/80 border-t border-slate-200">
                            <tr>
                                <td colspan="5" class="pl-6 py-3 text-xs font-black text-slate-600 uppercase tracking-wider text-right">Total Area</td>
                                <td class="pr-6 py-3 text-sm font-black text-blue-600 text-right">{{ $lead->measurements->sum('sqft') }} Sq.ft</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>

        <!-- Site Handover Details (Visible if Handed Over) -->
        @if($lead->lead_stage == 6)
        <div class="glass-panel rounded-[1.5rem] p-6 space-y-4 lg:col-span-2">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-emerald-500">handshake</span>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Site Handover Details</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Installed Date</label>
                    <p class="text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->installed_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Handover Date</label>
                    <p class="text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->handovered_date)->format('d M, Y') }}</p>
                </div>
                @if($lead->google_review)
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Google Review / Feedback</label>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 italic text-sm text-slate-600">
                        "{{ $lead->google_review }}"
                    </div>
                </div>
                @endif
            </div>

            <!-- Handover Photos -->
            @if($lead->handoverPhotos->count() > 0)
            <div class="mt-4">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Site Photos</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($lead->handoverPhotos as $photo)
                    <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-slate-200 hover:shadow-md transition-all group relative">
                        <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Site Photo" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        </div>

        <!-- Sidebar / Fabricator Info -->
        <div class="space-y-6">
            
            <!-- Project Status Card (Visible if Won or Lost) -->
            @if(in_array($lead->lead_stage, [5, 6, 7]))
            <div class="glass-panel rounded-[1.5rem] p-6 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 {{ in_array($lead->lead_stage, [7]) ? 'bg-red-500' : 'bg-green-500' }}"></div>
                
                @if(in_array($lead->lead_stage, [7]))
                    <!-- Lost State -->
                    <div class="w-16 h-16 mx-auto bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-[32px]">thumb_down</span>
                    </div>
                    <h3 class="text-xl font-black text-red-600 uppercase tracking-tight mb-1">Lead Lost</h3>
                    <p class="text-xs font-bold text-red-400 uppercase tracking-widest mb-4">Better luck next time</p>
                    
                    <div class="text-left space-y-3 bg-red-50/50 p-4 rounded-xl border border-red-100">
                        <div>
                            <p class="text-[10px] font-black text-red-400 uppercase tracking-wider">Reason</p>
                            <p class="text-xs font-bold text-slate-700">{{ $lead->lost_type ?? 'N/A' }}</p>
                        </div>
                        @if($lead->competitor)
                        <div>
                            <p class="text-[10px] font-black text-red-400 uppercase tracking-wider">Competitor</p>
                            <p class="text-xs font-bold text-slate-700">{{ $lead->competitor }}</p>
                        </div>
                        @endif
                    </div>
                @elseif($lead->lead_stage == 6)
                    <!-- Handover State -->
                    <div class="w-16 h-16 mx-auto bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-[32px]">handshake</span>
                    </div>
                    <h3 class="text-xl font-black text-emerald-600 uppercase tracking-tight mb-1">Site Handovered</h3>
                    <p class="text-xs font-bold text-emerald-400 uppercase tracking-widest mb-4">Project Completed</p>

                    <div class="text-left space-y-3 bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-black text-emerald-600/60 uppercase tracking-wider">Installed</p>
                                <p class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->installed_date)->format('d M, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-emerald-600/60 uppercase tracking-wider">Handover</p>
                                <p class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->handovered_date)->format('d M, Y') }}</p>
                            </div>
                        </div>
                        
                        @if($lead->google_review)
                        <div>
                            <p class="text-[10px] font-black text-emerald-600/60 uppercase tracking-wider">Review</p>
                            <p class="text-xs italic text-slate-600">"{{ $lead->google_review }}"</p>
                        </div>
                        @endif
                    </div>

                @else
                    <!-- Won State (Stage 5) -->
                     <div class="w-16 h-16 mx-auto bg-green-100 text-green-600 rounded-2xl flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-[32px]">celebration</span>
                    </div>
                    <h3 class="text-xl font-black text-green-600 uppercase tracking-tight mb-1">Project Won</h3>
                    <p class="text-xs font-bold text-green-400 uppercase tracking-widest mb-4">Congratulations!</p>

                    <div class="text-left space-y-3 bg-green-50/50 p-4 rounded-xl border border-green-100">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-black text-green-600/60 uppercase tracking-wider">Won Date</p>
                                <p class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->won_date)->format('d M, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-green-600/60 uppercase tracking-wider">Install Date</p>
                                <p class="text-xs font-bold text-slate-700">{{ \Carbon\Carbon::parse($lead->expected_installation_date)->format('d M, Y') }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-green-600/60 uppercase tracking-wider">Advance Received</p>
                            <p class="text-sm font-black text-green-700">₹ {{ number_format($lead->advance_received, 2) }}</p>
                        </div>
                        
                        @if($lead->final_quotation_pdf)
                        <a href="{{ asset('storage/'.$lead->final_quotation_pdf) }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition-all">
                            <span class="material-symbols-outlined text-[16px]">download</span>
                            Download Final Quote
                        </a>
                        @endif
                    </div>
                @endif
            </div>
            @endif

            <!-- Assigned User Card -->
            <div class="glass-panel rounded-[1.5rem] p-6 text-center">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/30 mb-3">
                    <span class="material-symbols-outlined text-[32px]">person</span>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Assigned To</p>
                <h3 class="text-lg font-black text-slate-800">{{ $lead->assignedUser->name ?? 'Unassigned' }}</h3>
                <p class="text-xs font-bold text-slate-500 mt-1">{{ $lead->assigned_by ? 'Assigned by Admin' : 'Self Assigned' }}</p>
            </div>

            <!-- Fabricator Requests -->
            <div class="glass-panel rounded-[1.5rem] p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-orange-500">engineering</span>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Fabricator Requests</h3>
                </div>
                
                <div class="space-y-3">
                    @forelse($lead->fabricatorRequests as $req)
                    <div class="p-4 bg-white/60 border border-slate-100 rounded-xl relative group hover:border-blue-200 transition-all">
                        
                        <!-- Header: Name & Status -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">To Fabricator</p>
                                <p class="text-xs font-bold text-slate-800">{{ $req->fabricator->name ?? 'Unknown' }}</p>
                            </div>
                            <span class="inline-flex px-2 py-1 {{ $req->status == 'Quotation Sent' ? 'bg-purple-50 text-purple-600' : ($req->status == 'Completed' ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600') }} rounded-lg text-[9px] font-black uppercase tracking-wider">
                                {{ $req->status ?? 'Pending' }}
                            </span>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Approx Area</p>
                                <p class="text-xs font-bold text-slate-800">{{ $req->approx_sqft }} Sq.ft</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Date Sent</p>
                                <p class="text-xs font-bold text-slate-800">{{ $req->created_at->format('d M') }}</p>
                            </div>
                        </div>

                        <!-- Quotation Details (if available) -->
                        @if($req->rate_per_sqft || $req->fabrication_pdf)
                        <div class="pt-3 border-t border-slate-100 mt-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Rate / Sq.ft</p>
                                    <p class="text-sm font-black text-blue-600">₹ {{ $req->rate_per_sqft ?? 'N/A' }}</p>
                                </div>
                                @if($req->fabrication_pdf)
                                <a href="{{ asset('storage/' . $req->fabrication_pdf) }}" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all" title="View Quotation PDF">
                                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                </a>
                                @endif
                            </div>
                        </div>
                        @else
                           @if($req->status != 'Completed' && $req->status != 'Quotation Sent')
                           <div class="mt-2 text-center">
                                <span class="text-[10px] text-orange-400 font-bold italic">Awaiting Quotation</span>
                           </div>
                           @endif
                        @endif

                    </div>
                    @empty
                    <div class="text-center py-4">
                        <span class="material-symbols-outlined text-slate-300 text-[32px] mb-1">inbox_customize</span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">No requests sent</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
