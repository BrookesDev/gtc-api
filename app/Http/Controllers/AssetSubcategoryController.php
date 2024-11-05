<?php

namespace App\Http\Controllers;

use App\Models\asset_subcategory;
use App\Models\asset_category;
use App\Models\DepreciationMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetSubcategoryController extends Controller
{
    public function getAssetSubcategoryList()
    {
        $data['asset_subcategories'] = asset_subcategory::with(['assetCategory'])->get();
        

        return respond(true, 'Asset subcategories fetched successfully', $data, 200);
    }

    public function getAssetSubcategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_subcategories,id',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Record Not Found', $validator->errors()->first(), 404);
        }

        $data = asset_subcategory::find($request->id);

        return respond(true, 'Asset subcategory fetched successfully', $data, 200);
    }

    public function createAssetSubcategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'description' => [
            //     'required',
            //     Rule::unique('asset_subcategories'),
            // ],
            'description' => 'required',
            'category_id' => 'required',
            //'created_by' => 'required|unique:asset_categories,created_by',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation Error', $validator->errors(), 422);
        }

        try {
            $assetSubcategoryData = $request->all();
            //  $assetSubcategoryData['created_by'] = auth()->id(); // Set the user ID or another appropriate value
            //$assetSubcategoryData['created_by'];

            $assetSubcategory = asset_subcategory::create($assetSubcategoryData);

            return respond(true, 'Asset subcategory created successfully', $assetSubcategory, 201);
        } catch (\Exception $exception) {
            return respond(false, 'Server Error', $exception->getMessage(), 500);
        }
    }


    public function updateAssetSubcategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'category_id' => 'required|exists:asset_subcategories,id',
            'description' => 'required',

            //'created_by' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation Error', $validator->errors(), 422);
        }

        try {
            $id = $request->id;
            $assetSubcategory = asset_subcategory::findOrFail($id);
            $assetSubcategory->update($request->all());
            return respond(true, 'Asset subcategory updated successfully', $assetSubcategory, 200);
        } catch (\Exception $exception) {
            return respond(false, 'Server Error', $exception->getMessage(), 500);
        }
    }

    public function deleteAssetSubcategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_subcategories,id',
            'category_id' => 'required|exists:asset_subcategories,id',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Record Not Found', $validator->errors()->first(), 404);
        }

        $id = $request->id;
        asset_subcategory::find($id)->delete();

        return respond(true, 'Asset subcategory deleted successfully', $id, 200);
    }

    public function createDepreciationMethods(Request $request)
    {
        $validator = Validator::make($request->all(), [
        
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {

            $depreciationMethod = DepreciationMethod::create([
                'name' => $request->description
            ]);

            return respond(true, 'Depreciation created successfully', $depreciationMethod, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function updateDepreciationMethods(Request $request)
    {
        $validator = Validator::make($request->all(), [
        
            'id' => 'required|exists:depreciation_methods,id',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;

            $depreciationMethod = DepreciationMethod::find($id);

            if(!$depreciationMethod){
                return respond (false, 'invalid id', null, 400);
            }
            $depreciationMethod->update([
            'name' => $request->description
            ]);

            return respond(true, 'Depreciation updated successfully', $depreciationMethod, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function deleteDepreciationMethods(Request $request)
    {
        $validator = Validator::make($request->all(), [
        
            'id' => 'required|exists:depreciation_methods,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;

            $depreciationMethod = DepreciationMethod::find($id);

            if(!$depreciationMethod){
                return respond (false, 'invalid id', null, 400);
            }
            $depreciationMethod->delete();

            return respond(true, 'Depreciation deleted successfully', $depreciationMethod, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function fetchDepreciationMethods(Request $request)
    {
    $depreciationMethod = DepreciationMethod::where('company_id',auth()->user()->company_id)->with('company')->orderBy('created_at','desc')->get();
    return respond (true,'Depreciation methods fetched successfully', $depreciationMethod, 200);

    }
}
// to push it
