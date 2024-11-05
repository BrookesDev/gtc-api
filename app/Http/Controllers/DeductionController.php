<?php

namespace App\Http\Controllers;

use App\Imports\UploadDeduction;
use App\Models\Deduction;
use App\Models\DeductionAmount;
use App\Models\DeductionType;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Excel;

class DeductionController extends Controller
{
    protected $currentRouteName;
    public function __construct()
    {
        $this->currentRouteName = Route::currentRouteName();
    }
    public function index(Request $request)
    {

        

        $data['deductions'] = Deduction::with(['Staff', 'DeductionType'])->where('company_id',  auth()->user()->company_id)->get();
        $data['deductionTypes'] = DeductionType::where('description', '!=', 'Tax')->where('company_id',  auth()->user()->company_id)->get();
        $data['levels'] = Level::where('company_id',  auth()->user()->company_id)->get();

        $yearAndMonth = $this->getYeargetMonth();
        $data['years'] = $yearAndMonth['years'];
        $data['months'] = $yearAndMonth['months'];

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Data fetched successfully"], 201);
        }

        return view('admin.deduction.index', $data);
    }

    public function getYeargetMonth()
    {

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

        $data['years'] = array_reverse($years);

        return $data;
    }

    public function upload(Request $request)
    {
        $input = $request->all();
        //validation


        $validator = Validator::make($request->all(), [
            
            'year' => 'required',
            'month' => 'required',
            'deduction' => 'required',
            'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:5048',

        ]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }
        // dd($input);
        $checkDuplicate = Deduction::where('year', $request->year)->where('month', $request->month)
            ->where('deduction_type_id', $request->deduction)->where('company_id',  auth()->user()->company_id)->first();

        if ($checkDuplicate) {
            //notify the user that records have been uploaded for this deduction type for this month
            //if request comes via api
            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(['error' => "Deductions have been uploaded for this deduction for this month!"], 400);
            }

            return redirect()->back()->withErrors("Deductions have been uploaded for this deduction for this month!");
        }
        //proceed to upload

        try {
            //code...
            Session::put('year', $request->year);

            Session::put('month', $request->month);

            Session::put('deduction', $request->deduction);
            Session::put('companyID',  auth()->user()->company_id);



            \Excel::import(new UploadDeduction, request()->file('file'));


            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["data" => true, "message" => "Deduction uploaded successfully."], 201);
            }

            return redirect()->back()->with('success', "Deduction uploaded successfully.");
        } catch (\Exception $exception) {

            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(['error' => $exception->getMessage()], 400);
            }
            return redirect()->back()->withErrors($exception->getMessage());
        }
        // dd($input, $checkDuplicate);
    }

    public function type(Request $request)
    {

        //
        //fetch all grades
        $data['deductionTypes'] = DeductionType::where('company_id',  auth()->user()->company_id)->with(['CreatedBy'])->get();


        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Deduction fetched successfully."], 201);
        }


        return view('admin.deduction.type', $data);
    }

    public function specification(Request $request)
    {

        //fetch all grades
        $data['deductions'] = DeductionAmount::where('company_id', auth()->user()->company_id)->get();
        $data['deductionTypes'] = DeductionType::where('description', '!=', 'Tax')->where('company_id',  auth()->user()->company_id)->get();
        $data['levels'] = Level::where('company_id',  auth()->user()->company_id)->get();
        // dd
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $data, "message" => "Data fetched successfully."], 201);
        }
        return view('admin.deduction.specification', $data);
    }


    public function specifyNewDeduction(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
           
            'spec_type' => 'required',
            'spec_value' => 'required',
            'deduction_type' => 'required',
            'lower_level' => 'required',
            'upper_level' => 'required',


        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $input['percentage'] = $request->spec_type == "percentage" ? $request->spec_value : 0;
        $input['fixed_amount'] = $request->spec_type != "percentage" ? $request->spec_value : 0;
        $input['deduction_id'] = $request->deduction_type;
        $input['created_by'] = auth()->user()->id;
        $input['company_id'] = auth()->user()->company_id;
        //check whether the data exist for such level
        $checkDuplicate = DeductionAmount::where('lower_level', $request->lower_level)
            ->where('upper_level', $request->upper_level)->where('deduction_id', $request->deduction_type)->where('company_id',  auth()->user()->company_id)->first();
        if ($checkDuplicate) {

            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(['error' => "Record already exist"], 400);
            }

            return redirect()->back()->withErrors("Record already exist");
        }
        $saveDeductionAMount = DeductionAmount::create($input);
        // $saveCompany_id = Company_id::create($input);
        // DeductionAmount
        // dd($input);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $saveDeductionAMount, "message" => "Record saved successfully."], 201);
        }
        return redirect()->back()->with('success', "Record saved successfully.");
    }


    public function deleteSpec(Request $request)
    {
        $id = $request->id;

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $saveAllowDeductionType = DeductionAmount::find($id);
        //delete step$saveAllowDeductionType
        $saveAllowDeductionType->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Deduction Spec. deleted successfully!",
            "data" => true
        ];

        $response = response()->json(
            $responseArray
        );

        return $response;
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

        //validate record
        $checkRecord = DeductionType::where('description', $request->description)->where('company_id',  auth()->user()->company_id)->first();
        if ($checkRecord) {
            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["message" => "Deduction type already added"], 400);
            }
            return redirect()->back()->withErrors('Deduction type already added');
        }

        $input['created_by'] = auth()->user()->id;
        $input['company_id'] =  auth()->user()->company_id;
        $saveType = DeductionType::create($input);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $saveType, "message" => "Deduction Type created successfully."], 201);
        }
        return redirect()->back()->with('success', "Deduction Type Added successfully");
    }

    public function updateType(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'id' => 'required',
           
        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        //check if the grade already exist
        $existingDeductionType = DeductionType::where('description', $request->description)->where('id', '!=', $request->id)->where('company_id', auth()->user()->company_id)->first();
        if ($existingDeductionType) {

            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(['error' => "DeductionType already exist"], 400);
            }

            return redirect()->back()->withErrors("DeductionType already exist");
        }
        $thisDeductionType = DeductionType::find($request->id);
        //update new DeductionType
        $saveDeductionType = $thisDeductionType->update(['description' => $request->description]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $thisDeductionType, "message" => "DeductionType updated successfully."], 201);
        }
        return redirect()->back()->with('message', 'DeductionType updated successfully');
    }

    public function deleteType(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }


        $saveAllowDeductionType = DeductionType::find($id);
        //delete step$saveAllowDeductionType
        $saveAllowDeductionType->delete();


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
}
