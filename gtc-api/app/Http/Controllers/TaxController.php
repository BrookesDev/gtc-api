<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\WorkingMonth;
use App\Models\Company;
use Carbon\Carbon;

class TaxController extends Controller
{
    public function getallTaxes()
    {
        $tax = Tax::all();


        return respond(true, 'All tax information retrieved successfully', $tax, 201);


    }

    public function getTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 404);
        }
        $company_id = $request->company_id;

        $taxes = Tax::where('company_id', $company_id)->get();

        return respond(true, 'tax information fetched successfully', $taxes, 200);
    }
    public function getCompanyTax(Request $request)
    {
        $company_id = auth()->user()->company_id;

        $taxes = Tax::where('company_id', $company_id)->with('company', 'report_gl')->get();

        return respond(true, 'tax information fetched successfully', $taxes, 200);
    }

    public function createTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'short_name' => 'required',
            'rate' => 'required',
            'report_gl' => 'required|exists:accounts,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 404);
        }
        try {
            $input = $request->all();
            $taxes = Tax::create($input);
            return respond(true, 'Tax created successfully', $taxes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function updateTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:taxes,id',
            'description' => 'nullable',
            'short_name' => 'nullable',
            'rate' => 'nullable',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 404);
        }
        try {
            $input = $request->all();
            $taxes = Tax::find($request->id);
            $taxes->update($input);
            return respond(true, 'Tax updated successfully', $taxes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function deleteTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:taxes,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 404);
        }
        try {
            $taxes = Tax::find($request->id);
            $taxes->delete();
            return respond(true, 'Tax archived successfully', $taxes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }


    public function fetchDistinctUUID()
    {
        $user = auth()->user();
        $distinctUuids = Journal::where('company_id', $user->company_id)->select('uuid')->distinct()->get();
        $distinctUuids->map(function ($receipt) {
            $verify = Journal::where('uuid', $receipt->uuid)->first();
            // dd($verify)
            $receipt->total_credit = Journal::where('uuid', $receipt->uuid)->sum('credit');
            $receipt->total_debit = Journal::where('uuid', $receipt->uuid)->sum('debit');
            $receipt->description = $verify->details; // Example of adding a new column// Example of adding a new column
            $receipt->transaction_date = $verify->transaction_date; // Example of adding a new column// Example of adding a new column
            $receipt->created_at = $verify->created_at; // Example of adding a new column// Example of adding a new column
            return $receipt;
        });
        return respond(true, 'Transactions fetched successfully!', $distinctUuids, 200);
    }

    public function deleteJournalEntries(Request $request)
    {
        try {
            $user = auth()->user();
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'uuid' => 'required|exists:journals,uuid',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $uuid = $request->input('uuid');
            $entry = Journal::where('uuid', $uuid)->first();


            if ($entry->company_id != $user->company_id) {
                return respond(false, "You can't delete this transaction", null, 400);
            } else {
                Journal::where('uuid', $uuid)->delete();
            }

            DB::commit();
            return respond(true, 'Journal entry archived successfully!', $entry, 200);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function getDeletedJournalEntries()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Journal::where('company_id', auth()->user()->company_id)
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

    public function restoreDeletedJournalEntries(Request $request)
    {


        // Validate the request data
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:journals,uuid',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $department = Journal::withTrashed()->find($request->uuid);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived journal entry restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Journal entry is not yet archived!', null, 400);
        } else {
            return respond(false, 'Journal entry not found in archive!', null, 404);
        }
    }
    public function restoreAllJournalEntries(Request $request)
    {

        $deletedDepartments = Journal::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No journal entries found to permanently delete!', null, 404);
        }
        Journal::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All journal entries permanently deleted successfully!', null, 200);
    }

    public function forceDeleteJournalEntry(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:journals,uuid',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = Journal::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Journal not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function forceDeleteAllJournalEntries()
    {

        try {

            $accounts = Journal::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function getJournalTrans(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'uuid' => 'required|exists:journals,uuid',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $uuid = $request->input('uuid');
            $entry = Journal::where('uuid', $uuid)->with('account')->get();

            return respond(true, 'Transaction fetched successfully!', $entry, 200);

        } catch (\Exception $exception) {
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }


    public function fetchSoftdelete()
    {
        $deleted = Tax::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archived tax successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Tax::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived tax restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archived tax is not deleted!', null, 400);
        } else {
            return respond(false, 'Archived tax not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Tax::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived tax found to restore!', null, 404);
        }
        Tax::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archived tax restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Tax::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'archived tax permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Customer is not yet archived!', null, 400);
        } else {
            return respond(false, 'archived tax not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Tax::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No tax found to permanently delete!', null, 404);
        }
        Tax::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All tax permanently deleted successfully!', null, 200);
    }
    public function updateProvinceMonth(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = auth()->user()->company_id;
            $province = Company::find($id);
            if (!$province) {
                return respond(false, 'Invalid Company', $id, 400);
            }
            // Check if current_month is set, if not use created_at
            if (empty($province->current_month)) {
                $currentMonth = Carbon::parse($province->created_at);
            } else {
                $currentMonth = Carbon::parse($province->current_month);
            }
            if (empty($province->last_date)) {
                $lastdate = Carbon::parse($province->date);
            } else {
                $lastdate = Carbon::parse($province->last_date);
            }

            // Calculate the last day of the next month
            // $nextMonth = $currentMonth->copy()->addMonthNoOverflow();
            // $nextMonth = $lastdate->copy()->addMonthNoOverflow();
            $nextMonth = $currentMonth->copy()->addMonth()->endOfMonth();

            // Update the current_month column
            $province->current_month = $nextMonth->toDateString();
            $province->last_date = $nextMonth->toDateString();
            $province->already_calculated_depreciation = 0;
            $province->save();
            // Insert into working_month table
            $workingMonthData = [
                'company_id' => $province->id,
                'current_date' => $nextMonth->toDateString(),
                'last_date' => $nextMonth->toDateString(),
            ];
            $workingMonth = WorkingMonth::create($workingMonthData);
            DB::commit();
            return respond(true, 'Company current month updated successfully', $province, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, 'Server Error', $exception->getMessage(), 500);
        }
    }
    public function reverseProvinceMonth(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = auth()->user()->company_id;
            $province = Company::find($id);
            // dd($province);
            if (!$province) {
                return respond(false, 'Invalid Company', $id, 400);
            }
            // Determine the current month to use (current_month or created_at)
            $getCreatedAt = Carbon::parse($province->date);
            if (empty($province->current_month)) {
                $currentMonth = Carbon::parse(now());
            } else {
                $currentMonth = Carbon::parse($province->current_month);
            }
            if (empty($province->last_date)) {
                $lastdate = Carbon::parse($province->date);
            } else {
                $lastdate = Carbon::parse($province->last_date);
            }
            // $previousMonth = $currentMonth->subMonth()->endOfMonth();
            $previousMonth = $currentMonth->copy()->subMonthNoOverflow();
            $previousMonth = $lastdate->copy()->subMonthNoOverflow();
            // dd($previousMonth);
            // dd($previousMonth);
            // Check if previous month is before the created_at date

            // if ($getCreatedAt >= ($previousMonth)) {
            //     return respond(false, "You can't go beyond your created date", null, 403);
            // }

            if ($currentMonth->format('Y-m') == $getCreatedAt->format('Y-m') && $lastdate->format('Y-m') == $getCreatedAt->format('Y-m')) {
                return respond(false, "You can't go beyond your created date", null, 403);
            }

            // Update the current_month column
            $province->current_month = $previousMonth->toDateString();
            $province->last_date = $previousMonth->toDateString();
            $province->save();
            $workingMonthData = [
                'company_id' => $province->id,
                'current_date' => $previousMonth->toDateString(),
                'last_date' => $previousMonth->toDateString(),
            ];
            $workingMonth = WorkingMonth::create($workingMonthData);


            DB::commit();
            return respond(true, 'Company current month updated to previous month successfully', $province, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, 'Server Error', $exception->getMessage(), 500);
        }
    }

}
