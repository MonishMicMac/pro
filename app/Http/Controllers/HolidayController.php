<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        return view('holiday_management.index');
    }

    // FullCalendar calls this automatically with 'start' and 'end' params
    public function fetch(Request $request)
    {
        $holidays = Holiday::all();

        // Map data to FullCalendar Event Format
        $events = $holidays->map(function ($holiday) {
            return [
                'title' => $holiday->holiday_name,
                'start' => $holiday->date,
                'allDay' => true,
                'display' => 'block'
            ];
        });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $holidays = $request->input('holidays', []);

        foreach ($holidays as $h) {
            // If it exists, delete it (toggle behavior), otherwise create it
            $exists = Holiday::where('date', $h['date'])->first();

            if ($exists) {
                $exists->delete();
            } else {
                Holiday::create([
                    'date' => $h['date'],
                    'holiday_name' => $h['name'],
                    'month' => Carbon::parse($h['date'])->month
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }
}
