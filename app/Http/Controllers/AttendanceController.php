<?php

namespace App\Http\Controllers;

use App\Models\UserAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('attendance.index', compact('users'));
    }

    /**
     * Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = UserAttendance::leftJoin('users', 'users_attendances.user_id', '=', 'users.id')
                ->select([
                    'users_attendances.*',
                    'users.name as user_name'
                ]);

            if ($request->filled('user_id')) {
                $query->where('users_attendances.user_id', $request->user_id);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('users_attendances.date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('users_attendances.date', '<=', $request->to_date);
            }

            return DataTables::of($query)
                ->addColumn('formatted_date', function ($row) {
                    return $row->date ? Carbon::parse($row->date)->format('Y-m-d') : '-';
                })
                ->editColumn('punch_in_time', function ($row) {
                    return $row->punch_in_time ? Carbon::parse($row->punch_in_time)->format('h:i A') : '-';
                })
                ->editColumn('punch_out_time', function ($row) {
                    return $row->punch_out_time ? Carbon::parse($row->punch_out_time)->format('h:i A') : '-';
                })
                ->addColumn('spend_time', function ($row) {
                    if ($row->punch_in_time && $row->punch_out_time) {
                        $startTime = Carbon::parse($row->punch_in_time);
                        $endTime = Carbon::parse($row->punch_out_time);
                        return $startTime->diff($endTime)->format('%H:%I:%S');
                    }
                    return '-';
                })
                ->addColumn('travel_time', function ($row) {
                    if (!$row->punch_in_time || !$row->punch_out_time) return '-';

                    $totalSeconds = Carbon::parse($row->punch_in_time)->diffInSeconds(Carbon::parse($row->punch_out_time));
                    
                    // Sum up site visit times for this user on this day
                    $siteVisits = \App\Models\LeadVisit::where('user_id', $row->user_id)
                        ->whereDate('intime_time', $row->date) // Assuming intime_time contains date or we check date
                        ->get();
                    
                    $siteSeconds = 0;
                    foreach ($siteVisits as $visit) {
                        if ($visit->intime_time && $visit->out_time) {
                            $siteSeconds += Carbon::parse($visit->intime_time)->diffInSeconds(Carbon::parse($visit->out_time));
                        }
                    }

                    $travelSeconds = max(0, $totalSeconds - $siteSeconds);
                    
                    $hours = floor($travelSeconds / 3600);
                    $minutes = floor(($travelSeconds / 60) % 60);
                    $seconds = $travelSeconds % 60;

                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                })
                ->addColumn('traveled_km', function ($row) {
                    if (isset($row->start_km) && isset($row->end_km)) {
                        return round($row->end_km - $row->start_km, 2);
                    }
                    return '-';
                })
                ->addColumn('start_km_photo_url', function ($row) {
                    return $row->start_km_photo ? asset('storage/users_punch_in_images/' . $row->start_km_photo) : null;
                })
                ->addColumn('end_km_photo_url', function ($row) {
                    return $row->end_km_photo ? asset('storage/users_punch_out_images/' . $row->end_km_photo) : null;
                })
                ->addColumn('map_data', function ($row) {
                    return [
                        'in_lat' => $row->in_lat,
                        'in_long' => $row->in_long,
                        'out_lat' => $row->out_lat,
                        'out_long' => $row->out_long
                    ];
                })
                ->rawColumns(['map_data'])
                ->make(true);
        }
    }
}
