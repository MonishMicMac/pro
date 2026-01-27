<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fabricator;
use App\Models\FabricatorInvoice;
use App\Models\FabricatorPayment;
use App\Models\InvoiceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FabricatorAccountingApiController extends Controller
{
    /**
     * Get Current Outstanding for a Fabricator
     * Input: fabricator_id
     */
    public function currentOutstanding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fabricator_id' => 'required|exists:fabricators,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $fabricator = Fabricator::find($request->fabricator_id);
        $custId = $fabricator->cust_id;

        if (!$custId) {
            return response()->json([
                'status' => true,
                'data' => [
                    'fabricator_id' => $fabricator->id,
                    'cust_id' => null,
                    'shop_name' => $fabricator->shop_name,
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'outstanding_balance' => 0
                ]
            ]);
        }

        $totals = FabricatorInvoice::where('cust_id', $custId)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $outstanding = ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);

        return response()->json([
            'status' => true,
            'data' => [
                'fabricator_id' => $fabricator->id,
                'cust_id' => $custId,
                'shop_name' => $fabricator->shop_name,
                'total_debit' => (float)($totals->total_debit ?? 0),
                'total_credit' => (float)($totals->total_credit ?? 0),
                'outstanding_balance' => (float)$outstanding
            ]
        ]);
    }

    /**
     * Get Payment Details for a Fabricator
     * Input: cust_id
     */
    public function paymentDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cust_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $payments = FabricatorPayment::where('cust_id', $request->cust_id)
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Payment details retrieved successfully',
            'data' => $payments
        ]);
    }

    /**
     * Get Collection Details for a Fabricator
     * Input: cust_id
     */
    public function collectionDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cust_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $collections = InvoiceCollection::where('cust_id', $request->cust_id)
            ->orderBy('invoice_date', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Collection details retrieved successfully',
            'data' => $collections
        ]);
    }
}
