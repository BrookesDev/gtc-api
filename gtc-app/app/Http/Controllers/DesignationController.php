<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use App\Models\States;
use App\Models\LGA;
use App\Models\Country;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{


    public function index(Request $request)
    {
        $designations['data'] = Designation::where('company_id', auth()->user()->company_id)->latest()->get();
        return response()->json(["data" => $designations, "message" => "Department fetch successfully"], 201);
    }

    public function create(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
       
        //validate record
        if (Designation::where('name', $request->name)->where('company_id', auth()->user()->company_id)->first()) {
            return response()->json(["message" => "Designation already added"], 400);
        }
        try {
            $saveDesignation = new Designation();
            $saveDesignation->name = $request->name;
            $saveDesignation->save();
            return response()->json(["message" => "Designation added successfully","data" => $saveDesignation], 200);
        } catch (\Exception $exception) {
            return respond(false, 'Failed to update department', null, 500);
        }
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:designations,id',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $designations = Designation::findOrFail($id);
            if ($user->company_id !== $designations->company_id) {
                return respond(false, 'Unauthorized: You do not have permission to update this department.', null, 403);
            }
            $designations->update(['name' => $request->name]);
            return respond(true, 'department updated successfully', $designations, 200);
        } catch (\Exception $exception) {
            return respond(false, 'Failed to update department', null, 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }


        $id = $request->id;
        $designations = Designation::findOrFail($id);
        if (auth()->user()->company_id !== $designations->company_id) {
            return respond(false, 'Unauthorized: You do not have permission to update this designation.', null, 403);
        }
        try {
            $updateDepartment = Designation::find($id)->delete();
        } catch (\Exception $exception) {
            return respond(false, 'Failed to update department', null, 500);
        }

        return response()->json(["message" => "Designation deleted successfully"], 200);
    }
    //

    public function fetchSoftdelete()
    {
        $deleted = Designation::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch deleted designation successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $designation = Designation::withTrashed()->find($request->id);

        if ($designation && $designation->trashed()) {
            $designation->restore();
            return respond(true, 'Designation restored successfully!', $designation, 200);
        } elseif ($designation) {
            return respond(false, 'Designation is not deleted!', null, 400);
        } else {
            return respond(false, 'Designation not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDesignations = Designation::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDesignations->isEmpty()) {
            return respond(false, 'No deleted designation found to restore!', null, 404);
        }
        Designation::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All deleted designation restored successfully!', $deletedDesignations, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $designation = Designation::withTrashed()->find($request->id);

        if ($designation && $designation->trashed()) {
            $designation->forceDelete();
            return respond(true, 'Designation permanently deleted successfully!', null, 200);
        } elseif ($designation) {
            return respond(false, 'Desingnation is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Designation not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDesignation = Designation::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDesignation->isEmpty()) {
            return respond(false, 'No deleted designation found to permanently delete!', null, 404);
        }
        Designation::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All deleted designation permanently deleted successfully!', null, 200);
    }
}
