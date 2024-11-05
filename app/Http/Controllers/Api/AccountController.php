<?php

namespace App\Http\Controllers\Api;

use function App\Helpers\api_request_response;
use function App\Helpers\generate_random_password;
use function App\Helpers\generate_uuid;
use function App\Helpers\unauthorized_status_code;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Account;
use App\Models\Category;
use App\Models\Classes;
use Carbon\Carbon;
use App\Models\Import\AccountImport;
use QrCode;
use App\Models\Receipt;
use App\Models\Journal;
use App\Models\TempJournal;
use App\Models\company;
use App\Role;
use App\Imports\OpeningBalance;
use App\Imports\JournalEntryLoad;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Cashbook;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Models\AccountStatus;
use App\Models\StatusDetails;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function getAccountByClassId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        $id = $request->class_id;
        $data = Account::where('company_id', getCompanyid())->where('class_id', $id)->get();
        return respond(true, 'List of accounts by class id fetched!', $data, 201);
    }
    public function getAccountByCategoryId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        $id = $request->category_id;
        $data = Account::where('company_id', getCompanyid())->where('category_id', $id)->get();
        return respond(true, 'List of accounts by category id fetched!', $data, 201);
    }
    public function getCashBank()
    {
        $data = Account::where('company_id', getCompanyid())->whereIn('sub_category_id', [11, 2])->get();
        return respond(true, 'List of bank and cash accounts!', $data, 201);
    }

    public function uploadOpeningBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|max:2048', // Ensure file is Excel and within size limit
            'transaction_date' => 'required|date'
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $uuid = rand();
            $date = $request->transaction_date;
            $import = new OpeningBalance($uuid, $date);
            Excel::import($import, $request->file('file'));
            // dd($date);
            DB::commit();
            $data = TempJournal::where('uuid', $uuid)->get();
            return respond(true, "File uploaded successfully!", $data, 200);
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

    public function uploadJournalEntries(Request $request)
    {
        $validator = Validator::make($request->all(), [


            'file' => 'required|mimes:xlsx,xls|max:2048', // Ensure file is Excel and within size limit
            // 'transaction_date' => 'required|date',
            // 'description' => 'required',
            // 'debit_gl' => 'required',
            // 'credit_gl' => 'required',
            // 'amount' => 'required',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $uuid = rand();
            $import = new JournalEntryLoad($uuid);
            Excel::import($import, $request->file('file'));
            // dd($date);
            $data = TempJournal::where('uuid', $uuid)->get();
            DB::commit();
            return respond(true, "File uploaded successfully!", $data, 200);
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
    public function getAccountBySubCategoryId(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'sub_category_id' => 'required|exists:categories,id',
        // ]);
        // if ($validator->fails()) {
        //     return respond(false,'Error!', $validator->errors(),400);
        // }
        $id = $request->sub_category_id;
        if (is_array($id)) {
            $data = Account::where('company_id', getCompanyid())->whereIn('sub_category_id', $id)->get();
        } else {
            $data = Account::where('company_id', getCompanyid())->where('sub_category_id', $id)->get();
        }
        return respond(true, 'List of accounts by sub category id fetched!', $data, 201);
    }
    public function getAccountByMode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        if ($request->type == 3 || $request->type == 1 || $request->type == "direct payment") {

            $data = Account::where('company_id', getCompanyid())->where('sub_category_id', 11)->get();
        } else {
            $data = Account::where('company_id', getCompanyid())->where('sub_category_id', 2)->get();
        }
        return respond(true, 'List of accounts fetched!', $data, 201);
    }
    public function createIncome()
    {
        $createincome = createIncome::get();
        return respond(true, 'List of categorie fetched!', $createincome, 201);
    }
    public function index(Request $request)
    {
        $id = getCompanyid();
        //dd($id);
        if($request->has('gl_name')) {
            $accounts = Account::where('company_id', $id)
            ->where('gl_name', $request->gl_name)
            ->with(['class', 'category', 'Subcategory'])->orderBy('class_id','ASC')->orderBy('category_id','ASC')->orderBy('sub_category_id','ASC')->get();
        }
        $accounts = Account::where('company_id', $id)
        ->with(['class', 'category', 'Subcategory'])->orderBy('class_id','ASC')->orderBy('category_id','ASC')->orderBy('sub_category_id','ASC')->get();

        return respond(true, 'List of Accoounts fetched!', $accounts, 201);
    }

    public function addNewAccount(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $validator = Validator::make($data, [
                'sub_category_id' => 'required|exists:sub_categories,id|numeric',
                'gl_name' => ['required', 'string', 'max:255'],
                // 'direction' => ['required', 'numeric'],
                'transaction_date' => ['required', 'date'],
                // 'opening_balance' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/']
            ]);
            // dd($last);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }


            // $type = getplanID();
            // $getNumber = $type->no_of_accounts;

            // // Count the number of existing accounts
            // $currentNumberOfAccounts = Account::where('company_id', getCompanyid())->count();

            // // Check if the current number of accounts exceeds the limit
            // if ($currentNumberOfAccounts >= $getNumber) {
            //     return respond('error', 'You have reached the maximum number of accounts allowed for your plan.', null, 400);
            // }

            $getSubCategoryDetails = SubCategory::find($request->sub_category_id);
            $data['class_id'] = $getClassId = $getSubCategoryDetails->class_id;
            $data['category_id'] = $getCategoryId = $getSubCategoryDetails->category_id;
            $test = Account::where('sub_category_id', $request->sub_category_id)->count();
            $data['gl_code'] = $data['class_id'] . '' . $data['category_id'] . '' . $request->sub_category_id . "0" . '' . $test + 1;
            // dd($input['gl_code'] );
            // check if selected category is credit or debit
            $class = Classes::find($getClassId);
            $data['direction'] = $class->status;
            $data['balance'] = $request->opening_balance;
            // dd($data['class_id'], $data['category_id'],  $getSubCategoryDetails);
            $balance = $request->opening_balance;
            // dd($data);
            $newValue = Account::create($data);
            $detail = "Opening Balance";
            $uuid = rand();
            $glcode = $newValue->id;
            // dd($class);
            if($request->opening_balance > 0){
                if ($class->status == 1) {
                    //debit
                    postDoubleEntries($uuid, $glcode, $balance, 0, $detail, $request->transaction_date);
                } else {
                    //credit
                    postDoubleEntries($uuid, $glcode, 0, $balance, $detail, $request->transaction_date);
                }
            }

            DB::commit();
            return respond(true, 'New Account added successfully!', $newValue, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), $data, 400);
            // return response()->json(['message' => $e->getMessage()], 400);
        }

    }

    public function addMultipleAccounts(Request $request)
{
    DB::beginTransaction();
    try {
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'required|array',
            'sub_category_id.*' => 'required|exists:sub_categories,id|numeric',
            'gl_name' => ['required', 'array'],
            'gl_name.*' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'array'],
            'direction.*' => ['required', 'numeric'],
            'transaction_date' => ['required', 'array'],
            'transaction_date.*' => ['required', 'date'],
            'opening_balance' => ['required', 'array'],
            'opening_balance.*' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/']
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $data = $request->all();
        $accounts = [];

        foreach ($data['sub_category_id'] as $index => $subCategoryId) {
            // Fetch SubCategory details
            $getSubCategoryDetails = SubCategory::find($subCategoryId);
            $class_id = $getSubCategoryDetails->class_id;
            $category_id = $getSubCategoryDetails->category_id;

            // Generate gl_code
            $test = Account::where('sub_category_id', $subCategoryId)->count();
            $gl_code = $class_id . '' . $category_id . '' . $subCategoryId . "0" . ($test + 1);

            // Determine the direction
            $direction = $data['direction'][$index] == 1 ? 1 : 2;
            $balance = $data['opening_balance'][$index];

            // Create the account data
            $accountData = [
                'sub_category_id' => $subCategoryId,
                'gl_name' => $data['gl_name'][$index],
                'direction' => $direction,
                'transaction_date' => $data['transaction_date'][$index],
                'opening_balance' => $balance,
                'class_id' => $class_id,
                'category_id' => $category_id,
                'gl_code' => $gl_code,
                'balance' => $balance,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Save the account
            $newValue = Account::create($accountData);
            $uuid = rand();
            $glcode = $newValue->id;
            $detail = "Opening Balance";

            // Post double entries
            if ($direction == 1) {
                // debit
                postDoubleEntries($uuid, $glcode, $balance, 0, $detail, $data['transaction_date'][$index]);
            } else {
                // credit
                postDoubleEntries($uuid, $glcode, 0, $balance, $detail, $data['transaction_date'][$index]);
            }

            $accounts[] = $newValue;
        }

        DB::commit();
        return respond(true, 'New Accounts added successfully!', $accounts, 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return respond(false, $e->getMessage(), null, 400);
    }
}


    public function deleteAccount(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $accountId = $request->id;
            $account = Account::findOrFail($accountId);

            $journal = Journal::where('gl_code',$account->gl_code)->first();
            if($journal){
                return respond(false, 'Account cannot be deleted because it already has a posting', $journal, 401);
            }
            // if ($account->journals()->exists()) {
            //     return respond(false, 'Account cannot be deleted because it already has posting', null, 400);
            // }

            $account->delete();

            DB::commit();
            return respond(true, 'Account deleted successfully!', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Account::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived account permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Account is not yet deleted!', null, 400);
        } else {
            return respond(false, 'Account not found!', null, 404);
        }
    }

    public function fetchSoftdelete()
    {
        $deleted = Account::where('company_id',auth()->user()->company_id)->onlyTrashed()
        ->with(['class', 'category', 'Subcategory'])->orderBy('class_id','ASC')->orderBy('category_id','ASC')->orderBy('sub_category_id','ASC')->get();
        return respond(true, 'Archived accounts fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Account::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived account restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Account is not yet deleted!', null, 400);
        } else {
            return respond(false, 'Account not found!', null, 404);
        }
    }



    public function getAllBankAccounts()
    {
        try {
            $category = Category::where('description', 'Like', '%BANK%')->pluck('id')->toArray();
            // dd($category);
            $check = Category::whereIn('category_parent', $category)->first();
            if ($check) {
                $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
            } else {
                $group = $category;
            }
            $accounts = Account::whereIn('category_id', $group)->get();
            return response(
                [
                    "status" => "success",
                    "data" => $accounts,
                    "message" => 'Bank Accounts fetched Successfully'
                ],
                201
            );
        } catch (\Exception $e) {
            return response([
                "status" => "error",
                "data" => null,
                "message" => $e->getMessage()
            ], 401);
        }
    }

    public function doubleEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            set_time_limit(8000000);
            ini_set('max_execution_time', '5000');
            $data = $request->all();
            // Access the 'getDetails' and 'getInvoice' data
            $getDetails = $data['getDetails'];
            $getInvoice = $data['getInvoice'];
            // credit the accounts one by one
            foreach ($getDetails as $details) {
                $newJournal = new Journal();
                $newJournal->gl_code = $details['account_id'];
                $newJournal->debit = 0;
                $newJournal->credit = $details['amount'];
                $newJournal->details = $details['description'];
                $newJournal->uuid = $details['transaction_id'];
                $newJournal->save();
            }
            // debit receivable
            foreach ($getInvoice as $invoice) {
                $newJournal = new Journal();
                $newJournal->gl_code = 39;
                $newJournal->debit = $invoice['amount'];
                $newJournal->credit = 0;
                $newJournal->details = $invoice['description'];
                $newJournal->uuid = $invoice['transaction_id'];
                $newJournal->save();
            }

            DB::commit();
            return response([
                "status" => "success",
                "data" => $data,
                "message" => "Posted to journal successfully"
            ], 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return response([
                "status" => "error",
                "data" => $data,
                "message" => $exception->getMessage()
            ], 401);
        }

    }
    public function postCashBook(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataArray = $request->all();

            // Loop through the array to process the data
            $code = $dataArray[0]['account_code'];
            foreach ($dataArray as $key => $data) {
                // Access the values in each data item
                if ($key != 0) {
                    // debit account one by one
                    // $newJournal = new Journal();
                    // $newJournal->gl_code = $data['account_code'];
                    // $newJournal->debit = $data['amount'];
                    // $newJournal->credit = 0;
                    // $newJournal->details =$data['details'];
                    // $newJournal->uuid = $data['transaction_id'];
                    // $newJournal->save();
                    // credit receivable
                    $newJournal = new Journal();
                    $newJournal->gl_code = $data['account_code'] == "payment" ? 39 : $data['account_code'];//39;
                    $newJournal->debit = 0;
                    $newJournal->credit = $data['amount'];
                    $newJournal->details = $data['details'];
                    $newJournal->uuid = $data['transaction_id'];
                    $newJournal->save();
                    // post to cashbook
                    $cashbook = new Cashbook();
                    $cashbook->transaction_date = now();
                    $cashbook->particular = $data['particular'] ?? "Amos";
                    $cashbook->details = $data['details'];
                    $cashbook->bank = $data['amount'];
                    $cashbook->gl_code = $code ?? 38;
                    // $cashbook->chq_teller = $data['transaction_id'];
                    $cashbook->uuid = $data['transaction_id'];
                    $cashbook->save();

                } else {
                    // debit bank
                    $newJournal = new Journal();
                    $newJournal->gl_code = $code ?? 38;
                    $newJournal->debit = $data['amount'];
                    $newJournal->credit = 0;
                    $newJournal->details = $data['details'];
                    $newJournal->uuid = $data['transaction_id'];
                    $newJournal->save();
                }
            }


            DB::commit();
            return response([
                "status" => "success",
                "data" => $data,
                "message" => "Posted to cashbook successfully"
            ], 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return response([
                "status" => "error",
                "data" => null,
                "message" => $exception->getMessage()
            ], 401);
        }

    }
    public function postPayroll(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataArray = $request->all();

            // Loop through the array to process the data
            $code = $dataArray[0]['account_code'];
            foreach ($dataArray as $key => $data) {
                // Access the values in each data item
                if ($key != 0) {
                    // credit receivable
                    $newJournal = new Journal();
                    $newJournal->gl_code = $data['account_code'] == "payment" ? 39 : $data['account_code'];//39;
                    $newJournal->debit = 0;
                    $newJournal->credit = $data['amount'];
                    $newJournal->details = $data['details'];
                    $newJournal->uuid = $data['transaction_id'];
                    $newJournal->save();
                    // post to cashbook
                    $cashbook = new Cashbook();
                    $cashbook->transaction_date = now();
                    $cashbook->particular = $data['particular'] ?? "Amos";
                    $cashbook->details = $data['details'];
                    $cashbook->pbank = $data['amount'];
                    $cashbook->gl_code = $code ?? 38;
                    // $cashbook->chq_teller = $data['transaction_id'];
                    $cashbook->uuid = $data['transaction_id'];
                    $cashbook->save();

                } else {
                    // debit bank
                    $newJournal = new Journal();
                    $newJournal->gl_code = $code ?? 38;
                    $newJournal->debit = $data['amount'];
                    $newJournal->credit = 0;
                    $newJournal->details = $data['details'];
                    $newJournal->uuid = $data['transaction_id'];
                    $newJournal->save();
                }
            }


            DB::commit();
            return response([
                "status" => "success",
                "data" => $data,
                "message" => "Posted to cashbook successfully"
            ], 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return response([
                "status" => "error",
                "data" => null,
                "message" => $exception->getMessage()
            ], 401);
        }

    }
    public function updateAccount(Request $request)
    {
        try {
            $input = $request->all();
            $account = Account::where('gl_code', $request->gl_code)->update(['gl_name' => $request->gl_name]);
            return api_request_response(
                "ok",
                "Data Saved successful!",
                success_status_code(),
                $account
            );
        } catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }
    public function postAccount(Request $request)
    {
        $input = $request->all();
        $category = Category::where('description', "INCOME")->first();
        $codeValue = $category->code;
        // dd($category);
        $test = Account::where('category_id', $category->id)->withTrashed()->get();
        $count = $test->count();
        $value = $category->id;
        // dd($value);
        $input['category_id'] = $value;
        $input['gl_code'] = $codeValue . '' . "0" . '' . $count + 1;
        // dd("here");
        try {
            DB::beginTransaction();
            $newAccount = new Account;
            $newAccount->created_by = $request->created_by;
            $newAccount->category_id = $value;
            $newAccount->gl_code = $codeValue . '' . "0" . '' . $count + 1;
            //    $newAccount->balance = $request->amount;
            $newAccount->direction = "1";
            $newAccount->gl_name = $request->gl_name;
            $newAccount->save();
            DB::rollBack();
            return api_request_response(
                "ok",
                "Data Saved successful!",
                success_status_code(),
                $newAccount
            );
        } catch (\Exception $exception) {
            DB::rollback();

            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }

    }
    public function postAccount1(Request $request)
    {
        $input = $request->all();
        $category = Category::where('description', 'INCOME')->first();
        $codeValue = $category->code;

        $test = Account::where('category_id', $category->id)->withTrashed()->get();
        $count = $test->count();
        $value = $category->id;
        // dd($value);
        $input['category_id'] = $value;
        $input['gl_code'] = $codeValue . '' . "0" . '' . $count + 1;
        // dd("here");
        try {
            DB::beginTransaction();
            $newAccount = new Account;
            $newAccount->created_by = Auth::company()->id;
            $newAccount->category_id = $value;
            $newAccount->gl_code = $codeValue . '' . "0" . '' . $count + 1;
            $newAccount->balance = $request->amount;
            $newAccount->direction = "1";
            $newAccount->gl_name = $request->description;
            $newAccount->save();
            DB::rollBack();
            return api_request_response(
                "ok",
                "Data Saved successful!",
                success_status_code(),
                $newAccount
            );
        } catch (\Exception $exception) {
            DB::rollback();

            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }

    }

    public function createSavingAccount(Request $request)
    {
        $input = $request->all();
        $category = Category::where('description', 'PAYABLES')->first();
        $codeValue = $category->code;

        $test = Account::where('category_id', $category->id)->withTrashed()->get();
        $count = $test->count();
        $value = $category->id;
        // dd($value);
        $input['category_id'] = $value;
        $input['gl_code'] = $codeValue . '' . "0" . '' . $count + 1;
        // dd("here");
        try {
            DB::beginTransaction();
            $newAccount = new Account;
            $newAccount->created_by = Auth::company()->id;
            $newAccount->category_id = $value;
            $newAccount->gl_code = $codeValue . '' . "0" . '' . $count + 1;
            $newAccount->balance = $request->amount;
            $newAccount->direction = "1";
            $newAccount->gl_name = $request->description;
            $newAccount->save();
            DB::rollBack();
            return api_request_response(
                "ok",
                "Data Saved successful!",
                success_status_code(),
                $newAccount
            );
        } catch (\Exception $exception) {
            DB::rollback();

            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }

    }
    public function createLoanAccount(Request $request)
    {
        $input = $request->all();
        $loanCategory = Category::where('description', 'LOANS')->first();
        $loanCodeValue = $loanCategory->code;
        $loanTest = Account::where('category_id', $loanCategory->id)->withTrashed()->get();
        $loanCount = $loanTest->count();
        $loanValue = $loanCategory->id;
        // dd($value);
        $input['category_id'] = $loanValue;
        $input['gl_code'] = $loanCodeValue . '' . "0" . '' . $loanCount + 1;
        // income category data
        $incomeCategory = Category::where('description', 'INCOME')->first();
        $incomeCodeValue = $incomeCategory->code;
        $incomeTest = Account::where('category_id', $incomeCategory->id)->withTrashed()->get();
        $incomeCount = $incomeTest->count();
        $incomeValue = $incomeCategory->id;
        //dd($value);
        //$input['category_id'] = $incomeValue;
        try {
            DB::beginTransaction();
            $newAccount = new Account;
            $newAccount->created_by = Auth::company()->id;
            $newAccount->category_id = $loanValue;
            $newAccount->gl_code = $loanCodeValue . '' . "0" . '' . $loanCount + 1;
            $newAccount->balance = $request->amount;
            $newAccount->direction = "1";
            $newAccount->gl_name = $request->description;
            $newAccount->save();

            $newInterestAccount = new Account;
            $newInterestAccount->created_by = Auth::company()->id;
            $newInterestAccount->category_id = $incomeValue;
            $newInterestAccount->gl_code = $incomeCodeValue . '' . "0" . '' . $incomeCount + 1;
            $newInterestAccount->balance = $request->amount;
            $newInterestAccount->direction = "1";
            $newInterestAccount->loan_id = $newAccount->id;
            $newInterestAccount->gl_name = $request->description . '' . "INTEREST ACCOUNT";
            $newInterestAccount->save();
            DB::rollBack();
            return api_request_response(
                "ok",
                "Data Saved successful!",
                success_status_code(),
                $newAccount
            );
        } catch (\Exception $exception) {
            DB::rollback();

            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }

    public function getAccountsByLastCategory(Request $request){
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => 'required|exists:sub_categories,id|numeric',
        ]);
        // dd($last);
        if ($validator->fails()) {
            return respond('error', $validator->errors()->first(), null, 400);
        }
        $id = $request->id;
        // $accounts = Account::where('sub_category_id', $id)->where('company_id', auth()->user()->province_id)->orWhereNull('company_id')->with(['category', 'class'])->orderBy('class_id','ASC')->orderBy('category_id','ASC')->get();
        $accounts = Account::where('sub_category_id', $id)->where(function ($query) {
            $query->where('company_id', auth()->user()->company_id)
                  ->orWhereNull('company_id');
        })->with(['category', 'class', 'Subcategory'])
          ->orderBy('class_id', 'ASC')
          ->orderBy('category_id', 'ASC')
          ->orderBy('sub_category_id','ASC')
          ->get();

        return respond(true, 'List of Accounts fetched!', $accounts, 201);
    }
}
