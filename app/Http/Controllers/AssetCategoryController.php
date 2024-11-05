<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\asset_category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AssetCategoryController extends Controller
{


    public function getAllAssetCategories()
    {
        $assetCategories = asset_category::all();


        return respond(true, 'All asset categories retrieved successfully', $assetCategories, 201);


    }


    public function saveAssetCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|max:255',
            'depreciation_rate' => 'required',
            'depreciation_method' => 'required',
            //'depreciation_method' => 'required',

            //'created_by' => 'required|unique:asset_categories,created_by',
            //'asset_id' => 'required|exists:asset_categories,id'
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 401);
        }
        try {

            $assetCategory = asset_category::create($request->all());


            return respond(true, 'Asset category created successfully', $assetCategory, 201);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the process
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function showAssetCategory(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_categories,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $myasset = asset_category::where('id', $request->id)->first();
        if (!$myasset) {
            return respond(false, 'Asset category not found', null, 401);
        }

        //nwsest
        $id = $request->id;
        $assetCategory = asset_category::find($id);


        /*if (!$assetCategory) {

            return respond(false, 'Asset category not found.', null, 404);
        }
*/
        return respond(true, $assetCategory, 'Asset category retrieved successfully.', 200);
    }

    public function updateAssetCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_categories,id',
            //'description' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $existingasset = asset_category::where('id', $request->id)->first();
        if (!$existingasset) {
            return respond(false, 'Asset not found', null, 401);
        }

        $id = $request->id;
        $assetCategory = asset_category::find($id);

        //        if (!$assetCategory) {
        //          return respond(false, 'Asset category not found.', null, 404);
        //    }

        $assetCategory->update($request->all());
        // Update other fields as ne);
        return respond(true, 'Asset category updated successfully.', $assetCategory, 201);
    }

    public function deleteAssetCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_categories,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $existingasset = asset_category::where('id', $request->id)->first();
        if (!$existingasset) {
            return respond(false, 'Asset not found', null, 401);
        }

        $id = $request->id;
        $assetCategory = asset_category::find($id);

        // Delete the asset category
        $assetCategory->delete($request->all());

        return respond(true, 'Asset category deleted successfully.', $assetCategory, 201);
    }

    public function fetchSoftdelete()
    {
        $deleted = asset_category::onlyTrashed()->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Fetch deleted department successfully!', $deleted, 201);
    }

    public function restoreSingleSoftdelete(Request $request)
    {
        $department = asset_category::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved categories restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Category is not yet archived!', null, 400);
        } else {
            return respond(false, 'Category not found!', null, 404);
        }
    }

    public function deleteSingleSoftdelete(Request $request)
    {
        $department = asset_category::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved category permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved category is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved category not found!', null, 404);
        }
    }
}
