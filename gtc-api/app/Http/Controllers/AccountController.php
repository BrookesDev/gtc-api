<?php

namespace App\Http\Controllers;

use function App\Helpers\api_request_response;
use function App\Helpers\generate_random_password;
use function App\Helpers\generate_uuid;
use function App\Helpers\unauthorized_status_code;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
use Illuminate\Support\Facades\Session;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Import\AccountImport;
use QrCode;
use App\Models\Receipt;
use Illuminate\Support\Facades\Validator;
use App\Models\Journal;
use App\Models\User;
use App\Role;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Models\AccountStatus;
use App\Models\StatusDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public static function getParentCategory($id)
    {
        $category = Category::where('id', $id)->first();
        // dd($category);
        $parentCategory = $category->category_parent ?? "";
        $parent = Category::where('id', $parentCategory)->first();
        return $parent->description ?? "";
    }
    public function index()
    {
        $data['items'] = Category::whereNull('category_id')->get();
        $data['accounts'] = Account::all();
        // dd($data);
        // $revenue = Receipt::all();
        // $data['revenue']=$revenue->sum('amount');
        // $data['lodge'] = Receipt::where('lodgement_status', 1)->sum('amount');
        // $data['outstanding'] = Receipt::where('lodgement_status', 0)->sum('amount');
        // dd($data);
        return view('admin.account.index', $data);
        // return view ('check', $data);
    }

    public function getDeletedAccounts()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Account::onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Deleted accounts fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreAccount(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = Account::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Account restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllAccounts(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = Account::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }
    

            return respond(true, 'Accounts restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteAccount(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:accounts,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = Account::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Account not found', null, 404);
            }
            
            $account->forceDelete();

            return respond(true, 'Account data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllAccounts()
    {
     
        try {

            $accounts = Account::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Accounts data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function getSubCategory(Request $request)
    {
        // dd($request->id);
        $pp['data'] = $info = Category::where('category_id', $request->id)->get();
        return json_encode($pp);
        //dd($info);
    }

    public function getSubCategorys(Request $request)
    {
        // dd($request->id);
        $pp['data'] = $info = Category::where('category_id', $request->id)->get();//where('has_child', '!=', 0)->get();
        return json_encode($pp);
        //dd($info);
    }

    public function search()
    {
        $users = Receipt::all();
        $data["transactions"] = $users;
        $data['accounts'] = Account::all();
        return view('account.search', $data)->with('i');
        // return view ('check', $data);
    }

    public function searchData(Request $request)
    {
        $data['accounts'] = Account::all();
        $data['transactions'] = Receipt::where('bank_lodge', $request->bank_lodge)->get();
        return view('account.search', $data)->with('i');
    }

    public function import()
    {
        return view('account.import');
    }

    public function importData(Request $request)
    {
        try {

            \Excel::import(new AccountImport, request()->file('filess')->store('temp'));
            // dd("here");

            return Redirect::back()->with(['success' => 'Accounts  uploaded successfully!.']);
        } catch (\Exception $exception) {


            return Redirect::back()->withErrors($exception->getMessage());
        }
    }


    public function edit(Request $request)
    {
        //  dd($request->all());
        $account = Account::where('id', $request->id)->first();//Beneficiaries::all();
        //  return response()->json($account);
        return respond(true, 'successful', $account, 200);
    }

    public function update(Request $request)
    {

        // dd("here");
        try {
            $input = $request->all();
            $id = $request->id;

            $account = Account::where('id', $id)->firstOrFail();
            //    dd($account);
            $account = $account->update($input);

            return api_request_response(
                "ok",
                "Data Update successful!",
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

    public function delete(Request $request)
    {
        $id = $request->id;
        $account = Account::find($id);
        $account->delete();
        return redirect()->back()->with('deleted', 'Delete Success!');
    }

    public function save(Request $request)
    {
        $input = $request->all();
        $last = $request->category_id;



        if (is_array($last)) {
            $totalElements = count($request->category_id);
            $value = $last[$totalElements - 1];
        } else {
            $value = $request->category_id;
        }
        // dd($last);
        $test = Account::where('category_id', $value)->withTrashed()->get();
        $count = $test->count();
        $category = Category::where('id', $value)->first();
        $codeValue = $category->code;

        $input['created_by'] = Auth::user()->id;
        $input['category_id'] = $value;
        $input['gl_code'] = $codeValue . '' . "0" . '' . $count + 1;

        DB::beginTransaction();
        try {
            $newAccount = new Account;
            $newAccount->created_by = Auth::user()->id;
            $newAccount->category_id = $value;
            $newAccount->gl_code = $codeValue . '' . "0" . '' . $count + 1;
            $newAccount->balance = $request->gl_balance;
            $newAccount->direction = $request->direction;
            $newAccount->gl_name = $request->gl_name;
            $newAccount->save();
            // dd([$newAccount]);
            $apiUrl = env('API_URL') . "/post-account";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 1|xDVN0Toig1fMaK9SbQ4OxJMIt25X8ymlBbBXSA0z',
                'Accept' => 'application/json',
            ])->post($apiUrl, $newAccount->toArray());
            $real = $response->json();
            if ($real['status'] != "success") {
                DB::rollback();
                return api_request_response(
                    "error",
                    $real['message'],
                    bad_response_status_code()
                );

            }
            DB::commit();
            return api_request_response(
                "ok",
                "Data Update successful!",
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

    public function getCode(Request $request)
    {
        $stock = Account::where('id', $request->id)->first();
        return response()->json($stock);
    }

    public function allocateIndex()
    {
        $data['accounts'] = Account::all();
        $data['transactions'] = Receipt::where('lodgement_status', 0)->get();
        return view('account.manage_accounts', $data);
    }

    public function lodgeJournal()
    {
        $category = Category::where('description', 'LIKE', 'BANKS')->pluck('id')->toArray();
        // dd($category);
        $check = Category::whereIn('category_parent', $category)->first();
        if ($check) {
            $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
        } else {
            $group = $category;
        }
        $data['accounts'] = Account::whereIn('category_id', $group)->get();
        $data['transactions'] = Receipt::where('lodgement_status', 1)->get();
        return view('admin.account.lodge_accounts', $data);
    }

    public function saveAccount(Request $request)
    {
        // dd("here");
        $input = $request->all();
        // $input = $request->all();
        $input['particulars'] = $request->recepient_name;
        $input['teller_number'] = $request->teller_no;
        $item = $input['account_name'];
        $input['uuid'] = $order_id = rand();
        $input['voucher_number'] = $voucher = rand();
        $input['lodgement_status'] = 0;
        $input['amount'] = $request->all_sum;
        $input['created_by'] = Auth::user()->id;
        Session::put('voucherrr', $input['voucher_number']);
        try {

            foreach ($item as $key => $item) {
                $account = new AccountStatus;
                $account->account_name = $input['account_name'][$key];
                $account->account_code = $input['account_code'][$key];
                $account->amount = $request->amount[$key];
                $account->uuid = $input['uuid'];
                $account->save();
            }

            $details = Receipt::create($input);
            $id = $details->uuid;

            return api_request_response(
                "ok",
                "Data Update successful!",
                success_status_code(),
                $details
            );
        } catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );
            // return redirect()->back()->withErrors(['exception' => $exception->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function lodge(Request $request)
    {
        $data['accounts'] = Account::all();
        $input = $request->all();
        $uuid = $input['uuid'];
        $data["transactions"] = $stake = Receipt::whereIn('uuid', $uuid)->get();
        $data["sum"] = $stake->sum('amount');
        $data['category'] = Category::where('category_id', 9)->get();
        // dd( $data["sum"]);
        // foreach ($uuid as $key => $uuid) {
        //     $value[] = Receipt::where('uuid', $uuid)->first();
        // }
        // $data["transactions"] = $value;
        // $data["sum"] = $value->sum('amount');
        // dd( $data["transactions"]);
        // $data['revenue']=$data["transactions"]->sum('amount');
        return view('account.lodge', $data);

    }

    public function lodgeBankk(Request $request)
    {
        $data['accounts'] = Account::all();
        $data["transactions"] = Receipt::where('uuid', $request->id)->get();
        return view('account.lodge', $data);
        // dd($id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function bankLodge(Request $request)
    {
        try {
            $input = $request->all();
            // dd($request->all());
            $uuid = $input['uuid'];
            // dd($request->all());
            foreach ($uuid as $key => $uuid) {
                $checkUp[] = Receipt::where('uuid', $uuid)->first();
                $value = Receipt::where('uuid', $uuid)->first();
                $value->update(['lodgement_status' => 1, 'bank_lodge' => $request->bank_lodge, 'date_lodged' => Carbon::now()]);

            }

            return api_request_response(
                "ok",
                "Data Update successful!",
                success_status_code(),
                $value
            );
        } catch (\Exception $exception) {
            // return api_request_response(
            //     "error",
            //     $exception->getMessage(),
            //     bad_response_status_code()
            // );
            return redirect()->back()->withErrors(['exception' => $exception->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function receipt(Request $request)
    {
        $checkHighestvalue = Journal::where('uuid', $request->id)->orderBy('debit', 'DESC')->first();
        // dd($checkHighestvalue);
        $uuid = Journal::where('uuid', $request->id)->where('debit', 0)->where('credit', '!=', $checkHighestvalue->debit)->get();//->where('credit', '!=', $checkHighestvalue->credit)->get();
        //   dd($checkHighestvalue);
        $data['receipt'] = $receipt = Receipt::where('uuid', $request->id)->first();
        $data['details'] = $uuid;
        $data['qrcode'] = "Transaction receipt confirmed . Receipt ID is $receipt->voucher_number .";
        // dd("here");
        // $data['qrcode'] = QrCode::size(100)->generate($uuid);
        return view('receipt', $data);

    }
    public function receipt1(Request $request)
    {
        $variable = $request->session()->get('Variable');
        // dd($variable);
        foreach ($variable as $key => $uuid) {
            // $checkUp[] = Receipt::where('uuid', $uuid)->first();
            $value[] = AccountStatus::where('uuid', $uuid)->get();
            // $value->update(['lodgement_status' => 1, 'bank_lodge' => $request->bank_lodge, 'date_lodged' => Carbon::now()]);
            // $value = [];
        }
        // $input = $request->all();
        // $data['account'] = $receipt = Receipt::where('teller_number', $request->id)->first();
        // $uuid = $receipt->uuid;
        // $data['details'] = AccountStatus::where('uuid', $uuid)->get();
        $data['details'] = $value;
        // dd($data['details']);
        return view('receipt', $data);
        // dd($input);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        //
    }
}
