<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Step;
use App\Models\Level;
use App\Models\Staff;
use App\Models\Country;
use App\Models\Department;
use App\Models\LocalGovt;
use App\Models\State;
use function App\Helpers\api_request_response;
use function App\Helpers\generate_random_password;
use function App\Helpers\generate_uuid;
use function App\Helpers\unauthorized_status_code;
use function App\Helpers\success_status_code;
use function App\Helpers\bad_response_status_code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StaffTemplateExport;
use App\Models\UserDesignation;

class StaffController extends Controller
{
    protected $currentRouteName;
    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();
    }
    public function staffIndex(Request $request)
    {

        //validate request company_id

        if (substr($this->currentRouteName, 0, 3) == "api") {

           
            $data['employees'] = Staff::where('company_id', auth()->user()->company_id)->with(['Grade', 'Level', 'Step'])->get();
            $data['departments'] = Department::where('company_id', auth()->user()->id)->get();
            $data['designations'] = UserDesignation::get();
            return response()->json(["data" => $data, "message" => "Records fetch successfully"], 200);
        }
        $data['grades'] = Grade::get(['id', 'description']);
        $data['levels'] = Level::get(['id', 'description']);
        $data['steps'] = Step::get(['id', 'description']);

        if ($request->ajax()) {
            $employees['data'] = Staff::with(['Grade', 'Level', 'Step'])->get();
            // dd($employees);
            return json_encode($employees);
        }
        return view('admin.staff.staff', $data['employees']);
    }


    function view_staff(Request $request)
    {
        // dd($id);
        $id = $request->id;
        $data['staff'] = $staff = Staff::find($id);
        if (!$staff) {
            return redirect()->back()->withErrors("Record not found");
        }
        $data['grades'] = Grade::get(['id', 'description']);
        $data['levels'] = Level::get(['id', 'description']);
        $data['step'] = Step::get(['id', 'description']);
        // $data['countries'] = Country::all();
        // $data['states'] = State::all();
        $data['departments'] = Department::all();
        // $data['localgovt'] = LocalGovt::all();
        // dd($data);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Record fetch successfully"], 200);
        }

        return view('admin.staff.create_staff', $data);
    }

    public function createStaff(Request $request)
    {
        $input = $request->all();
        try {


            if ($request->isMethod('get')) {
                //
                $data['grades'] = Grade::get(['id', 'description']);
                $data['levels'] = Level::get(['id', 'description']);
                $data['step'] = Step::get(['id', 'description']);
                $data['countries'] = Country::all();
                $data['states'] = State::all();
                $data['departments'] = Department::all();
                $data['localgovt'] = LocalGovt::all();

                return view('admin.staff.create_staff', $data);
            }

            //let's validate
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'name' => 'required',

                // 'dob' => 'required|date',
                'gender' => 'required',
                'marital_status' => 'required',
                'phone_number' => 'required',
                'email' => 'required',
                // 'staff_id' => 'required',
                'staff_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:100',
                'rsa_number' => 'required',
                'qualification' => 'required',
                'state' => 'required|integer',
                'lga' => 'required|integer',
                'step' => 'required|integer',
                'grade' => 'required|integer',
                'level' => 'required|integer',
                'dept_id' => 'required',
                'country' => 'required',
                'address' => 'required',
                'city' => 'required',
                'employment_date' => 'required',
                'account_number' => 'required',
                'account_bank' => 'required',
                // 'net_pay' => 'required',
                'medical_condition' => 'required',

            ]);

            if (substr($this->currentRouteName, 0, 3) == "api") {
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()->first()], 400);
                }
            }

            //check if the staff already exist
            $existingSt = Staff::where([['phone_number', $request->phone_number], ['company_id', auth()->user()->company_id]])
                ->orWhere('email', $request->email)->first();

            if ($existingSt) {
                throw new \Exception("Staff record already exist", 1);

                // return redirect()->back()->withErrors("Staff record already exist");
            }

            //save new staff
            // $input['dob'] = date("d-m-Y", strtotime($request->dob));
            $input['net_pay'] = 0;

            // dd($input);
            if ($request->has('staff_image')) {
                $file = $request->file('staff_image');
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/uploads', $fileName);
                $input['staff_image'] = $fileName;
            }
            $input['company_id'] = auth()->user()->company_id;
            $input['date_of_appointment'] = $input['employment_date'];

            $saveGrade = Staff::create($input);


            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["data" => $saveGrade, "message" => "Staff added successfully"], 201);
            }


            return api_request_response(
                'ok',
                'Staff added successfully!',
                success_status_code(),
                $saveGrade
            );
            //code...
        } catch (\Exception $exception) {
            // ( $exception->getMessage() );
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }
    public function updateStaff(Request $request)
    {
        $input = $request->all();
        try {

            //let's validate
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'title' => 'required',
                'name' => 'required',
                'dob' => 'required',
                'gender' => 'required',
                'marital_status' => 'required',
                'phone_number' => 'required',
                'email' => 'required',
                'rsa_number' => 'required',
                'qualification' => 'required',
                'step' => 'required|integer',
                'grade' => 'required|integer',
                'level' => 'required|integer',
                'dept_id' => 'required',
                'country' => 'required|integer',
                'address' => 'required',
                'city' => 'required',
                'employment_date' => 'required',
                'account_number' => 'required',
                'account_bank' => 'required',
                'net_pay' => 'required',
                'medical_condition' => 'required',

            ]);

            if (substr($this->currentRouteName, 0, 3) == "api") {
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()->first()], 400);
                }
            }

            $thisStaff = Staff::find($request->id);
            if (!$thisStaff) {
                return response()->json(['message' => "Staff does not exist"], 400);
            }
            //check if the staff already exist
            $existingSt = Staff::where([['phone_number', $request->phone_number], ['company_id', auth()->user()->company_id]])->orWhere('email', $request->email)->where('id', '!=', $request->id)->first();
            if ($existingSt) {
                if (substr($this->currentRouteName, 0, 3) == "api") {
                    return response()->json(['message' => "There is another staff with this information"], 400);
                }

                return redirect()->back()->withErrors("Staff already exist");
            }


            // dd($input);
            if ($request->has('staff_image')) {
                $file = $request->file('staff_image');
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/uploads', $fileName);
                $input['staff_image'] = $fileName;
            }
            //update Staff
            $input['company_id'] = auth()->user()->company_id;
            $updateStaff = $thisStaff->update($input);

            return api_request_response(
                'ok',
                'Staff updated successfully!',
                success_status_code(),
                $thisStaff
            );
            //code...
        } catch (\Exception $exception) {
            // ( $exception->getMessage() );
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }


    public function deleteStaff(Request $request)
    {
        $id = $request->id;
        $saveStep = Staff::find($id);
        if (auth()->user()->company_id == $saveStep->company_id) {
            $saveStep->delete();

            $responseArray = [
                "status" => 200,
                "message" => "Staff deleted successfully!",
                "data" => true
            ];

            $response = response()->json(
                $responseArray
            );
            return $response;
        }
        $responseArray = [
            "status" => 200,
            "message" => "Staff cannot be deleted, permission denied!",
            "data" => true
        ];

        $response = response()->json(
            $responseArray
        );
        return $response;
        //delete step$saveStep


    }

    public function downloadTemplate()
    {
        return Excel::download(new StaffTemplateExport, 'staff_template.xlsx');
    }
}
