<?php

namespace App\Http\Controllers;
use App\Models\Grade;
use App\Models\Step;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    protected $currentRouteName;
    public function __construct()
    {
        $this->currentRouteName = Route::currentRouteName();
    }

    public function index(Request $request) {

        $grades['data'] = Grade::where('company_id', auth()->user()->company_id)->get();
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["data" => $grades, "message" => "Grades fetch successfully"], 201);
        }
        if($request->ajax()){
            //fetch all grades
            return json_encode($grades);
        }
        // pass them to view
        return view('admin.payrol.grade');
    }


    public function create(Request $request){
        $input = $request->all();

        $validator = Validator::make($request->all(), [
            'description' => 'required',
        ]);

        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        //check if the grade already exist
        $existingGrade = Grade::where('description', $request->description)->where('company_id', auth()->user()->company_id)->first();
        if($existingGrade){
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json([ "message" => "Grade already exist"], 400);
            }
            return redirect()->back()->withErrors("Grade already exist");
        }
        //save new grade
        $input['created_by']= auth()->user()->id;
        $input['company_id'] = auth()->user()->company_id;
        $saveGrade = Grade::create($input);

        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["data" => $saveGrade, "message" => "Grade added successfully"], 201);
        }

        return redirect()->back()->with('message', 'grade added successfully');
    }
    public function update(Request $request){
        $input = $request->all();
        //check if the grade already exist
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'id' => 'required',
        ]);


        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        $existingGrade = Grade::where([['description', $request->description],['company_id',auth()->user()->company_id]])->where('id','!=',$request->id)->first();
        if($existingGrade){
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(["message" => "Grade already exist"], 400);
            }
            return redirect()->back()->withErrors("Grade already exist");
        }
        $thisGrade = Grade::find($request->id);
        //save new grade
        $saveGrade = $thisGrade->update(['description'=> $request->description]);
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Grade updated successfully", "data" => $thisGrade], 200);
        }
        return redirect()->back()->with('message', 'grade updated successfully');
    }


    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=> 'required',

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }
        $id = $request->id;
        $grade = Grade::find($id);
        //delete grade
        $grade->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Grade deleted successfully!",
            "data" => true
        ];


        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Grade deleted successfully"], 200);
        }

        $response = response()->json(
            $responseArray
        );

        return $response;

    }


    //step methods

    public function stepIndex(Request $request) {
        //fetch all steps
       
       

        $steps['data'] = Step::where('company_id',auth()->user()->company_id)->get();

        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["Steps"=> $steps,"message" => "Step fetched successfully"], 200);
        }
        if ($request->ajax()) {
// dd($steps);
            return json_encode($steps);
        }
        // dd($data);
        // pass them to view
        return view('admin.payrol.step');
    }


    public function createStep(Request $request){
        $input = $request->all();
        //check if the step already exist
        $validator = Validator::make($request->all(), [
            'description' => 'required',
        ]);

        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }
        $existingStep = Step::where('description', $request->description)->where('company_id',auth()->user()->company_id)->first();
        if($existingStep){
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(["message" => "Step already exist"], 400);
            }
            return redirect()->back()->withErrors("Step already exist");
        }
        //save new grade
        $input['created_by']= auth()->user()->id;
        $input['company_id'] = auth()->user()->company_id;
        $saveGrade = Step::create($input);
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Step added successfully","step"=> $saveGrade,], 200);
        }
        return redirect()->back()->with('message', 'Step added successfully');
    }
    public function updateStep(Request $request){
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'id' => 'required',

        ]);

        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }
        //check if the grade already exist
        $existingStep = Step::where([['description', $request->description],['company_id',auth()->user()->company_id]])->where('id','!=',$request->id)->first();
        if($existingStep){
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json(["message" => "Step already exist"], 400);
            }
            return redirect()->back()->withErrors("Step already exist");
        }
        $thisStep = Step::find($request->id);
        //update new Step
        $saveStep = $thisStep->update(['description'=> $request->description]);
        $thisStep = Step::find($request->id);
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Step updated successfully","step"=> $thisStep,], 200);
        }
        return redirect()->back()->with('message', 'Step updated successfully');
    }


    public function deleteStep(Request $request){

        $validator = Validator::make($request->all(), [
            'id'=> 'required',

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }
        $id = $request->id;
        $saveStep = Step::find($id);
        //delete step$saveStep
        $saveStep->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Step deleted successfully!",
            "data" => true
        ];


        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["message" => "Step deleted successfully"], 200);
        }

        $response = response()->json(
            $responseArray
        );



        return $response;

    }
}
