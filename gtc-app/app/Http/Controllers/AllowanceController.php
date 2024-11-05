<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\AllowanceAmount;
use App\Models\AllowanceType;
use App\Models\Level;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class AllowanceController extends Controller
{

    protected $currentRouteName;
    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();

    }
    public function index(Request $request)
    {

       

        $data['allowances'] = Allowance::where('company_id', auth()->user()->company_id)->get();

        $year = $endYear = date('Y');
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = date('F', mktime(0, 0, 0, $month, 1, $year));
        }

        $data['months'] = $months;
        $startYear = 2022;
        $years = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[] = $year;
        }
        $data['years'] = $years;

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Data fetch successfully"], 201);
        }
        // dd($months, $years);
        return view('admin.allowance.index', $data);
    }

    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffs,id',
            'allowance_type_id' => 'required',
            'year' => 'required',
            'month' => 'required',
            'amount' => 'required',

        ]);
        $existingSt = Allowance::where('staff_id', $request->staff_id)
        ->where('company_id', auth()->user()->company_id)
        ->where('allowance_type_id', $request->allowance_type_id)
        ->where('year', $request->year)
        ->where('month', $request->month)
        ->where('amount', $request->amount)
        ->first();
       

        if ($existingSt) {
            return response()->json(['message' => "Allowance record already exist"], 400);
        }
        //save new grade
        $input['company_id'] = auth()->user()->company_id;
        $allowance = Allowance::create($input);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Allowance added successfully", "data" => $allowance], 200);
        }
    }
    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffs,id',
            'allowance_type_id' => 'required',
            'year' => 'required',
            'month' => 'required',
            'amount' => 'required',
            'id' => 'required'

        ]);
       
        //save new grade
       
        $thisAllowance = Allowance::find($request->id);
        if (!$thisAllowance) {
            return response()->json(['message' => "Allowance does not exist"], 400);
        }
        //check if the allowance already exist
        $existingAll= Allowance::where([['staff_id', $request->description],['company_id',auth()->user()->company_id]])->where('id','!=',$request->id)->first();
      
      
        if ($existingAll) {
            return response()->json(['message' => "Allowance record already exist"], 400);
        }
        $input['company_id'] = auth()->user()->company_id;
        $updateStaff = $thisAllowance->update($input);
        return response()->json(["message" => "Allowance updated successfully", "data" => $thisAllowance], 200);

      
       
    }
    public function specification(Request $request)
    {
        $data['allowances'] = AllowanceAmount::where('company_id', auth()->user()->company_id)->get();
        $data['allowanceTypes'] = AllowanceType::where('company_id', auth()->user()->company_id)->get();
        $data['levels'] = Level::where('company_id', auth()->user()->company_id)->get();

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Data fetch successfully"], 201);
        }
        // dd
        return view('admin.allowance.specification', $data);
    }


    public function type(Request $request)
    {
        


        //fetch all grades
        $data['allowanceTypes'] = AllowanceType::where('company_id', auth()->user()->company_id)->with(['CreatedBy'])->get();
        // dd($data, $request->company_id);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Data fetch successfully"], 201);
        }
        return view('admin.allowance.type', $data);
    }
    public function addType(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'description' => 'required',
        ]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        //save the incoming data
        $input['created_by'] = auth()->user()->id;
        $input['company_id'] = auth()->user()->company_id;
        $saveType = AllowanceType::create($input);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $saveType, "message" => "Allowance Type Added successfully"], 201);
        }
        return redirect()->back()->with('success', "Allowance Type Added successfully");

    }

    public function specifyNewAllowance(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'spec_type' => 'required',
            'lower_level' => 'required',
            'upper_level' => 'required',
            'allowance_type' => 'required',
            'spec_value' => 'required'

        ]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $input['percentage'] = $request->spec_type == "percentage" ? $request->spec_value : 0;
        $input['fixed_amount'] = $request->spec_type != "percentage" ? $request->spec_value : 0;
        $input['allowance_id'] = $request->allowance_type;
        $input['created_by'] = auth()->user()->id;
        //check whether the data exist for such level
        $checkDuplicate = AllowanceAmount::where('lower_level', $request->lower_level)
            ->where('upper_level', $request->upper_level)->where('allowance_id', $request->allowance_type)->where('company_id',auth()->user()->company_id)->first();
        if ($checkDuplicate) {

            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["data" => null, "message" => "Record already exist"], 400);
            }

            return redirect()->back()->withErrors("Record already exist");
        }

        $input['company_id'] = auth()->user()->company_id;
        // dd($input);
        $saveAllowanceAMount = AllowanceAmount::create($input);
        // AllowanceAmount
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $saveAllowanceAMount, "message" => "Record Added successfully"], 201);
        }
        return redirect()->back()->with('success', "Record saved successfully.");
    }

    public function updateType(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        //check if the grade already exist
        $existingAllowanceType = AllowanceType::where('description', $request->description)->where('id', '!=', $request->id)->where('company_id', auth()->user()->company_id)->first();
        if ($existingAllowanceType) {
            return redirect()->back()->withErrors("AllowanceType already exist");
        }
        $thisAllowanceType = AllowanceType::find($request->id);
        //update new AllowanceType
        $saveAllowanceType = $thisAllowanceType->update(['description' => $request->description]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $thisAllowanceType, "message" => "Record Updated successfully"], 201);
        }

        return redirect()->back()->with('message', 'AllowanceType updated successfully');
    }

    public function deleteType(Request $request)
    {
        $id = $request->id;

        $saveAllowAllowanceType = AllowanceType::find($id);
        //delete step$saveAllowAllowanceType
        $saveAllowAllowanceType->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Type deleted successfully!",
            "data" => true
        ];

        $response = response()->json(
            $responseArray
        );

        return $response;

    }


    public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'=> 'required',

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }
        $id = $request->id;
        $allowance = Allowance::find($id);
        //delete grade
        $allowance->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Allowance deleted successfully!",
            "data" => true
        ];


        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Allowance deleted successfully"], 200);
        }

        $response = response()->json(
            $responseArray
        );

        return $response;

    }
}
