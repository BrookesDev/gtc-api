<?php

namespace App\Http\Controllers;

use App\Helpers\api_request_response;
use App\Helpers\bad_response_status_code;
use App\Helpers\success_status_code;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $brands = Brand::all();
        return api_request_response(
            "ok",
            "Brands retrieved successfully",
            success_status_code(),
            $brands
        );
    }

    /**
     * Store or update a brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();

        try {
            if ($request->has('id')) {
                $brand = Brand::findOrFail($request->id);
                $brand->update($input);

                // Audit log
                $description = auth()->user()->name . " updated a brand";
                myaudit('updated', $brand, $description);

                return api_request_response(
                    "ok",
                    "Brand updated successfully!",
                    success_status_code(),
                    $brand
                );
            } else {
                $brand = Brand::create($input);

                // Audit log
                $description = auth()->user()->name . " created a brand";
                myaudit('created', $brand, $description);

                return api_request_response(
                    "ok",
                    "Brand created successfully!",
                    success_status_code(),
                    $brand
                );
            }

        } catch (\Exception $e) {
            return api_request_response(
                "error",
                $e->getMessage(),
                bad_response_status_code()
            );
        }
    }

    /**
     * Show the specified brand.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return api_request_response(
                "ok",
                "Brand retrieved successfully",
                success_status_code(),
                $brand
            );
        } catch (\Exception $e) {
            return api_request_response(
                "error",
                $e->getMessage(),
                bad_response_status_code()
            );
        }
    }

    /**
     * Remove the specified brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $brand = Brand::findOrFail($request->id);
            $brand->delete();

            return api_request_response(
                "ok",
                "Brand deleted successfully!",
                success_status_code()
            );
        } catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }
}
