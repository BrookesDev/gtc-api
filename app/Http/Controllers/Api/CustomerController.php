<?php

namespace App\Http\Controllers\Api;

use App\Customers;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Repayment;
use App\Models\NominalLedger;
use App\Models\CustomerPersonalLedger;
use App\Models\Account;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Exports\RepaymentExport;
use App\Imports\RepaymentImport;
use App\Exports\RepaymentTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssetsImport;
use App\Exports\MemberExport;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id = getCompanyid();
        //dd($id);
        $customers = Customers::where('company_id', $id)->with(['ledgers'])->paginate(100);
        return respond(true, 'List of customers fetched!', $customers, 201);
    }
    public function deleteCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:customers,id'
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $member = Customers::find($request->id);
            $memberId = $member->id;
            $cpl = CustomerPersonalLedger::where('customer_id', $memberId)->first();
            $cplID = $cpl->customer_id;
            if ($memberId == $cplID) {
                return respond(false, 'Customer can\'t  be archived because customer already has a transaction', null, 400);
            }
            //dd($id);
            $member->delete();
            return respond(true, 'Member archived successfully', $member, 201);
        } catch (\Exception $e) {

            return respond(false, $e->getMessage(), null, 400);
        }

    }
    public function updateCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:customers,id',
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                Rule::unique('customers', 'email')->ignore($request->id),
            ],
            'phone' => [
                'nullable',
                'string',
                Rule::unique('customers', 'phone')->ignore($request->id),
            ],
            'address' => ['nullable'],
            'office_address' => ['nullable'],
            'status' => ['required']
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $input = $request->all();
            $member = Customers::find($request->id);

            if ($request->has('phone')) {
                $formattedNumber = formatPhoneNumber($request->phone);
                if ($formattedNumber['status'] == false) {
                    return respond(false, $formattedNumber['message'], $formattedNumber['data'], 400);
                }
            }


            // dd($member);
            //dd($id);
            $member->update($input);

            // $membername = $member->name;


            // $user = User::where('email', $member->email)->first();

            // // $user = User::where('id', $userid);

            // $user->update([
            //    'name' => $request->name,
            //     // 'email' => $request->email,
            //     'phone_no' => $request->phone,

            // ]);


            return respond(true, 'Member updated successfully', $member, 201);
        } catch (\Exception $e) {

            return respond(false, $e->getMessage(), null, 400);
        }

    }

    public function cooperativeManagerDashboard()
    {
        try {

            // Prepare an array to store the monthly data
            $monthlyData = [];

            // Iterate through each month of the current year
            for ($month = 1; $month <= 12; $month++) {
                // Calculate cash out for the current month
                $cashout = MemberLoan::where('company_id', auth()->user()->company_id)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('principal_amount');

                // Calculate cash in for the current month
                $cashin = MemberSavings::where('company_id', auth()->user()->company_id)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('amount');

                // Calculate the cashflow balance
                $cashflow = $cashin - $cashout;

                // Add the data to the monthly data array
                $monthlyData[] = [
                    'month' => Carbon::create()->month($month)->format('F'),
                    'cash_in' => $cashin,
                    'cash_out' => $cashout,
                    'cashflow' => $cashflow,
                ];
            }

            $savings = MemberSavings::where('company_id', auth()->user()->company_id)
                ->whereMonth('created_at', Carbon::now()->month)->sum('amount');
            $loans = MemberLoan::where('company_id', auth()->user()->company_id)
                ->whereMonth('created_at', Carbon::now()->month)->sum('principal_amount');
            //$outstanding = MemberLoan::where('employee_id', $customerId)->select('balance')->get();

            // Prepare the response data
            $response = [
                'monthly_data' => $monthlyData,
                'savings_for_the_month' => $savings,
                'loans_for_the_month' => $loans,

                // 'outstanding' => $outstanding,
            ];

            // Return the response
            return respond(true, 'Data fetched successfully!', $response, 201);

        } catch (\Exception $e) {
            // Handle any exceptions
            // return response()->json(['message' => $e->getMessage()], 400);
            return respond(false, $e->getMessage(), null, 400);


        }
    }


    public function nonIndex()
    {
        $id = getCompanyid();
        //dd($id);
        $customers = Customers::where('company_id', $id)->select('id', 'name', 'address', 'balance')->get();
        return respond(true, 'List of customers fetched!', $customers, 201);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    private function generateUniqueEmployeeNo()
    {
        do {
            $employeeNo = rand(10000, 99999);
        } while (Customers::where('employee_no', $employeeNo)->exists());

        return $employeeNo;
    }
    public function customerCount()
    {
        $customerCount = Customers::where('company_id', auth()->user()->company_id)->count();

        $response = [
            'total_number_of_customers' => $customerCount
        ];

        return respond(true, 'Data fetched successfully', $response, 200);
    }

    public function addNewCustomer(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $validator = Validator::make($data, [
                'employee_no' => 'nullable|unique:customers,employee_no',
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:customers'],
                'phone' => ['required', 'unique:customers'],
                'address' => ['required'],
                'office_address' => ['required']
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            if ($request->has('phone')) {
                $formattedNumber = formatPhoneNumber($request->phone);
                if ($formattedNumber['status'] == false) {
                    return respond(false, $formattedNumber['message'], $formattedNumber['data'], 400);
                }
            }

            if (empty($request->employee_no)) {
                $employeeNo = $this->generateUniqueEmployeeNo();
            } else {
                $employeeNo = $request->employee_no;
            }
            // dd($data);
            $data['employee_no'] = $employeeNo;
            $newValue = Customers::create($data);

            $data['password'] = Hash::make("secret");
            $data['company_id'] = getCompanyid();
            $data['user_type'] = "Member";
            $data['new_user_type'] = "Member";
            $data['member_no'] = $employeeNo;
            $data['created_by'] = auth()->user()->id;

            $user = User::create($data);

            DB::commit();
            return respond(true, 'New Customer added successfully!', $newValue, 201);


        } catch (\Exception $e) {
            DB::rollback();
            return respond(false, $e->getMessage(), null, 400);
        }

    }
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'variable' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $variable = $request->variable;
        // dd($variable);
        $users = Customers::where('company_id', auth()->user()->company_id)->where("name", 'like', "%$variable%")->Orwhere("email", 'like', "%$variable%")
            ->Orwhere("phone", 'like', "%$variable%")->Orwhere("ippis_no", 'like', "%$variable%")
            ->Orwhere("employee_no", 'like', "%$variable%")->Orwhere("department", 'like', "%$variable%")
            ->Orwhere("phone_no", 'like', "%$variable%")->with('ledgers')->get();
        return respond(true, 'Customer filtered successfully!', $users, 200);
    }

    public function loanDetails(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:member_loans,employee_id',

        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $data = MemberLoan::where('employee_id', $request->customer_id)->where('balance', '>', 0)->get();

            if (!$data) {
                return respond(false, $validator->errors(), null, 404);
            }
            return respond(true, "Loan details fetched successfully!", $data, 201);
        } catch (\Exception $exception) {
            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function ledger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:beneficiaries,id',

        ]);
        // Check for validation errors
        if ($validator->fails()) {
            return respond(
                false,
                $validator->errors(),
                null
                ,
                400
            );
        }
        $data = CustomerPersonalLedger::where('customer_id', $request->customer_id)->get();
        return respond(true, "Customer personal ledger fetched successfully!", $data, 201);
    }
    public function personalLedger(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }

        $start_date = Carbon::parse($request->start_date)->startOfDay()->toDateTimeString();
        $end_date = Carbon::parse($request->end_date)->endOfDay()->toDateTimeString();
        $ledger = CustomerPersonalLedger::where('customer_id', $request->customer_id)
            ->where('company_id', auth()->user()->company_id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
            ->with(['customer', 'company'])->get();
        // dd($sales);

        return respond(true, 'Customer personal ledger fetched successfully!', $ledger, 200);
    }

    public function SavingDetails(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:member_savings,member_id',

        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $data = MemberSavings::where('member_id', $request->customer_id)->get();

            if (!$data) {
                return respond(false, $validator->errors(), null, 404);
            }
            return respond(true, "Saving details fetched successfully!", $data, 201);
        } catch (\Exception $exception) {
            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function loanRepayment(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'transaction_date' => 'required',
            'customer_id' => 'required|exists:member_loans,employee_id',

        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $data = MemberLoan::where('employee_id', $request->customer_id)->get();

            if (!$data) {
                return respond(false, $validator->errors(), null, 404);
            }
            return respond(true, "Loan details fetched successfully!", $data, 201);
        } catch (\Exception $exception) {
            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function saveRepayment(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0', 'not_regex:/^(\[.*\]|\{.*\})$/'],
            'transaction_date' => ['required', 'date', 'not_regex:/^(\[.*\]|\{.*\})$/'],
            'cheque_number' => ['nullable', 'not_regex:/^(\[.*\]|\{.*\})$/'],
            // 'loan_type' => 'required|exists:nominal_ledgers,id',
            'account_id' => ['required', 'exists:member_loans,id', 'not_regex:/^(\[.*\]|\{.*\})$/'],
            'bank' => ['required', 'exists:accounts,id', 'not_regex:/^(\[.*\]|\{.*\})$/'],
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id'),
                Rule::exists('member_loans', 'employee_id'),
                'not_regex:/^(\[.*\]|\{.*\})$/',
            ],
            'mode_of_payments' => ['nullable', 'not_regex:/^(\[.*\]|\{.*\})$/'],
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();
            // Retrieve the account and member loan based on provided IDs
            $account = Account::where('id', $request->bank)->first();
            $memberLoan = MemberLoan::where('employee_id', $request->customer_id)->where('id', $request->account_id)->first();
            if ($memberLoan->balance < $request->amount) {
                return respond(false, "You can't repay more than your balance!", null, 400);
            }
            $memberLoan->balance -= $request->amount;
            $memberLoan->save();
            $type = $memberLoan->loan_name;
            $ledger = NominalLedger::find($type);
            // Create a new repayment instance
            $repayment = new Repayment();
            $repayment->amount = $request->amount;
            $repayment->transaction_date = $request->transaction_date;
            $repayment->bank = $account->id; // Assuming 'bank' is a property of the Account model
            $repayment->account_id = $memberLoan->id; // Assuming 'bank' is a property of the Account model
            $repayment->customer_id = $memberLoan->employee_id;
            $repayment->type = 2; // Equating type to 2 (constant)
            $repayment->cheque_number = $request->cheque_number;
            $repayment->mode_of_payments = $request->mode_of_payments;
            // $repayment->loan_type = $request->loan_type;
            // Save the repayment
            $repayment->save();
            $bankcode = $request->bank;
            $glcode = $ledger->report_to;
            $detail = $memberLoan->beneficiary->name . ' ' . "loan repayment ";
            $amount = $request->amount;
            $transaction_date = $request->transaction_date;
            // credit report to account
            postDoubleEntries($memberLoan->prefix, $glcode, 0, $amount, $detail, $transaction_date);
            receivablesInsertion($amount, $request->cheque_number, 3, $detail, $transaction_date, NULL);
            // debit the bank
            postDoubleEntries($memberLoan->prefix, $bankcode, $amount, 0, $detail, $transaction_date);
            saveCustomerLedger($request->customer_id, $memberLoan->prefix, $request->amount, 0, $detail, $memberLoan->balance);
            DB::commit();
            return respond(true, "Loan repayment saved successfully!", $repayment, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            // Return an error response
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function saveDeposit(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'cheque_number' => 'nullable',
            'account_id' => 'required|exists:member_savings,id',
            'bank' => 'required|exists:accounts,id',
            'customer_id' => 'required|exists:member_savings,member_id|exists:customers,id',
            'mode_of_payments' => 'nullable',
        ]);

        // dd($request->all());
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $membersavings = MemberSavings::where('id', $request->account_id)->first();
        if ($membersavings->member_id != $request->customer_id) {
            return respond(false, "account not corresponding", null, 400);
        }


        try {
            DB::beginTransaction();
            // Retrieve the account and member loan based on provided IDs
            $account = Account::where('id', $request->bank)->first();
            $membersavings = MemberSavings::where('member_id', $request->customer_id)->where('id', $request->account_id)->first();
            // dd($membersavings)
            $membersavings->balance += $request->amount;
            $membersavings->save();
            // dd($membersavings);
            $type = $membersavings->savings_type;
            $ledger = NominalLedger::find($type);
            // Create a new repayment instance
            $repayment = new Repayment();
            $repayment->amount = $request->amount;
            $repayment->transaction_date = $request->transaction_date;
            $repayment->bank = $account->id; // Assuming 'bank' is a property of the Account model
            $repayment->account_id = $membersavings->id; // Assuming 'bank' is a property of the Account model
            $repayment->customer_id = $membersavings->member_id;
            $repayment->type = 3; // Equating type to 2 (constant)
            $repayment->cheque_number = $request->cheque_number;
            $repayment->mode_of_payments = $request->mode_of_payments;
            // Save the repayment
            $repayment->save();
            $bankcode = $request->bank;
            $glcode = $ledger->report_to;
            $detail = $membersavings->beneficiary->name . ' ' . "add to savings";
            $amount = $request->amount;
            $transaction_date = $request->transaction_date;
            // credit report to account
            // postDoubleEntries($membersavings->prefix, $glcode, 0, $amount, $detail, $transaction_date);
            // receivablesInsertion($amount, $request->cheque_number, 3, $detail, $transaction_date, NULL);
            // debit the bank
            //     postDoubleEntries($membersavings->prefix, $bankcode, $amount, 0, $detail, $transaction_date);
            //     saveCustomerLedger($request->customer_id, $membersavings->prefix, $request->amount, 0, $detail, $membersavings->balance);
            //     DB::commit();
            //     return respond(true, "Loan repayment saved successfully!", $repayment, 201);
            // } catch (\Exception $exception) {
            //     DB::rollback();
            //     // Return an error response
            //     return respond(false, $exception->getMessage(), null, 500);
            // }
            // Credit the bank account
            postDoubleEntries($membersavings->prefix, $glcode, 0, $amount, $detail, $transaction_date);
            receivablesInsertion($amount, $request->cheque_number, 1, $detail, $transaction_date, NULL);
            // Debit the bank
            postDoubleEntries($membersavings->prefix, $bankcode, $amount, 0, $detail, $transaction_date);
            saveCustomerLedger($request->customer_id, $membersavings->prefix, $request->amount, $membersavings->balance, $detail);

            DB::commit();
            return respond(true, "Deposit saved successfully!", $repayment, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            // Return an error response
            return respond(false, $exception->getMessage(), null, 500);
        }

    }

    public function saveSavingsWithdrawal(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:member_savings,id',
            'bank' => 'required|exists:accounts,id',
            'customer_id' => 'required|exists:member_savings,member_id',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();
            // Retrieve the bank account and member savings based on the provided IDs
            // $account = MemberSavings::findOrFail($request->account_id);
            $memberSaving = MemberSavings::findOrFail($request->account_id);
            $type = $memberSaving->savings_type;
            $account = NominalLedger::find($type);
            // Check if the member has sufficient funds
            if ($memberSaving->balance < $request->amount) {
                return respond(false, 'Insufficient funds to withdraw', null, 400);
            }

            // Deduct the withdrawal amount from the member's savings account balance
            $balance = $memberSaving->balance -= $request->amount;
            $memberSaving->save();

            // Create a new repayment instance
            $repayment = new Repayment();
            $repayment->amount = $request->amount;
            $repayment->transaction_date = $request->transaction_date;
            $repayment->bank = $request->bank;
            $repayment->account_id = $memberSaving->id;
            $repayment->customer_id = $memberSaving->member_id;
            $repayment->type = 1; // Assuming 'type' refers to withdrawal
            $repayment->cheque_number = $request->cheque_number;
            $repayment->save();
            $bankcode = $request->bank;
            $glcode = $account->report_to;
            $detail = $memberSaving->beneficiary->name . ' ' . "withdraw from savings";
            $transaction_date = $request->transaction_date;
            // debit report to account
            postDoubleEntries($memberSaving->prefix, $glcode, $request->amount, 0, $detail, $transaction_date);
            // credit the bank
            postDoubleEntries($memberSaving->prefix, $bankcode, 0, $request->amount, $detail, $transaction_date);
            saveCustomerLedger($request->customer_id, $memberSaving->prefix, 0, $request->amount, $detail, $balance);
            DB::commit();
            return respond(true, "Withdrawal successful!", $repayment, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function newSaveSavingsWithdrawal(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:member_savings,id',
            'bank' => 'required|exists:accounts,id',
            'customer_id' => 'required|exists:member_savings,member_id',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();
            // Retrieve the bank account and member savings based on the provided IDs
            // $account = MemberSavings::findOrFail($request->account_id);
            $memberSaving = MemberSavings::findOrFail($request->account_id);
            $type = $memberSaving->savings_type;
            $account = NominalLedger::find($type);
            // Check if the member has sufficient funds

            $checkLoan = MemberLoan::where('employee_id', $memberSaving->member_id)->first();
            $loanSum = 0;

            if ($checkLoan) {
                $loanSum = MemberLoan::where('employee_id', $memberSaving->member_id)
                    ->sum('total_loan');  // Total outstanding loan sum
            }

            $availableBalance = $memberSaving->balance - $loanSum;

            if ($availableBalance < $request->amount) {
                return respond(false, 'Insufficient funds to withdraw after loan adjustment.', null, 400);
            }

            // Deduct the withdrawal amount from the member's savings account balance
            $balance = $memberSaving->balance -= $request->amount;
            $memberSaving->save();

            // Create a new repayment instance
            $repayment = new Repayment();
            $repayment->amount = $request->amount;
            $repayment->transaction_date = $request->transaction_date;
            $repayment->bank = $request->bank;
            $repayment->account_id = $memberSaving->id;
            $repayment->customer_id = $memberSaving->member_id;
            $repayment->type = 1; // Assuming 'type' refers to withdrawal
            $repayment->cheque_number = $request->cheque_number;
            $repayment->save();
            $bankcode = $request->bank;
            $glcode = $account->report_to;
            $detail = $memberSaving->beneficiary->name . ' ' . "withdraw from savings";
            $transaction_date = $request->transaction_date;
            // debit report to account
            postDoubleEntries($memberSaving->prefix, $glcode, $request->amount, 0, $detail, $transaction_date);
            // credit the bank
            postDoubleEntries($memberSaving->prefix, $bankcode, 0, $request->amount, $detail, $transaction_date);
            saveCustomerLedger($request->customer_id, $memberSaving->prefix, 0, $request->amount, $detail, $balance);
            DB::commit();
            return respond(true, "Withdrawal successful!", $repayment, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function GetLoanRepaymentData()
    {
        try {
            $user = Auth::user();

            $loanrepayment = Repayment::where('company_id', $user->company_id)->where('type', 2)->with(['LoanAccount', 'Account', 'Customer', 'Created_by'])->get();


            return respond(true, 'Data fetched successfully', $loanrepayment, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function getSavingData(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:member_savings,member_id',
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $loanrepayment = Repayment::where('company_id', $user->company_id)->where('customer_id', $request->customer_id)
                ->where('type', 3)->with(['SavingsAccount', 'Account', 'Customer', 'Created_by'])->orderBy('created_at', 'DESC')->get();


            return respond(true, 'Data fetched successfully', $loanrepayment, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function getSavingDataForCompany(Request $request)
    {
        try {
            $user = Auth::user();

            $loanrepayment = Repayment::where('company_id', $user->company_id)->where('type', 3)->with(['SavingsAccount', 'Account', 'Customer', 'Created_by'])->orderBy('created_at', 'DESC')->get();


            return respond(true, 'Data fetched successfully', $loanrepayment, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function GetSavingsWithdrawal()
    {
        try {
            $user = Auth::user();

            $loanrepayment = Repayment::where('company_id', $user->company_id)->where('type', 1)->with(['SavingsAccount', 'Account', 'Customer', 'Created_by'])->get();


            return respond(true, 'Data fetched successfully', $loanrepayment, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function RepaymentExcel()
    {
        return Excel::download(new RepaymentExport(), 'repayments.xlsx');
    }

    public function uploadRepaymentTemplate(Request $request)
    {
        // Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|max:2048', // Ensure file is Excel and within size limit
            'transaction_date' => 'required|date', // Ensure file is Excel and within size limit
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(request()->file('file'));
        $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $countdata = count($sheetData) - 1;
        // dd($countdata, $request->all());
        if ($countdata < 1) {
            return respond(false, 'Excel File Is Empty! Populate And Upload!', $countdata, 400);
        }

        // Process the uploaded file
        try {
            DB::beginTransaction();
            $date = $request->transaction_date;
            // dd($date);
            \Excel::import(new RepaymentImport($date), request()->file('file'));
            DB::commit();
            return respond(true, "File uploaded successfully!", $date, 200);
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


    public function import(Request $request)
    {
        // dd('here');
        $validator = Validator::make($request->all(), [
            // 'category_id' => 'required|exists:asset_categories,id',
            'file' => 'required|file|mimes:xls,xlsx',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(request()->file('file'));
        // $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();
        // // dd($spreadsheet);
        // $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        // dd($sheetData);
        // $countdata = count($sheetData) - 1;
        // dd($countdata, $request->all());
        // if ($countdata < 1) {
        //     return respond(false, "Excel File Is Empty! Populate And Upload! ", $countdata, 400);
        // }
        // DB::beginTransaction();
        try {
            $categeory = $request->category_id;
            \Excel::import(new AssetsImport($categeory), request()->file('file'));
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


    public function downloadMemberTemplate()
    {
        return Excel::download(new MemberExport(), 'membertemplate.xlsx');
    }

    public function fetchSoftdelete()
    {
        $deleted = Customers::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved customers successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Customers::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived customers restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Customer is not yet deleted!', null, 400);
        } else {
            return respond(false, 'Archieved customers not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Customers::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved customers found to restore!', null, 404);
        }
        Customers::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved customers restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Customers::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived customers permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Customer is not yet archived!', null, 400);
        } else {
            return respond(false, 'Archived customers not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Customers::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved customers found to permanently delete!', null, 404);
        }
        Customers::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved customers permanently deleted successfully!', null, 200);
    }

    public function updatePrefixInLedger()
    {
        //Mr Apuwabi
        $allMemberSavings = MemberSavings::where('company_id', 30)->select('member_id', 'prefix')->get();
        // loop through
        // dd($allMemberSavings);
        foreach ($allMemberSavings as $single) {
            $getAllLedgers = CustomerPersonalLedger::where('customer_id', $single->member_id)
                ->whereNull('invoice_number')
                ->where('description', 'like', "%retirement%")
                ->update(['invoice_number' => $single->prefix]);
        }

        return respond(true, 'O ti do be!', 30, 200);
    }

}
