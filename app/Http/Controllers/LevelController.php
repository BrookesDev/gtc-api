<?php

namespace App\Http\Controllers;

use App\Models\Level;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    protected $currentRouteName;
    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();

    }

    public function index(Request $request)
    {
        $input = $request->all();
       


        //fetch all level
        $level['data'] = Level::where('company_id', auth()->user()->company_id)->with(['CreatedBy'])->get();
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["data" => $level, "message" => "Level fetch successfully"], 201);
        }

        if ($request->ajax()) {

            return json_encode($level);
        }
        // dd($data);
        // pass them to view
        return view('admin.payrol.level');
    }


    public function create(Request $request)
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
        //check if the level already exist
        $existingLevel = Level::where('description', $request->description)->where('company_id', auth()->user()->company_id)->first();
        if ($existingLevel) {
            if (substr($this->currentRouteName, 0, 3) == "api") {
                return response()->json(["message" => "Level already exist"], 400);
            }
            return redirect()->back()->withErrors("Level already exist");
        }
        //save new grade
        $input['created_by'] = auth()->user()->id;
        $input['company_id'] =  auth()->user()->company_id;
        $saveGrade = Level::create($input);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Level added successfully", "data" => $saveGrade], 201);
        }
        return redirect()->back()->with('message', 'Level added successfully');
    }
    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'description' => 'required',
          
            'id' => 'required'
        ]);

        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }
        //check if the grade already exist
        $existingLevel = Level::where('description', $request->description)->where('company_id', auth()->user()->company_id)->where('id', '!=', $request->id)->first();
        if ($existingLevel) {
            return redirect()->back()->withErrors("Level already exist");
        }
        $thisLevel = Level::find($request->id);
        // dd($thisLevel, $input);
        //update new Level
        $saveLevel = $thisLevel->update(['description' => $request->description]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Level updated successfully", "data" => $thisLevel], 201);
        }
        return redirect()->back()->with('message', 'Level updated successfully');
    }


    public function delete(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if (substr($this->currentRouteName, 0, 3) == "api") {
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        }

        $id = $request->id;
        $saveLevel = Level::find($id);
        //delete level$saveLevel
        $saveLevel->delete();

        $responseArray = [
            "status" => 200,
            "message" => "Level deleted successfully!",
            "data" => true
        ];

        if (substr($this->currentRouteName, 0, 3) == "api") {
            return response()->json(["message" => "Level deleted successfully"], 201);
        }

        $response = response()->json(
            $responseArray
        );

        return $response;

    }
}
