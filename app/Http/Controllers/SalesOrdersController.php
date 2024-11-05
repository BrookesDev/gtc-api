<?php

namespace App\Http\Controllers;

use App\Models\SalesOrders;
use App\Models\GeneralInvoice;
use App\Models\Account;
use App\Models\SupportingDocument;
use App\Customers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\SendSalesOrderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\SalesRep;
use App\Models\Item;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Currency;
use Carbon\Carbon;

class SalesOrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $salesorder = getSalesOrders()->get();
        return respond(true, 'Sales orders fetched successfully', $salesorder, 200);
    }
    public function pendingOrders()
    {
        $salesorder = getSalesOrders()->where('status', 0)->get();
        return respond(true, 'Sales orders fetched successfully', $salesorder, 200);
    }
    public function CompletedOrders()
    {
        $salesorder = getSalesOrders()->where('status', 1)->get();
        return respond(true, 'Sales orders fetched successfully', $salesorder, 200);
    }

    public function totalorderCount()
    {
        $salesCount = getSalesOrders()->count();
        $salesValue = GeneralInvoice::where('type', 'Sales')->sum('amount');

        $response = [
            'total_number_of_orders' => $salesCount,
            'total_order_amount' => $salesValue,
        ];

        return respond(true, 'Data fetched successfully', $response, 200);
    }
    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $companyId = auth()->user()->company_id;
            $company = Company::where('id', $companyId)->first();
            $setting = Setting::where('company_id', $companyId)->first();

            if ($setting && $setting->working_month == 1) {
                $companyCurrentMonth = Carbon::parse($company->current_month)->format('Y-m');
                $transactionDate = Carbon::parse($request->transaction_date);
                $transactionMonth = $transactionDate->format('Y-m'); // Year and Month

                if ($transactionMonth != $companyCurrentMonth) {
                    return respond(false, "Transaction date must be within the current working month ({$companyCurrentMonth})", null, 400);
                }
            }
            $validator = Validator::make($request->all(), [
                'option_type' => 'required|in:product,service',
                'customer_id' => 'required|exists:customers,id',
                'document_number' => 'required|unique:sales_orders',
                'reference' => 'nullable',
                'date' => 'required|date',
                'transaction_date' => 'required|date',
                'expiring_date' => 'required|date',
                'tax_id' => 'nullable',
                'sales_rep' => 'nullable|exists:sales_reps,id',
                'total_amount' => 'required|numeric',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'currency' => 'required|exists:currencies,id',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'required|array',
                'item.*' => 'required|exists:items,id',
                'quantity' => 'nullable|array',
                'quantity.*' => 'nullable|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'service_amount' => 'nullable|array',
                'service_amount.*' => 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
                'description' => 'nullable|array',
                'description.*' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            //dd($request->all());
            $data = $request->all();
            // $uuid = generate_uuid();
            $month = now()->format('m');
            $uuid = "SO" . '-' . $month . '-' . rand(1000000, 99999999);

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
            } else {
                $data['supporting_document'] = null;
            }
            if ($request->has('supporting_document')) {
                $document = SupportingDocument::create([
                    'uuid' => $uuid,
                    'type' => "Sales",
                    'file' => $data['supporting_document'],
                ]);
            }
            foreach ($data['item'] as $index => $itemId) {
                // Retrieve item name
                $item = Item::find($itemId);
                if (!$item) {
                    return respond(false, 'Item not found', null, 400);
                }

                GeneralInvoice::create([
                    'item_id' => $itemId,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales",
                    'amount' => $data['amount'][$index] ?? '0',
                    'service_amount' => $data['service_amount'][$index] ?? '0',
                    'description' => $data['description'][$index] ?? "",
                    'quantity' => $data['quantity'][$index] ?? '0',
                    'tax_id' => $data['tax_id'][$index] ?? "",
                    'discount' => $data['discount'][$index] ?? "",
                    'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                ]);
            }
            $data['status'] = 0;
            $data['uuid'] = $uuid;
            $data['amount'] = $request->total_amount;
            $data['transaction_date'] = $request->transaction_date;
            $sales = SalesOrders::create($data);
            DB::commit();
            return respond(true, 'Sales Order created successfully', $sales, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function creating(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'option_type' => 'required|in:product,service',
                'customer_id' => 'required|exists:customers,id',
                'document_number' => 'required|unique:sales_orders',
                'reference' => 'nullable',
                'date' => 'required|date',
                'transaction_date' => 'required|date',
                'expiring_date' => 'required|date',
                'tax_id' => 'nullable',
                'sales_rep' => 'nullable|exists:sales_reps,id',
                'total_amount' => 'required|numeric',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                // 'currency' => 'required|exists:currencies,id',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                // 'item' => 'required|array',
                // 'item.*' => 'required|exists:items,id',
                'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
                'amount' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                'amount.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
                'account' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                'account.*' => $request->option_type === 'service' ? 'required|exists:accounts,id' : 'nullable|exists:accounts,id',
                'item' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                'item.*' => $request->option_type === 'product' ? 'required|exists:items,id' : 'nullable|exists:items,id',
                'service_amount' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                'service_amount.*' => $request->option_type === 'service' ? 'required|numeric' : 'nullable|numeric',
                'service_description' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                'service_description.*' => $request->option_type === 'service' ? 'required|string' : 'nullable|string',
                'total_service_amount' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                'total_service_amount.*' => $request->option_type === 'service' ? 'required|numeric' : 'nullable|numeric',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            //dd($request->all());
            $data = $request->all();
            // $uuid = generate_uuid();
            $month = now()->format('m');
            $uuid = "SO" . '-' . $month . '-' . rand(1000000, 99999999);

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
            } else {
                $data['supporting_document'] = null;
            }
            if ($request->has('supporting_document')) {
                $document = SupportingDocument::create([
                    'uuid' => $uuid,
                    'type' => "Sales",
                    'file' => $data['supporting_document'],
                ]);
            }

            if ($request->option_type === 'service') {
                foreach ($data['account'] as $index => $accountID) {
                    // Retrieve item name
                    $account = Account::find($accountID);
                    if (!$account) {
                        return respond(false, 'Account not found', null, 400);
                    }

                    GeneralInvoice::create([
                        'account_id' => $accountID,
                        'invoice_number' => $uuid,
                        'uuid' => $uuid,
                        'type' => "Quote",
                        'service_amount' => $data['service_amount'][$index],
                        'total_service_amount' => $data['total_service_amount'][$index],
                        'service_description' => $data['service_description'][$index],
                        // 'amount' => $data['amount'][$index],
                        // 'quantity' => $data['quantity'][$index],
                        'tax_id' => $data['tax_id'][$index] ?? "",
                        'discount' => $data['discount'][$index] ?? "",
                        'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                    ]);

                }
            } else {
                foreach ($data['item'] as $index => $itemId) {
                    // Retrieve item name
                    $item = Item::find($itemId);
                    if (!$item) {
                        return respond(false, 'Item not found', null, 400);
                    }

                    GeneralInvoice::create([
                        'item_id' => $itemId,
                        'invoice_number' => $uuid,
                        'uuid' => $uuid,
                        'type' => "Sales",
                        'amount' => $data['amount'][$index],
                        'quantity' => $data['quantity'][$index],
                        'tax_id' => $data['tax_id'][$index] ?? "",
                        'discount' => $data['discount'][$index] ?? "",
                        'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                    ]);
                }
            }

            $data['status'] = 0;
            $data['uuid'] = $uuid;
            $data['amount'] = $request->total_amount;
            $data['transaction_date'] = $request->transaction_date;
            $data['option_type'] = $request->option_type;
            $sales = SalesOrders::create($data);
            DB::commit();
            return respond(true, 'Sales Order created successfully', $sales, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $companyId = auth()->user()->company_id;
            $company = Company::where('id', $companyId)->first();
            $setting = Setting::where('company_id', $companyId)->first();

            if ($setting && $setting->working_month == 1) {
                $companyCurrentMonth = Carbon::parse($company->current_month)->format('Y-m');
                // $companyCurrentMonth = $company->current_month->format('Y-m');
                // $transactionDate = date('Y-m', strtotime($request->transaction_date));
                $transactionDate = Carbon::parse($request->transaction_date);
                $transactionMonth = $transactionDate->format('Y-m'); // Year and Month

                if ($transactionMonth != $companyCurrentMonth) {
                    return respond(false, "Transaction date must be within the current working month ({$companyCurrentMonth})", null, 400);
                }
            }
            // Validate the request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sales_orders,id',
                'customer_id' => 'nullable|exists:customers,id',
                'document_number' => [
                    'nullable',
                    'string',
                    Rule::unique('sales_orders', 'document_number')->ignore($request->id),
                ],
                'reference' => 'nullable',
                'date' => 'nullable|date',
                'transaction_date' => 'nullable|date',
                'expiring_date' => 'nullable|date',
                'sales_rep' => 'nullable|exists:sales_reps,id',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'nullable|array',
                'item.*' => 'nullable|exists:items,id',
                'tax_id' => 'nullable|array',
                'tax_id.*' => 'nullable|exists:taxes,id',
                'quantity' => 'nullable|array',
                'quantity.*' => 'nullable|numeric',
                'total_amount' => 'nullable|numeric',
                'amount' => 'nullable|array',
                'amount.*' => 'nullable|numeric',
                'service_amount' => 'nullable|array',
                'service_amount.*' => 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $quote = SalesOrders::find($id);
            $olduuid = $quote->uuid;

            $data = $request->all();

            // Check if customer_id has changed, and if so, generate a new UUID
            if ($request->customer_id && $quote->customer_id !== $request->customer_id) {
                $month = now()->format('m');
                $newUuid = "SO" . '-' . $month . '-' . rand(1000000, 99999999);

                $quote->uuid = $newUuid;
                $quote->customer_id = $request->customer_id;

                // Update related entries with the new UUID
                GeneralInvoice::where('invoice_number', $quote->uuid)->update(['uuid' => $newUuid]);
                SupportingDocument::where('uuid', $quote->uuid)->update(['uuid' => $newUuid]);
            }

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
                SupportingDocument::where('uuid', $olduuid)->update([
                    'uuid' => $quote->uuid,
                    'file' => $data['supporting_document'],
                ]);
            } else {
                SupportingDocument::where('uuid', $olduuid)->update([
                    'uuid' => $quote->uuid,
                ]);
            }

            // Update the GeneralInvoice entries related to the quote
            GeneralInvoice::where('invoice_number', $olduuid)->delete(); // Delete existing entries to replace with updated ones
            foreach ($data['item'] as $index => $itemId) {
                // Retrieve item name
                $item = Item::find($itemId);
                if (!$item) {
                    return respond(false, 'Item not found', null, 400);
                }

                GeneralInvoice::create([
                    'item_id' => $itemId,
                    'invoice_number' => $quote->uuid,
                    'uuid' => $quote->uuid,
                    'type' => "Quote",
                    'amount' => $data['amount'][$index],
                    'service_amount' => $data['service_amount'][$index] ?? '0',
                    'quantity' => $data['quantity'][$index] ?? '0',
                    'tax_id' => $data['tax_id'][$index] ?? "",
                    'discount' => $data['discount'][$index] ?? "",
                    'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                ]);
            }

            // Update the quote with new data
            $quote->document_number = $request->document_number ?? $quote->document_number;
            $quote->reference = $request->reference ?? $quote->reference;
            $quote->date = $request->date ?? $quote->date;
            $quote->transaction_date = $request->transaction_date ?? $quote->transaction_date;
            $quote->expiring_date = $request->expiring_date ?? $quote->expiring_date;
            $quote->sales_rep = $request->sales_rep ?? $quote->sales_rep;
            $quote->sub_total = $request->sub_total ?? $quote->sub_total;
            $quote->total_vat = $request->total_vat ?? $quote->total_vat;
            $quote->total_discount = $request->total_discount ?? $quote->total_discount;
            $quote->total_price = $request->total_price ?? $quote->total_price;
            $quote->amount = $request->total_amount;
            $quote->status = 0;
            $quote->save();

            DB::commit();
            return respond(true, 'Quote updated successfully', $quote, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function delete(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'id' => 'required|exists:sales_orders,id',
            ]);
            if ($validator->fails()) {
                return respond('message', $validator->errors(), null, 400);
            }
            $salesorder = SalesOrders::find($request->id);
            $salesorder->delete();
            return respond(true, 'sales order archived successfully', $salesorder, 200);
        } catch (\Exception $e) {
            // return response()->json(['message' => $e->getMessage()], 400);
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function sendQuoteEmail(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'order_id' => 'required|exists:sales_orders,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            // Fetch the quote and related customer
            $quoteId = $request->order_id;
            $quote = SalesOrders::find($quoteId);
            if (!$quote) {
                return respond(false, 'Quote not found', null, 404);
            }

            $customer = Customers::find($quote->customer_id);
            if (!$customer) {
                return respond(false, 'Customer not found', null, 404);
            }
            $customerEmail = $customer->email;
            $name = $customer->name;

            // Fetch all quotes with the same UUID
            $quotes = SalesOrders::where('uuid', $quote->uuid)->get();
            if ($quotes->isEmpty()) {
                return respond(false, 'No quotes found with the same UUID', null, 404);
            }

            // Prepare email data
            $itemsData = [];
            foreach ($quotes as $quoteItem) {
                $itemsData[] = [
                    'item' => $quoteItem->item,
                    'quantity' => $quoteItem->quantity,
                    'amount' => $quoteItem->amount,
                    'discount' => $quoteItem->discount,
                ];
            }

            $emailData = [
                'document_number' => $quote->document_number,
                'reference' => $quote->reference ?? "",
                'date' => $quote->date,
                'expiring_date' => $quote->expiring_date,
                'sales_rep' => $quote->sales_rep,
                // 'currency' => $quote->currency,
                'items' => $itemsData,
            ];

            Mail::to($customerEmail)->send(new SendSalesOrderMail($emailData, $name));

            return respond(true, 'Quote email sent successfully', $emailData, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchSoftdelete()
    {
        $deleted = SalesOrders::where('company_id', auth()->user()->company_id)->onlyTrashed()
            ->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Sales orders archive successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = SalesOrders::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archived sales order restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Sales order is not yet archived!', null, 400);
        } else {
            return respond(false, 'Sales order not found in archive!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = SalesOrders::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived sales orders found to restore!', null, 404);
        }
        SalesOrders::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archived sales orders restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = SalesOrders::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archived sales orders permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Sales order is not yet archived!', null, 400);
        } else {
            return respond(false, 'Sales order not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = SalesOrders::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archived sales orders found to permanently delete!', null, 404);
        }
        SalesOrders::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archived sales orders permanently deleted successfully!', null, 200);
    }

}
