@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>

<style type="text/tailwindcss">
    @layer components {
        .glass-panel { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); }
        .custom-select { @apply appearance-none bg-no-repeat bg-right pr-10 cursor-pointer transition-all duration-300; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-size: 1.2em; }
        /* Critical fix for visibility */
        .perm-check-hidden { display: none !important; }
    }
</style>

<div class="flex-1 overflow-y-auto p-8 space-y-6 bg-[#f8fafc]">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Permissions Matrix</h1>
            <p class="text-slate-500 text-sm font-medium">Configure access rights for system roles.</p>
        </div>

        <div class="relative w-full max-w-xs group">
            <label class="absolute -top-2 left-3 px-1 bg-[#f8fafc] text-[10px] font-bold text-slate-400 uppercase tracking-widest  transition-colors group-focus-within:text-blue-600">Configure Access For</label>
            <select id="roleSelector" class="custom-select w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-2xl outline-none text-sm font-bold text-slate-700 focus:border-blue-500 transition-all shadow-sm">
                <option value="" disabled selected>-- Choose a Role --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div id="matrixWrapper" style="display: none;">
        <div class="glass-panel rounded-[2rem] overflow-hidden">
            <div class="px-8 py-3 bg-white/50 border-b border-slate-200 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-1 bg-blue-600 rounded-full"></div>
                    <span class="text-xs font-black text-slate-700 uppercase tracking-widest">Access Control Mapping</span>
                </div>
                <label class="flex items-center gap-3 px-4 py-3 bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-800 transition-all group">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest group-hover:text-white">Grant All Access</span>
                    <input type="checkbox" id="globalSelectAll" class="w-4 h-4 rounded border-slate-700 bg-slate-800 focus:ring-0 cursor-pointer">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left bg-slate-50/50">
                            <th class="pl-8 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider">Module Name</th>
                            <th class="px-4 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider text-center">Full Access</th>
                            <th class="px-4 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider text-center">View</th>
                            <th class="px-4 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider text-center">Add</th>
                            <th class="px-4 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider text-center">Edit</th>
                            <th class="px-4 py-4 text-[11px] font-black text-slate-400 uppercase tracking-wider text-center">Del</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($groupedPermissions as $module => $perms)
                            <tr class="hover:bg-blue-50/30 transition-colors group">
                                <td class="pl-8 py-3">
                                    <span class="text-sm font-bold text-slate-700 capitalize">{{ str_replace(['.', '_'], ' ', $module) }}</span>
                                </td>
                                
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" class="moduleRowSelector w-5 h-5 rounded-lg border-slate-300 text-blue-600 focus:ring-0 cursor-pointer">
                                </td>

                                @php $actions = ['view', 'create', 'edit', 'delete']; @endphp
                                @foreach($actions as $action)
                                    <td class="px-4 py-3 text-center">
                                        @php $permName = "$module.$action"; @endphp
                                        @if($permissions->contains('name', $permName))
                                            @foreach($roles as $role)
                                                <input type="checkbox" 
                                                       class="permission-trigger role-col-{{ $role->id }} perm-check-hidden w-5 h-5 rounded-lg border-slate-300 text-blue-600 focus:ring-0 cursor-pointer transition-all"
                                                       data-role-id="{{ $role->id }}" 
                                                       data-permission-name="{{ $permName }}"
                                                       {{ $role->permissions->contains('name', $permName) ? 'checked' : '' }}>
                                            @endforeach
                                        @else
                                            <div class="h-1 w-4 bg-slate-200 rounded-full mx-auto"></div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const token = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });

    // Sync function to send data to server
    function syncPermissions(roleId) {
        const perms = [];
        $(`.role-col-${roleId}:checked`).each(function() { 
            perms.push($(this).data('permission-name')); 
        });

        $.ajax({
            url: "{{ route('userroles.update') }}",
            method: "POST",
            data: { role_id: roleId, permissions: perms },
            success: function() { 
                Toast.fire({ icon: 'success', title: 'Security Updated' }); 
            }
        });
    }

    // Dropdown change logic
    $('#roleSelector').on('change', function() {
        const id = $(this).val();
        if(id) {
            // Reset toggles visually
            $('#globalSelectAll, .moduleRowSelector').prop('checked', false);
            
            // Hide all checkboxes first, then show only the ones for this role
            $('.permission-trigger').addClass('perm-check-hidden');
            $(`.role-col-${id}`).removeClass('perm-check-hidden');
            
            // Show the table wrapper
            $('#matrixWrapper').show();
        }
    });

    // Row-level Select All
    $('.moduleRowSelector').on('change', function() {
        const roleId = $('#roleSelector').val();
        const state = $(this).prop('checked');
        $(this).closest('tr').find(`.role-col-${roleId}`).prop('checked', state);
        syncPermissions(roleId);
    });

    // Global Select All
    $('#globalSelectAll').on('change', function() {
        const roleId = $('#roleSelector').val();
        const state = $(this).prop('checked');
        $(`.role-col-${roleId}, .moduleRowSelector`).prop('checked', state);
        syncPermissions(roleId);
    });

    // Individual checkbox click
    $(document).on('change', '.permission-trigger', function() {
        syncPermissions($(this).data('role-id'));
    });

    // Initialize if role is already selected (on refresh)
    if($('#roleSelector').val()) $('#roleSelector').trigger('change');
});
</script>
@endsection