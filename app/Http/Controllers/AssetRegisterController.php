<?php

namespace App\Http\Controllers;

use App\Models\AssetRegisterDocument;
use Illuminate\Http\Request;
use App\Models\Fixed_Asset_Register;
use App\Models\DisapprovalComment;
use App\Models\asset_category;
use App\Models\Parish;
use App\Models\Province;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Imports\AssetsImport;
use App\Imports\FixedAssetImport;
use App\Exports\AssetImport;
use App\Exports\FixedAssetExport;
use App\Exports\ProvinceParishAssetExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Exports\ProvinceParishExport;
use Illuminate\Support\Facades\Storage;
use App\Models\AssetDisposal;


class AssetRegisterController extends Controller
{

    public function getStatisticByProvince(){
        return Excel::download(new ProvinceParishAssetExport(), "PROVINCE PARISH ASSET REPORT.xlsx");
    }

    public function getParishStatisticByProvinceExcel(Request $request){
        $validator = Validator::make($request->all(), [
            'province_id' => 'required|exists:provinces,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }
        $province = $request->province_id;
        $mainProvince = Province::find($province);
        return Excel::download(new ProvinceParishExport($province), "PARISH ASSET REPORT FOR $mainProvince->description.xlsx");
    }
    public function getParishStatisticByProvince(Request $request){
        $validator = Validator::make($request->all(), [
            'province_id' => 'required|exists:provinces,id',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }
        $province = $request->province_id;
        $perPage = $request->input('per_page', 100);
        $provinces = Province::where('id', $province)->with('parishes')->get();
        $data = [];
        // dd($provinces);
        foreach ($provinces as $province) {

            foreach ($province->parishes as $parish) {
                $parishId = $parish->id;
                $registered = getAvailableAssets()->where('parish_id', $parishId)->count() ;
                $approved = getAvailableAssets()->where('parish_id', $parishId)->where('approval_status', 2)->count();
                $value = getAvailableAssets()->where('parish_id', $parishId)->where('approval_status', 2)->sum('amount_purchased');

                $data[] = [
                    'parish' => $parish->description,
                    'registered' => $registered,
                    'approved' => $approved,
                    'value' => $value,
                ];
            }
        }
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($data, ($currentPage - 1) * $perPage, $perPage);
        $paginatedData = new LengthAwarePaginator($currentItems, count($data), $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
        // dd($data);
        return respond(true, 'Province assets retrieved successfully', $paginatedData, 200);
    }
    public function statistics(){
        $allAssets = getAllAssets()->get();
        $data['totalAssets'] = getAllAssets()->whereNotNull('parish_id')->count();
        $data['totalValue'] = getAllAssets()->where('approval_status', 2)->whereNotNull('parish_id')->sum('amount_purchased');
        $data['totalUsers'] = User::count();
        $getParishAssets = getAllAssets()->whereNotNull('parish_id')->select('parish_id')->distinct()->get();
        foreach($getParishAssets as $parish){
            $parishId = $parish->parish_id;
            $parishDetails = Parish::find($parishId);
            $parishAssets[] = [
                'id' => $parishDetails->id ?? "",
                'name' => $parishDetails->description ?? "",
                'registered' => getAllAssets()->where('parish_id', $parishId)->count(),
                'approved' => getAllAssets()->where('parish_id', $parishId)->where('approval_status', 2)->count(),
                'value' => getAllAssets()->where('parish_id', $parishId)->where('approval_status', 2)->sum('amount_purchased'),
            ];
        }
        $data['parishAssets'] = $parishAssets;
        return respond(true, 'Statistics fetched successfully!', $data, 200);

    }
    public function updateDisposed()
    {
        $disposedAssets = Fixed_Asset_Register::whereNotNull("date_disposed")->get();
        // dd($disposedAssets);
        foreach ($disposedAssets as $disposed) {
            $checkout = AssetDisposal::where('asset_id', $disposed->id)->first();
            if (!$checkout) {
                AssetDisposal::create([
                    "asset_id" => $disposed->id,
                    "amount_disposed" => 0,
                    "date_disposed" => $disposed->created_at,
                    "disposed_by" => $disposed->approved_by,
                    "created_by" => $disposed->approved_by,
                    "continent_id" => $disposed->continent_id,
                    "region_id" => $disposed->region_id,
                    "province_id" => $disposed->province_id,
                    "zone_id" => $disposed->zone_id,
                    "area_id" => $disposed->area_id,
                    "parish_id" => $disposed->parish_id,
                ]);
            }
        }
    }
    // public function getAllFixedAssets2()
    // {
    //     //$userType = auth()->user()->type;


    //     $fixedAssets = Fixed_Asset_Register::with(['assetCategory', 'assetSubCategory', 'documents'])->get();

    //     // // Filter fixed assets based on user type
    //     // if ($userType === 'Checker' || $userType === 'Initiator') {
    //     //     $fixedAssets->where('approval_status', 0);

    //     // } elseif ($userType === 'Provincial Admin' || $userType === 'Provincial Accountant') {
    //     //     $fixedAssets->whereIn('approval_status', [1, 2]);
    //     // } elseif ($userType === 'Super Admin') {
    //     //     $fixedAssets->where('approval_status', 2);
    //     // } else {
    //     //     return respond(false, 'Unauthorized user type to view fixed assets', null, 400);
    //     // }

    //     // $fixedAssets = $fixedAssets->get();

    //     return respond(true, 'Fixed assets retrieved successfully', $fixedAssets, 200);
    // }

    public function getAllFixedAssets()
    {
        $assets = getAssets()->with(['assetCategory', 'assetSubCategory', 'documents','asset_gl','depre_expense_account','depre_method'])->get();
        // dd($assets);
        return respond(true, 'Assets fetched successfully!', $assets, 200);
    }


    public function ggetAllFixedAssets()
    {

        // Initialize the query to fetch fixed assets
        $fixedAssetsQuery = Fixed_Asset_Register::with(['assetCategory', 'assetSubCategory', 'documents', 'approved_by_checker', 'approved_by']);

        // Execute the query and retrieve the fixed assets
        $fixedAssets = $fixedAssetsQuery->get();

        // Return the response
        return respond(true, 'Fixed assets retrieved successfully', $fixedAssets, 200);
    }

    public function getSuperAdminParishFixedAssets(Request $request)
    {

        $fixedAssets = Fixed_Asset_Register::where('approval_status', "2")
        ->with(['assetCategory', 'assetSubCategory', 'documents', 'approved_by_checker', 'approved_by'])
        ->paginate(100);
        // Return the response
        return respond(true, 'Fixed assets retrieved successfully', $fixedAssets, 200);
    }


    public function getallApprovedFixedAssets(Request $request)
    {


        $fixedAssets = getAssets()->where('approval_status', "2")->with(['assetCategory', 'assetSubCategory', 'documents', 'approved_by','company'])->paginate(100);

        // If the user type is unrecognized, return an error

        // Return the response
        return respond(true, 'Fixed assets retrieved successfully', $fixedAssets, 200);
    }

    public function getApprovedFixedAssetsForProvince(Request $request)
    {


        $fixedAssets = getAssets()->where('approval_status', "1")->with(['assetCategory', 'assetSubCategory', 'documents', 'approved_by_checker', 'approved_by', 'parish', 'province', 'area', 'zone'])->get();

        // If the user type is unrecognized, return an error

        // Return the response
        return respond(true, 'Fixed assets retrieved successfully', $fixedAssets, 200);
    }



    public function saveFixedAsset(Request $request)
    {
        // if (auth()->user()->type != "Initiator") {
        //     return respond(false, "Unauthorised", null, 403);
        // }
        $validator = Validator::make($request->all(), [
            'identification_number' => 'required|max:255',
            'description' => 'required|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'amount_purchased' => 'required|numeric',
            'date_purchased' => 'required|date',
            'depreciation_rate' => 'required',
            'depre_cal_period' => 'required',
            'asset_code' => 'required',
            'depre_expenses_account' => 'required|exists:accounts,id',
            'depre_method' => 'required|exists:depreciation_methods,id',
            'accumulated_depreciation' => 'required',
            'asset_gl' => 'required|exists:accounts,id',
            'location' => 'required|max:255',
            'lifetime_in_years' => 'required|numeric',
            'residual_value' => 'nullable|numeric',
            'quantity' => 'numeric',
            'asset_document' => 'nullable|array',
            'asset_document.*' => 'nullable|mimes:pdf,jpg,jpeg,png',
            'remarks' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();
            $getDepreciation = asset_category::find($request->category_id);
            $getValue = $getDepreciation->depreciation_rate;
            $currentYear = now()->year;
            $purchasedDate = $request->date_purchased;
            $carbonDate = \Carbon\Carbon::parse($purchasedDate);
            $year = $carbonDate->year;
            $range = getYearRange($year, $currentYear);
            $yearValue = count($range);
            $value = $yearValue - 1;
            // dd($value);
            $amount = $request->amount_purchased;
            if ($value <= 0) {
                $request['net_book_value'] = $amount;
            } elseif ($value < 6) {
                $percent = $amount / 100 * $getValue;
                $after = $percent * $value;
                $request['net_book_value'] = $amount - $after;
            } else {
                $request['net_book_value'] = 0;
            }
            $input = $request->all();
            // dd($input);
            $fixedAsset = Fixed_Asset_Register::create($request->except("asset_document"));

            if ($request->has('asset_document')) {

                foreach ($request->asset_document as $doc) {
                    $input['pdf_file'] = $path = uploadImage($doc, "assets_documents"); //for array
                    AssetRegisterDocument::create(["asset_id" => $fixedAsset->id, "pdf_file" => $input['pdf_file']]);
                }

            }

            DB::commit();
            return respond(true, 'Fixed asset created successfully', $fixedAsset, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function getFixedAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:fixed_asset_registers,id',
        ]);
        //making
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $id = $request->id;
        $fixedAsset = Fixed_Asset_Register::where('id', $id)->with('users')->get();

        if (!$fixedAsset) {
            return respond(false, 'Fixed asset not found.', null, 404);
        }

        return respond(true, $fixedAsset, 'Fixed asset retrieved successfully.', 200);
    }

    public function updateFixedAsset(Request $request)
    {
        // if (auth()->user()->type != "Initiator") {
        //     return respond(false, "Unauthorised", null, 403);
        // }
        $validator = Validator::make($request->all(), [
            'id' => 'required||exists:fixed_asset_registers,id',
            // 'category_id' => 'required|exists:asset_categories,id',
            //'subcategory_id' => 'required|exists:asset_subcategories,id',
            // 'description' => 'required',
            'asset_document' => 'nullable|array',
            'asset_document.*' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',

        ]);


        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        try {
            $id = $request->id;
            $fixedAsset = Fixed_Asset_Register::findOrFail($id);


            if ($request->has('asset_document')) {

                foreach ($request->asset_document as $doc) {
                    $input['pdf_file'] = $path = uploadImage($doc, "assets_documents"); //for array
                    AssetRegisterDocument::create(["asset_id" => $fixedAsset->id, "pdf_file" => $input['pdf_file']]);
                }

            }
            if ($fixedAsset->approval_status == 3){
                $fixedAsset->update(['approval_status' => '0']);
            }
            //dd($fixedAsset);
            $fixedAsset->update($request->except('asset_document'));



            return respond(true, 'Fixed asset updated successfully', $fixedAsset, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 401);
        }
    }

    public function deleteFixedAsset(Request $request)
    {
        // if (auth()->user()->type != "Initiator") {
        //     return respond(false, "Unauthorised to delete asset", null, 403);
        // }
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:fixed_asset_registers,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $id = $request->id;
        $fixedAsset = Fixed_Asset_Register::where('id', $id)->first();

        if ($fixedAsset->approval_status != "0") {
            return respond(false, 'You cannot delete this asset', null, 400);
        }
        $fixedAsset->delete();

        return respond(true, 'Fixed asset deleted successfully', $fixedAsset, 200);
    }
    public function downloadTemplate()
    {
        return Excel::download(new FixedAssetExport, 'Fixed_Asset.xlsx');
    }
    public function assetregisterDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|exists:fixed_asset_registers,id',
            'asset_document' => 'required|array',
            'asset_document.*' => 'required|mimes:pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 422);
        }
        $input = $request->all();
        foreach ($request->asset_document as $doc) {
            $input['pdf_file'] = $path = uploadImage($doc, "assets_documents"); //for array
            AssetRegisterDocument::create(["asset_id" => $request->asset_id, "pdf_file" => $path]); //for array
        }

        return respond(true, 'file uploaded and saved successfully', $path, 200);
    }
    public function uploadexcel(Request $request)
    {
        $request->validate([
            'asset_document' => 'required|mimes:xlsx,xls|max:10240', // Adjust the file validation as needed
        ]);

        // Get the uploaded file
        $file = $request->file('asset_document');

        // Process the uploaded Excel file (you might want to validate and store it)
        $import = new FixedAssetExport;
        Excel::import($import, $file);

        // You can add additional logic here, such as saving the data to the database

        // return response()->json(['message' => 'Excel file uploaded and processed successfully']);
        return respond(true, 'Excel file uploaded and processed successfully', $import, 200);
    }
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|exists:fixed_asset_registers,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 422);
        }
        try {
            DB::beginTransaction();
            $id = $request->asset_id;
            $fixedAsset = Fixed_Asset_Register::find($id);
            $old = $fixedAsset->toArray();
            $input = $request->except('asset_id');
            $fixedAsset->update($input);
            // dd($fixedAsset,$newValue);
            transferAsset($old, $fixedAsset->refresh());
            DB::commit();

            return respond(true, 'Asset transfer successfully', $fixedAsset->refresh(), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), $e, 400);
        }
    }

    public function transferReport()
    {
        $report = getAssetTransfer()->with(['company'])->paginate(100);


        return respond(true, 'Asset transfer report retrieved successfully', $report, 200);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:asset_categories,id',
            'file' => 'required|file|mimes:xls,xlsx',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(request()->file('file'));
        $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $countdata = count($sheetData) - 1;
        // dd($countdata,$request->all());
        if ($countdata < 1) {
            return respond(false, "Excel File Is Empty! Populate And Upload! ", $countdata, 400);
        }
        // DB::beginTransaction();
        try {
            $categeory = $request->category_id;
            Excel::import(new FixedAssetImport($categeory), request()->file('file'));
            // DB::commit();
            return respond(true, "Import successful!!", $countdata, 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // DB::rollback();
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $errormess = '';
                foreach ($failure->errors() as $error) {
                    $errormess = $errormess . $error;
                }
                $errormessage[] = 'There was an error on Row ' . ' ' . $failure->row() . '.' . ' ' . $errormess;
            }
            return respond(false, $errormessage, $countdata, 400);
        } catch (\Exception $exception) {
            // DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            // dd($errorCode);
            if (is_int($errorCode)) {
                return respond(false, $exception->errorInfo[2], $countdata, 400);
            } else {
                // dd($exception);
                return respond(false, $exception->getMessage(), $countdata, 400);
            }
        }
    }

    // public function filter(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'continent_id' => 'required|exists:asset_categories,id',
    //         'region_id' => 'nullable|exists:regions,id',
    //         'province_id' => 'nullable|exists:provinces,id',
    //         'zone_id' => 'nullable|exists:zones,id',
    //         'area_id' => 'nullable|exists:areas,id',
    //         'parish_id' => 'nullable|exists:parishes,id',
    //     ]);
    //     if ($validator->fails()) {
    //         return respond(false, $validator->errors(), null, 400);
    //     }
    //     $continent = $request->continent_id;
    //     $region = $request->region_id;
    //     $province = $request->province_id;
    //     $zone = $request->zone_id;
    //     $area = $request->area_id;
    //     $parish = $request->parish_id;
    //     // dd($parish);
    //     if ($region == null) {
    //         $filterData = Fixed_Asset_Register::where('continent_id', $continent)->with(['assetCategory'])->get();
    //     } elseif ($province == null) {
    //         $filterData = Fixed_Asset_Register::where('region_id', $region)->with(['assetCategory'])->get();
    //     } elseif ($zone == null) {
    //         $filterData = Fixed_Asset_Register::where('province_id', $province)->with(['assetCategory'])->get();
    //     } elseif ($area == null) {
    //         $filterData = Fixed_Asset_Register::where('zone_id', $zone)->with(['assetCategory'])->get();
    //     } elseif ($parish == null) {
    //         $filterData = Fixed_Asset_Register::where('area_id', $area)->with(['assetCategory'])->get();
    //     } elseif ($parish != null) {
    //         $filterData = Fixed_Asset_Register::where('parish_id', $parish)->with(['assetCategory'])->get();
    //     }
    //     return respond(true, "Filter successful!!", $filterData, 200);

    // }


    public function approveFixedAssetRegister(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:fixed_asset_registers,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $register = Fixed_Asset_Register::findOrFail($id);
            // $userType = auth()->user()->type;

            $register->update(['approval_status' => '1']);

            return respond(true, 'Fixed asset register approved successfully', $register, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }




    public function disapproveFixedAssetRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:fixed_asset_registers,id',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $register = Fixed_Asset_Register::findOrFail($id);
            if ($register->approval_status === '3') {
                return respond(false, 'Fixed asset is already Disapproved!', null, 400);
            }

            $register->update(['approval_status' => '3', 'disapproved_by' => auth()->user()->id]);
            DisapprovalComment::create([

                'description' => $request->description,
            ]);

            $register->save();

            return respond(true, 'Fixed asset register disapproved successfully', $register, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchSoftdelete()
    {
        $deleted = Fixed_Asset_Register::where('province_id',auth()->user()->company_id)
        ->onlyTrashed()->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Data fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Fixed_Asset_Register::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived asset restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Asset is not yet archived!', null, 400);
        } else {
            return respond(false, 'Asset not found!', null, 404);
        }
    }

    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Fixed_Asset_Register::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived asset permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Asset is not yet archived!', null, 400);
        } else {
            return respond(false, 'Asset not found!', null, 404);
        }
    }

    public function getAssetByParish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        $parish_id = $request->company_id;

        $Parishes = Fixed_Asset_Register::where('company_id', $parish_id)->orderBy('created_at', 'desc')
            ->with(['assetCategory', 'assetSubCategory', 'documents', 'approved_by_checker', 'approved_by'])
            ->get();
        return respond(true, 'company assets fetched successfully', $Parishes, 201);
    }
    public function getAssetForParish(Request $request)
    {
        $user = Auth::user();
        // dd($user);
        $parish_id = $user->company_id;

        $Province = Fixed_Asset_Register::where('province_id', $parish_id)->whereNull('date_disposed')->with([ 'company','assetCategory', 'assetSubCategory', 'documents', 'approved_by_checker', 'approved_by'])->get();
        return respond(true, 'Parish assets fetched successfully', $Province, 200);
    }


}

