<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\User;
use App\Models\LocalGovt;
use App\Models\State;
use App\Models\Country;
use Hash;
use Illuminate\Support\Facades\File;

class AgentController extends Controller
{
    public function registerAgent(Request $request)
    {
        $input = $request->all();
        // dd($input);
        //validate duplicate record
        $existingRecord = User::where('phone_number',$request->phone_number)
        ->orWhere('email', $request->email)->first();
        if($existingRecord){
            return redirect()->back()->withErrors("Duplicate entry! There is another user with your email or phone number!");
        }
        //create user from the input
        $newUser = new User();
        $newUser->name = $input['full_name'];
        $newUser->email = $input['email'];
        $newUser->phone_number = $input['phone_number'];
        $newUser->password =Hash::make($request->password);
        $newUser->deleted =0;
        $newUser->is_first_time =0;
        $newUser->user_type ='agent';
        $newUser->last_login =now();
        $createNewUser = $newUser->save();

        // create agent and link it with the use with user id
        $newAgent = new Agent();
        $newAgent->fullname =$input['full_name'];
        $newAgent->email = $input['email'];
        $newAgent->phone_number = $input['phone_number'];
        $newAgent->title = $input['title'];
        $newAgent->user_id = $newUser->id;
        $createNewAgent = $newAgent->save();

        return redirect()->back()->with('success','You have successfully registered with us!, login and upload your property');
        # code...
    }

    public function dashboard(Request $request)
    {
        return view('agent.dashboard');
    }

    public function profile()
    {
        $data['myprofile'] = $thisAgent = Agent::where('user_id',auth()->user()->id)->first();
        $data['countries'] = Country::all();
        $data['states'] = State::all();
        $data['lgas'] = LocalGovt::all();
        return view('agent.profile', $data);
    }

    public function getLga(Request $request)
    {
        // dd($request->id);
        $pp['data'] = $info = LocalGovt::where('state_id', $request->id)->get();

        return json_encode($pp);
    }

    public function update_profile (Request $request)
    {
        $agent = Agent::where('user_id',auth()->user()->id)->first();
        //save photo

        $input = $request->all();

        // dd($input);
        if ($request->has('photo')) {
             $photoName = time()  . '.' . $request->photo->extension();
            $request->photo->move(public_path('profile_picture'), $photoName);
            $input["photo"]= $photoName;

            $filename = public_path() . '/profile_picture/' . $agent->photo;

            if (File::exists($filename)) {

                File::delete($filename);
            }
         }

         $updateAgentRecord = $agent->fill($input)->save();

        return redirect()->back()->with('success','Your record has been updated successfully!');
        # code...
    }


    public function agentProperties(Request $request)
    {
        return view('agent.properties');
    }
}
