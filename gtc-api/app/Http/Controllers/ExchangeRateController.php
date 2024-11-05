<?php

namespace App\Http\Controllers;

use function App\Helpers\api_request_response;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExchangeRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['rates'] = ExchangeRate::orderBy('created_at', 'desc')->get();
        return view('admin.rate.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        DB::beginTransaction();
        $input = $request->all();
        try {
            $validator = Validator::make($input, [
                // 'date' => 'required|date',
                'currency' => 'required|exists:currencies,id',
                'exchange_rate' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $input['specified_by'] = Auth::user()->id;
            $input['rate'] = preg_replace('/[^\d.]/', '', $request->exchange_rate);
            $rate = ExchangeRate::create($input);

            // Commit the transaction
            DB::commit();

            return respond(true, 'Exchange Rate created successfully', $input, 200);
        } catch (\Exception $exception) {
            // Rollback the transaction in case of error
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function fetch()
    {
        try {
            // $exchangeRates = ExchangeRate::all();
            $exchangeRates = ExchangeRate::with('currency', 'specify_by')->orderBy('created_at', 'desc')->get();

            return respond(true, 'Exchange Rates fetched successfully', $exchangeRates, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function update(Request $request)
    {
        DB::beginTransaction();
        $input = $request->all();
        try {
            $validator = Validator::make($input, [
                'id' => 'required|exists:exchange_rates,id',
                'date' => 'required|date',
                'currency' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable',
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $id = $request->id;
            $exchangeRate = ExchangeRate::findOrFail($id);
            $input['specified_by'] = Auth::user()->id;
            $input['rate'] = preg_replace('/[^\d.]/', '', $request->exchange_rate);
            $exchangeRate->update($input);


            // Commit the transaction
            DB::commit();

            return respond(true, 'Exchange Rate updated successfully', $input, 200);
        } catch (\Exception $exception) {
            // Rollback the transaction in case of error
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function edit(ExchangeRate $exchangeRate)
    {
        //
    }



    public function delete(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:exchange_rates,id',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $rate = ExchangeRate::find($request->id);
            if (auth()->user()->company_id !== $rate->company_id) {
                return respond('error', 'You do not have permission to delete this exchange rate', null, 403);
            }
            $rate->delete();
            return respond(true, 'Exchange Rate deleted successfully', $rate, 200);

        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }

    }
    public function fetchSoftdelete()
    {
        $deleted = ExchangeRate::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved exchange rate successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = ExchangeRate::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved exchange rate restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Archieved exchange rate is not deleted!', null, 400);
        } else {
            return respond(false, 'Archieved exchange rate not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = ExchangeRate::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved exchange rate found to restore!', null, 404);
        }
        ExchangeRate::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved exchange rate restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = ExchangeRate::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved exchange rate permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved exchange rate is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved exchange rate not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = ExchangeRate::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved exchange rate found to permanently delete!', null, 404);
        }
        ExchangeRate::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved exchange rate permanently deleted successfully!', null, 200);
    }
}
