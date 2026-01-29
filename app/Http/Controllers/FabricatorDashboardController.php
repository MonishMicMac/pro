<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FabricatorRequest;
use App\Models\Fabricator;
use App\Models\Lead;
use Validator;
use App\Models\MeasurementDetail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helpers\S3UploadHelper;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\FabricatorStockManagement;




class FabricatorDashboardController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {

        $fabricator = Auth::guard('fabricator')->user();

        $id = $fabricator->id;

        $total = FabricatorRequest::where('fabricator_id', $id)->count();

        $pending = FabricatorRequest::where('fabricator_id', $id)
            ->where('status', '0')->count();

        $completed = FabricatorRequest::where('fabricator_id', $id)
            ->whereIn('status', ['1', '2'])->count();

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

            $query = FabricatorRequest::with([
                'lead:id,name,user_id',
                'lead.assignedUser:id,name'
            ])
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

                ->addColumn('bdo', fn($r) => $r->lead->assignedUser->name ?? '-')

                ->editColumn('status', function ($r) {
                    if ($r->status == '0') {
                        return '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px]">
                    Pending
                </span>';
                    }

                    if ($r->status == '1') {
                        return '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px]">
                    Initial
                </span>';
                    }

                    if ($r->status == '2') {
                        return '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px]">
                    Final
                </span>';
                    }

                    return '-';
                })

                ->addColumn('approx_sqft', fn($r) => $r->approx_sqft ?? '-')
                ->editColumn('created_at', fn($r) => $r->created_at->format('d/m/Y'))

                ->addColumn('quotation_pdf', function ($r) {

                    // Prefer FINAL quotation
                    if (!empty($r->lead?->final_quotation_pdf)) {
                        $url = Storage::disk('s3')->url($r->lead->final_quotation_pdf);

                        return '
            <a href="' . $url . '" target="_blank" class="text-emerald-600" title="Final Quotation">
                <span class="material-symbols-outlined">picture_as_pdf</span>
            </a>
        ';
                    }

                    // Fallback to INITIAL quotation
                    if (!empty($r->fabrication_pdf)) {
                        $url = Storage::disk('s3')->url($r->fabrication_pdf);

                        return '
            <a href="' . $url . '" target="_blank" class="text-blue-600" title="Initial Quotation">
                <span class="material-symbols-outlined">picture_as_pdf</span>
            </a>
        ';
                    }

                    return '<span class="text-slate-400 text-xs">Not Uploaded</span>';
                })


                ->addColumn('view', function ($r) {
                    return '
            <a href="' . route('fabricator.measurements', $r->lead_id) . '" class="text-blue-600">
                <span class="material-symbols-outlined">visibility</span>
            </a>';
                })


                ->rawColumns(['status', 'quotation_pdf', 'view'])
                ->make(true);
        }

        // Normal page load
        return view('fabricator.assignments');
    }

    public function measurementView($leadId)
    {
        $lead = \App\Models\Lead::findOrFail($leadId);

        $measurements = MeasurementDetail::where('lead_id', $leadId)->get();

        $fabricatorRequest = FabricatorRequest::where('lead_id', $leadId)
            ->where('fabricator_id', auth('fabricator')->id())
            ->first();

        return view(
            'fabricator.measurements',
            compact('measurements', 'lead', 'fabricatorRequest')
        );
    }

    public function uploadFabricationDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id'       => 'required|exists:leads,id',
            'fabricator_id' => 'required|exists:users,id',
            'rate_per_sqft' => 'required|numeric|min:0',
            'pdf_file'      => 'required|file|mimes:pdf|max:10240',
            'total_value'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'reason' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $lead = Lead::lockForUpdate()->findOrFail($request->lead_id);

            $fabRequest = FabricatorRequest::where('lead_id', $request->lead_id)
                ->where('fabricator_id', $request->fabricator_id)
                ->firstOrFail();

            // ================================
            // Determine upload type
            // ================================
            $isFirstUpload  = empty($fabRequest->fabrication_pdf);
            $isSecondUpload = !$isFirstUpload && empty($lead->final_quotation_pdf);

            // ❌ Block 3rd upload
            if (!$isFirstUpload && !$isSecondUpload) {
                return response()->json([
                    'status' => false,
                    'reason' => 'Quotation already finalized. Further uploads are not allowed.'
                ], 403);
            }

            // ================================
            // Store file
            // ================================
            $file = $request->file('pdf_file');
            $fileName = 'fab_' . $request->lead_id . '_' . time() . '.pdf';
            $path = $file->storeAs('fabrication_docs', $fileName, 'public');

            // ================================
            // FIRST UPLOAD → FabricatorRequest
            // ================================
            if ($isFirstUpload) {
                $fabRequest->update([
                    'fabrication_pdf' => $path,
                    'rate_per_sqft'   => $request->rate_per_sqft,
                    'total_value'     => $request->total_value,
                    'status'          => '1', // Initial
                ]);

                $lead->update([
                    'lead_stage'  => 4,
                    'total_value' => $request->total_value,
                ]);

                LeadHelper::logStatus($lead, $lead->building_status, 4);
            }

            // ================================
            // SECOND UPLOAD → Leads table ONLY
            // ================================
            if ($isSecondUpload) {
                $lead->update([
                    'final_quotation_pdf' => $path,
                    'total_value'         => $request->total_value,
                    'final_rate_per_sqft'   => $request->rate_per_sqft,
                ]);

                // Optional status update
                $fabRequest->update([
                    'status' => '2', // Final submitted
                ]);
            }

            DB::commit();

            return response()->json([
                'status'   => true,
                'message'  => $isFirstUpload
                    ? 'Initial quotation uploaded successfully'
                    : 'Final quotation uploaded successfully',
                'pdf_url'  => asset('storage/' . $path),
                'is_final' => $isSecondUpload
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'reason' => $e->getMessage()
            ], 500);
        }
    }




    // public function uploadFabricationDetails(Request $request)
    // {
    //     // 1. Initial Validation
    //     $validator = Validator::make($request->all(), [
    //         'lead_id'       => 'required|exists:leads,id',
    //         'fabricator_id' => 'required|exists:fabricators,id',
    //         'rate_per_sqft' => 'required|numeric|min:0',
    //         'total_quotation_amount'  => 'required|numeric|min:0',
    //         'pdf_file'      => 'required|file|mimes:pdf|max:10240',
    //     ]);

    //     if ($validator->fails()) {
    //         // This will now catch the "failed to upload" error early
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Validation Error',
    //             'reason'  => $validator->errors()->first()
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $file = $request->file('pdf_file');

    //         if (!$file->isValid()) {
    //             throw new \Exception("System Upload Error: " . $file->getErrorMessage());
    //         }

    //         /**
    //          * Upload to S3 → quotation folder
    //          */
    //         $upload = S3UploadHelper::upload(
    //             $file,
    //             'quotation'   // 👈 folder name in S3
    //         );



    //         // Update Record
    //         $fabRequest = \App\Models\FabricatorRequest::where('lead_id', $request->lead_id)
    //             ->where('fabricator_id', $request->fabricator_id)
    //             ->firstOrFail();

    //         $fabRequest->update([
    //             'fabrication_pdf'        => $upload['path'],
    //             'rate_per_sqft'   => $request->rate_per_sqft,
    //             'total_quotation_amount'   => $request->total_quotation_amount,
    //             'status'          => '1'
    //         ]);

    //         // Update Lead Stage to 4
    //         $lead = \App\Models\Lead::findOrFail($request->lead_id);
    //         $lead->update([
    //             'lead_stage' => 4,
    //             'total_quotation_amount'   => $request->total_quotation_amount
    //         ]);

    //         \App\Helpers\LeadHelper::logStatus($lead, $lead->building_status, 4);

    //         MeasurementDetail::where('lead_id', $request->lead_id)
    //             ->update([
    //                 'is_sent_to_quote' => 1
    //             ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Quotation uploaded successfully',
    //             'pdf_url' => $upload['url']
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Upload Failed',
    //             'reason'  => $e->getMessage()
    //         ], 500);
    //     }
    // }



     public function stockReport()
    {
        $fabricators = Fabricator::where('status', '0')->orderBy('shop_name')->get(); 
        $zones = \App\Models\Zone::where('action', '0')->orderBy('name')->get();
        
        return view('fabricator.fabricator_stock_report', compact('fabricators', 'zones'));
    }


    /**
     * Yajra DataTable endpoint for stock records
     */
   public function data(Request $request)
{    auth()->shouldUse('fabricator');

    $fabricator = Auth::guard('fabricator')->user();

    if (!$fabricator) {
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
    }

    $startDate = $request->from_date
        ? Carbon::parse($request->from_date)->startOfDay()
        : null;

    $endDate = $request->to_date
        ? Carbon::parse($request->to_date)->endOfDay()
        : null;

    /**
     * Get latest stock per product FOR THIS FABRICATOR ONLY
     */
    $latestIds = FabricatorStockManagement::where('fabricator_id', $fabricator->id)
        ->selectRaw('MAX(id) as id')
        ->groupBy('product_id')
        ->pluck('id');

    $query = FabricatorStockManagement::with([
        'product.category',
        'product.subCategory'
    ])->whereIn('id', $latestIds);

    // Date filter (updated between)
    if ($startDate && $endDate) {
        $query->whereBetween('updated_at', [$startDate, $endDate]);
    }

    // Product filters
    if ($request->category_id) {
        $query->whereHas('product', fn($q) =>
            $q->where('category_id', $request->category_id)
        );
    }

    if ($request->sub_category_id) {
        $query->whereHas('product', fn($q) =>
            $q->where('sub_category_id', $request->sub_category_id)
        );
    }

    if ($request->product_id) {
        $query->where('product_id', $request->product_id);
    }

    return DataTables::of($query)
        ->addColumn('date', fn($r) => $r->updated_at->format('d-M-Y'))
        ->addColumn('fabricator_zone', fn() => '-') // removed
        ->addColumn('fabricator_name', fn() => '-') // removed
        ->addColumn('category_name', fn($r) => $r->product->category->name ?? '-')
        ->addColumn('sub_category_name', fn($r) => $r->product->subCategory->name ?? '-')
        ->addColumn('product_name', fn($r) => $r->product->name ?? '-')
        ->addColumn('current_stock', fn($r) => $r->current_stock)
        ->addColumn('updated_by', function ($r) {
            return optional(\App\Models\User::find($r->updated_by))->name ?? '-';
        })
        ->make(true);
}


    public function getCategories()
    {
        $data = \App\Models\Category::where('action', '0')->orderBy('name')->select('id', 'name')->get();
        return response()->json($data);
    }

    public function getSubCategories(Request $request)
    {
        $query = \App\Models\SubCategory::where('action', '0')->orderBy('name')->select('id', 'name', 'category_id');
        if($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        return response()->json($query->get());
    }

    public function getProducts(Request $request)
    {
        $query = \App\Models\Product::where('action', '0')->orderBy('name')->select('id', 'name', 'item_code');
        if($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if($request->sub_category_id) {
            $query->where('sub_category_id', $request->sub_category_id);
        }
        return response()->json($query->get());
    }
}
