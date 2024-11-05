<?php

namespace App\Http\Controllers;

use App\Models\OrganisationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class OrganisationTypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $organisationTypes = OrganisationType::latest()->get();


        return response()->json(["data" => $organisationTypes, "message" => "Organisation Types fetched successfully"], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('organisation_types.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }


        $organisationType = OrganisationType::create($request->all());


        return response()->json(["data" => $organisationType, "message" => "Organisation Type created successfully"], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrganisationType  $organisationType
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }


        $organisationType = OrganisationType::find($request->id);

        return response()->json(["data" => $organisationType, "message" => "Organisation Type fetched successfully"], 200);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrganisationType  $organisationType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'id' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }


        $organisationType = OrganisationType::find($request->id);

        $organisationType->update($request->all());


        return response()->json(["data" => $organisationType, "message" => "Organisation Type updated successfully"], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrganisationType  $organisationType
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }



        OrganisationType::find($request->id)->delete();


        return response()->json(["message" => "Organisation Type deleted successfully"], 200);
    }
    public function fetchSoftdelete()
    {
        $deleted = OrganisationType::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved organisation type successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = OrganisationType::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved organisation type restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archieved organisation type is not deleted!', null, 400);
        } else {
            return respond(false, 'Archieved organisation type not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = OrganisationType::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved organisation type found to restore!', null, 404);
        }
        OrganisationType::where('company_id',auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved organisation type restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = OrganisationType::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved organisation type permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved organisation type is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved organisation type not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = OrganisationType::where('company_id',auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved organisation type found to permanently delete!', null, 404);
        }
        OrganisationType::where('company_id',auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved organisation type permanently deleted successfully!', null, 200);
    }
}
