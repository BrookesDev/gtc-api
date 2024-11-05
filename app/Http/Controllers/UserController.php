<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use function App\Helpers\api_request_response;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;

class UserController extends Controller
{
    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();

    }
    public function index(Request $request)
    {
        $data['roles'] = Role::all();
        $data['users'] = User::with(['roles'])->get();
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(['data'=>$data, 'message'=>"User records fetch successfully"],200);
        }
        return view('admin.user', $data);
    }


    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'name' => 'required',
            'phone_no' => 'required',
            'role'=> 'required'

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        $password = Str::random(10);
        $this->password = $password;
        $input['password'] = Hash::make($password);
        $input['user_type'] = 'admin';
        $input['is_first_time'] = 0;

        // dd($input);
        $this->input = $input;
        try {

            if ($this->user = User::where('email', $this->input['email'])->first()) {

                if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(["error" => "This email already exists!"], 400);
                }
                 return redirect()->back()->withInput()->withErrors(["This email already exists!"]);
            }

            if ($this->user = User::where('phone_no', $this->input['phone_no'])->first()) {
                if(substr($this->currentRouteName,0,3)== "api"){
                    return response()->json(["message" => "The phone number has already been taken.!"], 400);
                }
                return redirect()->back()->withInput()->withErrors(["The phone number has already been taken.!"]);
            }


            $this->input = $input;
            // dd($input);
            $this->user = User::create($input);
            $this->user->assignRole($request->input('role'));

            $input['user_id'] = $this->user->id;


            // Mail::to($this->user->email)->send(new SendRegistrationDetails(
            //     $this->user,
            //     $this->company_name,
            //     $this->password
            // ));
            if(substr($this->currentRouteName,0,3)== "api"){
            return  response()->json(["data" =>  $this->user, "message" => "User created successfully"],200);
            }
            return redirect()->back()->with("message", "User created successfully!");
        } catch (\Exception $e) {
            if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => $e->getMessage()], 400);
            }
             return redirect()->back()->withInput()->withErrors([$e->getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        $id = $request->id;
        // dd($id);
        $premise_id = User::where('id', $id)->with('roles')->first();
        return response()->json($premise_id);
        # code...
    }


    public function update(Request $request)
    {

        // dd($id);
        $id = $request->id;


        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone_no' => 'required|unique:users,phone,' . $id,
        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }


        try{
        $input = $request->all();
        $user = User::find($id);
        if(!$user){
            return response()->json(['message' => "User record not found!"],400);
        }
        if ($request->has('role')) {
        $input['role'] = $request->role;
        }
        // dd($id);


        $user->update($input);

        if ($request->has('role')) {
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        $user->assignRole($request->input('role'));
        }

        if(substr($this->currentRouteName,0,3)== "api"){

                return response()->json(['message' => "Record updated successfully"],200);

        }

        return  redirect()->back()->with("message", "Record updated successfully!");

        } catch (\Exception $e) {
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(["message" => $e->getMessage()], 400);
            }
            return  redirect()->back()->withInput()->withErrors([$e->getMessage()]);
        }
    }



    public function getUserInfo(Request $request)
    {
        $id = $request->id;        // dd($id);
        $user = User::where('id', $id)->first();
        if(!$user){

         return response()->json(["message" => "Record not found"], 400);

        }
        return response()->json($user);
    }


    public  function delete(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'id' => 'required'

        ]);

        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        $user = User::find($id);
        if (!$user) {
        if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(['message' => "User record not found"],400);
            }
        }

        $user->update(['deleted_at' => now()]);

        //delete the client
        // User::where( 'id', '=', $id )->delete();
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(['message' => "Record deleted successfully"],200);
        }
        return redirect()->back()->with('deleted', 'Delete Success!');
        //Session::flash( 'message', 'Delete successfully.' );
    }
    public function getRiders()
    {
        $riders = User::where('user_type', 'Rider')->get();
        return respond(true, 'Riders fetched successfully', $riders, 200);
    }

    public function createRider(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'phone_number' => 'required|numeric|unique:users,phone_no',
                // 'address' => 'required',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $data = $request->all();
            $data['name'] = $data['last_name'] . ' ' . $data['first_name'];
            $data['phone_no'] = $data['phone_number'];
            $data['user_type'] = "Rider";
            $data['password'] = Hash::make('password');
            $rep = User::create($data);



            return respond(true, 'Rider created successfully', $rep, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updateRider(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
                'first_name' => 'nullable',
                'last_name' => 'nullable',
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    Rule::unique('users', 'email')->ignore($request->id),
                ],
                'phone_number' => [
                    'nullable',
                    'string',
                    Rule::unique('users', 'phone_number')->ignore($request->id),
                ],
                'title' => 'nullable',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;
            $rep = User::find($id);

            $data = $request->all();

            $rep->update($data);

            return respond(true, 'Rider updated successfully', $rep, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

}
