<?php

namespace App\Http\Controllers\Api;

use App\Models\Beneficiary;
use App\Models\SupplierPersonalLedger;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id = getCompanyid();
        // dd($id);
        $beneficiaries = Beneficiary::where('company_id', $id)->with('banks', 'ledgers')->get();
        return respond(true, 'List of Beneficiaries fetched!', $beneficiaries, 201);

    }

    public function addNewBeneficiary(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:beneficiaries'],
                'phone_number' => ['required', 'unique:beneficiaries'],
                'address' => ['required'],
                'account_name' => ['required'],
                'account_number' => ['required'],
                'bank_name' => ['required'],
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            if ($request->has('phone_number')) {
                $formattedNumber = formatPhoneNumber($request->phone_number);
                if ($formattedNumber['status'] == false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $formattedNumber['message'],
                        'data' => $formattedNumber['data'],
                    ], 400);
                }
            }
            $newValue = Beneficiary::create($data);
            return respond(true, 'New Beneficiary added successfully!', $newValue, 201);


        } catch (\Exception $e) {
            return respond(false, $validator->errors(), null, 400);
        }

    }

    public function supplierLedger(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:beneficiaries,id',
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
        $ledger = SupplierPersonalLedger::where('supplier_id', $request->supplier_id)
            ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
            ->with(['supplier'])->get();
        // dd($sales);

        return respond(true, 'Supplier ledger fetched successfully!', $ledger, 200);
    }
    public function deleteBeneficiary(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:beneficiaries,id',
            ]);
            // Check for validation errors
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $beneficiaries = Beneficiary::findOrFail($id);
            if (auth()->user()->company_id !== $beneficiaries->company_id) {
                return respond('error', 'You do not have permission to delete this Beneficiary.', null, 403);
            }
            $beneficiaries->delete();
            return respond(true, 'Beneficiary deleted successfully!', $beneficiaries, 201);

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function fetchSoftdelete()
    {
        $deleted = Beneficiary::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Beneficiary fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Beneficiary::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived beneficiary restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Beneficiary is not yet deleted!', null, 400);
        } else {
            return respond(false, 'Beneficiary account not found in archive!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Beneficiary::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived beneficiary found to restore!', null, 404);
        }
        Beneficiary::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archived beneficiary restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Beneficiary::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived beneficiary account permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Beneficiary is not yet archived!', null, 400);
        } else {
            return respond(false, 'Beneficiary not found in archive!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Beneficiary::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived beneficiary account found to permanently delete!', null, 404);
        }
        Beneficiary::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archived beneficiary account permanently deleted successfully!', null, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:beneficiaries,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $id = $request->id;
            $beneficiary = Beneficiary::findOrFail($id);

            $beneficiary->delete();


            return respond(true, 'Course deleted successfully', $beneficiary, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


}
