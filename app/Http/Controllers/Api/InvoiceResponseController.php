<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvoiceResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\FabricatorInvoice;
class InvoiceResponseController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by invoice_no if provided in query params.
     */
    public function index(Request $request)
    {
        $query = InvoiceResponse::query();

        // Allow filtering by invoice_no (e.g., /api/invoice-responses?invoice_no=INV-1001)
        if ($request->has('invoice_no')) {
            $query->where('invoice_no', $request->invoice_no);
        }

        $responses = $query->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $responses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
    {
        // 1. Validate the Request
        $validator = Validator::make($request->all(), [
            'fabricator_id'       => 'required|integer',
            'invoice_type'        => 'required|string|max:50',
            'invoice_no'          => 'required|string|max:100', // Ensure unique if necessary
            'invoice_date'        => 'required|date',
            'amount'              => 'required|numeric',
            'category'            => 'nullable|string',
            'qty'                 => 'required|integer',
            'original_invoice_no' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Use a Transaction to ensure both tables are updated together
        try {
            DB::beginTransaction();

            // A. Store Main Details in 'fabricator_invoices'
            // We use updateOrCreate to avoid duplicates if the same invoice is sent twice
            $invoice = FabricatorInvoice::updateOrCreate(
                ['invoice_no' => $request->invoice_no], // Search criteria
                [
                    'fabricator_id'       => $request->fabricator_id,
                    'invoice_type'        => $request->invoice_type,
                    'invoice_date'        => $request->invoice_date,
                    'amount'              => $request->amount,
                    'category'            => $request->category,
                    'qty'                 => $request->qty,
                    'original_invoice_no' => $request->original_invoice_no,
                ]
            );

            // B. Store the Raw Request Log in 'invoice_responses'
            $log = InvoiceResponse::create([
                'invoice_no' => $request->invoice_no,
                'request'    => $request->all(), // Save the full JSON payload
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Invoice stored and logged successfully',
                'data'    => [
                    'invoice' => $invoice,
                    'log_id'  => $log->id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to process invoice',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $invoiceResponse = InvoiceResponse::find($id);

        if (!$invoiceResponse) {
            return response()->json(['status' => false, 'message' => 'Record not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $invoiceResponse
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $invoiceResponse = InvoiceResponse::find($id);

        if (!$invoiceResponse) {
            return response()->json(['status' => false, 'message' => 'Record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'invoice_no' => 'sometimes|string|exists:fabricator_invoices,invoice_no',
            'request'    => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $invoiceResponse->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Invoice Response updated successfully',
            'data'    => $invoiceResponse
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $invoiceResponse = InvoiceResponse::find($id);

        if (!$invoiceResponse) {
            return response()->json(['status' => false, 'message' => 'Record not found'], 404);
        }

        $invoiceResponse->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Record deleted successfully'
        ]);
    }
}