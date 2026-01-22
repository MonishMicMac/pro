<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FabricatorRequest;
use App\Models\Fabricator;
use Validator;
use App\Models\MeasurementDetail;
use Illuminate\Support\Facades\DB;


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
    public function myAssignments(Request $request)
    {
        $fabricatorId = auth('fabricator')->id();

        // If ajax request (datatable)
        if ($request->ajax()) {

            $query = FabricatorRequest::with('lead')
                ->where('fabricator_id', $fabricatorId);

            // STATUS FILTER
            if ($request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // DATE FILTER
            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            return datatables()->of($query)
                ->addColumn('lead', fn($r) => $r->lead->name)

                ->editColumn('status', function ($r) {
                    return $r->status == '0'
                        ? '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px]">Pending</span>'
                        : '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px]">Completed</span>';
                })

                ->editColumn('created_at', fn($r) => $r->created_at->format('d/m/Y'))

                ->addColumn('view', function ($r) {
                    return '
                <a href="' . route('fabricator.measurements', $r->lead_id) . '" 
                   class="text-blue-600">
                    <span class="material-symbols-outlined">visibility</span>
                </a>';
                })

                ->rawColumns(['status', 'view'])
                ->make(true);
        }

        // Normal page load
        return view('fabricator.assignments');
    }

    public function measurementView($leadId)
    {
        $lead = \App\Models\Lead::findOrFail($leadId);

        $measurements = MeasurementDetail::where('lead_id', $leadId)->get();

        return view(
            'fabricator.measurements',
            compact('measurements', 'lead')
        );
    }



    public function uploadFabricationDetails(Request $request)
    {
        // 1. Initial Validation
        $validator = Validator::make($request->all(), [
            'lead_id'       => 'required|exists:leads,id',
            'fabricator_id' => 'required|exists:fabricators,id',
            'rate_per_sqft' => 'required|numeric|min:0',
            'pdf_file'      => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            // This will now catch the "failed to upload" error early
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'reason'  => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('pdf_file');

            // Check for PHP system errors during upload
            if (!$file->isValid()) {
                throw new \Exception("System Upload Error: " . $file->getErrorMessage());
            }

            $fileName = 'fab_' . $request->lead_id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Store the file and verify success
            $path = $file->storeAs('fabrication_docs', $fileName, 'public');

            if (!$path) {
                throw new \Exception("Failed to write file to disk. Check storage folder permissions.");
            }

            // Update Record
            $fabRequest = \App\Models\FabricatorRequest::where('lead_id', $request->lead_id)
                ->where('fabricator_id', $request->fabricator_id)
                ->firstOrFail();

            $fabRequest->update([
                'fabrication_pdf' => $path,
                'rate_per_sqft'   => $request->rate_per_sqft,
                'status'          => '1'
            ]);

            // Update Lead Stage to 4
            $lead = \App\Models\Lead::find($request->lead_id);
            $lead->update(['lead_stage' => 4]);

            \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 4);

            MeasurementDetail::where('lead_id', $request->lead_id)
                ->update([
                    'is_sent_to_quote' => 1
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Quotation uploaded successfully',
                'pdf_url' => asset('storage/' . $path)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Upload Failed',
                'reason'  => $e->getMessage()
            ], 500);
        }
    }
}
