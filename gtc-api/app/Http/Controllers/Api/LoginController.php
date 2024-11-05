<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Company;
use App\Models\Module;
use App\Models\AssignModule;
use App\Models\Plan;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\SendPasswordMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMaill;
use App\Models\Fixed_Asset_Register;
use DB;
use Paystack;
use Carbon\Carbon;

// use Unicodeveloper\Paystack\Facades\Paystack;
class LoginController extends Controller
{

    function respond($status, $message, $data, $code)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function test(Request $request)
    {
        // dd(url('/'));
        $url = url('/');
        $response = Http::post("$url/api/v1/register", [
            'email' => 'test@admin.com',
            'password' => 'Amos1255##',
            'password_confirmation' => 'Amos1255##',
            'name' => 'Persie Amos',
            'phone' => '09034456757',
            // Add more fields as needed for your API request
        ]);
        $response->status(); // Get the HTTP status code
        $response->json();
        // dd($response->json());

        return $response->json();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // dd($data);
        try {
            DB::beginTransaction();
            $data = $request->all();

            $validator = Validator::make($data, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'company_name' => ['required', 'string', 'max:255'],
                'country' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:60', 'unique:users'],
                'phone_no' => 'required|unique:companies,user_phone|unique:users,phone_no',
            ]);
            if ($validator->fails()) {
                return $this->respond('message', $validator->errors(), null, 400);
            }
            if ($request->has('phone_no')) {
                $formattedNumber = formatPhoneNumber($request->phone_no);
                if ($formattedNumber['status'] == false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $formattedNumber['message'],
                        'data' => $formattedNumber['data'],
                    ], 400);
                }
            }

            $password = Str::random(8);

            if (isset($data['email'])) {

                Mail::to($data['email'])->send(new SendPasswordMail($password));
            }



            $newCompany = new Company();

            $newCompany->name = $request->company_name;
            $newCompany->email = $data['email'];
            $newCompany->phone_number = null;
            $newCompany->address = null;
            $newCompany->plan_id = Null;
            $newCompany->user_name = $data['first_name'] . " " . $data['last_name'];
            $newCompany->user_email = $data['email'];
            $newCompany->user_phone = $data['phone_no'];
            $newCompany->amount = null;
            $newCompany->reference = null;
            $newCompany->password = Hash::make($password);

            $newCompany->save();

            // dd($newCompany);
            $currentDateTime = Carbon::now();

            // Add one year to the current date and time
            $nextMonthTime = $currentDateTime->addMonth();


            $newCompany->update(['expiry_date' => $nextMonthTime]);

            $user = User::create([
                'name' => $data['first_name'] . " " . $data['last_name'],
                'email' => $data['email'],
                'phone_no' => $data['phone_no'],
                'is_admin' => 1,
                'user_type' => 'Super Admin',
                'company_id' => $newCompany->id,
                'password' => Hash::make($password),
                'is_first' => 0,

            ]);



            $user->comany_name = $user->Company->name;

            $token = $user->createToken('myAppToken')->plainTextToken;
            $check = [
                'user' => $user,
                'token' => $token,

            ];

            $permissions = Module::get();
            // dd($permissions);
            foreach ($permissions as $permissionName) {
                // dd($permissionName);
                AssignModule::create([
                    'company_id' => $newCompany->id,
                    'user_id' => $user->id,
                    'module_id' => $permissionName->id,
                ]);
            }


            DB::commit();
            return respond(true, 'Registration Successful', $check, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // return response()->json(['message' => $e->getMessage()], 400);
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function authorized(Request $request)
    {
        $user = auth()->user()->id;
        $userdetails = User::where('id', $user)->first();
        $userdetails->update([
            'is_first' => 1
        ]);
    }

    public function sendPasswordResetLink(Request $request)
    {
        // Validate the request
        $data = $request->all();
        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            // Generate a random token
            $token = Str::random(60);
            // Save the token to the user's record in the database
            $user = User::where('email', $request->email)->first();
            $email = $user->email;
            DB::table("password_resets")->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => now()
            ]);
            $name = $user->name;
            // Generate the reset link
            // $link = env('APP_URL') . 'reset_password' . '?token=' . $token;
            $link = "https://admin.ogunstate.gov.ng/reset_password" . '?token=' . $token;

            // Send the reset link to the user's email
            Mail::to($user->email)->send(new ResetPasswordMaill($link, $name));
            return respond(true, "Password Link Sent To Provided Email", $data, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function resetPassword(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'current_password' => 'required|string',
            'password_confirmation' => 'required|min:8',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // Get the currently authenticated user
        $user = auth()->user();

        // Check if the new password is the same as the old password
        if (Hash::check($data['new_password'], $user->password)) {
            return respond(false, 'New password cannot be the same as the current password', null, 400);
        }

        // Update the user's password
        $user->update(['password' => Hash::make($data['new_password'])]);

        return respond(true, 'Password reset successfully', $data, 200);
    }

    public function createUser(Request $request)
    {
        // dd($data);
        try {
            DB::beginTransaction();
            $data = $request->all();
            // Set your Paystack API key
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            // dd(env('PAYSTACK_SECRET_KEY'));
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'company_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:60', 'unique:users'],
                'company_email' => 'required|email|unique:companies,email',
                'company_phone_number' => 'required|unique:companies,phone_number',
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone' => ['required'],
                'plan_id' => ['required', 'numeric'],
                'address' => ['required', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                return $this->respond('message', $validator->errors(), null, 400);
            }
            if ($request->has('phone')) {
                $formattedNumber = formatPhoneNumber($request->phone);
                if ($formattedNumber['status'] == false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $formattedNumber['message'],
                        'data' => $formattedNumber['data'],
                    ], 400);
                }
                $formattedNumberC = formatPhoneNumber($request->company_phone_number);
                if ($formattedNumberC['status'] == false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $formattedNumberC['message'],
                        'data' => $formattedNumberC['data'],
                    ], 400);
                }
            }
            // Paystack::setApiKey(env('PAYSTACK_SECRET_KEY'));
            $reference = Paystack::genTranxRef();
            // dd($reference);
            // get selected plan
            $plan = Plan::find($request->plan_id);
            $getAmount = $plan->yearly;
            //let's create this company
            $newCompany = new Company();
            $newCompany->name = $request->company_name;
            $newCompany->email = $request->company_email;
            $newCompany->phone_number = $request->company_phone_number;
            $newCompany->address = $request->address;
            $newCompany->plan_id = $request->plan_id;
            $newCompany->user_name = $data['name'];
            $newCompany->user_email = $data['email'];
            $newCompany->user_phone = $data['phone'];
            $newCompany->reference = $reference;
            $newCompany->amount = $getAmount;
            $newCompany->password = Hash::make($data['password']);
            $newCompany->save();

            if ($getAmount > 0) {

                $data = array(
                    "amount" => $getAmount * 100,
                    "reference" => $reference,
                    "email" => $request->company_email,
                    "currency" => "NGN",
                    "orderID" => $reference,
                );

                $check = Paystack::getAuthorizationUrl($data); //->redirectNow();
                // $paystackUrl = $check->redirectUrl();
                $url = stripslashes($check->url);
            } else {


                // Create a transaction
                // $transaction = $paystack->transaction()->initialize([
                //     'amount' => $getAmount * 100, // Replace with your desired amount
                //     'email' => $request->company_email, // Replace with the customer's email
                //     'reference' => $reference, // Replace with a unique reference
                // ]);
                // dd($transaction);
                // Get the payment link from the initialized transaction
                // $paymentLink = $transaction['data']['authorization_url'];
                //let's create this user as our admin user
                $currentDateTime = Carbon::now();
                $password = Str::random(8);

                if (isset($data['email'])) {
                    // Send email with password
                    Mail::to($data['email'])->send(new SendPasswordMail($password));
                }

                // Add one year to the current date and time
                $nextMonthTime = $currentDateTime->addMonth();
                $newCompany->update(['expiry_date' => $nextMonthTime]);
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone_no' => $data['phone'],
                    'is_admin' => 1,
                    'user_type' => 'Super Admin',
                    'company_id' => $newCompany->id,
                    'password' => Hash::make($password),
                    'is_first' => 0,

                ]);

                $user->comany_name = $user->Company->name;

                $token = $user->createToken('myAppToken')->plainTextToken;
                $check = [
                    'user' => $user,
                    'token' => $token,

                ];
            }


            DB::commit();
            return respond(true, 'Registration Successful', $check, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // return response()->json(['message' => $e->getMessage()], 400);
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function changePassword(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'password_confirmation' => 'required',
            'password' => ['required', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $yourToken = $request->bearerToken();
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($yourToken);
            // Get the assigned user
            $user = $token->tokenable;
            $id = $user->id;
            $userUpdate = User::where('id', $id)->first();
            $userUpdate->update(['password' => bcrypt($request->password), 'is_first_time' => 0]);
            return respond(true, 'Password Set Successfully', $userUpdate, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }


    public function changeCustomerPassword(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'password_confirmation' => 'required',
            'old_password' => 'required',
            'new_password' => ['required', 'min:8'],
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {

            //compare the password
            if ($request->password_confirmation != $request->new_password) {
                return respond(false, 'Password and Confirm password are not the same', null, 400);
            }
            $user = auth()->user();

            //check if the current password is currect
            if (!$user || !Hash::check($request->old_password, $user->password)) {
                return respond(false, 'Incorrect Password', null, 400);
            }

            // let's update this user
            $new_password = $request->new_password;
            $user->update([
                'password' => Hash::make($new_password),
            ]);

            return respond(true, 'Password changed Successfully', null, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Get the authenticated user
            $user = auth()->user();
            // dd($user);
            // Check if the user is authenticated
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Revoke all tokens for the user if tokens exist
            if ($user->tokens) {
                $user->currentAccessToken()->delete();
                // $user->tokens()->delete();
            }

            return respond(true, 'User logged out successfully', null, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 500);
        }
    }


    // public function logout(Request $request)
// {
//     try {
//         // Begin a database transaction
//         DB::beginTransaction();

    //         // Get the authenticated user
//         $user = auth()->user();

    //         // Check if the user is authenticated
//         if (!$user) {
//             throw new \Exception('User not authenticated');
//         }

    //         // Revoke all tokens for the user if tokens exist
//         if ($user->tokens) {
//             $user->tokens()->delete();
//         }

    //         // Logout the user
//         auth()->guard('web')->logout();

    //         // Commit the transaction
//         DB::commit();

    //         return respond(true, 'User logged out successfully', null, 201);
//     } catch (\Exception $e) {
//         // Rollback the transaction if there's an error
//         DB::rollback();
//         // Return an error response
//         return respond('error', $e->getMessage(), null, 500);
//     }
// }


    public function login(Request $request)
    {
        // dd($data);

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 401);
        }
        $email = $request->email;
        $status = getUserStatus($email);
        // dd($status);
        if ($status == false) {
            return respond(false, 'Account Suspended Due To Expired Plan', $data, 400);
        }
        $user = User::where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return respond(false, 'Invalid Credentials', $data, 401);
        }
        $user = User::where('email', $request->email)->with('Company', 'roles.permissions:name')->first();
        $companyCurrentMonth = $user->Company->current_month;
        $currentDate =   now()->format('Y-m-d');
        $posted = $user->Company->already_calculated_depreciation;
        if($companyCurrentMonth){
            if($currentDate >= $companyCurrentMonth){
                if($posted == 0){
                    //get all monthly depreciation assets
                    $allAssets = Fixed_Asset_Register::where('depre_cal_period', 1)->whereNull('date_disposed')->get();
                    foreach($allAssets as $asset){
                        //calculate straight line depreciation expenses
                        $costSalvage = $asset->amount_purchased - $asset->residual_value;
                        $sLDE =  $costSalvage  / $asset->lifetime_in_years;
                        //now let's post
                        $uuid = $asset->identification_number;
                        $dEA = $asset->depre_expenses_account;
                        $aDa = $asset->accumulated_depreciation;
                        $detail = "posting of straight line depreciation expenses for $asset->asset_name for the month $companyCurrentMonth" ;
                        // debit depreciation expense account
                        postDoubleEntries($uuid, $dEA, $sLDE, 0, $detail, $companyCurrentMonth);
                        //credit accumulated depreciation account
                        postDoubleEntries($uuid, $aDa, 0, $sLDE,  $detail, $companyCurrentMonth);
                    }
                    $user->Company->already_calculated_depreciation = 1;
                    $user->Company->save();
                }
            }
        }
        //dd($currentDate,$companyCurrentMonth);
        $userCompanyName = $user->Company->name;
        // dd('here');
        $token = $user->createToken('myAppToken')->plainTextToken;
        // $permissions = $user->getAllPermissions();
        // $userId = auth()->id();
        $modules = AssignModule::where('user_id', $user->id)->pluck('module_id')->toArray();
        $permissions = Module::whereIn('id', $modules)->get();
        $response = [
            'user' => $user,
            'permissions' => $permissions,
            'company_name' => $userCompanyName,
            'token' => $token
        ];

        return respond(true, 'Login Successful', $response, 200);
    }
    public function loginMe(Request $request)
    {
        // dd($data);

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 401);
        }
        $email = $request->email;
        $status = getUserStatus($email);
        // dd($status);
        if ($status == false) {
            return respond(false, 'Account Suspended Due To Expired Plan', $data, 400);
        }
        $user = User::where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return respond(false, 'Invalid Credentials', $data, 401);
        }
        $user = User::where('email', $request->email)->with('Company', 'roles.permissions:name')->first();
        // $permissions = $user->roles->flatMap(function ($role) {
        //     return $role->permissions->pluck('name');
        // })->unique()->values()->toArray();
        $userCompanyName = $user->Company->name;
        // dd('here');
        $token = $user->createToken('myAppToken')->plainTextToken;
        // $permissions = $user->getAllPermissions();
        // $userId = auth()->id();
        // $modules = AssignModule::where('user_id', $user->id)->pluck('module_id')->toArray();
        // $permissions = Module::whereIn('id', $modules)->get();
        $response = [
            'user' => $user,
            // 'permissions' => $permissions,
            'company_name' => $userCompanyName,
            'token' => $token
        ];

        return respond(true, 'Login Successful', $response, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function setPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'pin' => 'required|numeric|digits:4',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }
        // check email in user db
        $pin = bcrypt($request->pin);
        $user = User::where('email', $request->email)->update(['pin' => $pin]);
        return $this->respond(true, 'Pin Set Successfully!', $pin, 201);
    }
    public function verifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'pin' => 'required|numeric|digits:4',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }
        $pin = $request->pin;
        $user = User::where('email', $request->email)->first();
        if (!Hash::check($request->pin, $user->pin)) {
            return $this->respond("error", 'Invalid Pin!', $pin, 400);
        }
        return $this->respond(true, 'Access Granted !', $pin, 201);
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
    public function destroy($id)
    {
        //
    }
}
