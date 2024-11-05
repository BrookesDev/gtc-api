<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Beneficiary;
use App\Models\SupplierPersonalLedger;
use App\Models\MyTransactions;
use App\Models\Beneficiary_Account;
use App\Models\Account;
use App\Models\PaymentVoucherComment;
use App\Models\Journal;
use App\Models\BeneficiaryAccount;
use App\Models\Budget;
use App\Models\Tax;
use App\Models\PaymentVoucher;
use App\Models\UploadPaymentVoucher;
use App\Exports\PaymentExport;
use App\Imports\PaymentImport;
use App\Models\TaxDeduction;
use App\Models\PaymentVoucherBreakdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentVoucherController extends Controller
{

    public function indexVoucher()
    {
        $vouchers = getUserPaymentVoucher()->with(['beneficiary', 'expenses', 'payables', 'PreparerDetail', 'beneficiariesAccount', 'bank', 'approver'])->get();
        return respond(true, 'payment fetched successfully!', $vouchers, 201);
    }
    public function add()
    {
        $category = Category::where('name', 'LIKE', '%EXPENSES%')->pluck('id')->toArray();
        // dd($category);
        $check = Category::whereIn('category_parent', $category)->first();
        if ($check) {
            $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
        } else {
            $group = $category;
        }

        $value = Category::where('name', 'LIKE', '%PAYABLES%')->pluck('id')->toArray();
        // dd($category);
        $getAll = Category::whereIn('category_parent', $value)->first();
        if ($getAll) {
            $getAllCategory = Category::whereIn('category_parent', $value)->pluck('id')->toArray();
        } else {
            $getAllCategory = $value;
        }
        // dd($group, $value);
        try {
            $data['accounts'] = Account::where('category_id', 12)->get();
            $data['beneficiaries'] = Beneficiary::where('user_id', getCompanyid())->get();
            $data['gl_accounts'] = Account::whereIn('category_id', $group)->get();
            $data['taxes'] = Tax::all();
            return respond(true, 'Data needed in payment blade!', $data, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function create(Request $request)
    {
        $input = $request->all();
        // dd($input);
        //validation
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'total_amount' => 'required',
            'total_tax_amount' => 'required',
            'gl_account' => 'required',
            'beneficiary_account_id' => 'required|exists:beneficiary_accounts,id',
            'description' => 'required',
            'contract_amount' => 'required',
            'account' => 'required',
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'document' => 'required|mimes:pdf,jpg,jpeg,png'

        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), $request->document, 400);
        }

        DB::beginTransaction();
        try {

            $input['transaction_date'] = $request->date;
            $input['total_amount'] = preg_replace('/[^\d.]/', '', $request->total_amount);
            $input['total_tax_amount'] = preg_replace('/[^\d.]/', '', $request->total_tax_amount) ?? 0;
            $beneficiary = Beneficiary::find($request->beneficiary_id);
            $input['beneficiary_details'] = $beneficiary;
            $account = Account::where('id', $request->gl_account)->first();
            $input['pvnumber'] = rand(10000, 99999);
            $input['prepared_by'] = Auth::user()->id;
            $input['approval_status'] = 0;
            $input['payment_status'] = 0;
            $input['remittance_status'] = 0;
            // $input['beneficiary_account_id'] = $request->beneficiary_account_id;
            //$input = Beneficiary::where('beneficiary_id', $request->beneficiary_id)->first();

            //  dd($input);
            if ($request->has('document')) {
                $input['document'] = uploadImage($request->document, "documents");
            }
            //approval level
            //the gl_account param sent
            $input['bank_lodged'] = $request->gl_account;
            //the account param sent
            $input['payable'] = $request->account;
            $input['balance'] = $input['total_amount'];
            $input['uuid'] = rand();
            $savethisPayment = PaymentVoucherBreakdown::create($input);

            // $postJournal = new journalController;
            postDoubleEntries($input['uuid'], $input['payable'], $input['total_amount'], 0, $savethisPayment->description); // debit the payable account
            postDoubleEntries($input['uuid'], $input['bank_lodged'], 0, $input['total_amount'], $savethisPayment->description); // credit the bank account
            //save tax  deduction if available
            $taxes = $request->tax;
            if (!empty($taxes[0])) {

                foreach ($taxes as $key => $tax) {
                    $deduction = new TaxDeduction();
                    $deduction['tax_id'] = $tax;
                    $deduction['breakdown_id'] = $savethisPayment->id;
                    $deduction['tax_percentage'] = $request->taxpercent[$key];
                    $deduction['contract_amount'] = $request->contract_amount;
                    $deduction['contractor_id'] = $request->beneficiary_id;
                    $deduction['deducted_amount'] = preg_replace('/[^\d.]/', '', $request->taxamount[$key]);
                    $deduction['prepared_by'] = Auth::user()->id;
                    $saveDeduction = $deduction->save();
                }
            }
            DB::commit();

            //return redirect()->route('payment_vouchers')->with('message', 'Payment gazette successfully');
            return respond(true, 'Payment created successfully', $savethisPayment->load('beneficiary'), 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }

        # code...
    }

    public function save_beneficiary_payment(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'transaction_date' => 'required|date',
            'description' => 'required',
            'total_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            // 'total_tax_amount' => 'required|integer',
            'gl_account' => 'required|exists:accounts,id',
            'account' => 'required|exists:accounts,id',
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            //'tax' => 'nullable|array',
            // 'taxpercent' => 'numeric|required',
            // 'taxamount' => 'numeric|required',
            'contract_amount' => 'required|integer',
            // 'beneficiary_account' => 'required|min:10|max:10',


        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors()->first(), null, 400);
        }
        // dd($input);
        DB::beginTransaction();
        try {

            $input['transaction_date'] = $request->transaction_date;
            $input['total_amount'] = $request->total_amount;
            $input['total_tax_amount'] = $request->total_tax_amount ?? 0;

            $account = Account::where('id', $request->gl_account)->first();
            $current = $input['total_amount'];
            $input['pvnumber'] = rand(10000, 99999);
            $input['prepared_by'] = Auth::user()->id;
            $input['approval_status'] = 0;
            $input['payment_status'] = 0;
            $input['remittance_status'] = 0;
            $input['beneficiary_account_id'] = $request->beneficiary_account;


            //  dd($input);
            if ($request->has('document')) {
                $input['document'] = uploadImage($request->document, "documents");
            }
            $input['expense'] = $request->gl_account;
            $input['payable'] = $request->account;

            $savethisPayment = PaymentVoucherBreakdown::create($input);
            //   dd($input);

            // $postJournal = new journalController;
            // $postJournal->postDoubleEntries($savethisPayment->id, $request->gl_account, $input['total_amount'],0, $savethisPayment->description) ; // debit the expense account
            // $postJournal->postDoubleEntries($savethisPayment->id, $request->account, 0 , $input['total_amount'], $savethisPayment->description) ; // credit the payable account
            //save tax  deduction if available
            $taxes = $request->tax;
            if (!empty($taxes[0])) {

                foreach ($taxes as $key => $tax) {
                    $deduction = new TaxDeduction();
                    $deduction['tax_id'] = $tax;
                    $deduction['breakdown_id'] = $savethisPayment->id;
                    $deduction['tax_percentage'] = $request->taxpercent[$key];
                    $deduction['contract_amount'] = $request->contract_amount;
                    $deduction['contractor_id'] = $request->beneficiary_id;
                    $deduction['deducted_amount'] = preg_replace('/[^\d.]/', '', $request->taxamount[$key]);
                    $deduction['prepared_by'] = Auth::user()->id;
                    $saveDeduction = $deduction->save();
                }
            }
            DB::commit();

            return respond(true, 'Voucher prepared successfully!', null, 201);


        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), $input, 400);

            //  return redirect()->back()->withErrors($exception->getMessage());
        }

        # code...
    }

    public function getDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:payment_voucher_breakdowns,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        $id = $request->id;
        $data = PaymentVoucherBreakdown::where('id', $id)->with('beneficiary', 'expenses', 'payables')->first();
        return respond(true, 'Voucher details fetched successfully!', $data, 201);
    }
    public function download()
    {
        $type = "xlsx";
        return \Excel::download(new PaymentExport, 'payment.' . $type);
    }

    public function postPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xls,xlsx',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(request()->file('file'));
        $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $countdata = count($sheetData) - 1;
        // dd($countdata,$request->all());
        if ($countdata < 1) {

            return respond(false, 'Excel File Is Empty! Populate And Upload!', $countdata, 400);

        }
        DB::beginTransaction();
        try {
            $uuid = rand();
            \Excel::import(new PaymentImport($uuid), request()->file('file'));
            DB::commit();
            // get all imported datas
            $upload = UploadPaymentVoucher::where("uuid", $uuid)->get();
            return respond(true, 'Import successful!!', $upload, 201);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollback();
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $errormess = '';
                foreach ($failure->errors() as $error) {
                    $errormess = $errormess . $error;
                }
                $errormessage[] = 'There was an error on Row ' . ' ' . $failure->row() . '.' . ' ' . $errormess;
            }


            return respond(false, $errormessage, null, 400);
        } catch (\Exception $exception) {
            // DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            if (is_int($errorCode)) {

                return respond(false, $exception->errorInfo[2], null, 400);
            } else {
                // dd($exception);

                return respond(false, $exception->getMessage(), null, 400);
            }
        }
    }
    public function payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:payment_voucher_breakdowns,id',
            'amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'expense' => 'required|exists:accounts,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        // DB::beginTransaction();
        try {
            $id = $request->id;
            $voucher = PaymentVoucherBreakdown::where('id', $id)->first();
            $voucherAmount = $voucher->total_amount;
            $totalAmountPaid = $voucher->amount_paid;
            $balance = $voucher->balance;
            $incomingAmount = $request->amount;
            $journal = Journal::where('uuid', $id)->first();
            $uuid = $voucher->uuid ?? $journal->uuid ?? rand();
            $payable = $voucher->payable;
            $expense = $request->expense;
            if (!$totalAmountPaid && !$balance) {
                if ($incomingAmount > $voucherAmount) {
                    return respond(false, 'Amount greater than voucher payment!', null, 400);
                }
                $newBalance = $voucherAmount - $incomingAmount;
            } else {
                if ($incomingAmount > $balance) {
                    return respond(false, 'Amount greater than voucher balance!', null, 400);
                }
                $newBalance = $balance - $incomingAmount;
            }

            if ($incomingAmount == $balance) {
                $voucher->payment_status = 1;
            }
            $voucher->expense = $expense;
            $voucher->balance = $newBalance;
            $voucher->teller_number = $request->teller_number ?? "";
            $voucher->amount_paid = $voucherAmount - $balance + $incomingAmount;
            // dd( $voucher->amount_paid);
            $voucher->save();
            //credit the payable account
            postDoubleEntries($uuid, $payable, 0, $incomingAmount, $voucher->description, $request->transaction_date);
            //debit the expense account
            postDoubleEntries($uuid, $expense, $incomingAmount, 0, $voucher->description, $request->transaction_date);
            return respond(true, 'Payment successful!', $voucher, 201);
            // DB::commit();
        } catch (\Exception $e) {
            //  DB::rollback();
            return respond(false, $e->getMessage(), null, 400);
        }

    }
    public function payment1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'id. *' => 'required|array|exists:payment_voucher_breakdowns,id',
            'bank' => 'required|exists:accounts,id',
            'gateway' => 'required',
            // 'reference' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }

        try {

            $input = $request->all();
            // dd($input);

            // $breakdown = PaymentVoucherBreakdown::where('id', $request->id)->first();
            if ($request->gateway == "bank") {
                try {
                    $ids = $request->input('id');
                    $uuid = $input["reference"] = rand(10000, 99999);
                    foreach ($ids as $key => $id) {
                        $updateVoucher = PaymentVoucherBreakdown::where('id', $id)->update(["transaction_id" => $input["reference"], "bank_lodged" => $input['bank'], "channel" => $input['gateway']]);
                        $breakdown = PaymentVoucherBreakdown::where('id', $id)->first();
                        $journal = Journal::where('uuid', $id)->where('credit', '!=', 0)->first();
                        $gl_code = $journal->gl_code ?? "";
                        $bankcode = $input['bank'];
                        $amount = $breakdown->contract_amount;
                        $detail = $breakdown->description ?? "All is well";
                        // post to journal
                        postDoubleEntries($uuid, $bankcode, 0, $amount, $detail); // credit the bank account

                        postDoubleEntries($uuid, $gl_code, $amount, 0, $detail); // debit the payable account
                        $transaction_date = now();
                        $particular = $breakdown->description ?? "All is well";
                        $details = $breakdown->description ?? "All is well";
                        $bank_amount = $breakdown->contract_amount;
                        $chq_teller = $breakdown->pvnumber;
                        $payment_mode = $input['gateway'];
                        // post to cashbook

                        // dd($input);
                        $breakdown->update(['payment_status' => 1, 'channel' => $request->gateway]);
                    }
                    // return api_request_response(
                    //     "ok",
                    //     "Payment Successfully!",
                    //     success_status_code(),
                    //     $bankcode
                    // );
                    return respond(true, 'Payment Successfully!', $bankcode, 200);
                    // return Paystack::getAuthorizationUrl()->redirectNow();
                } catch (\Exception $e) {

                    return respond(false, $e->getMessage(), null, 400);
                }
            }
            if ($request->gateway == "instruction") {
                $ids = $request->input('id');
                $uuid = $input["reference"] = rand(10000, 99999);
                foreach ($ids as $key => $id) {
                    $updateVoucher = PaymentVoucherBreakdown::where('id', $id)->update(["transaction_id" => $uuid, "bank_lodged" => $input['bank'], "channel" => $input['gateway']]);
                    $breakdown = PaymentVoucherBreakdown::where('id', $id)->first();
                    $journal = Journal::where('uuid', $id)->where('credit', '!=', 0)->first();
                    $gl_code = $journal->gl_code ?? "";
                    // $uuid = $input["reference"];
                    $bankcode = $input['bank'];
                    $amount = $breakdown->contract_amount;
                    $detail = $breakdown->description ?? "All is well";
                    // dd($id);
                    // post to journal
                    postDoubleEntries($uuid, $bankcode, 0, $amount, $detail); // credit the bank account

                    postDoubleEntries($uuid, $gl_code, $amount, 0, $detail); // debit the payable account
                    $transaction_date = now();
                    $particular = $breakdown->description ?? "All is well";
                    $details = $breakdown->description ?? "All is well";
                    $bank_amount = $breakdown->contract_amount;
                    $chq_teller = $breakdown->pvnumber;
                    $payment_mode = $input['gateway'];

                    // dd($input);
                    $breakdown->update(['payment_status' => 1, 'channel' => $request->gateway]);
                }

                // return redirect()->back()->with("message", "Payment Instruction Generated Successfully");
                // return redirect()->route('payment_voucher_instruction', $breakdown->id)->with("message", "Payment successfully made");
                // return redirect()->back()->with('message', 'Instruction Gateway Unavailable');
            }
            if ($request->gateway == "cheque") {
                $ids = $request->input('id');
                $uuid = $input["reference"] = rand(10000, 99999);
                foreach ($ids as $key => $id) {
                    $updateVoucher = PaymentVoucherBreakdown::where('id', $id)->update(["transaction_id" => $uuid, "bank_lodged" => $input['bank'], "channel" => $input['gateway']]);
                    $breakdown = PaymentVoucherBreakdown::where('id', $id)->first();
                    $journal = Journal::where('uuid', $id)->where('credit', '!=', 0)->first();
                    $gl_code = $journal->gl_code ?? "";
                    // $uuid = $input["reference"];
                    $bankcode = $input['bank'];
                    $amount = $breakdown->contract_amount;
                    $detail = $breakdown->description ?? "All is well";
                    // post to journal
                    postDoubleEntries($uuid, $bankcode, 0, $amount, $detail); // credit the bank account

                    postDoubleEntries($uuid, $gl_code, $amount, 0, $detail); // debit the payable account
                    $transaction_date = now();
                    $particular = $breakdown->description ?? "All is well";
                    $details = $breakdown->description ?? "All is well";
                    $bank_amount = $breakdown->contract_amount;
                    $chq_teller = $breakdown->pvnumber;
                    $payment_mode = $input['gateway'];
                    // post to cashbook

                    // dd($input);
                    $breakdown->update(['payment_status' => 1, 'channel' => $request->gateway]);
                }
                // $result = array(
                //     "transaction_id" => $input["reference"],
                // );
                // return json_encode($result);
                // return redirect()->back()->with('message', 'Cheque Gateway Unavailable');
            }
            $breakdown = PaymentVoucherBreakdown::wherein('id', $request->id)->with(['beneficiary', 'expenses', 'payables', 'PreparerDetail', 'beneficiariesAccount', 'approver', 'bank'])->get();
            return respond(true, 'Data Update successful!', $breakdown, 200);
            // dd($input);
            // return redirect()->back()->with('message', 'Voucher saved successfully');
            // return api_request_response(
            //     "error",
            //     "payment made successfully!",
            //     success_status_code(),
            //     $input
            // );
        } catch (\Exception $exception) {
            // return redirect()->back()->withErrors($exception->getMessage());

            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function approvedPaidVouchers()
    {

        try {
            $category = Category::where('description', 'BANKS')->pluck('id')->toArray();
            // dd($category);
            $check = Category::whereIn('category_parent', $category)->first();
            if ($check) {
                $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
            } else {
                $group = $category;
            }
            $data['accounts'] = Account::whereIn('category_id', $group)->get();
            $data['total'] = PaymentVoucherBreakdown::all();
            $data['vouchers'] = $data['total']->where('approval_status', 1)->where('payment_status', 1);
            // dd($data['vouchers']);
            return respond(true, 'approved paid voucher!', $data, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function approved_payment_list()
    {
        try {

            $data['payments'] = getUserPaymentVoucher()->where('approval_status', 1)->where('payment_status', 1)->with(['beneficiary', 'expenses', 'payables', 'PreparerDetail', 'beneficiariesAccount', 'approver', 'bank'])->orderBy('updated_at', 'DESC')->get();
            return respond(true, ' approved payment voucher', $data, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);

        }

    }
    public function pending_payment_list()
    {
        try {

            $data['payments'] = getUserPaymentVoucher()->where('approval_status', 1)->where('payment_status', 0)->with(['beneficiary', 'bank', 'expenses', 'payables', 'PreparerDetail', 'beneficiariesAccount', 'approver'])->orderBy('updated_at', 'DESC')->get();
            // dd("here");
            return respond(true, 'pending payment voucher!', $data, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }

    }
    public function approve_voucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:payment_voucher_breakdowns,id',

        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }

        $id = $request->id;
        // dd($id);
        //approve voucher
        try {
            $voucher = PaymentVoucherBreakdown::find($id);
            $upd = $voucher->update([
                'approval_status' => 1,
                'approval_date' => now(),
                'approved_by' => auth()->user()->id,
            ]);

            return respond(true, 'Data approved successfully and you can now proceed to make payment for this voucher', $voucher, 200);

            //    return redirect()->back()->with('message','Voucher approved successfully, you can now make payment for this voucher!');
            # code...
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function disapprove_voucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:payment_voucher_breakdowns,id',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Enter the reason for dissapproving', $validator->errors(), 400);
        }

        $id = $request->id;

        try {
            $voucher = PaymentVoucherBreakdown::find($id);
            //$voucher = PaymentVoucherComment::where('description',$request->description)->first();

            // Check if the voucher is already disapproved
            if ($voucher->approval_status == 2) {
                return respond(false, 'The voucher is already disapproved.', null, 400);
            }

            // Disapprove voucher
            $upd = $voucher->update([
                'approval_status' => 2,
                'approval_date' => null,
                'approved_by' => null,
            ]);

            PaymentVoucherComment::create([
                'payment_voucher_breakdown_id' => $id,
                'description' => $request->description,
            ]);

            return respond(true, 'Payment voucher disapproved successfully.', $voucher, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function downloadJournal($type)
    {
        return \Excel::downloadJournal(new PaymentExport, 'journal.' . $type);
    }

    public function postBulkJournal(Request $request)
    {
        try {
            $input = $request->all();
            $validator = Validator::make($request->all(), [
                'beneficiary_id' => 'required|array',
                'beneficiary_id.*' => 'required|exists:beneficiaries,id',
                'beneficiary_account_id' => 'required|array',
                'beneficiary_account_id.*' => 'required|exists:beneficiary_accounts,id',
                'debit_gl' => 'required|array',
                'debit_gl.*' => 'required|exists:accounts,id',
                'credit_gl' => 'required|array',
                'credit_gl.*' => 'required|exists:accounts,id',
                'credit' => 'required|array',
                'credit.*' => 'required',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            DB::beginTransaction();
            $getBen = $input['beneficiary_id'];
            foreach ($getBen as $key => $ben) {
                if ($input['debit_gl'][$key] == $input['credit_gl'][$key]) {
                    DB::rollBack();
                    return respond(false, "You can't credit and debit same account!", null, 400);
                }
            }
            foreach ($getBen as $key => $ben) {
                $amount = str_replace(',', '', $input['credit'][$key]);
                $account = BeneficiaryAccount::where('beneficiary_id', $ben)->first();
                $beneficiary = Beneficiary::where('id', $ben)->first();
                $uuid = $chq_teller = rand();
                $debitCode = $input['debit_gl'][$key];
                $creditCode = $input['credit_gl'][$key];
                $detail = $beneficiary->name;
                $newPaymnet = PaymentVoucherBreakdown::create([
                    "beneficiary_id" => $ben,
                    "beneficiary_account_id" => $request->beneficiary_account_id[$key] ?? "",
                    "approval_status" => 1,
                    "payment_status" => 0,
                    "date" => now(),
                    "prepared_by" => auth()->user()->id,
                    "total_amount" => $amount,
                    "contract_amount" => $amount,
                    "description" => $beneficiary->name,
                    'pvnumber' => $uuid,
                    'expense' => $debitCode,
                    'payable' => $creditCode,
                ]);
                postDoubleEntries($newPaymnet->id, $creditCode, 0, $amount, $detail); // credit the cash account

                postDoubleEntries($newPaymnet->id, $debitCode, $amount, 0, $detail);
                // ; // debit the cash account

            }//
            DB::commit();
            return respond(true, 'Payment Posted Successful!', $input, 200);

        } catch (\Exception $exception) {
            // DB::rollback();
            DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            if (is_int($errorCode)) {
                return respond(false, $errorCode, null, 400);
            } else {
                // dd($exception);
                return respond(false, $exception->getMessage(), null, 400);
            }
        }
    }

    public function postBulkJournalExcel(Request $request)
    {
        try {
            $input = $request->all();
            $validator = Validator::make($request->all(), [

                'uuid' => 'required|exists:upload_payment_vouchers,uuid',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            DB::beginTransaction();
            $uuid = $request->uuid;
            $upload = UploadPaymentVoucher::where("uuid", $uuid)->get();

            foreach ($upload as $key => $ben) {
                $creditCode = $ben->credit_GL_code;
                $debitCode = $ben->debit_GL_code;
                $amount = $ben->amount;
                $date = $ben->transaction_date;
                $description = $ben->description;
                $detail = $ben->description;
                $newPaymnet = PaymentVoucherBreakdown::create([
                    "date" => $date,
                    "prepared_by" => auth()->user()->id,
                    "total_amount" => $amount,
                    "contract_amount" => $amount,
                    "description" => $description,
                    'pvnumber' => $uuid,
                    'expense' => $debitCode,
                    'payable' => $creditCode,
                    'date_lodged' => $date,
                    'lodgement_status' => 1,
                    'bank_name' => $ben->bank_name,
                    'account_name' => $ben->account_name,
                    'account_number' => $ben->account_number,
                    'payment_status' => 0,
                    'approval_status' => 1,
                    'lodge_by' => Auth::user()->id,
                    'prepared_by' => Auth::user()->id,
                ]);
                postDoubleEntries($newPaymnet->id, $creditCode, 0, $amount, $detail, $date); // credit the cash account

                postDoubleEntries($newPaymnet->id, $debitCode, $amount, 0, $detail, $date);
                // ; // debit the cash account
                $ben->delete();
            }
            DB::commit();
            return respond(true, 'Payment Posted Successful!', $input, 200);

        } catch (\Exception $exception) {
            // DB::rollback();
            DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            if (is_int($errorCode)) {
                return respond(false, $exception->errorInfo[2], null, 400);
            } else {
                // dd($exception);
                return respond(false, $exception->getMessage(), null, 400);
            }
        }
    }

    public function deletePaymentVoucher(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:payment_voucher_breakdowns,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $beneficiaries = PaymentVoucherBreakdown::findOrFail($id);
            $beneficiaries->delete();
            return respond(true, 'Payment Voucher archived successfully!', $beneficiaries, 200);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function getDeletedPaymentVouchers()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = PaymentVoucherBreakdown::where('company_id', auth()->user()->company_id)
                ->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restorePaymentVouchers(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:payment_voucher_breakdowns,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = PaymentVoucherBreakdown::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllPaymentVouchers(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = PaymentVoucherBreakdown::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeletePaymentVoucher(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:payment_voucher_breakdowns,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = PaymentVoucherBreakdown::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Invoice not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function forceDeleteAllPaymentVouchers()
    {

        try {

            $accounts = PaymentVoucherBreakdown::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function saveAccountPayable(Request $request)
    {
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
        $validator = Validator::make($data, [
            'description' => 'required',
            'payable_type' => 'required|exists:payable_types,id',
            'invoice_number' => 'required|unique:my_transactions,invoice_number',
            'teller_number' => 'nullable',
            'transaction_date' => 'required|date',
            'supplier_id' => 'required|exists:beneficiaries,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'credit_gl_code' => 'required|numeric|exists:accounts,id',
            'account_id' => 'required|array',
            'account_id.*' => 'required|exists:accounts,id',
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
        $count = array_count_values($items);
        foreach ($items as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Account exists more than once , see row $sum !", [$sum, $amount], 400);
            }
        }
        //dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        try {
            foreach ($items as $key => $item) {
                $glcode = $item;
                $amount = $input['account_amount'][$key];
                //  $uuid = rand();
                // debit the  accounts
                postDoubleEntries($uuid, $glcode, $amount, 0, $detail);
                // $input = $request->except('amount', 'account_amount', 'account_id', 'quantity', 'particulars', 'all_sum','supporting_document');
                $input['amount'] = $request->amount;
                // $amount = $input['amount'];
                if ($request->has('supporting_document')) {
                    $input['document'] = uploadImage($request->supporting_document, "documents");
                }
                // $input['debit'] = $input['amount'];
                $input['transaction_date'] = $request->transaction_date ?? now();
                $payableType = $request->payable_type;
                //post as payable
                insertPayable($amount, $amount, 0, $input['transaction_date'], $detail, $request->invoice_number, 2, $uuid, $input['transaction_date'], "Payable", $request->supplier_id, $input['credit_gl_code'], $input['account_id'][$key], $input['document'] ?? NULL, $request->teller_number ?? NULL, $payableType);
                // $input = $request->all();
            }
            // credit receiveable
            postDoubleEntries($uuid, $request->credit_gl_code, 0, $request->amount, $detail);
            $getCustomer = Beneficiary::find($request->supplier_id);
            $balance = $getCustomer->balance + $amount;
            $amount = $input['amount'];
            $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            //insert ledger
            saveSupplierLedger($getCustomer->id, $uuid, 0, $amount, $detail, $balance);
            DB::commit();
            return respond(true, 'Transaction successful!!', $amount, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function fetchPaidPayables()
    {
        $payable = allTransactions()->where('type', 2)->where('balance', '<', 1)->with('teller')->orderBy('created_at', 'DESC')->paginate(100);
        return respond(true, 'Payables fetched successfully', $payable, 200);
    }
    public function fetchPendingPayables()
    {
        $payable = allTransactions()->where('type', 2)->where('balance', '>', 0)->with('supplier')->orderBy('created_at', 'DESC')->paginate(100);
        return respond(true, 'Payables fetched successfully', $payable, 200);
    }

    public function fetchSoftdelete()
    {
        $deleted = allTransactions()->where('type', 2)->onlyTrashed()->get();
        return respond(true, 'Deleted payables fetched successfully!', $deleted, 201);
    }
    public function pendingPayables()
    {
        $payables = payables()->where('balance', '>', 0)->with(['supplier', 'customer'])->get();
        return respond(true, 'Pending payables fetched!', $payables, 201);
    }
    public function fetchPayables()
    {
        $payable = allTransactions()->where('type', 2)->orderBy('created_at', 'DESC')->paginate(100);
        return respond(true, 'Payables fetched successfully', $payable, 200);
    }

    public function deleteAccountPayable(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:my_transactions,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $transaction = MyTransactions::where('id', $request->id)->first();
        // Check if the authenticated user's company ID matches the transaction's company ID
        if (auth()->user()->company_id !== $transaction->company_id) {
            return respond('error', 'You do not have permission to delete this transaction.', null, 403);
        }

        // Check if the transaction's amount_paid is greater than zero
        if ($transaction->amount_paid > 0) {
            return respond(false, 'You cannot archive this transaction because it has already been paid.', null, 400);
        }
        DB::beginTransaction();
        try {
            // Use the UUID saved during transaction creation for deleting related records
            $uuid = $transaction->uuid;

            // Delete related entries in the journal table
            Journal::where('uuid', $uuid)->delete();

            // Delete related entries in the supplier personal ledger
            SupplierPersonalLedger::where('invoice_number', $uuid)->delete();

            $transaction->delete();
            DB::commit();

            return respond(true, 'Transaction archived successfully', $transaction, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond('error', $e->getMessage(), null, 400);
        }
    }
    public function forceDeleteAccountPayable(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:my_transactions,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $transaction = MyTransactions::withTrashed()->where('id', $request->id)->first();

        DB::beginTransaction();
        try {
            // Use the UUID saved during transaction creation for deleting related records
            $uuid = $transaction->uuid;

            // Delete related entries in the journal table
            Journal::withTrashed()->where('uuid', $uuid)->forceDelete();

            // Delete related entries in the supplier personal ledger
            SupplierPersonalLedger::withTrashed()->where('invoice_number', $uuid)->forceDelete();

            $transaction->forceDelete();
            DB::commit();

            return respond(true, 'Transaction deleted successfully', $transaction, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond('error', $e->getMessage(), null, 400);
        }
    }


    public function restoreAccountPayable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:my_transactions,id',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }

        $transaction = MyTransactions::withTrashed()->where('id', $request->id)->first();

        if (!$transaction->trashed()) {
            return respond(false, 'Transaction not found in archived.', null, 404);
        }
        DB::beginTransaction();
        try {

            $uuid = $transaction->uuid;

            Journal::withTrashed()->where('uuid', $uuid)->restore();

            SupplierPersonalLedger::withTrashed()->where('invoice_number', $uuid)->restore();

            $transaction->restore();
            DB::commit();

            return respond(true, 'Transaction restored successfully', $transaction, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }



}
