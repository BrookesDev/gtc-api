<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Setting;

class SettingController extends Controller
{
    public function activateWorkingMonth(Request $request)
    {
        try {
            $id = auth()->user()->company_id;
            $set = Company::find($id);
            Setting::create([
                'company_id' => $id,
                'working_month' => 1,
            ]);

            return respond(true, 'Tax created successfully', $set, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function deactivateWorkingMonth(Request $request)
    {
        try {
            $id = auth()->user()->company_id;
            $set = Company::find($id);


            $setting = Setting::where('company_id', $id)->first();

            if ($setting) {
                $setting->update(['working_month' => 0]);

                return respond(true, 'Working month deactivated successfully', $set, 200);
            } else {
                return respond(false, 'Setting not found for the company', null, 404);
            }

        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }


}



