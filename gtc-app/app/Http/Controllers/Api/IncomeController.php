<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\Receipt;
use App\Models\Mode_of_Saving;
use App\Models\Currency;
use App\Exports\IncomeExport;
use App\Imports\IncomeImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index()
    {
        $queryId = getCompanyid();
        // dd($queryId);
        try {
            $category = Category::where('name', 'LIKE', 'ASSETS')->pluck('id')->toArray();
            // dd($category);
            $check = Category::whereIn('category_parent', $category)->first();
            if ($check) {
                $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
            } else {
                $group = $category;
            }
            $income = Category::where('name', 'LIKE', 'INCOME')->pluck('id')->toArray();
            $incomeGroup = Category::whereIn('category_parent', $income)->pluck('id')->toArray();
            $data['incomes'] = Account::whereIn('category_id', $incomeGroup)->where('user_id', $queryId)->get();
            $data['currencies'] = Currency::all();
            $data['type'] = [
                ['name' => 'Bank Transfer', 'value' => 'bank'],
                ['name' => 'Cheque', 'value' => 'cheque'],
                ['name' => 'Teller', 'value' => 'teller'],
                ['name' => 'Cash', 'value' => 'cash']
            ];
            return respond(true, 'Resources needed for creating income!', $data, 201);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getPaymentMethod()
    {
        $type = Mode_of_Saving::get();
        // [
        //     ['name' => 'Bank Transfer', 'id' => 1],
        //     ['name' => 'Cheque', 'id' => 2],
        //     ['name' => 'Teller', 'id' => 3],
        //     ['name' => 'Cash', 'id' => 4]
        // ];
        return respond(true, 'payment method fetched successfully!', $type, 201);
    }

    public function TestingPlan()
    {
        $type = getplanID();
        // $getNumber = $type->no_of_users;

        $getNumber = $type->no_of_accounts;

        // Count the number of existing accounts
        $currentNumberOfAccounts = Account::where('company_id', getCompanyid())->count();
        if ($currentNumberOfAccounts >= $getNumber) {
            return respond('error', 'You have reached the maximum number of accounts allowed for your plan.', [$getNumber, $currentNumberOfAccounts], 400);
        }
        // [
        //     ['name' => 'Bank Transfer', 'id' => 1],
        //     ['name' => 'Cheque', 'id' => 2],
        //     ['name' => 'Teller', 'id' => 3],
        //     ['name' => 'Cash', 'id' => 4]
        // ];
        return respond(true, 'payment method fetched successfully!', $type, 201);
    }
    public function allCurrencies()
    {
        $currencies = Currency::all();
        return respond(true, 'currencies fetched successfully !', $currencies, 201);
    }

    public function list()
    {
        $transactions = getUserReceipt()->where('type', 1)->with('user')->get();
        return respond(true, 'All receipts fetched successfully!', $transactions, 201);
    }
    public function totalIncome()
    {
        $transactions = getUserReceipt()->where('type', 1)->sum('amount');
        return respond(true, 'Sum of total income !', $transactions, 201);
    }
    public function totalLodged()
    {
        $transactions = getUserReceipt()->where('type', 1)->where('lodgement_status', 1)->sum('amount');
        return respond(true, 'Sum of total lodged !', $transactions, 201);
    }
    public function totalPending()
    {
        $transactions = getUserReceipt()->where('type', 1)->where('lodgement_status', 0)->sum('amount');
        return respond(true, 'Sum of total lodged !', $transactions, 201);
    }
    public function totalExpenses()
    {
        $transactions = getUserReceipt()->where('type', 2)->sum('amount');
        return respond(true, 'Sum of total lodged !', $transactions, 201);
    }
    public function listExpenses()
    {
        $transactions = getUserReceipt()->where('type', 2)->with('user')->get();
        return respond(true, 'All receipts fetched successfully!', $transactions, 201);
    }
    public function listPending()
    {
        $transactions = getUserReceipt()->where('type', 1)->where('lodgement_status', 0)->orderBy('created_at', 'desc')->get();
        return respond(true, 'All pending receipts fetched successfully!', $transactions, 201);
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();
            //let's validate the incoming request
            $validator = Validator::make($request->all(), [
                'teller_no' => 'required',
                'particulars' => 'required',
                'description' => 'required',
                'payment_mode' => 'required',
                'transaction_date' => 'required|date',
                'gl_code' => 'required|exists:accounts,id',
                'total_amount' => 'required|numeric',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'breakdown_amount' => 'required|array',
                'breakdown_amount.*' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 400);
            }
            // dd()
            $input = $request->except('_token');
            // return respond(false,'Transaction successful!',[$request->all(),array_sum($input['breakdown_amount'])],400);
            $input['teller_number'] = $request->teller_no;
            $item = $input['account_id'];
            $input['uuid'] = $voucher = rand();
            $input['voucher_number'] = $input['uuid']; //$voucher = rand();
            $input['lodgement_status'] = 0;
            $count = array_count_values($item);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond(false, "An account exists more than once , see row $sum !", [$sum], 400);
                }
            }
            $input['initial_amount'] = $request->total_amount;
            $currency = $request->currency;
            $amount = $input['initial_amount'];
            $arrayAmount = $input['breakdown_amount'];
            $sum = array_sum($arrayAmount);
            if ($sum != $amount) {
                return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
            }
            // dd($sum);
            $input['amount'] = $amount;
            $detail = $request->description;
            $transaction_date = now();
            $input['transaction_date'] = $request->transaction_date ?? now();
            $bank_amount = $input['amount'];
            $gl_code = $request->gl_code;
            $chq_teller = $request->teller_no;
            $uuid = $input['uuid'];
            $transactionDate = $request->transaction_date ?? now();
            foreach ($item as $key => $item) {
                $glcode = $input['account_id'][$key];
                $amount_paid = $input['breakdown_amount'][$key];
                $uuid = $input['uuid'];
                if ($request->type == 2) {
                    postDoubleEntries($uuid, $glcode, $amount_paid, 0, $detail, $transactionDate); // debit the  accounts
                } else {
                    postDoubleEntries($uuid, $glcode, 0, $amount_paid, $detail, $transactionDate); // credit the  accounts
                }
            }

            $gl_code = $request->gl_code;
            $debit = $input['amount'];
            $uuid = $input['uuid'];
            // dd($debit);
            // dd($debit);
            if ($request->type == 2) {
                postDoubleEntries($uuid, $gl_code, 0, $debit, $detail, $transactionDate); // credit the cash account
            } else {
                postDoubleEntries($uuid, $gl_code, $debit, 0, $detail, $transactionDate); // debit the cash account
            }
            if (in_array($request->payment_mode, ["teller", "bank", "direct payment"])) {
                $input['lodge_by'] = Auth::user()->id;
                $input['date_lodged'] = now();
                $input['bank_lodged'] = $gl_code;
                $input['lodgement_status'] = 1;
            }
            // dd($input);
            $receipt = Receipt::create($input);
            if ($request->type != 2) {
                if (in_array($request->payment_mode, ["teller", "bank", "direct payment"])) {

                    postDoubleEntries($uuid, $glcode, 0, $bank_amount, $detail, $transactionDate); // credit the cash account

                    postDoubleEntries($uuid, $glcode, $bank_amount, 0, $detail, $transactionDate); // debit the cash account

                    // $payment_mode = $request->payment_mode;
                    // postToCashbook($transaction_date, $input['particulars'], $detail, $bank_amount, $gl_code, $chq_teller, $uuid, $payment_mode, $debit) ;
                }
            }
            receivablesInsertion($amount, $request->teller_no, 2, $detail , $transactionDate , $request->payment_mode);
            return respond(true, 'Transaction successful!', $input, 201);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function lodgeToBank(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'chq_teller' => 'required',
                'bank_account_id' => 'required|exists:accounts,id',
                'uuid' => 'required|array',
                'uuid.*' => 'required|exists:receipts,uuid',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $input = $request->all();
            // dd($input);
            $uuid = $input['uuid'];
            $bankcode = $request->bank_account_id;
            // dd($input);
            foreach ($uuid as $key => $uuid) {
                $txns = Receipt::where('uuid', $uuid)->first();
                if ($txns->lodgement_status != 1) {
                    $glcode = $txns->gl_code;
                    $amount = $txns->amount;
                    $detail = $txns->description;
                    $currentAmount = $txns->currency_amount;
                    ///txn begin
                    postDoubleEntries($uuid, $glcode, 0, $amount, $detail); // credit the cash account

                    postDoubleEntries($uuid, $bankcode, $amount, 0, $detail); // debit the bank account

                    $transaction_date = Carbon::now();
                    $particular = $txns->particulars;
                    $details = $txns->description;
                    $bank_amount = $txns->amount;
                    $gl_code = $request->bank_account_id;
                    $chq_teller = $request->chq_teller ?? "";
                    $payment_mode = $txns->payment_mode;
                    //save to cashbook
                    // $postCashbook = new CashbookController;
                    // $postCashbook->postToCashbook($transaction_date, $particular, $details, $bank_amount, $gl_code, $chq_teller, $uuid, $payment_mode) ; // save the bank lodge to cashbook

                    $txns->update(['lodgement_status' => 1, 'bank_lodged' => $bankcode, 'date_lodged' => Carbon::now(), 'lodge_by' => Auth::user()->id]);
                }
            }
            DB::commit();
            return respond(true, 'Trasaction successful!', $uuid, 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }
    public function download()
    {
        $type = "xlsx";
        $template = \Excel::download(new IncomeExport, 'income.' . $type);
        return $template;
        return respond(true, 'Income template downloaded successfully!', $template, 201);
        $template = \Excel::download(new IncomeExport, 'income.' . $type);
        $filePath = $template->getFile();
        $fileResponse = new BinaryFileResponse($filePath);

        // Set headers to force download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return respond(true, 'Income template downloaded successfully!', $fileResponse->withHeaders($headers), 201);
    }

    public function postUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xls,xlsx',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(request()->file('file'));
        $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $countdata = count($sheetData) - 1;
        // dd($countdata,$request->all());
        if ($countdata < 1) {
            return respond(false, 'Excel File Is Empty! Populate And Upload! ', $countdata, 400);

        }
        DB::beginTransaction();
        try {
            \Excel::import(new IncomeImport(), request()->file('file'));
            DB::commit();

            return respond(True, 'Import successful!!! ', $countdata, 200);

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

            return respond(false, $validator->errors(), $errormessage, 400);
        } catch (\Exception $exception) {
            // DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            if (is_int($errorCode)) {
                return respond(false, $validator->errors(), $errorCode, 400);
            } else {
                // dd($exception);
                return respond(false, $validator->errors(), $errorCode, 400);
            }
        }
    }
}
