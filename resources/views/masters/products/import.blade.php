@extends('layouts.app')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 20px 0 rgba(31, 38, 135, 0.05);
        }
        .step-badge {
            @apply w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300;
        }
        .step-active { @apply bg-blue-600 text-white shadow-lg shadow-blue-500/30 scale-110; }
        .step-inactive { @apply bg-slate-100 text-slate-400; }
    }
</style>

<div class="flex-1 overflow-y-auto p-5 space-y-6 bg-[#f8fafc] min-h-screen">
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                <span>Masters</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <a href="{{ route('masters.products.index') }}" class="hover:text-blue-600">Product</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-blue-600">Import Tool</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Bulk Import Products</h1>
        </div>
        <a href="{{ route('masters.products.index') }}" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to List
        </a>
    </div>

    <div class="flex items-center justify-center gap-12 mb-4">
        <div class="flex flex-col items-center gap-2">
            <div id="step1-badge" class="step-badge step-active">1</div>
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Upload</span>
        </div>
        <div class="h-[2px] w-20 bg-slate-200 mb-6"></div>
        <div class="flex flex-col items-center gap-2">
            <div id="step2-badge" class="step-badge step-inactive">2</div>
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Map Columns</span>
        </div>
        <div class="h-[2px] w-20 bg-slate-200 mb-6"></div>
        <div class="flex flex-col items-center gap-2">
            <div id="step3-badge" class="step-badge step-inactive">3</div>
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Summary</span>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <div id="section-step1" class="glass-panel rounded-[2rem] p-10 text-center space-y-6">
            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[40px]">upload_file</span>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800">Select your Excel File</h2>
                <p class="text-slate-500 text-xs font-medium mt-1">Upload .xlsx or .xls files only</p>
            </div>

            <form id="uploadPreviewForm" class="max-w-md mx-auto space-y-4">
                @csrf
                <div class="flex gap-4 items-end justify-center">
                    <div class="text-left flex-1">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-2">Header Row</label>
                        <input type="number" name="head_row" value="1" min="1" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500/10 outline-none transition-all"/>
                    </div>
                    <div class="text-left flex-[2]">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-2">Excel File</label>
                        <input type="file" name="excel_file" accept=".xlsx, .xls" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer"/>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 bg-slate-900 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-slate-900/20 hover:scale-[1.02] active:scale-95 transition-all">
                    Analyze spreadsheet
                </button>
            </form>
        </div>

        <div id="section-step2" class="hidden glass-panel rounded-[2rem] overflow-hidden">
            <div class="p-6 bg-white/50 border-b border-white flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-800">Map Columns to Database</h2>
                <span class="text-[10px] bg-emerald-500 text-white px-3 py-1 rounded-full font-black uppercase tracking-tighter">File Ready</span>
            </div>
            
            <form id="finalImportForm">
                @csrf
                <div id="mappingContainer" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    </div>
                
                <div class="p-6 bg-slate-50 border-t border-slate-100 flex gap-4">
                    <button type="button" onclick="location.reload()" class="flex-1 py-3 text-slate-400 font-black uppercase text-xs tracking-widest">Reset</button>
                    <button type="submit" class="flex-[2] py-3 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-blue-500/20">
                        Start Import Process
                    </button>
                </div>
            </form>
        </div>

        <div id="section-step3" class="hidden glass-panel rounded-[2rem] p-12 text-center space-y-6">
            <div id="statusIcon"></div>
            <h2 id="summaryHeading" class="text-2xl font-black text-slate-800">Import Complete</h2>
            <div id="summaryStats" class="flex justify-center gap-8 py-4">
                </div>
            <div id="errorBox" class="text-left bg-rose-50 text-rose-600 p-4 rounded-xl text-xs font-bold hidden"></div>
            <a href="{{ route('masters.products.index') }}" class="inline-block px-10 py-3 bg-slate-900 text-white rounded-2xl font-black uppercase text-xs tracking-widest">Return to Product Master</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    // Step 1: Upload and get Headers
    $('#uploadPreviewForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('masters.products.uploadPreview') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#section-step1').fadeOut(300, function() {
                    $('#mappingContainer').html(res.mappingFields);
                    $('#section-step2').fadeIn().removeClass('hidden');
                    $('#step1-badge').removeClass('step-active').addClass('bg-emerald-500 text-white');
                    $('#step2-badge').addClass('step-active').removeClass('step-inactive');
                });
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.error || "Error analyzing file");
            }
        });
    });

    // Step 2: Final Import
    $('#finalImportForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: "{{ route('masters.products.import') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(res) {
                $('#section-step2').fadeOut(300, function() {
                    $('#section-step3').fadeIn().removeClass('hidden');
                    $('#step2-badge').removeClass('step-active').addClass('bg-emerald-500 text-white');
                    $('#step3-badge').addClass('step-active').removeClass('step-inactive');
                    
                    $('#statusIcon').html('<span class="material-symbols-outlined text-[80px] text-emerald-500">check_circle</span>');
                    $('#summaryStats').html(`
                        <div class="text-center"><p class="text-[10px] text-slate-400 font-black uppercase">Success</p><p class="text-3xl font-black text-emerald-600">${res.successSummary.match(/\d+/) || 0}</p></div>
                        <div class="text-center"><p class="text-[10px] text-slate-400 font-black uppercase">Updated</p><p class="text-3xl font-black text-blue-600">${res.successSummary.match(/Updated (\d+)/)?.[1] || 0}</p></div>
                    `);
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Start Import Process');
                $('#section-step2').fadeOut(300, function() {
                    $('#section-step3').fadeIn().removeClass('hidden');
                    $('#statusIcon').html('<span class="material-symbols-outlined text-[80px] text-rose-500">error</span>');
                    $('#summaryHeading').text('Import Failed');
                    $('#errorBox').html(xhr.responseJSON?.error || 'A system error occurred during import.').show();
                });
            }
        });
    });
});
</script>
@endsection