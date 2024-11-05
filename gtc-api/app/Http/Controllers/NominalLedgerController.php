<?php

namespace App\Http\Controllers;

use App\Models\NominalLedger;
use App\Models\MemberLoan;
use App\Models\Journal;
use App\Models\User;
use App\Models\Mode_of_Saving;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Repayment;
use App\Models\DisapprovalComment;
use App\Models\LoanDisapprovalComment;
use Carbon\Carbon;
use App\Models\CustomerPersonalLedger;
use App\Models\MemberSavings;
use App\Models\AllTransaction;
use App\Models\MyTransactions;
use App\Models\MonthlyDeduction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NominalLedgerController extends Controller
{
    public function createLoan(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:nominal_ledgers,code',
            'description' => 'required',
            'opening_balance' => 'required',
            'report_to' => 'required',
            'type' => 'required',
            'interest' => 'required',
            'interest_gl' => 'required|exists:accounts,id',


        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // dd(auth()->user());

        DB::beginTransaction();
        try {
            $newAccount = NominalLedger::create($input);
            DB::commit();
            return respond(true, "Operation successful!", $newAccount, 200);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);

        }

    }

    // public function updateLoan(Request $request)
    // {
    //     $input = $request->all();

    //     $validator = Validator::make($input, [
    //         'id' => 'required|exists:nominal_ledgers,id',
    //         'code' => 'required|exists:nominal_ledgers,code' . $request->user_id,
    //         'description' => 'required',
    //         'opening_balance' => 'required',
    //         'report_to' => 'required',
    //         'type' => 'required',
    //         'interest' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return respond(false, $validator->errors(), null, 400);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Find the loan record by ID
    //         $loan = NominalLedger::findOrFail($input['id']);

    //         // Update the loan record with new data
    //         $loan->update($input);

    //         DB::commit();
    //         return respond(true, "Loan updated successfully!", $loan, 200);
    //     } catch (\Exception $exception) {
    //         DB::rollback();
    //         return respond(false, $exception->getMessage(), null, 400);
    //     }
    // }
    public function updateLoan(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|exists:nominal_ledgers,id',
            'code' => 'required|unique:nominal_ledgers,code,' . $input['id'],
            'description' => 'required',
            'opening_balance' => 'required',
            'report_to' => 'required',
            'type' => 'required',
            'interest' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        DB::beginTransaction();
        try {
            // Find the loan record by ID
            $loan = NominalLedger::findOrFail($input['id']);

            // Update the loan record with new data
            $loan->update($input);

            DB::commit();
            return respond(true, "Loan updated successfully!", $loan, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchStaffSavingsNeww(Request $request)
    {
        // dd($id);

        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
            'savings_type' => 'required|exists:nominal_ledgers,id',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {

            $memberID = MemberSavings::where('member_id', $request->member_id)->where('savings_type', $request->savings_type)->first();

            $sales = AllTransaction::where('transaction_number', $memberID->uuid)
                ->where('type', 4)->with([
                        'savings',
                        'company'
                    ])->orderBy('created_at', 'DESC')->get();

            // $sales = getSalesInvoice()->with(['customer', 'items'])->get();
            return respond(true, 'Staff savings successfully fetched!', $sales, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function fetchStaffLoanTransactions(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
            'account_id' => 'required|exists:nominal_ledgers,id',
            // Ensure the member_id exists in the customers table
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Retrieve all member savings records for the given member_id
            $memberSavings = MemberLoan::where('employee_id', $request->member_id)
                ->where('loan_name', $request->account_id)->pluck('prefix')->toArray();


            // Fetch all transactions related to the member's savings (type = 4)
            $savingsTransactions = CustomerPersonalLedger::whereIn('invoice_number', $memberSavings)
                // ->where('type', 4)
                // ->with(['savings', 'company']) // Eager load related 'savings' and 'company'
                ->with('loandata')->orderBy('created_at', 'DESC')
                ->get();
            
            

            // Return success response with the savings transactions data
            return respond(true, 'Staff loan successfully fetched!', $savingsTransactions, 200);
        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function fetchStaffSavingsNew(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
            'savings_type' => 'required|exists:nominal_ledgers,id',
            // Ensure the member_id exists in the customers table
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Retrieve all member savings records for the given member_id
            $memberSavings = MemberSavings::where('member_id', $request->member_id)
                ->where('savings_type', $request->savings_type)->pluck('prefix')->toArray();


            // Fetch all transactions related to the member's savings (type = 4)
            $savingsTransactions = CustomerPersonalLedger::whereIn('invoice_number', $memberSavings)
                // ->where('type', 4)
                // ->with(['savings', 'company']) // Eager load related 'savings' and 'company'
                ->orderBy('created_at', 'ASC')
                ->get();

            // Return success response with the savings transactions data
            return respond(true, 'Staff savings successfully fetched!', $savingsTransactions, 200);
        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function StaffSavingsType(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Retrieve all member savings records for the given member_id
            $memberSavings = MemberSavings::where('member_id', $request->member_id)->pluck('savings_type')->toArray();

            $savingType = NominalLedger::whereIn('id', $memberSavings)
                ->orderBy('created_at', 'DESC')->get();
            // Fetch all transactions related to the member's savings (type = 4)


            // Return success response with the savings transactions data
            return respond(true, 'Staff saving type successfully fetched!', $savingType, 200);
        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function StaffSavingsTypeNew(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Retrieve all member savings records for the given member_id
            $memberSavings = MemberSavings::where('member_id', $request->member_id)
                ->select('savings_type', DB::raw('SUM(balance) as total_saved')) // Sum the amount per savings type
                ->groupBy('savings_type') // Group by savings type to get totals
                ->get();

            // Get the list of savings types (savings_type) as an array
            $savingsTypeIds = $memberSavings->pluck('savings_type')->toArray();

            // Fetch the savings type names from the NominalLedger table using the savings_type IDs
            $savingsTypes = NominalLedger::whereIn('id', $savingsTypeIds)
            ->orderBy('created_at', 'DESC')->get(); // Retrieve only the necessary fields

            // Map the total saved amounts to the corresponding savings types
            $result = $savingsTypes->map(function ($savingType) use ($memberSavings) {
                // Find the total savings for this particular savings type
                $totalSaved = $memberSavings->firstWhere('savings_type', $savingType->id)->total_saved;

                return [
                    'savings_type' => $savingType, // Fetch the savings type name
                    'total_saved' => $totalSaved, // Total saved amount for this savings type
                    // 'balance' => $memberSavings->balance, // Total saved amount for this savings type
                ];
            });

            // Return success response with the savings types and total saved amounts
            return respond(true, 'Staff savings types and totals fetched successfully!', $result, 200);

        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function StaffSavingsTypeByPrefix(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Retrieve all member savings records for the given member_id
            $memberSavings = MemberSavings::where('member_id', $request->member_id)->get();

            // Get the list of savings types (savings_type) as an array
            $savingsTypeIds = $memberSavings->pluck('savings_type')->toArray();

            // Fetch the savings type names from the NominalLedger table using the savings_type IDs
            $savingsTypes = NominalLedger::whereIn('id', $savingsTypeIds)
                ->orderBy('created_at', 'DESC')
                ->get(); // Retrieve only the necessary fields

            $result = [];
             // Initialize the counter for numbering savings types

            foreach ($savingsTypes as $savingType) {
                // Get all savings records of this particular savings type
                $savingsOfThisType = $memberSavings->where('savings_type', $savingType->id);
                $counter = 1;
                foreach ($savingsOfThisType as $saving) {
                    // Sum the balance for each savings with the same prefix
                    $balance = $savingsOfThisType->where('prefix', $saving->prefix)->sum('balance');

                    // Prepare data, numbering each savings type entry
                    $result[] = [
                        'id' => $saving->id,
                        'savings_type' => $savingType->description . ' ' . sprintf("%02d", $counter), // Append counter
                        // 'prefix' => $saving->prefix, // Prefix for this saving entry
                        'total_saved' => $balance, // Amount saved for this entry
                    ];

                    // Increment counter for numbering
                    $counter++;
                }
            }

            // Return success response with the savings types and total saved amounts
            return respond(true, 'Staff savings types and totals fetched successfully!', $result, 200);

        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function StaffSavingsTypeWithData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Step 1: Retrieve all member savings records for the given member_id
            $memberSavings = MemberSavings::where('member_id', $request->member_id)->get();

            // Step 2: Extract savings type IDs (savings_type) from the memberSavings records
            $savingsTypeIds = $memberSavings->pluck('savings_type')->toArray();

            // Step 3: Fetch the savings type names from the NominalLedger table using the savings_type IDs
            $savingsTypes = NominalLedger::whereIn('id', $savingsTypeIds)
                ->select('id', 'description') // Fetch only relevant fields
                ->get();

            // Step 4: Map the member savings to their corresponding savings types and calculate totals
            $result = [];
            $overallTotalSaved = 0; // Variable to hold the total savings across all types

            foreach ($savingsTypes as $savingType) {
                // Filter member savings by savings_type
                $savingsForThisType = $memberSavings->where('savings_type', $savingType->id);

                // Calculate the total saved amount for this particular savings type
                $totalSavedForThisType = $savingsForThisType->sum('amount');

                // Add this savings type's total to the overall total saved
                $overallTotalSaved += $totalSavedForThisType;

                // Create an array entry for each savings type with the corresponding savings details
                $counter = 1; // Initialize the counter for numbering savings types
                foreach ($savingsForThisType as $saving) {
                    // Create numbered description for savings types (e.g., "Savings Type 01")
                    $savingTypeDescription = $savingType->description . ' ' . sprintf("%02d", $counter);

                    // Add the savings data to the result array
                    if($saving->balance < 1){
                        $result[] = [
                            'savings_id' => $saving->id, // Unique ID of the member savings record
                            'savings_type' => $savingTypeDescription, // Savings type with numbering
                            // 'amount_saved' => $saving->balance, // Amount saved for this entry
                        ];
                    }else{
                        $result[] = [
                            'savings_id' => $saving->id, // Unique ID of the member savings record
                            'savings_type' => $savingTypeDescription, // Savings type with numbering
                            'amount_saved' => $saving->balance, // Amount saved for this entry
                        ];    
                    }

                    $counter++; // Increment the counter for each savings of this type
                }
            }

            // Step 5: Prepare the response data with savings types, individual savings details, and total saved
            $response = [
                'savings_details' => $result, // List of all savings with types and amounts
                'total_saved_overall' => $overallTotalSaved, // Overall total saved across all types
            ];

            // Step 6: Return the result
            return respond(true, 'Member savings and totals fetched successfully', $response, 200);

        } catch (\Exception $exception) {
            // Catch and handle any errors
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function fetchLoans()
    {
        try {
            $user = Auth::user();

            $loans = NominalLedger::where('company_id', $user->company_id)->where('type', 2)->with(['report', 'user'])->get();


            return respond(true, 'Loans fetched successfully', $loans, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function createSavings(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:nominal_ledgers,code',
            'description' => 'required',
            'opening_balance' => 'required',
            'report_to' => 'required',
            'type' => 'required',


        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        // dd(auth()->user());

        DB::beginTransaction();
        try {

            $newAccount = NominalLedger::create($input);
            DB::commit();
            return respond(true, "Account created successful!", $newAccount, 200);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);

        }

    }

    public function fetchSavings()
    {
        try {
            $user = Auth::user();

            $savings = NominalLedger::where('company_id', $user->company_id)->where('type', 1)->with(['report', 'user'])->get();


            return respond(true, 'savings fetched successfully', $savings, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function deleteSavings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:nominal_ledgers,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $data = NominalLedger::findOrFail($id);
            $data->delete();
            return respond(true, 'Data deleted successfully!', $data, 201);


        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }
    public function deleteLoans(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:member_loans,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $loan = MemberLoan::findOrFail($id);
            if($loan->approved == 1){
                return respond(false, 'You cannot delete an approved loan', null, 400);
            }
            Journal::where('uuid', $loan->prefix)->delete();
            CustomerPersonalLedger::where('invoice_number', $loan->prefix)->delete();
            
            $loan->delete();
            return respond(true, 'Data deleted successfully!', $loan, 201);


        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function updateAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:nominal_ledgers,id',
                'code' => [
                    'nullable',
                    'string',
                    Rule::unique('nominal_ledgers', 'code')->ignore($request->id),
                ],
                'description' => 'nullable',
                'opening_balance' => 'nullable',
                'report_to' => 'nullable',
                'type' => 'nullable',
                'interest' => 'nullable',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            //$password = Str::random(8);//"password";
            //$data['password'] = Hash::make($password);

            // if (isset($data['email'])) {
            //     // Send email with password
            //     Mail::to($data['email'])->send(new SendPasswordMail($password));
            // }

            // dd($request->all());
            $id = $request->id;
            $nominal_ledger = NominalLedger::findOrFail($id);
            $input = $request->all();
            $nominal_ledger->update($input);

            return respond(true, 'data updated successfully!', $nominal_ledger, 201);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function staffSavings(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'savings_type' => 'required|exists:nominal_ledgers,id',
            'mode_of_savings' => 'required|exists:mode_of_savings,id',
            'debit_account' => 'required|exists:accounts,id',
            // 'teller_no' => 'nullable',
            'cheque_no' => 'nullable'
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // Begin a database transaction
            DB::beginTransaction();
            $savings = NominalLedger::find($request->savings_type);
            // dd($savings);
            $prefix = convertToUppercase($savings->description);
            $orders = MemberSavings::where('savings_type', $request->savings_type)->get();

            $count = count($orders);
            $figure = $count + 1;
            $length = strlen($figure);
            if ($length == 1) {
                $code = "00" . $figure;
            }
            if ($length == 2) {
                $code = "00" . $figure;
            }
            if ($length == 3) {
                $code = "0" . $figure;
            }
            if ($length == 4) {
                $code = $figure;
            }

            // // Check if the member has ongoing savings
            // $check = MemberSavings::where('member_id', $request->member_id)->where('savings_type', $request->savings_type)->first();
            // if ($check) {
            //     throw new \Exception('Member has same ongoing saving!');
            // }

            // Prepare input data
            $input = $request->all();
            $input['prefix'] = $prefix . '-' . $code;
            $input['balance'] = $request->amount;
            $input['uuid'] = $uuid = rand();
            if($request->has('cheque_no')){
                $input['is_bank']  = 1;
            }

            // Create the savings record
            $savingNew = MemberSavings::create($input);

            // Perform accounting entries
            $bankcode = $request->debit_account;
            $amount = $input['balance'];
            $date = $request->transaction_date;
            $detail = $savingNew->beneficiary->name . ' ' . " started a saving plan";
            $glcode = $savings->report_to; // Assuming you have a field for savings GL code
            receivablesInsertion($amount, $uuid, 4, $detail, $request->transaction_date, $request->mode_of_savings);
            // Post accounting entries
            postDoubleEntries($savingNew->prefix, $bankcode, $amount, 0, $detail, $date); // debit the bank account
            postDoubleEntries($savingNew->prefix, $glcode, 0, $amount, $detail, $date); // credit the savings account
            saveCustomerLedger($request->member_id, $savingNew->prefix,$amount, 0,  $detail, $amount);
            // Commit the transaction
            DB::commit();

            // Return a success response
            return respond(true, "Savings record saved successfully!", $savingNew, 201);
        } catch (\Exception $exception) {
            // Rollback the transaction if an error occurs
            DB::rollback();

            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function staffLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_name' => 'required|exists:customers,id',
            'loan_name' => 'required|integer|exists:nominal_ledgers,id',
            'bank' => 'required|integer',
            'principal_amount' => 'required|numeric|min:0',
            'interest_amount' => 'required|numeric|min:0',
            'total_repayment' => 'required|numeric|min:0',
            'monthly_deduction' => 'required|numeric|min:0',
            'loan_interest' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'duration' => 'required',
            'cheque_number' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $loan = NominalLedger::find($request->loan_name);
            $prefix = convertToUppercase($loan->description);
            $orders = MemberLoan::where('loan_name', $request->loan_name)->get();

            $count = count($orders);
            $figure = $count + 1;
            $length = strlen($figure);
            if ($length == 1) {
                $code = "00" . $figure;
            }
            if ($length == 2) {
                $code = "00" . $figure;
            }
            if ($length == 3) {
                $code = "0" . $figure;
            }
            if ($length == 4) {
                $code = $figure;
            }
            // dd($prefix);
            // dd($request->all());
            // $check = MemberLoan::where('employee_id', $request->member_name)->where('loan_name', $request->loan_name)->where('balance', '>', 0)->first();
            $existingLoan = MemberLoan::where('employee_id', $request->member_name)->where('loan_name', $request->loan_name)->where('balance', '>', 0)->first();

            if ($existingLoan) {
                throw new \Exception('Member already has an ongoing loan of this type. Kindly reapply when repayment has been fully made.');

                // if ($check) {
                //     throw new \Exception('Member has ongoing package for this loan! Kindly reapply when repayment has been fully made! ');
                // throw new \Exception('You never pay the one u borrow finish! Unku Getaway');
            }

            $input = $request->all();
            $input['prefix'] = $prefix . '-' . $code;
            // dd($input);
            $input['uuid'] = $uuid = rand();
            $input['principal_amount'] = (preg_replace('/[^\d.]/', '', $request->principal_amount));
            $input['interest_amount'] = (preg_replace('/[^\d.]/', '', $request->interest_amount));
            $input['total_repayment'] = (preg_replace('/[^\d.]/', '', $request->total_repayment));
            $input['monthly_deduction'] = (preg_replace('/[^\d.]/', '', $request->monthly_deduction));
            $input['loan_interest'] = (preg_replace('/[^\d.]/', '', $request->loan_interest));
            $input['balance'] = (preg_replace('/[^\d.]/', '', $request->total_repayment));
            $input['employee_id'] = $request->member_name;
            $input['total_loan'] = $input['interest_amount'] + $input['principal_amount'];

            $input['action_by'] = Auth::user()->id;
            $input['gl_code'] = $request->loan_name;
            $loan = MemberLoan::create($input);
            // dd($input);
            // $loan->save;
            // get report to account
            $report = NominalLedger::find($request->loan_name);

            $glcode = $report->report_to;
            $amount = $loan->principal_amount;
            $detail = $loan->beneficiary->name . ' ' . "started a loan plan";
            $bankcode = $request->bank;
            $chq_teller = $loan->reciept_number;
            $transaction_date = $request->transaction_date;
            $payment_mode = "Bank";
            // credit report to account
            postDoubleEntries($loan->prefix, $glcode, 0, $amount, $detail, $transaction_date); // credit the bank account
            // debit the bank
            postDoubleEntries($loan->prefix, $bankcode, $amount, 0, $detail, $transaction_date); // debit the loan account
            saveCustomerLedger($input['employee_id'], $loan->prefix, $amount, 0, $detail, $amount);
            DB::commit();
            return respond(true, "Record saved successfully!", $loan, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function newStaffLoan(Request $request)
    {
        // Modify validation to accept arrays for most fields, excluding member_name and transaction_date
        $validator = Validator::make($request->all(), [
            'member_name' => 'required|exists:customers,id',
            'loan_name' => 'required|array',
            'loan_name.*' => 'integer|exists:nominal_ledgers,id',
            'principal_amount' => 'required|array',
            'principal_amount.*' => 'numeric|min:0',
            'interest_amount' => 'required|array',
            'interest_amount.*' => 'numeric|min:0',
            'total_repayment' => 'required|array',
            'total_repayment.*' => 'numeric|min:0',
            'monthly_deduction' => 'required|array',
            'monthly_deduction.*' => 'numeric|min:0',
            'loan_interest' => 'required|array',
            'loan_interest.*' => 'numeric|min:0',
            'transaction_date' => 'required|date',
            'duration' => 'required|array',
            'duration.*' => 'numeric',
            // Add any additional fields that require arrays
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();

            // Loop through each loan entry in the arrays
            foreach ($request->loan_name as $index => $loan_name) {
                $loan = NominalLedger::find($loan_name);
                $prefix = convertToUppercase($loan->description);
                $orders = MemberLoan::where('loan_name', $loan_name)->get();

                $count = count($orders);
                $figure = $count + 1;
                $code = str_pad($figure, 3, '0', STR_PAD_LEFT);

                // // Check for an existing loan of the same type for the member
                // $existingLoan = MemberLoan::where('employee_id', $request->member_name)
                //                         ->where('loan_name', $loan_name)
                //                         ->where('balance', '>', 0)
                //                         ->first();

                // if ($existingLoan) {
                //     throw new \Exception("Member already has an ongoing loan of this type. Kindly reapply when repayment has been fully made.");
                // }

                // Prepare input for each loan entry
                $input = [
                    'prefix' => $prefix . '-' . $code,
                    'uuid' => rand(),
                    'principal_amount' => preg_replace('/[^\d.]/', '', $request->principal_amount[$index]),
                    'interest_amount' => preg_replace('/[^\d.]/', '', $request->interest_amount[$index]),
                    'total_repayment' => preg_replace('/[^\d.]/', '', $request->total_repayment[$index]),
                    'monthly_deduction' => preg_replace('/[^\d.]/', '', $request->monthly_deduction[$index]),
                    'loan_interest' => preg_replace('/[^\d.]/', '', $request->loan_interest[$index]),
                    'balance' => preg_replace('/[^\d.]/', '', $request->total_repayment[$index]),
                    'employee_id' => $request->member_name,
                    'loan_name' => $loan_name,
                    'duration' => $request->duration[$index],
                    'transaction_date' => $request->transaction_date,
                    'total_loan' => $request->principal_amount[$index] + $request->interest_amount[$index],
                    'action_by' => Auth::user()->id,
                    // 'gl_code' => $loan_name,
                ];

                // Create loan record
                $loanRecord = MemberLoan::create($input);

                // Get report and handle double entries
                $report = NominalLedger::find($loan_name);
                if ($report->company_id != auth()->user()->company_id) {
                    return respond(false, "This account does not belongs to your company!", null, 400);
                }
                $glcode = $report->report_to;
                if (!$glcode) {
                    return respond(false, "No account specified for this loan account. Please fix!", null, 400);
                }
                $amount = $loanRecord->principal_amount;
                $detail = $loanRecord->beneficiary->name . ' ' . "started a loan plan";
                $bankcode = $report->interest_gl;
                if (!$bankcode) {
                    return respond(false, "No account specified for this loan account. Please fix!", null, 400);
                }
                $transaction_date = $request->transaction_date;

                // Post double entries for the loan transaction
                postDoubleEntries($loanRecord->prefix, $bankcode, 0, $amount, $detail, $transaction_date); // credit the bank account
                postDoubleEntries($loanRecord->prefix, $glcode, $amount, 0, $detail, $transaction_date); // debit the loan account

                // Save to customer ledger
                saveCustomerLedger($input['employee_id'], $loanRecord->prefix, $amount, 0, $detail, $amount);

                // Save transaction record
                MyTransactions::create([
                    "amount" => $amount,
                    "balance" => $amount,
                    "amount_paid" => 0,
                    "description" => $detail,
                    "transaction_date" => $transaction_date,
                    "debit_gl_code" => $glcode,
                    "credit_gl_code" => $bankcode,
                    "customer_id" => $input['employee_id'],
                    "type" => 2,
                    "narration" => "Loan",
                    "uuid" => $loanRecord->prefix,
                    "payable_type" => $loan_name,
                    "invoice_number" => $input['uuid']
                ]);
            }

            DB::commit();
            return respond(true, "Loans saved successfully!", $loanRecord, 200);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchStaffLoans1()
    {
        try {

            $user = Auth::user();

            $staffLoans = MyTransactions::where('type', 2)->with(['loanname', 'memberLoan'])
                ->orderBy('created_at', 'Desc')->get();
            return respond(true, 'Staff loans fetched successfully', $staffLoans, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchStaffLoans()
    {
        try {
            // Authenticated user
            $user = Auth::user();

            // Fetch transactions where type = 2 and eager load related loanname and memberLoan
            $staffLoans = MemberLoan::where('company_id', $user->company_id)
                ->with(['report', 'user', 'loan', 'beneficiary'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Return the fetched data
            return respond(true, 'Staff loans fetched successfully', $staffLoans, 200);

        } catch (\Exception $exception) {
            // Return error if something goes wrong
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function fetchApprovedStaffLoans()
    {
        try {
            // Authenticated user
            $user = Auth::user();

            // Fetch transactions where type = 2 and eager load related loanname and memberLoan
            $staffLoans = MemberLoan::where('company_id', $user->company_id)->where('approved', 1)
                ->select(['id', 'interest_amount', 'principal_amount', 'total_loan', 'balance', 'loan_interest', 'duration', 'loan_name', 'employee_id', 'disbursed', 'created_at'])->with(['loan', 'beneficiary'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Return the fetched data
            return respond(true, 'Staff loans fetched successfully', $staffLoans, 200);

        } catch (\Exception $exception) {
            // Return error if something goes wrong
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function approveMemberLoan(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:member_loans,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $register = MemberLoan::findOrFail($id);
            // $userType = auth()->user()->type;

            $register->update(['approved' => 1]);

            return respond(true, 'Loan approved successfully', $register, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function disburseMemberLoan(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:member_loans,id',
            'transaction_date' => 'required|date',
            'disbursed_amount' => 'required',
            'cheque_number' => 'nullable',
            'payment_mode' => 'required|exists:mode_of_savings,id',
            'bank' => 'required|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $register = MemberLoan::findOrFail($id);

            if ($register->approved != 1) {
                return respond(false, 'Loan is not yet approved', $register, 400);
            }
            // $userType = auth()->user()->type;

            $register->update([
                'disbursed' => 1,
                'transaction_date' => $request->transaction_date,
                'disbursed_amount' => $request->disbursed_amount,
                'payment_mode' => $request->payment_mode,
                'bank' => $request->bank,
                'cheque_number' => $request->cheque_number,
            ]);

            return respond(true, 'Loan disbursed successfully', $register, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function disapproveMemberLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:member_loans,id',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $register = MemberLoan::findOrFail($id);
            if ($register->approval_status === 2) {
                return respond(false, 'Loan is already disapproved', null, 400);
            }

            $register->update(['approved' => 2, 'disapproved_by' => auth()->user()->id]);
            LoanDisapprovalComment::create([
                'description' => $request->description,
            ]);

            $register->save();

            return respond(true, 'Loan disapproved successfully', $register, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchStaffSavings()
    {
        try {
            $user = Auth::user();
            // dd("here");
            $staffSavings = MemberSavings::where('company_id', $user->company_id)->with(['membername', 'report', 'user', 'SavingType', 'ModeOfSavings', 'DebitAccount'])->get();

            return respond(true, 'Staff savings fetched successfully', $staffSavings, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function fetchStaffBankSavings()
    {
        try {
            $user = Auth::user();
            // dd("here");
            $staffSavings = MemberSavings::where('company_id', $user->company_id)->where('is_bank',1)->with(['membername', 'report', 'user', 'SavingType', 'ModeOfSavings', 'DebitAccount'])->get();

            return respond(true, 'Staff savings fetched successfully', $staffSavings, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function fetchMemberSavings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $user = Auth::user();
            // dd("here");
            $staffSavings = MemberSavings::where('member_id', $request->member_id)->select('id', 'prefix', 'savings_type')->with(['SavingType'])->get();

            return respond(true, 'Staff savings fetched successfully', $staffSavings, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function fetchMemberLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $user = Auth::user();
            // dd("here");
            $staffSavings = MemberLoan::where('employee_id', $request->member_id)->select('id', 'prefix', 'loan_name')->with(['loan'])->get();

            return respond(true, 'Staff savings fetched successfully', $staffSavings, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }






// public function fetchMemberLoanWithTotal(Request $request)
// {
//     // Validate the request input
//     $validator = Validator::make($request->all(), [
//         'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
//     ]);

//     if ($validator->fails()) {
//         return respond(false, $validator->errors(), null, 400);
//     }

//     try {
//         // Step 1: Fetch all loans applied for by the member (loan_name, principal_amount, balance)
//         $memberLoans = MemberLoan::where('employee_id', $request->member_id)->get(); // Use get() to retrieve the actual data

//         // Step 2: Extract loan type IDs from the loans
//         $loanTypeIds = $memberLoans->pluck('loan_name')->toArray(); // loan_name represents nominal ledger ID

//         // Step 3: Fetch loan types (nominal ledgers)
//         $loanTypes = NominalLedger::whereIn('id', $loanTypeIds)
//             ->select('id', 'description') // Fetch only relevant fields
//             ->get();

//         // Step 4: Map and merge loan data (principal amounts, balances) and repayment data
//         $result = [];

//         foreach ($loanTypes as $loanType) {
//             // Find the loans that match this loan type
//             $loansForThisType = $memberLoans->where('loan_name', $loanType->id);

//             // Prepare the array structure for each loan type
//             $result[$loanType->description] = $loansForThisType->map(function ($loan) {
//                 // Fetch total repayment for this loan account
//                 $totalRepayment = Repayment::where('account_id', $loan->id)
//                     ->sum('amount'); // Sum the repayment amounts for this account_id

//                 return [
//                     'principal_amount' => number_format($loan->principal_amount, 2), // Principal amount for this loan
//                     'total_repayment' => number_format($totalRepayment, 2), // Total repayment for this loan
//                     'outstanding' => number_format($loan->balance, 2), // Outstanding balance for this loan
//                 ];
//             })->toArray(); // Convert collection to array
//         }

//         // Step 5: Return the result
//         return respond(true, 'Member loan details fetched successfully', $result, 200);

//     } catch (\Exception $exception) {
//         // Catch and handle any errors
//         return respond(false, $exception->getMessage(), null, 400);
//     }
// }

public function fetchMemberLoanWithTotal(Request $request)
{
    // Validate the request input
    $validator = Validator::make($request->all(), [
        'member_id' => 'required|exists:customers,id', // Ensure the member_id exists in the customers table
    ]);

    if ($validator->fails()) {
        return respond(false, $validator->errors(), null, 400);
    }

    try {
        // Step 1: Fetch all loans applied for by the member (loan_name, principal_amount, balance)
        $memberLoans = MemberLoan::where('employee_id', $request->member_id)->get(); // Use get() to retrieve the actual data

        // Step 2: Extract loan type IDs from the loans
        $loanTypeIds = $memberLoans->pluck('loan_name')->toArray(); // loan_name represents nominal ledger ID

        // Step 3: Fetch loan types (nominal ledgers)
        $loanTypes = NominalLedger::whereIn('id', $loanTypeIds)
            ->select('id', 'description') // Fetch only relevant fields
            ->get();

        // Step 4: Map and merge loan data (principal amounts, balances) and repayment data
        $result = [];

        foreach ($loanTypes as $loanType) {
            // Find the loans that match this loan type
            $loansForThisType = $memberLoans->where('loan_name', $loanType->id);

            // Prepare the array structure for each loan type with numbering
            $counter = 1; // Initialize the counter for each loan type

            foreach ($loansForThisType as $loan) {
                // Fetch total repayment for this loan account
                $totalRepayment = Repayment::where('account_id', $loan->id)
                    ->sum('amount'); // Sum the repayment amounts for this account_id

                // Create a numbered loan description like "Ileya Loan 01", "Ileya Loan 02"
                $loanDescription = $loanType->description . ' ' . sprintf("%02d", $counter); // Adds numbering with leading zeros (e.g., "01")

                // Attach the loan details to the result array with loan number
                $result[] = [
                    'id' => $loan->id, // Loan type with numbering
                    'loan_type' => $loanDescription, // Loan type with numbering
                    'total_loan' => $loan->total_loan, // Principal amount for this loan
                    'total_paid' => $totalRepayment, // Total repayment for this loan
                    'outstanding' => $loan->balance, // Outstanding balance for this loan
                ];

                $counter++; // Increment the counter for the next loan of the same type
            }
        }

        // Step 5: Return the result
        return respond(true, 'Member loan details fetched successfully', $result, 200);

    } catch (\Exception $exception) {
        // Catch and handle any errors
        return respond(false, $exception->getMessage(), null, 400);
    }
}





    public function fetchMemberLedgerByAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:customers,id',
            'account_id' => 'required|exists:nominal_ledgers,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $user = Auth::user();
            // dd("here");
            $staffSavings = MonthlyDeduction::where('member_id', $request->member_id)->where('type', $request->account_id)->select('id', 'description', 'type', 'member_id', 'transaction_date', 'created_at', 'amount')->get();
            $staffSavings = $staffSavings->map(function ($item) {
                $comparisonTimestamp = $item->created_at;
                // You can add more fields or manipulate existing ones here
                $ledgerEntries = CustomerPersonalLedger::where('customer_id', $item->member_id)
                    ->where('description', $item->description)
                    ->where(function ($query) use ($item) {
                        $query->where('debit', $item->amount)
                            ->orWhere('credit', $item->amount);
                    })
                    ->get();
                $ledgerEntry = $ledgerEntries->filter(function ($entry) use ($comparisonTimestamp) {
                    $entryTimestamp = Carbon::parse($entry->created_at);
                    return $entryTimestamp->diffInSeconds($comparisonTimestamp) <= 3;
                })->first();
                $item->debit = $ledgerEntry->debit; // Example of adding a new column
                $item->credit = $ledgerEntry->credit; // Example of adding a new column
                $item->balance = $ledgerEntry->balance; // Example of adding a new column
                return $item;
            });

            return respond(true, 'Member Ledger fetched successfully', $staffSavings, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


}
