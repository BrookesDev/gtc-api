<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\AllowanceAmount;
use App\Models\SalaryStructure;
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
    public function specificationold(Request $request)
    {
        $data['allowances'] = AllowanceAmount::where('company_id', auth()->user()->company_id)->get();
        $data['allowanceTypes'] = AllowanceType::where('company_id', auth()->user()->company_id)->get();
        $data['levels'] = Level::where('company_id', auth()->user()->company_id)->get();
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

    public function specifyNewAllowanceold(Request $request)
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
    public function specifyNewAllowanceedit(Request $request)
    {
        $input = $request->all();
    
        // Validate input data
        $validator = Validator::make($request->all(), [
            'spec_type' => 'required|in:fixed,percentage',
            'lower_level' => 'required|exists:levels,id',
            'upper_level' => 'required|exists:grades,id',
            'allowance_type' => 'required|exists:allowance_types,id',
            'spec_value' => 'required|numeric'
        ]);
    
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }
    
        // Map fields to match database structure
        $input['level'] = $request->lower_level;
        $input['grade'] = $request->upper_level;
        $input['configuration_type'] = $request->spec_type;
        $input['allowance_type_id'] = $request->allowance_type;  // Assuming allowance_type is referenced by ID
        $input['created_by'] = auth()->user()->id;
        $input['company_id'] = auth()->user()->company_id;
    
        // Fetch SalaryStructure for the specified level and step within the user's company
        $salaryStructure = SalaryStructure::where('level', $input['level'])
                                          ->where('grade', $input['grade'])
                                          ->where('company_id', $input['company_id'])
                                          ->first();
    
        if (!$salaryStructure) {
            return response()->json(['error' => 'Allowance not found for the specified level and step'], 404);
        }
    
        // Calculate amount based on spec_type (configuration_type)
        if ($input['configuration_type'] === 'percentage') {
            $input['percentage'] = $input['spec_value'];
            $input['amount'] = ($salaryStructure->amount * $input['percentage']) / 100;
        } else {
            $input['percentage'] = 0;  // Set percentage to 0 for fixed allowance
            $input['amount'] = $input['spec_value'];
        }
    
        // Check for duplicate allowance for the same level, step, and allowance type
        $checkDuplicate = AllowanceAmount::where('level', $input['level'])
            ->where('grade', $input['grade'])
            ->where('allowance_type_id', $input['allowance_type_id'])
            ->where('company_id', $input['company_id'])
            ->first();

    
        if ($checkDuplicate) {
            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["data" => null, "message" => "Record already exists"], 400);
            }
            return redirect()->back()->withErrors("Record already exists");
        }
    
        // Save the allowance amount record
        $saveAllowanceAmount = AllowanceAmount::create($input);
    
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $saveAllowanceAmount, "message" => "Record added successfully"], 201);
        }
        return redirect()->back()->with('success', "Record saved successfully.");
    }

    public function specification(Request $request)
{
    $companyId = auth()->user()->company_id;

    // Fetch all allowance amounts for the authenticated user's company
    $allowances = AllowanceAmount::where('company_id', $companyId)->get();

    return response()->json(["data" => $allowances, "message" => "Allowances retrieved successfully"], 200);
}
public function specifyNewAllowance(Request $request)
{
    // Extract only the specified input fields
    $input = $request->only([
        'level', 
        'step', 
        'configuration_type', 
        'allowance_type', 
        'percentage', 
        'fixed_amount' // renamed from amount for clarity
    ]);

    // Validation rules for specified fields
    $validator = Validator::make($input, [
        'configuration_type' => 'required|in:fixed,percentage', // corresponds to spec_type
        'level' => 'required|exists:levels,id', // corresponds to lower_level
        'step' => 'required|exists:grades,id', // corresponds to upper_level
        'allowance_type' => 'required|exists:allowance_types,id', // corresponds to allowance_id
        'fixed_amount' => 'required_if:configuration_type,fixed|numeric|min:0',  // required only if fixed
        'percentage' => 'required_if:configuration_type,percentage|numeric|min:0|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Check for existing record with the same level, step, allowance_type, and company_id
    $checkDuplicate = AllowanceAmount::where('lower_level', $input['level'])
        ->where('upper_level', $input['step'])
        ->where('spec_type', $input['configuration_type'])
        ->where('allowance_id', $input['allowance_type'])
        ->where('company_id', auth()->user()->company_id)
        ->first();

    if ($checkDuplicate) {
        return response()->json(["data" => null, "message" => "Record already exists"], 400);
    }

    // Set company_id and created_by attributes
    $input['company_id'] = auth()->user()->company_id;
    $input['created_by'] = auth()->user()->id;

    // Handle configuration for allowance amount calculation
    if ($input['configuration_type'] == "percentage") {
        // Nullify fixed_amount if configuration type is percentage
        $input['fixed_amount'] = null;

        // Fetch salary specification based on level and grade
        $salarySpecification = SalaryStructure::where('level_id', $input['level'])
            ->where('grade_id', $input['step'])
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$salarySpecification) {
            return response()->json(["data" => null, "message" => "Salary specification not found for specified level and step"], 404);
        }

        // Calculate fixed amount based on the percentage of salary specification
        $input['fixed_amount'] = ($salarySpecification->amount * $input['percentage']) / 100;
    } 

    // Map input keys for AllowanceAmount model
    $input['lower_level'] = $input['level'];
    $input['upper_level'] = $input['step'];
    $input['allowance_id'] = $input['allowance_type'];
    $input['spec_type'] = $input['configuration_type'];
    $input['spec_value'] = $input['fixed_amount']; 

    // Create the allowance amount record
    $saveAllowanceAmount = AllowanceAmount::create($input);

    return response()->json(["data" => $saveAllowanceAmount, "message" => "Record added successfully"], 201);
}



public function updateSpecification(Request $request)
{
    $input = $request->only([
        'id',
        'level',
        'step',
        'configuration_type',
        'allowance_type',
        'percentage',
        'fixed_amount' // renamed from amount for clarity
    ]);

    // Validation rules for specified fields
    $validator = Validator::make($input, [
        'id' => 'required|exists:allowance_amounts,id',
        'configuration_type' => 'required|in:fixed,percentage',
        'level' => 'required|exists:levels,id',
        'step' => 'required|exists:grades,id',
        'allowance_type' => 'required|exists:allowance_types,id',
        'fixed_amount' => 'required_if:configuration_type,fixed|numeric|min:0',
        'percentage' => 'required_if:configuration_type,percentage|numeric|min:0|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Fetch the specific allowance record by ID and company_id
    $allowance = AllowanceAmount::where('id', $input['id'])
        ->where('company_id', auth()->user()->company_id)
        ->first();

    if (!$allowance) {
        return response()->json(["data" => null, "message" => "Allowance not found"], 404);
    }

    // Set values based on configuration type
    if ($input['configuration_type'] == "percentage") {
        // Fetch salary specification for percentage calculation
        $salarySpecification = SalaryStructure::where('level_id', $input['level'])
            ->where('grade_id', $input['step'])
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$salarySpecification) {
            return response()->json(["data" => null, "message" => "Salary specification not found for specified level and step"], 404);
        }

        // Calculate fixed amount as a percentage of salary specification
        $allowance->fixed_amount = ($salarySpecification->amount * $input['percentage']) / 100;
    } else {
        // Set fixed_amount directly for fixed configuration
        $allowance->fixed_amount = $input['fixed_amount'];
    }

    // Update other attributes
    $allowance->spec_type = $input['configuration_type'];
    $allowance->lower_level = $input['level'];
    $allowance->upper_level = $input['step'];
    $allowance->allowance_id = $input['allowance_type'];
    $allowance->updated_by = auth()->user()->id; // Set the updated_by field

    $allowance->save();

    return response()->json(["data" => $allowance, "message" => "Allowance updated successfully"], 200);
}







 
 // Archive (soft-delete) a Allowance record
 public function deleteAllowance(Request $request)
 {
     try {
         $validator = Validator::make($request->all(), [
             'id' => 'required|exists:allowance_amounts,id',
         ]);

         if ($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()->first(),
             ], 422);
         }

         $allowance = AllowanceAmount::find($request->id);

         if (!$allowance) {
             return response()->json([
                 'success' => false,
                 'message' => 'Allowance not found',
             ], 404);
         }

         $allowance->delete();

         return response()->json([
             'success' => true,
             'message' => 'Allowance archived successfully!',
             'data' => $allowance,
         ], 200);
     } catch (\Exception $e) {
         return response()->json([
             'success' => false,
             'message' => 'Error archiving allowance: ' . $e->getMessage(),
         ], 500);
     }
 }

 // Permanently delete a Allowance (force delete)
 public function forceDeleteAllowance(Request $request)
 {
     try {
         $validator = Validator::make($request->all(), [
             'id' => 'required|exists:allowance_amounts,id',
         ]);

         if ($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()->first(),
             ], 422);
         }

         $allowance = AllowanceAmount::withTrashed()->find($request->id);

         if (!$allowance) {
             return response()->json([
                 'success' => false,
                 'message' => 'Allowance structure not found',
             ], 404);
         }

         if (!$allowance->trashed()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Cannot force delete a non-archived record',
             ], 400);
         }

         $allowance->forceDelete();

         return response()->json([
             'success' => true,
             'message' => 'Allowance permanently deleted successfully',
         ], 200);
     } catch (\Exception $e) {
         return response()->json([
             'success' => false,
             'message' => 'Error force deleting Allowance: ' . $e->getMessage(),
         ], 500);
     }
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
