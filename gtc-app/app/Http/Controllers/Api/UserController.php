<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AssignModule;
use App\Models\Module;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index1()
    {
        $id = getCompanyid();

        $users = User::where('company_id', $id)->with(['roles', 'permissions'])->get();
        return respond(true, 'List of users fetched!', $users, 201);
    }

    public function index()
    {
        $id = getCompanyid();
        $users = User::where('company_id', $id)->get();
        foreach ($users as $user) {
            $modules = AssignModule::where('user_id', $user->id)->pluck('module_id')->toArray();
            $Permissions = Module::whereIn('id', $modules)->select('id', 'name')->get();
            $user->modules = $Permissions;
        }
        return respond(true, 'List of users fetched!', $users, 201);
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|unique:users',
                'phone_no' => 'required|unique:users',
                // 'role' => 'required|exists:roles,id',
                // 'module' => 'required|array|exists:permissions,name',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $input = $request->all();
            $input['company_id'] = getCompanyid();
            $input['created_by'] = Auth::id();
            if ($request->id != "") {
                $user = User::find($request->id);
                $user->update($input);
                // DB::table('model_has_roles')->where('model_id', $request->id)->delete();
                // $user->assignRole($request->role);
                // $users = User::where('company_id', auth()->user()->company_id)->get();
                return respond(true, 'New user saved successfully!', $user, 201);
            }
            $input['password'] = Hash::make("secret");
            $user = User::create($input);
            // $user->assignRole($request->role);
            // $users = User::where('company_id', auth()->user()->company_id)->get();
            // $module = $request->module;
            // foreach ($module as $permissionName) {
            //     // dd($permissionName);
            //     $user->givePermissionTo($permissionName);
            // }
            DB::commit();
            return respond(true, 'New user saved successfully!', $user, 201);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function createWithModule(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|unique:users',
                'phone_no' => 'required|unique:users',
                // 'role' => 'required|exists:roles,id',
                'module' => 'required|array|exists:permissions,name',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $input = $request->all();
            $input['company_id'] = getCompanyid();
            $input['created_by'] = Auth::id();
            if ($request->id != "") {
                $user = User::find($request->id);
                $user->update($input);
                // DB::table('model_has_roles')->where('model_id', $request->id)->delete();
                // $user->assignRole($request->role);
                // $users = User::where('company_id', auth()->user()->company_id)->get();
                return respond(true, 'New user saved successfully!', $user, 201);
            }
            $input['password'] = Hash::make("secret");
            $user = User::create($input);
            // $user->assignRole($request->role);
            // $users = User::where('company_id', auth()->user()->company_id)->get();
            $module = $request->module;
            foreach ($module as $permissionName) {
                // dd($permissionName);
                $user->givePermissionTo($permissionName);
            }
            DB::commit();
            return respond(true, 'New user saved successfully!', $user, 201);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function assignModuleOld(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'module' => 'required|array|exists:permissions,name',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $user = User::find($request->user_id);
            DB::table('model_has_permissions')->where('model_id', $request->user_id)->delete();
            $module = $request->module;
            foreach ($module as $permissionName) {
                // dd($permissionName);
                $user->givePermissionTo($permissionName);
            }
            DB::commit();
            return respond(true, 'Module Assigned successfully!', $user, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }
    public function assignModule(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'module' => 'required|array|exists:modules,id',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }
            $user = User::find($request->user_id);
            // dd($user);
            $permissions = $request->module;
            // dd($permissions);
            foreach ($permissions as $permissionName) {

                $alreadyAssigned = AssignModule::where('user_id', $user->id)
                    ->where('module_id', $permissionName)->exists();
                // dd($permissionName);
                if (!$alreadyAssigned) {
                    AssignModule::create([
                        'company_id' => $user->company_id,
                        'user_id' => $user->id,
                        'module_id' => $permissionName,
                    ]);
                }


            }
            DB::commit();
            return respond(true, 'Module Assigned successfully!', $user, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function assignModuleIsAdmin(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get all users with is_admin equal to 1
            $adminUsers = User::where('is_admin', 1)->get();

            // Get all available modules
            $modules = Module::pluck('id')->toArray();

            foreach ($adminUsers as $user) {
                foreach ($modules as $moduleId) {
                    $alreadyAssigned = AssignModule::where('user_id', $user->id)
                        ->where('module_id', $moduleId)->exists();

                    if (!$alreadyAssigned) {
                        AssignModule::create([
                            'company_id' => $user->company_id,
                            'user_id' => $user->id,
                            'module_id' => $moduleId,
                        ]);
                    }
                }
            }

            DB::commit();
            return respond(true, 'Modules Assigned successfully to all admins!', $adminUsers, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }



    public function updateUser1(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'name' => 'nullable',
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    Rule::unique('users', 'email')->ignore($request->user_id),
                ],
                'phone_no' => [
                    'nullable',
                    'string',
                    Rule::unique('users', 'phone_no')->ignore($request->user_id),
                ],
                'module' => 'nullable|array|exists:modules,id',

                // 'role' => 'required|exists:roles,id',
            ]);
            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }

            $user = User::find($request->user_id);

            $input = $request->all();
            $input['company_id'] = getCompanyid();
            $input['created_by'] = Auth::id();

            $user->update($input);
            // DB::table('model_has_roles')->where('model_id', $request->user_id)->delete();
            // $user->assignRole($request->role);

            DB::commit();
            return respond(true, 'User updated successfully!', $user, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'name' => 'nullable',
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    Rule::unique('users', 'email')->ignore($request->user_id),
                ],
                'phone_no' => [
                    'nullable',
                    'string',
                    Rule::unique('users', 'phone_no')->ignore($request->user_id),
                ],
                'module' => 'nullable|array|exists:modules,id',
            ]);

            if ($validator->fails()) {
                return respond(false, 'Error!', $validator->errors(), 400);
            }

            $user = User::find($request->user_id);

            $input = $request->except(['module']);
            $input['company_id'] = getCompanyid();
            $input['created_by'] = Auth::id();

            $user->update($input);

            if ($request->has('module')) {
                AssignModule::where('user_id', $user->id)->delete();
                foreach ($request->module as $moduleId) {
                    AssignModule::create([
                        'company_id' => $user->company_id,
                        'user_id' => $user->id,
                        'module_id' => $moduleId,
                    ]);
                }
            }
            DB::commit();
            return respond(true, 'User updated successfully!', $user, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }


    public function deleteUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $user = User::find($request->id);
            $user->delete();
            return respond(true, 'User archived successfully!', $user, 200);

        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'variable' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $variable = $request->variable;

        $users = User::where("name", 'like', "%$variable%")
            ->orWhere("email", 'like', "%$variable%")
            ->orWhere("id", 'like', "%$variable%")
            ->orWhere("phone_no", 'like', "%$variable%")
            ->orWhere("user_type", 'like', "%$variable%")
            ->orWhere("created_by", 'like', "%$variable%")
            ->orWhere("company_id", 'like', "%$variable%")
            ->orWhere("role_id", 'like', "%$variable%")
            ->get();

        return respond(true, 'User filtered successfully!', $users, 200);
    }


    public function fetchSoftdelete()
    {
        $deleted = User::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Users fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = User::withTrashed()->find($request->id);

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
        $deletedDepartments = User::onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No deleted departments found to restore!', null, 404);
        }
        User::onlyTrashed()->restore();

        return respond(true, 'All deleted departments restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = User::withTrashed()->find($request->id);

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
        $deletedDepartments = User::onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No deleted departments found to permanently delete!', null, 404);
        }
        User::onlyTrashed()->forceDelete();
        return respond(true, 'All deleted departments permanently deleted successfully!', null, 200);
    }


}
