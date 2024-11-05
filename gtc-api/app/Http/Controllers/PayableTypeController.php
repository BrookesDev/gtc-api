<?php

namespace App\Http\Controllers;

use App\Models\Payable_Type;
use App\Models\MyTransactions;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayableTypeController extends Controller
{
    public function create(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'description' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('payable_types')->where(function ($query) {
                        return $query->where('province_id', Auth::user()->company_id);
                    })
                ],
                'gl_code' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();

                if ($errors->has('description')) {
                    // Check if the error is due to duplicate description for the same user
                    $existingPayable = Payable_Type::where('description', $data['description'])
                        ->where('province_id', Auth::user()->company_id)
                        ->first();

                    if ($existingPayable) {
                        return respond(false, 'You already created this payable type', null, 400);
                    }
                }

                return respond('error', $errors, null, 400);
            }

            $payable = Payable_Type::create($data);
            return respond(true, 'Payable type created successfully', $payable, 200);

        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }


    public function edit(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'id' => 'required|exists:payable_types,id',
                'description' => [
                    'required',
                    'string',
                    Rule::unique('payable_types', 'description')->ignore($request->id),
                ],
                'gl_code' => 'nullable|exists:accounts,id',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $payable = Payable_Type::find($request->id);
            $payable->update($data);
            return respond(true, 'Payable type updated successfully', $payable, 200);

        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getUnpaidPayables(Request $request)
    {
        // Get all aging buckets
        $agingBuckets = DB::table('aging_buckets')->get();

        // Initialize an array to hold results
        $results = [];

        // Get the current date
        $currentDate = Carbon::now();

        // Loop through each aging bucket
        foreach ($agingBuckets as $bucket) {
            $minDays = $bucket->min_days;
            $maxDays = $bucket->max_days;

            // Calculate the date range
            $startDate = $currentDate->copy()->subDays($maxDays);
            $endDate = $currentDate->copy()->subDays($minDays);

            // Fetch unpaid payables within the date range
            $payables = allTransactions()
                ->where('type', 2) // Payables type
                ->where('balance', '>', 0) // Unpaid balance
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->with('to')->get();

            if (!$payables->isEmpty()) {
                // Add to results
                $results[] = [
                    'description' => $bucket->description,
                    'payables' => $payables
                ];
            }
        }

        return respond(true, 'Data fetched successfully', $results, 200);
    }
    public function getUnpaidReceivables(Request $request)
    {
        // Get all aging buckets
        $agingBuckets = DB::table('aging_buckets')->get();

        // Initialize an array to hold results
        $results = [];

        // Get the current date
        $currentDate = Carbon::now();

        // Loop through each aging bucket
        foreach ($agingBuckets as $bucket) {
            $minDays = $bucket->min_days;
            $maxDays = $bucket->max_days;

            // Calculate the date range
            $startDate = $currentDate->copy()->subDays($maxDays);
            $endDate = $currentDate->copy()->subDays($minDays);
            // Fetch unpaid payables within the date range
            $receivables = allTransactions()
                ->where('type', 1) // Payables type
                ->where('balance', '>', 0) // Unpaid balance
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->with('customer')->get();
            // Add to results
            if (!$receivables->isEmpty()) {
                $results[] = [
                    'description' => $bucket->description,
                    'receivables' => $receivables
                ];
            }
        }

        return respond(true, 'Data fetched successfully', $results, 200);
    }


    public function index()
    {
        $data = getPayableTypes()->with('created_by', 'company', 'account')->get();
        return respond(true, 'Payable types fetched successfully', $data, 200);
    }
    public function delete(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:payable_types,id',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $payable = Payable_Type::where('id', $request->id)
                ->where('province_id', Auth::user()->company_id)
                ->first();
            if (!$payable) {
                return respond(true, 'Invalid payable type', null, 400);
            }
            $payable->delete();
            return respond(true, 'Payable type successfully Archieved', $payable, 200);

        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }

    }

    public function fetchSoftdelete()
    {
        $deleted = Payable_Type::where('province_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Archieved payable type fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Payable_Type::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved payable type restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archieved payable type is not deleted!', null, 400);
        } else {
            return respond(false, 'Archieved payable type not found!', null, 404);
        }
    }
    public function restoreSoftdelete()
    {
        $deletedDepartments = Payable_Type::where('province_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved payable type found to restore!', null, 404);
        }
        Payable_Type::where('province_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved payable type restored successfully!', $deletedDepartments, 200);
    }

    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Payable_Type::withTrashed()
            ->where('id', $request->id)
            ->where('province_id', Auth::user()->company_id)->first();

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived payable type permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archived payable type is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archived payable type not found!', $department, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Payable_Type::where('province_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved payable type found to permanently delete!', null, 404);
        }
        Payable_Type::where('province_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved payable type permanently deleted successfully!', $deletedDepartments, 200);
    }
}
