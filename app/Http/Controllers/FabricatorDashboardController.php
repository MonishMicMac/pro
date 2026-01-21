<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FabricatorRequest;
use App\Models\Fabricator;
use Validator;
use App\Models\MeasurementDetail;

class FabricatorDashboardController extends Controller
{

    public function index()
    {
        $fabricator = Auth::guard('fabricator')->user();

        $id = $fabricator->id;

        $total = FabricatorRequest::where('fabricator_id', $id)->count();

        $pending = FabricatorRequest::where('fabricator_id', $id)
            ->where('status', '0')->count();

        $completed = FabricatorRequest::where('fabricator_id', $id)
            ->where('status', '1')->count();

        return view('fabricator.dashboard', compact(
            'fabricator',
            'total',
            'pending',
            'completed'
        ));
    }

    /* UPDATE PROFILE */
    public function update(Request $request, $id)
    {
        $fabricator = Fabricator::findOrFail($id);

        $request->validate([
            'shop_name' => 'required',
            'mobile' => 'required|unique:fabricators,mobile,' . $id,
            'email' => 'nullable|email'
        ]);

        $fabricator->update($request->except('_token'));

        return back()->with('success', 'Profile updated successfully');
    }




    public function show()
    {
        $fabricator = Auth::guard('fabricator')->user();

        $fabricator = Fabricator::with([
            'state',
            'district',
            'city',
            'pincode',
            'brands',
            'requests.lead'
        ])->findOrFail($fabricator->id);

        return view('fabricator.show', compact('fabricator'));
    }

    // public function myAssignments()
    // {
    //     // Get logged-in fabricator ID
    //     $fabricatorId = auth('fabricator')->id();

    //     $assignments = \App\Models\FabricatorRequest::with([
    //         'lead:id,name,user_id',
    //         'lead.assignedUser:id,name'
    //     ])
    //         ->where('fabricator_id', $fabricatorId)
    //         ->latest()
    //         ->get()
    //         ->map(function ($row) {
    //             $row->measurements =
    //                 \App\Models\MeasurementDetail::where(
    //                     'lead_id',
    //                     $row->lead_id
    //                 )->get();
    //             return $row;
    //         });

    //     return view(
    //         'fabricator.assignments-list',
    //         compact('assignments')
    //     );
    // }
    public function myAssignments()
    {
        $fabricatorId = auth('fabricator')->id();

        $fabricator = Fabricator::with([
            'requests.lead.measurements'
        ])->findOrFail($fabricatorId);

        return view('fabricator.assignments', compact('fabricator'));
    }
}
