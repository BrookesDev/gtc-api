<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Locker;

class LockerController extends Controller
{
    public function checkLicense(Request $request){
        $validator = Validator::make($request->all(), [
            'appname' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 400,'error' => "Supply the app name"],400);
        }

        $appName = $request->appname;
        //check appName
        $checkAPp = Locker::where('name', $appName)->first();
        if(!$checkAPp){
            return response()->json(['status' => false,'message' => "App has not been licensed"],400);
        }
        if(!$checkAPp->lock){
            return response()->json(['status' => false,'message' => "App has not been licensed"],400);
        }

        return response()->json(['status' => true,'message' => "App is valid"],200);
    }

    public function createlockSystem(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'url' => 'required',
            'lock' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 400,'error' => $validator->errors()],400);
        }

        $input = $request->all();
        $checkDupplicate= Locker::where('name',$request->name)->orWhere('url', $request->url)->first();
        if(!$checkDupplicate){
            $createLockSystem = Locker::create($input);
        }
        return response()->json(['status' => true,'message' => "Lock Created successfully."],200);

    }

    public function lockSystem(Request $request){

        if($request->password !== "BrookesLockerP321"){
            return response()->json(['status' => 400,'error' => "Invalid Locker Password"],400);
        }
        $app = Locker::where('name', $request->appname)->firstOrFail();

        if($request->action== 'lock'){
                // dd($app, 'locked');
                $app->update(['lock'=>0]);
                return response()->json(['status' => true,'message' => "$request->appname locked successfully"],200);

        }else{
                    //  dd($app, 'unlocked');
                $app->update(['lock'=>1]);
                return response()->json(['status' => true,'message' => "$request->appname unlocked successfully"],200);

        }



    }

}
