<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function createService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required',
                'code' => 'required',
                'report_to' => 'required|exists:accounts,id'
                //'created_by' => 'required|unique:asset_categories,created_by',
                //'asset_id' => 'required|exists:asset_categories,id'
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $input = $request->all();

            $input ['company_id'] = auth()->user()->company_id; 
            // Create a new zone
            $Service = Service::create($input);

            return respond(true, 'Service created successfully', $Service, 201);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the process
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updateService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:services,id',
            'description' => 'nullable',
            'code' => 'nullable',
            'report_to' => 'nullable|exists:accounts,id'
        ]);
    
        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 400);
        }
    
        try {
            $input = $request->all();
            $id = $request->id;
            $service = Service::find($id);

            $service->update($input);

            return respond(true, 'Service updated successfully', $service, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function deleteService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:services,id',

        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 400);
        }

        $id = $request->input('id');
        $service = Service::find($id);
        if (!$service) {
            return respond(false, 'Service not found', null, 404);

        }

        $service->delete();
        return respond(true, 'Service deleted successfully', $service, 201);
    }
    public function getAllServices()
    {
        $id = getCompanyid();
        $Services = Service::where('company_id', $id)->with('Company', 'Account')->get();


        return respond(true, 'All services retrieved successfully', $Services, 201);


    }


    public function fetchSoftdelete()
    {
        $deleted = Service::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved services successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Service::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved services restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archieved services is not deleted!', null, 400);
        } else {
            return respond(false, 'Archieved services not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Service::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved services found to restore!', null, 404);
        }
        Service::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved services restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Service::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved services permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved services is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved services not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Service::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved services found to permanently delete!', null, 404);
        }
        Service::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved services permanently deleted successfully!', null, 200);
    }
}
