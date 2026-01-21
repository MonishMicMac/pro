@extends('layouts.app')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
@section('content')
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">

        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-bold text-slate-800 uppercase tracking-wider">
                Fabricator Requests
            </h3>

            <!-- FILTER -->
            <div class="flex gap-2">
                <button class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-slate-100" data-status="all">All</button>

                <button class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700"
                    data-status="0">Pending</button>

                <button class="filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700"
                    data-status="1">Completed</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-3">Lead</th>
                        <th>Approx Sq.ft</th>
                        <th>Status</th>
                        <th>Rate</th>
                        <th>PDF</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody id="requestTable">
                    @forelse($fabricator->requests as $req)
                        <tr class="border-b request-row" data-status="{{ $req->status }}">

                            <td class="py-3 font-semibold">
                                {{ $req->lead->name ?? '-' }}
                            </td>

                            <td>{{ $req->approx_sqft }}</td>

                            <td>
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold
        {{ $req->status == 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $req->status == 0 ? 'Pending' : 'Completed' }}
                                </span>
                            </td>

                            <td>{{ $req->rate_per_sqft ?? '-' }}</td>

                            <td>
                                @if ($req->fabrication_pdf)
                                    <a href="{{ asset('storage/' . $req->fabrication_pdf) }}" target="_blank"
                                        class="text-red-500 font-bold">
                                        PDF
                                    </a>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-slate-500">
                                {{ $req->created_at->format('d M Y') }}
                            </td>

                            {{-- MEASUREMENT DETAILS --}}
                            <td class="text-xs space-y-2">

                                @forelse($req->lead->measurements as $m)
                                    <div class="border rounded p-2 bg-slate-50">
                                        <b>Product:</b> {{ $m->product }} <br>

                                        <b>Design:</b> {{ $m->design_code }} <br>

                                        <b>Size:</b>
                                        {{ $m->width_val }} {{ $m->width_unit }}
                                        ×
                                        {{ $m->height_val }} {{ $m->height_unit }} <br>

                                        <b>Qty:</b> {{ $m->qty }}
                                        | <b>Sqft:</b> {{ $m->sqft }}

                                        @if ($m->color)
                                            <br><b>Color:</b> {{ $m->color }}
                                        @endif
                                    </div>

                                @empty
                                    <span class="text-slate-400">No measurement</span>
                                @endforelse

                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400 text-sm">
                                No requests found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>
    <script>
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {

                let status = this.dataset.status;

                document.querySelectorAll('.request-row')
                    .forEach(row => {

                        if (status === 'all') {
                            row.classList.remove('hidden');
                        } else {
                            row.dataset.status === status ?
                                row.classList.remove('hidden') :
                                row.classList.add('hidden');
                        }
                    });

                // active style
                document.querySelectorAll('.filter-btn')
                    .forEach(b => b.classList.remove('ring', 'ring-blue-400'));

                this.classList.add('ring', 'ring-blue-400');
            });
        });
    </script>
@endsection
