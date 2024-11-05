<?php

namespace App\Http\Controllers;

use App\Models\Fixed_Asset_Register;
use App\Models\AssetDisposal;  // Make sure to import the Asset Disposal model
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AssetDisposalController extends Controller
{
    public function getAssetDisposalList()
    {
        try {
            //$data['asset_disposals'] = AssetDisposal::all();
            // dd("here");
            $data['asset_disposals'] = getDisposedAssets()->with('asset')->get();

            return respond(true, 'Asset disposals fetched successfully', $data, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function getAssetDisposal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:asset_disposals,id',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Record Not Found', $validator->errors()->first(), 404);
        }

        $data = AssetDisposal::find($request->id);

        return respond(true, 'Asset disposal fetched successfully', $data, 200);
    }

    public function createAssetDisposal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'description' => 'required',
            'asset_id' => 'required|exists:fixed_asset_registers,id',
            'date_disposed' => 'required|date',
            'amount_disposed' => 'required|numeric',
            //'created_by' => 'required|unique:asset_disposals,created_by',
        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation Error', $validator->errors(), 422);
        }

        try {
            $assetDisposalData = $request->all();
            //  $assetDisposalData['created_by'] = auth()->id(); // Set the user ID or another appropriate value
            //$assetDisposalData['created_by'];
            $asset = Fixed_Asset_Register::find($request->asset_id);
            $asset->update(["date_disposed" => $request->date_disposed]);
            $assetDisposal = AssetDisposal::create($assetDisposalData);

            return respond(true, 'Asset disposal created successfully', $assetDisposal, 201);
        } catch (\Exception $exception) {
            return respond(false, 'Server Error', $exception->getMessage(), 500);
        }
    }

    // Implement update, delete, and other methods as needed
}
