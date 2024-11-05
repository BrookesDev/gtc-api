<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetImport;
use App\Customers;
use Carbon\Carbon;
use App\Imports\AssetsImport;
use App\Imports\Import;
use App\Models\Journal;
use App\Models\MyTransactions;
use App\Models\MonthlyDeduction;
use App\Models\CustomerPersonalLedger;

class JournalController extends Controller
{
    public function postJournalEntries(Request $request)
    { {
            $data = $request->all();
            $validator = Validator::make($data, [
                'all_credit' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'all_debit' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'direction' => 'required|array',
                'direction.*' => 'required|numeric',
                'transaction_date' => 'required|array',
                'transaction_date.*' => 'required|date',
                'description' => 'required|array',
                'description.*' => 'required',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            $item = $data['account_id'];
            $allCredit = $data['all_credit'];
            $allDebit = $data['all_debit'];
            $fAllCredit = $allCredit;
            $fAllDebit = $allDebit;
            if ($fAllCredit != $fAllDebit) {
                return respond('error', "Transactions Could Not Be Processed!", null, 400);
            }
            $data['uuid'] = rand();
            $count = array_count_values($item);
            if (count($count) < 2) {
                return respond('error', "Transactions Could Not Be Processed!", null, 400);
            }
            // dd($count);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond('error', "An account exists more than once , see row $sum ", null, 400);
                }
            }

            $uuid = $data['uuid'];

            DB::beginTransaction();
            try {
                foreach ($item as $key => $item) {
                    // dd($data['amount'][$key]);
                    if ($data['direction'][$key] == 1) {
                        $credit = 0;
                        $debit = $data['amount'][$key];
                    } else {
                        $debit = 0;
                        $credit = $data['amount'][$key];
                    }
                    $glcode = $data['account_id'][$key];
                    $detail = $data['description'][$key];
                    $date = $data['transaction_date'][$key];
                    $uuid = $data['uuid'];
                    postDoubleEntries($uuid, $glcode, $debit, $credit, $detail, $date); // credit the  accounts
                }

                DB::commit();
                return respond(true, 'Transaction successful!', $uuid, 201);
            } catch (\Exception $exception) {
                DB::rollBack();
                return respond('error', $exception->getMessage(), null, 400);
            }
        }
    }
    public function posting(Request $request)
    {
    }

    public function openingBalanceBulk(Request $request)
    { {
            $data = $request->all();
            $validator = Validator::make($data, [
                'all_credit' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'all_debit' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'direction' => 'required|array',
                'direction.*' => 'required|numeric',
                'transaction_date' => 'required',
                // 'transaction_date.*' => 'required|date',
                // 'description' => 'required|array',
                // 'description.*' => 'required',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            $item = $data['account_id'];
            $allCredit = $data['all_credit'];
            $allDebit = $data['all_debit'];
            $transaction_date = $data['transaction_date'];
            $fAllCredit = $allCredit;
            $fAllDebit = $allDebit;
            if ($fAllCredit != $fAllDebit) {
                return respond('error', "Transactions Could Not Be Processed!", null, 400);
            }
            $data['uuid'] = rand();
            $count = array_count_values($item);
            if (count($count) < 2) {
                return respond('error', "Transactions Could Not Be Processed!", null, 400);
            }
            // dd($count);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond('error', "An account exists more than once , see row $sum ", null, 400);
                }
            }

            $uuid = $data['uuid'];

            DB::beginTransaction();
            try {
                foreach ($item as $key => $item) {
                    // dd($data['amount'][$key]);
                    if ($data['direction'][$key] == 1) {
                        $credit = 0;
                        $debit = $data['amount'][$key];
                    } else {
                        $debit = 0;
                        $credit = $data['amount'][$key];
                    }
                    $glcode = $data['account_id'][$key];
                    // $detail = $data['description'][$key];
                    $detail = 'Opening Balance';
                    $date = $data['transaction_date'];
                    $uuid = $data['uuid'];
                    // postDoubleEntries($uuid, $glcode, $debit, $credit, $detail, $date); // credit the  accounts
                    $newJournal = new Journal();
                    $newJournal->gl_code = $glcode;
                    $newJournal->debit = $debit;
                    $newJournal->credit = $credit;
                    $newJournal->details = $detail;
                    $newJournal->company_id = auth()->user()->company_id;
                    $newJournal->uuid = $uuid;
                    $newJournal->transaction_date = $transaction_date;
                    $newJournal->created_at = $transaction_date;
                    $newJournal->save();
                }

                DB::commit();
                return respond(true, 'Transaction successful!', $uuid, 201);
            } catch (\Exception $exception) {
                DB::rollBack();
                return respond('error', $exception->getMessage(), null, 400);
            }
        }
    }
    public function downloadTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',

        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $companyId = $request->company_id;
        // dd($companyId);
        // Call the query method with the company_id
        // $customers = $this->query($companyId);

        // dd('here');
        return Excel::download(new AssetImport($companyId), 'Member.xlsx');
    }
    public function importTemplate(Request $request)
    {
        // dd('here');
        $validator = Validator::make($request->all(), [
            'type' => 'required|exists:nominal_ledgers,id',
            'description' => 'required',
            'bank' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'file' => 'required|file|mimes:xls,xlsx',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        // dd('file');
        $mode = $request->mode ?? "saving";
        $type = $request->type;
        $date = $request->transaction_date;
        $description = $request->description;
        $bank = $request->bank;
        $uuid =  rand();
        try {
            \Excel::import(new Import($date, $description, $type, $mode, $bank, $uuid), request()->file('file'));
            // DB::commit();
            return respond(true, "Import successful!!", $request->all(), 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // DB::rollback();
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $errormess = '';
                foreach ($failure->errors() as $error) {
                    $errormess = $errormess . $error;
                }
                $errormessage[] = 'There was an error on Row ' . ' ' . $failure->row() . '.' . ' ' . $errormess;
            }
            return respond(false, $errormessage, $request->all(), 400);
        } catch (\Exception $exception) {
            // DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            // dd($errorCode);
            if (is_int($errorCode)) {
                return respond(false, $exception->errorInfo[2], $request->all(), 400);
            } else {
                // dd($exception);
                return respond(false, $exception->getMessage(), $request->all(), 400);
            }
        }
    }

    public function getMonthlyDeduction()
    {
        $deduction = MonthlyDeduction::where('mode', "saving")->where('company_id', auth()->user()->company_id)->with(['employee', 'account'])->paginate(100);
        return respond(true, "Savings Deduction fetched successful!!", $deduction, 200);
    }
    public function getLoanMonthlyDeduction()
    {
        $deduction = MonthlyDeduction::where('mode', "loan")->where('company_id', auth()->user()->company_id)->with(['employee', 'account'])->paginate(100);
        return respond(true, "Loan Deduction fetched successful!", $deduction, 200);
    }

    public function uploadexcel(Request $request)
    {
        $request->validate([
            'asset_document' => 'required|mimes:xlsx,xls|max:10240', // Adjust the file validation as needed
        ]);

        // Get the uploaded file
        $file = $request->file('asset_document');

        // Process the uploaded Excel file (you might want to validate and store it)
        $import = new AssetImport;
        Excel::import($import, $file);

        // You can add additional logic here, such as saving the data to the database

        // return response()->json(['message' => 'Excel file uploaded and processed successfully']);
        return respond(true, 'Excel file uploaded and processed successfully', $import, 200);
    }

    public function deductionImport()
    {
        $deductions = MonthlyDeduction::whereNotNull('uuid')->where('company_id', getCompanyid())->select('uuid')->distinct()->pluck('uuid')->toArray();
        $journals = Journal::wherein('uuid', $deductions)->select('uuid')->distinct()->with('deductions')->get();
        $journals->map(function ($journal) {
            // You can add more fields or manipulate existing ones here
            $ledgerEntries = Journal::where('uuid', $journal->uuid)->where('credit', '!=', 0)->first();
            $journal->amount = $ledgerEntries->credit; // Example of adding a new column
            $journal->transaction_date = $ledgerEntries->transaction_date; // Example of adding a new column
            $journal->created_at = $ledgerEntries->created_at; // Example of adding a new column
            return $journal;
        });
        return respond(true, 'Available postings to be reversed fetched successfully', $journals, 200);
    }

    public function reverseDeduction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:journals,uuid',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $uuid = $request->uuid;
            $checkJournals = Journal::where('uuid', $uuid)->get();
            //count to see if it has already been reversed
            $count = $checkJournals->count();
            if ($count > 2) {
                return respond(false, "Deduction already reversed!", $uuid, 400);
            }
            foreach ($checkJournals as $single) {
                $credit = $single->credit == 0 ? $single->debit : 0;
                $debit = $single->debit == 0 ? $single->credit : 0;
                // check if the data is credit  and debit else credit
                postDoubleEntries($uuid, $single->gl_code, $credit, $debit, "reversal $single->details", $single->transaction_date);
            }
            // interact with deductions table with the uuid
            $deductions = MonthlyDeduction::where('uuid', $uuid)->get();
            foreach ($deductions as $deduction) {
                $comparisonTimestamp = $deduction->created_at;
                // interact with the member ledger straight up
                $ledgerEntries = CustomerPersonalLedger::where('customer_id', $deduction->member_id)
                    ->where('description', $deduction->description)
                    ->where(function ($query) use ($deduction) {
                        $query->where('debit', $deduction->amount)
                            ->orWhere('credit', $deduction->amount);
                    })
                    ->get();
                $ledgerEntry = $ledgerEntries->filter(function ($entry) use ($comparisonTimestamp) {
                    $entryTimestamp = Carbon::parse($entry->created_at);
                    return $entryTimestamp->diffInSeconds($comparisonTimestamp) <= 3;
                })->first();
                $credit = $ledgerEntry->credit == 0 ? $ledgerEntry->debit : 0;
                $debit = $ledgerEntry->debit == 0 ? $ledgerEntry->credit : 0;
                $balance = $credit - $debit;
                saveCustomerLedger($ledgerEntry->customer_id, NULL, $debit, $credit, "reversal $ledgerEntry->description",  $balance);

                $deduction->delete();
            }
            DB::commit();
            return respond(true, 'Transactions reversed successfully', $uuid, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), $request->all(), 400);
        }
    }

    public function ledgerPostings(){
        $postings = getJournalFilter()->with('account')->orderBy('created_at','DESC')->paginate(100);
        return respond(true, 'Posting fetched successfully', $postings, 200);
    }

    public function postReceipt(Request $request){
        try {
            // DB::beginTransaction();
            //let's validate the incoming request
            $validator = Validator::make($request->all(), [
                'particulars' => 'required',
                'description' => 'required',
                'payment_mode' => 'required|numeric',
                'transaction_date' => 'required|date',
                'gl_code' => 'required|exists:accounts,id',
                'teller_no' => 'required_if:payment_mode,2',
                'total_amount' => 'required|numeric',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'breakdown_amount' => 'required|array',
                'breakdown_amount.*' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->except('_token');
            $input['teller_number'] = $request->teller_no;
            $item = $input['account_id'];
            $input['uuid'] = rand();
            $count = array_count_values($item);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond(false, "An account exists more than once , see row $sum !", [$sum], 400);
                }
            }
            $amount = $input['total_amount'];
            $arrayAmount = $input['breakdown_amount'];
            $sum = array_sum($arrayAmount);
            if ($sum != $amount) {
                return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
            }
            // dd($sum);
            $input['amount'] = $amount;
            $detail = $request->description;
            $input['transaction_date'] =  $transactionDate = $request->transaction_date ?? now();
            $description = $input['description'];
            $gl_code = $request->gl_code;
            $chq_teller = $request->teller_no ??  $input['uuid'];
            $uuid = $input['uuid'];
            foreach ($item as $key => $item) {
                $glcode = $input['account_id'][$key];
                $amountPaid = $input['breakdown_amount'][$key];
                postDoubleEntries($uuid, $glcode, 0, $amountPaid, $detail, $transactionDate); // credit the  accounts
            }
            $particulars = $request->particulars;
            $mode = $request->payment_mode;
            postDoubleEntries($uuid, $gl_code, $amount, 0, $detail, $transactionDate); // debit the  accounts
            insertReceipt($amount, 0, 0, $transactionDate, $description, $chq_teller, 3, $uuid, $transactionDate,"Receipt", $mode,$chq_teller,$particulars);
            return respond(true, 'Transaction successful!', $input, 201);
            // DB::commit();
        } catch (\Exception $e) {
            // DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteReceipt(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:my_transactions,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $transaction = MyTransactions::where('id', $request->id)->first();

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

            $transaction->delete();
            DB::commit();

            return respond(true, 'Transaction archived successfully', $transaction, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond('error', $e->getMessage(), null, 400);
        }
    }
    public function forceDeleteReceipt(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:my_transactions,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $transaction = MyTransactions::where('id', $request->id)->first();

        DB::beginTransaction();
        try {
            // Use the UUID saved during transaction creation for deleting related records
            $uuid = $transaction->uuid;

            // Delete related entries in the journal table
            Journal::where('uuid', $uuid)->forceDelete();

            $transaction->forceDelete();
            DB::commit();

            return respond(true, 'Transaction permanently deleted', $transaction, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond('error', $e->getMessage(), null, 400);
        }
    }
    public function postReceivables(Request $request){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'debit' => 'required|exists:accounts,id',
            'credit' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'date_of_invoice' => 'required|date',
            'amount' => 'required|numeric',
            'invoice_number' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $transactionDate = $request->transaction_date;
            $invoiceDate = $request->date_of_invoice;
            $debitGl = $request->debit;
            $creditGl = $request->credit;
            $uuid =  rand();
            $credit = $request->amount;
            $debit = 0 ;
            $details = $request->description;
            $invoice = $request->invoice_number;
            postDoubleEntries($uuid, $debitGl, $credit, $debit, $details, $transactionDate);
            postDoubleEntries($uuid, $creditGl, $debit, $credit, $details, $transactionDate);
            insertTransaction($credit,0,0,$transactionDate,$details,$invoice,1,$uuid,$invoiceDate,"Posting");
            DB::commit();
            return respond(true, 'Transaction successful!', $uuid, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function postExpenses(Request $request){
        try {
            // DB::beginTransaction();
            //let's validate the incoming request
            $validator = Validator::make($request->all(), [
                'particulars' => 'required',
                'description' => 'required',
                'payment_mode' => 'required|numeric',
                'transaction_date' => 'required|date',
                'gl_code' => 'required|exists:accounts,id',
                'teller_no' => 'required_if:payment_mode,2',
                'total_amount' => 'required|numeric',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'breakdown_amount' => 'required|array',
                'breakdown_amount.*' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->except('_token');
            $input['teller_number'] = $request->teller_no;
            $item = $input['account_id'];
            $input['uuid'] = rand();
            $count = array_count_values($item);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond(false, "An account exists more than once , see row $sum !", [$sum], 400);
                }
            }
            $amount = $input['total_amount'];
            $arrayAmount = $input['breakdown_amount'];
            $sum = array_sum($arrayAmount);
            if ($sum != $amount) {
                return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
            }
            // dd($sum);
            $input['amount'] = $amount;
            $detail = $request->description;
            $input['transaction_date'] =  $transactionDate = $request->transaction_date ?? now();
            $description = $input['description'];
            $gl_code = $request->gl_code;
            $chq_teller = $request->teller_no ??  $input['uuid'];
            $uuid = $input['uuid'];
            foreach ($item as $key => $item) {
                $glcode = $input['account_id'][$key];
                $amountPaid = $input['breakdown_amount'][$key];
                postDoubleEntries($uuid, $glcode, $amountPaid, 0, $detail, $transactionDate); // credit the  accounts
            }
            $particulars = $request->particulars;
            $mode = $request->payment_mode;
            postDoubleEntries($uuid, $gl_code, 0, $amount, $detail, $transactionDate); // debit the  accounts
            insertReceipt($amount, 0, 0, $transactionDate, $description, $chq_teller, 4, $uuid, $transactionDate,"Receipt", $mode,$chq_teller,$particulars);
            return respond(true, 'Transaction successful!', $input, 201);
            // DB::commit();
        } catch (\Exception $e) {
            // DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function postExpenses1(Request $request){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'debit' => 'required|exists:accounts,id',
            'credit' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'date_of_invoice' => 'required|date',
            'amount' => 'required|numeric',
            'invoice_number' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $transactionDate = $request->transaction_date;
            $invoiceDate = $request->date_of_invoice;
            $debitGl = $request->debit;
            $creditGl = $request->credit;
            $uuid =  rand();
            $credit = $request->amount;
            $debit = 0 ;
            $details = $request->description;
            $invoice = $request->invoice_number;
            postDoubleEntries($uuid, $debitGl, $credit, $debit, $details, $transactionDate);
            postDoubleEntries($uuid, $creditGl, $debit, $credit, $details, $transactionDate);
            insertTransaction($credit,0,0,$transactionDate,$details,$invoice,4,$uuid,$invoiceDate,"Expenses");
            DB::commit();
            return respond(true, 'Transaction successful!', $uuid, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchReceivables(){
        $receivable = allTransactions()->where('type', 1)->orderBy('created_at','DESC')->paginate(100);
        return respond(true, 'Receivables fetched successfully', $receivable, 200);
    }
    public function fetchPendingReceivables(){
        $receivable = allTransactions()->where('type', 1)->where('balance', '>', 0)->orderBy('created_at','DESC')->paginate(100);
        return respond(true, 'Receivables fetched successfully', $receivable, 200);
    }
    public function fetchPayables(){
        $payable = allTransactions()->where('type', 2)->orderBy('created_at','DESC')->paginate(100);
        return respond(true, 'Payables fetched successfully', $payable, 200);
    }
    public function fetchReceipts(){
        $receipts = allTransactions()->where('type', 3)->orderBy('created_at','DESC')->with('mode')->paginate(100);
        return respond(true, 'Receipts fetched successfully', $receipts, 200);
    }
    public function receiptFetchSoftdelete(){
        $receipts = allTransactions()->where('type', 3)->onlyTrashed()->orderBy('created_at','DESC')->with('mode')->get();
        return respond(true, 'Receipts fetched successfully', $receipts, 200);
    }

    public function restoreReceipt(Request $request)
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

            $transaction->restore();
            DB::commit();

            return respond(true, 'Transaction restored successfully', $transaction, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function fetchAmountReceivables(){
        $receipts = allTransactions()->where('type', 1); 
        $totalpaid = $receipts->sum('amount_paid');
        $totalreceivables = $receipts->sum('balance');
        $response = [
            'total_paid' => $totalpaid,
            'total_receivables' => $totalreceivables,
        ];
        return respond(true, 'Receipts fetched successfully', $response, 200);
    }
    public function fetchExpenses(){
        $expenses = allTransactions()->where('type', 4)->orderBy('created_at','DESC')->paginate(100);
        return respond(true, 'Expenses fetched successfully', $expenses, 200);
    }
}
