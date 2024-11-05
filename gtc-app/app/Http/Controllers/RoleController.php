<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use function App\Helpers\api_request_response;
use function App\Helpers\generate_random_password;
use function App\Helpers\generate_uuid;
use function App\Helpers\unauthorized_status_code;
use Illuminate\Support\Facades\Route;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
use Illuminate\Support\Facades\Validator;
use App\Models\SavePermission;
use App\Models\Module;


class RoleController extends Controller
{

    public function __construct()
    {
        // $this->middleware('auth');
        // $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'store']]);
        // $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        // $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:role-delete', ['only' => ['destroy']]);
        $this->currentRouteName = Route::currentRouteName();

    }

    public function getPermissions()
    {
        $permissions = Module::get();
        return respond(true, 'permissions fetched successfully', $permissions, 200);

    }


    public function index(Request $request)
    {
        // dd('here');
        // $getAllRoles =
        $roles = Role::orderBy('id', 'desc')->get();
        $data["roles"] = $roles;
        // dd($data);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $roles, "message" => "Roles fetch successfully"], 201);
        }
        return view('admin.role.index', $data)->with('i');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permission = Permission::get();
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $permission, "message" => "Permission fetch successfully"], 200);
        } else {
            return view('admin.role.create', compact('permission'));
        }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'name' => 'required|unique:roles,name',
            'permission' => 'required|array',
            'permission.*' => 'required|exists:permissions,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $permissions = $request->input("permission");

        // Add double quotes to each element in the permissions array
        // $permissionsWithQuotes = array_map(function($permission) {
        //     return '"' . $permission . '"';
        // }, $permissions);
        // return respond(true, 'Role created successfully', $permissions, 200);
        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->input('name'), 'company_id' => $companyId]);
            $role->syncPermissions($permissions);
            DB::commit();

            return respond(true, 'Role created successfully', $role, 200);
        } catch (\Exception $e) {
            DB::rollback();

            return respond(false, $e->getMessage(), null, 400);

        }

    }
    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id'
            ]);
            // dd($request->all());
            if ($validator->fails()) {
                return respond(false, 'Validation error', $validator->errors(), 400);
            }

            DB::beginTransaction();

            // Fetch the role by its ID
            $role = Role::with('permissions')->findOrFail($request->role_id);
            DB::commit();

            return respond(true, 'Role found', $role, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);

        }
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'name' => 'required',
            'permission' => 'required|array'
        ]);
        if ($validator->fails()) {
            return respond(false, 'Validation error', $validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $id = $request->input('role_id');
            $role = Role::find($id);
            $role->name = $request->input('name');
            $role->save();

            // Sync permissions
            $role->syncPermissions($request->input('permission'));

            DB::commit();

            return respond(true, 'Role updated successfully', $role, 200);
        } catch (\Exception $e) {
            DB::rollback();

            return respond(false, 'Error', $e->getMessage(), 400);
        }
    }
    public function deleteRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id'

        ]);
        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            // $order = PurchaseOrder::findOrFail();
            $roleId = $request->input('role_id');
            $role = Role::findOrFail($roleId);
            $role->delete();

            DB::commit();
            return respond(true, 'Role deleted successfully!', $role, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }






    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getRoles()
    {
        try {

            $role = Role::with('permissions')->where('company_id', auth()->user()->company_id)->get();


            $data = $role;

            return respond(true, 'Roles  fetched successfully', $data, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions and return an error response
            return respond(false, 'Error', $exception->getMessage(), 400);
        }
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, $id)
    // {
    //     $this->validate($request, [
    //         'name' => 'required',
    //         'permission' => 'required',
    //     ]);


    //     DB::beginTransaction();
    //     try {

    //         $role = Role::find($id);
    //         $role->name = $request->input('name');

    //         // dd($request->all());
    //         $role->save();
    //         $role->syncPermissions($request->input('permission'));
    //         DB::commit();

    //         if (substr($this->currentRouteName, 0, 3) == "api") {
    //             return response()->json(["data" => $role, "message" => "Role updated successfully"], 200);
    //         } else {
    //             return redirect()->route('roles_home')->with("message", "Role updated successfully");
    //         }


    //     } catch (\Exception $e) {
    //         DB::rollback();

    //         if (substr($this->currentRouteName, 0, 3) == "api") {
    //             return response()->json(["message" => $e->getMessage()], 400);
    //         } else {
    //             return redirect()->back()->withInput()->withErrors([$e->getMessage()]);
    //         }


    //     }

    // }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function getAccountBranchList()
    {

        $account['data'] = Role::orderBy('description')->get();


        return json_encode($account);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $deleteROle = DB::table("roles")->where('id', $id)->delete();

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $deleteROle, "message" => "Role deleted successfully"]);
        } else {
            return redirect()->back()->with("message", "Role deleted successfully");
        }
    }

    public function getRolelist()
    {
        $role['data'] = Role::all();


        return json_encode($role);

    }


    public function create_permission(Request $request)
    {

        try {

            $input = $request->all();
            // dd($input);
            $input['guard_name'] = 'web';

            if ($this->user = SavePermission::where('name', $request->name)->first()) {
                throw new \Exception('This permission already exists!');
            }

            $saveInput = SavePermission::create($input);


            return api_request_response(
                'ok',
                'Data Update successful!',
                success_status_code(),
                $saveInput
            );

        } catch (\Exception $exception) {
            // ( $exception->getMessage() );
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }


    public function fetchSoftdelete()
    {
        $deleted = Role::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved role successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Role::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved role restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archieved role is not deleted!', null, 400);
        } else {
            return respond(false, 'Archieved role not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Role::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved role found to restore!', null, 404);
        }
        Role::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved role restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Role::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved role permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved role is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved role not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Role::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved role found to permanently delete!', null, 404);
        }
        Role::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved role permanently deleted successfully!', null, 200);
    }
}
