<?php

namespace App\Http\Controllers;

use App\Models\SalesRep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;



class SalesRepController extends Controller
{

    public function index()
    {
        $salesorder = SalesRep::where('company_id', auth()->user()->company_id)
            ->orderBy('created_at', 'DESC')->with('company')->get();
        return respond(true, 'Sales Representatives fetched successfully', $salesorder, 200);
    }

    public function create(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:sales_reps,email',
                'phone_number' => 'required|numeric|unique:sales_reps,phone_number',
                'address' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $data = $request->all();
            $rep = SalesRep::create($data);



            return respond(true, 'Sales Representative created successfully', $rep, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sales_reps,id',
                'name' => 'nullable',
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    Rule::unique('sales_reps', 'email')->ignore($request->id),
                ],
                'phone_number' => [
                    'nullable',
                    'string',
                    Rule::unique('sales_reps', 'phone_number')->ignore($request->id),
                ],
                'address' => 'nullable',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $rep = SalesRep::find($id);

            $data = $request->all();

            $rep->update($data);

            return respond(true, 'Sales Representative updated successfully', $rep, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sales_reps,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $student = SalesRep::findOrFail($id);
            $student->delete();

            return respond(true, 'Sales Representative archived successfully', null, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchSoftdelete()
    {
        $deleted = SalesRep::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Sales rep archives successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = SalesRep::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived sales rep restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archived sales rep is not deleted!', null, 400);
        } else {
            return respond(false, 'Archived sales rep not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = SalesRep::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived sales rep found to restore!', null, 404);
        }
        SalesRep::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archived sales rep restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = SalesRep::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived sales rep permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archived sales rep is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archived sales rep not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = SalesRep::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived sales rep found to permanently delete!', null, 404);
        }
        SalesRep::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archived sales rep permanently deleted successfully!', null, 200);
    }

}
