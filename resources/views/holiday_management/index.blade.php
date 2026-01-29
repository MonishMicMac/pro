@extends('layouts.app')

@section('title', 'Holiday Management')

@section('content')
<style>
    :root {
        --primary-color: #1e73be;
        --holiday-bg: #e11d48;
        --selected-bg: #16a34a;
        --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    }

    /* Page Header */
    .page-header h1 {
        font-weight: 900;
        color: #1e293b;
        letter-spacing: -0.5px;
    }

    /* Modern Card */
    .calendar-card {
        border: none;
        border-radius: 2rem;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        box-shadow: var(--card-shadow);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .calendar-header {
        background: linear-gradient(135deg, rgba(30, 115, 190, 0.05) 0%, rgba(22, 163, 106, 0.05) 100%);
        padding: 25px 30px;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        text-align: center;
    }

    .calendar-header h4 {
        margin: 0;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--primary-color);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* FullCalendar Customization */
    #calendar {
        padding: 25px;
    }

    .fc-theme-standard td, .fc-theme-standard th {
        border: 1px solid #f1f5f9 !important;
    }

    .fc-col-header-cell {
        background: #f8fafc;
        padding: 14px 0 !important;
        text-transform: uppercase;
        font-size: 0.7rem;
        font-weight: 800;
        color: #94a3b8;
        letter-spacing: 1px;
    }

    .fc-daygrid-day-number {
        font-weight: 700;
        color: #475569;
        padding: 12px !important;
        font-size: 0.9rem;
    }

    .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        color: #1e293b !important;
    }

    .fc-button-primary {
        background: white !important;
        border: 1px solid #e2e8f0 !important;
        color: #64748b !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        padding: 8px 16px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04) !important;
        transition: all 0.2s !important;
    }

    .fc-button-primary:hover {
        background: #f8fafc !important;
        color: #1e293b !important;
        transform: translateY(-1px);
    }

    .fc-button-primary:focus {
        box-shadow: 0 0 0 3px rgba(30, 115, 190, 0.1) !important;
    }

    /* Holiday & Selected Styling */
    .fc-event-holiday {
        background: linear-gradient(135deg, #e11d48 0%, #be123c 100%) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 5px 10px !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
    }

    .selected-day {
        background: linear-gradient(135deg, rgba(22, 163, 106, 0.1) 0%, rgba(22, 163, 106, 0.15) 100%) !important;
        transition: all 0.25s ease;
    }

    .selected-day .fc-daygrid-day-number {
        color: #166534 !important;
        background: linear-gradient(135deg, #86efac 0%, #4ade80 100%);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0 !important;
        margin: 8px;
        box-shadow: 0 4px 10px rgba(22, 163, 106, 0.3);
    }

    /* Modern Buttons */
    .btn-save-custom {
        background: linear-gradient(135deg, #1e73be 0%, #1d4ed8 100%);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 8px 25px rgba(30, 115, 190, 0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-save-custom:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(30, 115, 190, 0.4);
        color: white;
    }

    /* Legend */
    .calendar-legend {
        display: flex;
        gap: 25px;
        margin-top: 12px;
        justify-content: center;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
    }

    .dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .fc-daygrid-day:hover {
        background: rgba(30, 115, 190, 0.03);
        cursor: pointer;
    }
</style>

<div class="mb-6 d-flex justify-content-between align-items-end">
    <div class="page-header">
        <nav class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
            <span>Settings</span>
            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
            <span class="text-blue-600">Holiday Management</span>
        </nav>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-1">HOLIDAY CALENDAR</h1>
        <p class="text-slate-500 font-medium text-sm">Manage and schedule company holidays</p>
    </div>

    <button class="btn-save-custom" id="saveHolidays">
        <span class="material-symbols-outlined">check_circle</span>
        Confirm Changes
    </button>
</div>

<div class="calendar-card">
    <div class="calendar-header">
        <h4><span class="material-symbols-outlined align-middle mr-2" style="font-size: 1.2rem;">calendar_month</span> Official Calendar</h4>
        <div class="calendar-legend">
            <div class="legend-item"><span class="dot" style="background: linear-gradient(135deg, #e11d48, #be123c)"></span> Holiday</div>
            <div class="legend-item"><span class="dot" style="background: linear-gradient(135deg, #86efac, #4ade80)"></span> Selected</div>
        </div>
    </div>
    <div id="calendar"></div>
</div>

@push('scripts')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>

<script>
$(document).ready(function() {
    const calendarEl = document.getElementById('calendar');
    let selectedDates = new Set();

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: "{{ route('holidays.fetch') }}",
        eventDisplay: 'block',
        eventClassNames: ['fc-event-holiday'],

        dateClick: function(info) {
            const dateStr = info.dateStr;
            const cell = info.dayEl;

            if (selectedDates.has(dateStr)) {
                selectedDates.delete(dateStr);
                $(cell).removeClass('selected-day');
            } else {
                selectedDates.add(dateStr);
                $(cell).addClass('selected-day');
            }
        },

        dayCellDidMount: function(info) {
            const dateStr = info.date.toISOString().split('T')[0];
            if (selectedDates.has(dateStr)) {
                $(info.el).addClass('selected-day');
            }
        }
    });

    calendar.render();

    $('#saveHolidays').on('click', function() {
        if (selectedDates.size === 0) {
            Swal.fire('Info', 'Please select at least one date on the calendar.', 'info');
            return;
        }

        const holidayArray = Array.from(selectedDates).map(date => ({
            date: date,
            name: "Public Holiday"
        }));

        $.ajax({
            url: "{{ route('holidays.store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                holidays: holidayArray
            },
            success: function() {
                Swal.fire('Success', 'Calendar updated successfully!', 'success');
                selectedDates.clear();
                calendar.refetchEvents();
                $('.selected-day').removeClass('selected-day');
            },
            error: function() {
                Swal.fire('Error', 'Failed to update calendar.', 'error');
            }
        });
    });
});
</script>
@endpush
@endsection
