<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use App\Models\States;
use App\Models\LGA;
use App\Models\Country;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{

    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();
    }

    public function getallDepartment()
    {
        $user = auth()->user();
        $departments = Department::where('company_id', $user->company_id)->get();


        return respond(true, 'All departments retrieved successfully', $departments, 201);
    }

    public function index(Request $request)
    {
       
        $departments['data'] = Department::where('company_id', auth()->user()->company_id)->get();
        // dd('here');

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $departments, "message" => "Department fetch successfully"], 201);
        }


        if ($request->ajax()) {
            return json_encode($departments);
        }
        return view('admin.department');
    }


    public function getStates()
    {
        $states['data'] = States::get();

        return response()->json($states);
    }
    public function getCountries()
    {
        $countries['data'] = Country::get();

        return response()->json($countries);
    }

    public function getLocaGovt(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'state_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
        $states['data'] = LGA::where('state_id', $request->state_id)->get();


        return response()->json($states);
    }

    public function edit_department(Request $request)
    {
       

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $data = Department::where('id', $request->id)->where('company_id', $request->company_id)->first();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }
        //validate record
        if (Department::where('name', $request->name)->where('company_id', auth()->user()->company_id)->first()) {
            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["message" => "Department already added"], 400);
            }
            return redirect()->back()->withErrors('Department already added');
        }
        //$saveDepartment = Department::create($input);
        $saveDepartment = new Department;
        $saveDepartment->name = $request->name;
        $saveDepartment->save();
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Department added successfully", "data" => $saveDepartment], 200);
        }
        return redirect()->back()->with('message', 'Department added successfully');
    }
    public function update(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',

        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }
        $updateDepartment = Department::where('id', $id)->first();
        
        $updateDepartment->update(['name' => $request->name]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Department updated successfully","data" => $updateDepartment], 200);
        }
        return redirect()->back()->with('message', 'Department updated successfully');
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $id = $request->id;
        $updateDepartment = Department::where('id', $id)->delete();
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Department deleted successfully"], 200);
        }
        return redirect()->back()->with('message', 'Department deleted successfully');
    }
    //

    public function updateDepartment(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $departments = Department::findOrFail($id);
            if ($user->company_id !== $departments->company_id) {
                return respond(false, 'Unauthorized: You do not have permission to update this department.', null, 403);
            }
            $departments->update($request->all());
            return respond(true, 'department updated successfully', $departments, 200);
        } catch (\Exception $exception) {
            return respond(false, 'Failed to update department', null, 500);
        }
    }

    public function createDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:departments,name',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $departments = Department::create($request->all());
            return respond(true, 'department created succesfully', $departments, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception, null, 400);
        }
    }


    public function findDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $data = Department::find($request->id);

        return respond(true, 'Department fetched succesfully', $data, 201);
    }

    public function deleteDepartment(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $id = $request->id;
        $departments = Department::find($id);

        if ($user->company_id !== $departments->company_id) {
            return respond(false, 'Unauthorized: You do not have permission to delete this department.', null, 403);
        }

        $departments->delete();
        return respond(true, 'Department deleted successfully', $departments, 201);
    }

    public function fetchSoftdelete()
    {
        $deleted = Department::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch deleted department successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Department::withTrashed()->find($request->id);

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
        $deletedDepartments = Department::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No deleted departments found to restore!', null, 404);
        }
        Department::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All deleted departments restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Department::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Department permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Department is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Department not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Department::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No deleted departments found to permanently delete!', null, 404);
        }
        Department::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All deleted departments permanently deleted successfully!', null, 200);
    }
}
