<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\InvoiceResponse;
use App\Models\FabricatorInvoice;
use App\Models\Fabricator;
use App\Models\FabricatorPayment;
use App\Models\InvoiceCollection;

class InvoiceResponseController extends Controller
{
    /* =========================
       LIST RAW LOGS
    ==========================*/
    public function index(Request $request)
    {
        $query = InvoiceResponse::query();

        if ($request->has('invoice_no')) {
            $query->where('invoice_no', $request->invoice_no);
        }

        return response()->json([
            'status' => true,
            'data' => $query->latest()->paginate(10)
        ]);
    }

    /* =========================
       STORE INVOICE / NOTES
    ==========================*/
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cust_id' => 'required|exists:fabricators,cust_id',
            'invoice_type' => 'required|in:INVOICE,DEBIT NOTE,CREDI NOTE,CANCEL',
            'invoice_no' => 'required|string',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'qty' => 'required|integer|min:1',
            'original_invoice_no' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>false,'message'=>$validator->errors()->first()],422);
        }

        DB::beginTransaction();

        try {

            /* 1. Decide debit / credit */
            $debit = 0;
            $credit = 0;

            if (in_array($request->invoice_type, ['INVOICE','DEBIT NOTE'])) {
                $debit = $request->amount;
            }

            if (in_array($request->invoice_type, ['CANCEL','CREDI NOTE'])) {
                $credit = $request->amount;
            }

            /* 2. Save ledger row */
            FabricatorInvoice::create([
                'cust_id' => $request->cust_id,
                'invoice_type' => $request->invoice_type,
                'invoice_no' => $request->invoice_no,
                'invoice_date' => $request->invoice_date,
                'amount' => $request->amount,
                'qty' => $request->qty,
                'original_invoice_no' => $request->original_invoice_no,
                'debit' => $debit,
                'credit' => $credit
            ]);

            /* 3. Outstanding */
            $outstanding = FabricatorInvoice::where('cust_id',$request->cust_id)
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;

            /* 4. Update fabricator */
            $fabricator = Fabricator::where('cust_id',$request->cust_id)->firstOrFail();
            $fabricator->current_outstanding = $outstanding;
            $fabricator->save();

            /* 5. Risk flags */
            $risk = [
                'limit_crossed' => $outstanding > $fabricator->credit_limit,
                'overdue' => false
            ];

            $lastInvoice = FabricatorInvoice::where('cust_id',$request->cust_id)
                ->where('invoice_type','INVOICE')
                ->latest('invoice_date')
                ->first();

            if ($lastInvoice) {
                $due = Carbon::parse($lastInvoice->invoice_date)
                    ->addDays($fabricator->credit_days);
                if (now()->gt($due)) {
                    $risk['overdue'] = true;
                }
            }

            /* 6. INVOICE COLLECTION TRACK */
            if ($request->invoice_type === 'INVOICE') {

                $dueDate = Carbon::parse($request->invoice_date)
                    ->addDays($fabricator->credit_days);

                InvoiceCollection::updateOrCreate(
                    ['invoice_no' => $request->invoice_no],
                    [
                        'cust_id' => $request->cust_id,
                        'invoice_no' => $request->invoice_no,
                        'invoice_date' => $request->invoice_date,
                        'invoice_amount' => $request->amount,
                        'due_date' => $dueDate,
                        'collected_amount' => 0,
                        'due_amount' => $request->amount,
                        'overdue_days' => 0
                    ]
                );
            }

            /* 7. Save raw payload */
            InvoiceResponse::create([
                'invoice_no' => $request->invoice_no,
                'request' => $request->all()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Invoice processed',
                'outstanding' => $outstanding,
                'risk' => $risk
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>false,'error'=>$e->getMessage()],500);
        }
    }

    /* =========================
       STORE PAYMENT
    ==========================*/
    public function storePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cust_id' => 'required|exists:fabricators,cust_id',
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|string',
            'payment_date' => 'required|date',
            'ref_no' => 'nullable|string',
            'invoice_no' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>false,'message'=>$validator->errors()->first()],422);
        }

        DB::beginTransaction();

        try {

            /* 1. Save payment */
            FabricatorPayment::create($request->only([
                'cust_id','payment_mode','ref_no','amount','payment_date'
            ]));

            /* 2. Ledger credit */
            FabricatorInvoice::create([
                'cust_id' => $request->cust_id,
                'invoice_type' => 'CREDI NOTE',
                'invoice_no' => $request->ref_no,
                'invoice_date' => $request->payment_date,
                'amount' => $request->amount,
                'qty' => 1,
                'debit' => 0,
                'credit' => $request->amount
            ]);

            /* 3. Outstanding */
            $outstanding = FabricatorInvoice::where('cust_id',$request->cust_id)
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;

            /* 4. Update fabricator */
            $fabricator = Fabricator::where('cust_id',$request->cust_id)->firstOrFail();
            $fabricator->current_outstanding = $outstanding;
            $fabricator->save();

            /* 5. Update invoice collection if invoice_no passed */
            if ($request->invoice_no) {
                $collection = InvoiceCollection::where('invoice_no',$request->invoice_no)->first();
                if ($collection) {
                    $collection->collected_amount += $request->amount;
                    $collection->due_amount = $collection->invoice_amount - $collection->collected_amount;
                    $collection->collected_date = $request->payment_date;

                    if ($collection->collected_date > $collection->due_date) {
                        $collection->overdue_days =
                            Carbon::parse($collection->due_date)
                            ->diffInDays(Carbon::parse($collection->collected_date));
                    }

                    $collection->save();
                }
            }

            DB::commit();

            return response()->json([
                'status'=>true,
                'message'=>'Payment recorded',
                'outstanding'=>$outstanding
            ]);

        } catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status'=>false,'error'=>$e->getMessage()],500);
        }
    }
}
