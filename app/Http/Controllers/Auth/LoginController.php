<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo ='/admin';// RouteServiceProvider::HOME;


    public function loginAPI(Request $request)
    {
        // dd($data);

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()) {
           return response()->json(['message' => $validator->errors()], 400);
        }
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response([
                "message" => "Record Not Found"
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response([
                "message" => "Invalid Credentials"
            ], 401);
        }

        $token = $user->createToken('myAppToken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
        ];

        return response(
            [
                "data" => $response,
                "message" => 'Login Successful'
            ],
            201
        );
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


  public function logout()
  {
    Session::flush();
    Auth::logout();

    return redirect('/login');
  }
}
