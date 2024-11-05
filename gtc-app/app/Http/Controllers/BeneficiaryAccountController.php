<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\BeneficiaryAccount;
use App\Models\Beneficiary;
use App\Models\Bank;

class BeneficiaryAccountController extends Controller
{
    public function getAllbeneficiaryAccounts()
    {
        $assetCategories = BeneficiaryAccount::all();


        return respond(true, 'All beneficiary accounts retrieved successfully', $assetCategories, 201);


    }

    public function getBeneficiaryAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'beneficiary_id' => 'required|exists:beneficiaries,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 404);
        }
        $beneficiary_id = $request->beneficiary_id;

        $beneficiaries = BeneficiaryAccount::where('beneficiary_id', $beneficiary_id)->get();

        return respond(true, 'Beneficiary account fetched successfully', $beneficiaries, 200);
    }

    public function createBeneficiaryAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'beneficiary_id' => 'required|exists:beneficiaries,id',
                'bank_name' => 'required',
                'bank_account' => 'required',
                'account_name' => 'required',
                'bank_code' => '',
                //'created_by' => 'required|unique:asset_categories,created_by',
                //'asset_id' => 'required|exists:asset_categories,id'
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 400);
            }

            // Create a new zone
            $beneficiaryaccount = BeneficiaryAccount::create($request->all());

            return respond(true, 'Beneficiary account created successfully', $beneficiaryaccount, 201);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the process
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updateBeneficiaryAccount(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:beneficiaries,id',
        'bank_name' => 'nullable|array',
        'bank_account' => 'nullable|array',
        'account_name' => 'nullable|array',
        'name' => 'required',
        'phone_number' => 'required',
        'address' => 'required',
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return respond(false, $validator->errors()->first(), null, 400);
    }

    try {
        $input = $request->all();
        $id = $request->id;
        $beneficiaryAccount = Beneficiary::find($id);


        $beneficiaryAccount->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'email' => $request->email,
        ]);


        // Handle array updates
        if ($request->has('bank_name')) {
            BeneficiaryAccount::where('beneficiary_id', $id)->delete();
            foreach ($request->bank_name as $key => $bankName) {
                BeneficiaryAccount::create([
                "bank_name" => $request->bank_name[$key],
                "bank_account" => $request->bank_account[$key],
                "account_name" => $request->account_name[$key],
                "beneficiary_id" => $id
                ]);
            }
        }

        return respond(true, 'Beneficiary account updated successfully', $beneficiaryAccount, 201);
    } catch (\Exception $exception) {
        return respond(false, $exception->getMessage(), null, 400);
    }
}



    public function deleteBeneficiaryAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:beneficiary_accounts,id',

        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 400);
        }

        $id = $request->input('id');
        $beneficiaryaccount = BeneficiaryAccount::find($id);
        if (!$beneficiaryaccount) {
            return respond(false, 'Beneficiary account not found', null, 404);

        }

        $beneficiaryaccount->delete();
        return respond(true, 'Beneficiary account deleted successfully', $id, 201);
    }


    public function fetchSoftdelete()
    {
        $deleted = BeneficiaryAccount::onlyTrashed()->get();
        return respond(true, 'Fetch archived beneficiary account successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = BeneficiaryAccount::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived beneficiary account restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archived beneficiary account is not deleted!', null, 400);
        } else {
            return respond(false, 'Archived beneficiary account not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = BeneficiaryAccount::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived beneficiary account found to restore!', null, 404);
        }
        BeneficiaryAccount::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archived beneficiary account restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = BeneficiaryAccount::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived beneficiary account permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archived beneficiary account is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archived beneficiary account not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = BeneficiaryAccount::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived beneficiary account found to permanently delete!', null, 404);
        }
        BeneficiaryAccount::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archived beneficiary account permanently deleted successfully!', null, 200);
    }
}
