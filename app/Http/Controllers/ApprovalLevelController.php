<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Module;
use App\Models\ApprovalModule;
use App\Models\User;
use App\Models\ApprovalLevel;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ApprovalLevelController extends Controller
{

    public function index()
    {
        try {
            // Fetch all modules from the database
            $modules = Module::where('status', 1)->get();



            // Return the modules with a success message
            return respond(true, 'Record fetched', $modules, 200);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function getAll()
    {
        // $data['modules'] = ApprovalLevel::all();
        $data['modules'] = ApprovalLevel::where('company_id', auth()->user()->company_id)->with('name')->get();


        return respond(true, 'Approval levels fetched successfully!', $data, 200);
    }

    public function getDeletedApprovalModules()
    {
        try {
            // Fetch all soft-deleted users

            $deletedmodules = ApprovalModule::onlyTrashed()->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Deleted modules fetched successfully.', $deletedmodules, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreAllApprovalModules(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $modules = ApprovalModule::onlyTrashed()->get();

            foreach ($modules as $module) {
                $module->restore();
            }


            return respond(true, 'Modules restored successfully.', $modules, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreApprovalModule(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:approval_modules,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = ApprovalModule::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Module restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|exists:approval_modules,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',

        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $input = $request->all();
            $input['module'] = $module = $request->module;
            // dd($request->all());
            // validate module
            $modulevalidation = ApprovalLevel::where('module', $module)->where('company_id', getCompanyid())->first();
            if ($modulevalidation) {

                return respond(false, "approval level already set for this module", null, 400);
            } else {
                $input['list_of_approvers'] = json_encode(Arr::flatten($request->roles));
                // dd($input['list_of_approvers']);
                ApprovalLevel::create($input);

                // return redirect()->back()->with('success', 'Approval Level created successfully');
                return respond(true, 'Approval Level created successfully', $input, 200);
            }
        } catch (\Exception $e) {

            return respond(false, $e->getMessage(), null, 400);
            // } else {
            //     return redirect()->back()->withErrors(['error' => 'Approval Level has been set for this module!']);
            // }
            // dd($input);
        }

    }

    public function update(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:approval_level',
            'module' => 'required|string',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $approvalLevel = ApprovalLevel::findOrFail($request->id);

            $approvalLevel->update([
                'module' => $request->module,
                'list_of_approvers' => json_encode(Arr::flatten($request->roles)),
            ]);

            return respond(true, 'Approval Level updated successfully', $approvalLevel, 200);
        } catch (\Exception $e) {

            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function fetchApprovalLevelById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:approval_level,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            // $approvalLevel = ApprovalLevel::findOrFail($request->id);
            $approvalLevel = ApprovalLevel::findOrFail($request->id);
            $approvalLevel->load('name');
            // dd(json_decode($approvalLevel->list_of_approvers));
            // $list = [];
            foreach (json_decode($approvalLevel->list_of_approvers) as $level) {
                $role = Role::find($level);
                $list[] = $role->name;
            }
            $approvalLevel['rolesname'] = $list;
            // dd($approvalLevel);
            return respond(true, 'Approval level fetched successfully', $approvalLevel, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 404);
        }
    }

    public function deleteApprovalLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:approval_level',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $approvalLevel = ApprovalLevel::find($request->id);

            $approvalLevel->delete();
            return respond(true, 'Approval Level deleted successfully', $approvalLevel, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }


}
