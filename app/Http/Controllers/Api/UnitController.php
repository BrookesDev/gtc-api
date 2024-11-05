<?php

namespace App\Http\Controllers\Api;
use App\Models\Unit;
use App\Models\ReceivableType;
use App\Customers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index()
    {
        $unit = Unit::where('company_id', auth()->user()->company_id)->get();
        return respond(true, 'List of units fetched!', $unit, 201);
    }

    public function addNewUnit(Request $request)
    {
        $id = getCompanyid();
        $input = $request->all();
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            $unit = Unit::create($input);
            return respond(true, 'Unit saved successful!', $unit, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), $input, 400);

        }
    }
    public function saveLoanApplication(Request $request)
    {
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
        $validator = Validator::make($data, [
            'description' => 'required',
            // 'receivable_type' => 'required|exists:nominal_ledgers,id',
            'invoice_number' => 'required|unique:my_transactions,invoice_number',
            'teller_number' => 'nullable',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'transaction_date' => 'required|date',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
            'account_id' => 'required|array',
            'account_id.*' => 'required|exists:receivable_types,id',
            'account_amount' => 'required|array',
            'account_amount.*' => 'required|numeric',
            'supporting_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2024'

        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $amount = $input['amount'];
        $arrayAmount = $input['account_amount'];
        $sum = array_sum($arrayAmount);
        if ($sum != $amount) {
            return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        }
        // dd($input);
        $items = $input['account_id'];
        $input['uuid'] = $request->invoice_number;
        if ($request->type == 2) {
            $typeDescription = "Sales Invoice";
        } else {
            $typeDescription = "Loan";
        }
        $count = array_count_values($items);
        foreach ($items as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "A receivable type exists more than once , see row $sum !", [$sum, $amount], 400);
            }
        }
        // dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        try {
            foreach ($items as $key => $item) {
                // $glcode = $item;
                $amount = $input['account_amount'][$key];
                //  $uuid = rand();
                $receivableType = $input['account_id'][$key];
                $getReceivable = ReceivableType::find($receivableType);
                $glcode = $getReceivable->gl_code;
                // debit the  accounts
                postDoubleEntries($uuid, $glcode, 0, $amount, $detail);
                // $input = $request->except('amount', 'account_amount', 'account_id', 'quantity', 'particulars', 'all_sum','supporting_document');
                // $input['amount'] = $request->amount;
                // $amount = $input['amount'];
                if ($request->has('supporting_document')) {
                    $input['document'] = uploadImage($request->supporting_document, "documents");
                }
                // $input['debit'] = $input['amount'];
                $input['transaction_date'] = $request->transaction_date ?? now();

                // dd("stop");
                //post as receivable
                insertReceivable($amount, $amount, 0, $input['transaction_date'], $detail, $request->invoice_number, 1, $uuid, $input['transaction_date'], $typeDescription, $request->customer_id, $glcode, $input['debit_gl_code'], $input['document'] ?? NULL, $request->teller_number ?? NULL, $receivableType);
                // $input = $request->all();
            }
            // credit receiveable
            postDoubleEntries($uuid, $request->debit_gl_code, $request->amount, 0, $detail, $input['transaction_date']);
            $getCustomer = Customers::find($request->customer_id);
            // dd($getCustomer->balance);
            $balance = $getCustomer->balance - $amount;
            $amount = $input['amount'];
            $getCustomer->update(['balance' => $getCustomer->balance - $amount]);
            // save to customer ledger
            saveCustomerLedger($request->customer_id, $uuid, $input['amount'], 0, $detail, $balance);
            DB::commit();
            return respond(true, 'Transaction successful!!', $amount, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
