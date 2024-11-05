<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Plan;
use App\Models\AssignCompanyUsers;
use App\Models\BookingPayment;
use App\Models\AllTransaction;
use App\Models\User;
use App\Models\Account;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function duplicateAccount()
    {
        return respond(true, "All Done", null, 200);
        $bAccounts = Account::where('company_id', 6)->get();
        $provinces = [56];
        foreach ($bAccounts as $account) {
            foreach ($provinces as $province) {
                // dd($province);
                Account::create([
                    "class_id" => $account->class_id,
                    "category_id" => $account->category_id,
                    "sub_category_id" => $account->sub_category_id,
                    "gl_name" => $account->gl_name,
                    "sub_sub_category_id" => $account->sub_sub_category_id,
                    "direction" => $account->direction,
                    "created_by" => 66,
                    "gl_code" => $account->gl_code,
                    "company_id" => $province,
                ]);
            }
        }
        return respond(true, "All Done", $provinces, 200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function planDetails(Request $request)
    {
        $plan = Plan::find($request->id);
        $string = $plan->access;
        $string = str_replace(['{', '}'], '', $string);
        $array = explode(',', $string);
        $array = array_map(function ($item) {
            return trim($item, '"');
        }, $array);
        dd($array);
        dd($string);
    }

    public function getTime()
    {

        return respond(true, 'List of plans fetched!', [Carbon::now(),now()], 200);

    }

    public function getMonths(Request $request)
    {
        $company = Company::where('id', auth()->user()->company_id)->first();
        $startDate = Carbon::parse($company->created_at);
        $endDate = Carbon::now();
        $currentDate = $startDate->copy()->startOfMonth();
        while ($currentDate->lte($endDate) && $currentDate->month <= $endDate->month) {
            $monthsToView[] = $currentDate->format('F Y');
            $currentDate->addMonth();
        }
        return respond(true, 'Months fetched successfully!', $monthsToView, 200);
    }

    public function planList(Request $request)
    {
        $plans = Plan::get();
        $plans->map(function ($single) use ($request) {
            $string = $single->access;
            $string = str_replace(['{', '}'], '', $string);
            $array = explode(',', $string);
            $array = array_map(function ($item) {
                return trim($item, '"');
            }, $array);
            $single->priviledges = $array;
        });
        return respond(true, 'List of plans fetched!', $plans, 200);
    }

    public function migrate(Request $request)
    {
        $payments = BookingPayment::get();
        foreach ($payments as $payment) {
            // if(!AllTransaction::where('transaction_number', $payment->booking->booking_order)->first()){
            AllTransaction::create([
                "amount" => $payment->amount,
                "transaction_number" => $payment->booking->booking_order,
                "description" => $payment->booking->description,
                "type" => 1,
                "transaction_date" => $payment->payment_date,
                "action_by" => $payment->created_by,
                "company_id" => $payment->company_id
            ]);
            // }
        }
        return respond(true, 'Migration successful!', $payment, 200);
    }

    public function index()
    {
        //let's get all companies

        $data['companies'] = Company::where('auditor_id', auth()->user()->id)->get();

        //let's send those records to the requesting server or application
        return response()->json(["data" => $data, "message" => "Company fetched successfully"], 200);

        // return view('admin.company.index', $data);
    }
    public function fetchAllCompanies()
    {
        $data = Company::where('company_id', auth()->user()->company_id)->orderBy('created_at', 'DESC')->get();


        return respond(true, 'Purchase Order created successfully', $data, 200);

    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'address' => 'required',
                'phone_number' => 'nullable',
                'email' => 'nullable|email',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $company = Company::create([
                'name' => $request->name,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'company_id' => auth()->user()->company_id,
                'is_main' => 0,
            ]);

            return respond(true, 'Company Created Successfully!', $company, 201);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function updateCompany(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:companies,id',
                'name' => 'nullable',
                'address' => 'nullable',
                'phone_number' => 'nullable',
                'email' => 'nullable',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $input = $request->all();
            $company = Company::find($request->id);
            $company->update($input);

            return respond(true, 'Company updated Successfully!', $company, 201);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function deleteCompany(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $company = Company::find($request->id);
            // if ($company->company_id != auth()->user()->company_id){
            //     return respond(false, 'You cannot delete a company that is not yours!', null, 400);
            // }
            $company->delete();
            return respond(true, 'Company deleted Successfully!', $company, 201);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function AssignUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'company_id' => 'required|array',
                'company_id.*' => 'required|numeric|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            // Check if the user belongs to the authenticated user's company
            $authUserCompany = auth()->user()->company_id;
            $userCheck = User::where('id', $request->user_id)->where('company_id', $authUserCompany)->exists();
            if (!$userCheck) {
                return respond(false, 'This user does not belong to this company', null, 400);
            }

            // Check if the companies to be assigned belong to the authenticated user's company
            $companyIds = $request->company_id;
            $companyCheck = Company::whereIn('id', $companyIds)->where('company_id', $authUserCompany)->count();
            if ($companyCheck != count($companyIds)) {
                return respond(false, 'One or more companies do not belong to you', null, 400);
            }

            // Check if any of the companies are already assigned to another user
            $alreadyAssigned = AssignCompanyUsers::whereIn('company_id', $companyIds)->exists();
            if ($alreadyAssigned) {
                return respond(false, 'One or more companies are already assigned to another user', null, 400);
            }

            // Assign companies to user
            foreach ($companyIds as $companyId) {
                AssignCompanyUsers::create([
                    'user_id' => $request->user_id,
                    'company_id' => $companyId,
                ]);
            }

            return respond(true, 'User assigned successfully', $companyIds, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function getDeletedAssignedCompanies()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = AssignCompanyUsers::onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Deleted assigned companies fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreAssignedCompany(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:assign_company_users,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = AssignCompanyUsers::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllAssignedCompanies(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = AssignCompanyUsers::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteAssignedCompany(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:assign_company_users,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = AssignCompanyUsers::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Company not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function forceDeleteAllAssignedCompanies()
    {

        try {

            $accounts = AssignCompanyUsers::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(),null, 500);
        }
    }
    public function deleteAssignedUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:assign_company_users,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $assign_company = AssignCompanyUsers::find($request->id);

        $companydata = Company::where('id', $assign_company->company_id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$companydata) {
            return respond(false, 'This data does not belong to you', null, 400);
        }

        $assign_company->delete();
        return respond(true, 'Data deleted successfully', $assign_company, 200);
    }



    public function UserCompanies()
    {
        try {
            $user = auth()->user()->id;
            $companyusers = AssignCompanyUsers::where('user_id', $user)->pluck('company_id')->toArray();
            $companies = Company::whereIn('id', $companyusers)->get();
            return respond(true, 'Companies fetched successfully', $companies, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = validator::make(
            $request->all(),
            [
                'company_name' => 'required',
                'company_address' => 'required',
                'company_email' => 'required|email|unique:companies,company_email',
                'company_phone_number' => 'required|unique:companies,company_phone_number',
                'company_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:100',
                'description' => 'required',
                'manager_name' => 'required'

            ]

        );
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        //let's validate duplicate

        $newCompany = new Company();
        $newCompany->company_name = $request->company_name;
        $newCompany->company_address = $request->company_address;
        $file = $request->file('company_logo');
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/uploads', $fileName);
        $newCompany->company_logo = $fileName;
        $newCompany->company_email = $request->company_email;
        $newCompany->company_phone_number = $request->company_phone_number;
        $newCompany->description = $request->description;
        $newCompany->manager_name = $request->manager_name;
        //some...
        $newCompany->auditor_id = auth()->user()->id;

        $newCompany->save();
        //company



        return response()->json(['data' => $newCompany, 'message' => "Company created successfully"], 200);

    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        return response()->json(['data' => $company], 200);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\C
     * company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        //ome
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }

    public function registerAuditor(Request $request)
    {
        //validate input
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'phone_no' => 'required|unique:users,phone_no',
                'password' => 'required',
                'company_name' => 'required',
                'company_phone_number' => 'required|unique:users,company_phone_number',
                'company_email' => 'required|email|unique:users,company_email',
                'company_address' => 'required',
                'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:100',


            ]
        );
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        //    dd($request->all());
        $newAuditor = new User();
        $newAuditor->name = $request->name;
        $newAuditor->email = $request->email;
        $password = $request->password;
        $newAuditor->password = Hash::make($password);
        $newAuditor->phone_no = $request->phone_no;
        $newAuditor->role_id = $request->role_id;
        $newAuditor->company_name = $request->company_name;
        $newAuditor->company_phone_number = $request->company_phone_number;
        $newAuditor->company_email = $request->company_email;
        // save the company logo
        if ($request->has('company_logo')) {
            $file = $request->file('company_logo');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/uploads', $fileName);
            $newAuditor->company_logo = $fileName;
        }
        $newAuditor->company_address = $request->company_address;

        $newAuditor->save();
        //Auditor




        return response()->json(['data' => $newAuditor, 'message' => "Auditor created successfully"], 200);


    }

    public function fetchSoftdelete()
    {
        $deleted = Company::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch deleted department successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Company::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Department restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Department is not deleted!', null, 400);
        } else {
            return respond(false, 'Department not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Company::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved companies found to restore!', null, 404);
        }
        Company::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved companies restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Company::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved companies permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved companies is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved companies not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Company::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved companies found to permanently delete!', null, 404);
        }
        Company::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved companies permanently deleted successfully!', null, 200);
    }
    public function updateBTDate()
    {
        $id = 6;//getCompanyid();
        // dd($id);
        $bookings = BookingPayment::where('company_id', $id)->get();
        $count = 0;
        foreach($bookings as $booking){
            $rBooking = Booking::find($booking->booking_id);
            $journal = Journal::where('uuid', $rBooking->uuid)
            ->where(function ($query) use ($booking) {
                $query->where('credit', $booking->amount)
                    ->orWhere('debit', $booking->amount);
            })
            ->first();
            if($journal){
                $booking->update(['payment_date' => $journal->transaction_date]);
                $count += 1;
            }
        }
        return respond(true, 'Booking payments fetched successfully!', $count, 201);
    }

}
