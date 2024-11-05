<?php

namespace App\Http\Controllers;

use App\Models\Quotes;
use App\Models\SupportingDocument;
use App\Customers;
use Illuminate\Support\Facades\DB;
use App\Models\SalesRep;
use App\Models\Item;
use App\Models\Account;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Currency;
use App\Models\GeneralInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendQuoteDetailsMail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class QuotesController extends Controller
{

    public function index()
    {

        $quotes = getAllQuotes()->get();
        return respond(true, 'Quotes fetched successfully', $quotes, 200);

    }
    public function getPendingQuotes()
    {
        $salesorder = getAllQuotes()->where('status', 0)->get();

        return respond(true, 'All pending quotes fetched successfully', $salesorder, 200);
    }
    public function getCompleteQuotes()
    {
        $salesorder = getAllQuotes()->where('status', 1)->get();

        return respond(true, 'All complete quotes fetched successfully', $salesorder, 200);
    }
    public function totalquotesCount()
    {
        $quotesCount = getAllQuotes()->count();
        $quotevalue = GeneralInvoice::where('company_id', auth()->user()->company_id)->where('type', 'Quote')->sum('amount');

        $response = [
            'total_number_of_quotes' => $quotesCount,
            'total_quotes_amount' => $quotevalue,
        ];

        return respond(true, 'Data fetched successfully', $response, 200);
    }



    public function delete(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'id' => 'required|exists:quotes,id',
            ]);
            if ($validator->fails()) {
                return respond('message', $validator->errors(), null, 400);
            }
            $quotes = Quotes::find($request->id);

            if (auth()->user()->company_id !== $quotes->company_id) {
                return respond('error', 'You do not have permission to delete this quote.', null, 403);
            }
            $quotes->delete();
            return respond(true, 'Quote archived successfully', $quotes, 200);
        } catch (\Exception $e) {
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function creating(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'option_type' => 'required|in:product,service',
                'customer_id' => 'required|exists:customers,id',
                'document_number' => 'required|unique:quotes',
                'reference' => 'nullable',
                'date' => 'required|date',
                'transaction_date' => 'required|date',
                'expiring_date' => 'required|date',
                'sales_rep' => 'nullable|exists:sales_reps,id',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                // 'sales_rep' => 'nullable',
                'currency' => 'required|exists:currencies,id',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'required|array',
                'item.*' => 'required|exists:items,id',
                'tax_id' => 'nullable|array',
                'tax_id.*' => 'nullable|exists:taxes,id',
                'amount' => 'nullable|array',
                'amount.*' => 'nullable|numeric',
                'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
                'total_amount' => 'required|numeric',
                // 'amount' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                // 'amount.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
                // 'account' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                // 'account.*' => $request->option_type === 'service' ? 'required|exists:accounts,id' : 'nullable|exists:accounts,id',
                // 'debit_gl' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                // 'debit_gl.*' => $request->option_type === 'service' ? 'required|exists:accounts,id' : 'nullable|exists:accounts,id',
                // 'item' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                // 'item.*' => $request->option_type === 'product' ? 'required|exists:items,id' : 'nullable|exists:items,id',
                // 'service_amount' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                // 'service_amount.*' => $request->option_type === 'service' ? 'required|numeric' : 'nullable|numeric',
                // 'service_description' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                // 'service_description.*' => $request->option_type === 'service' ? 'required|string' : 'nullable|string',
                // 'total_service_amount' => $request->option_type === 'service' ? 'required|array' : 'nullable|array',
                // 'total_service_amount.*' => $request->option_type === 'service' ? 'required|numeric' : 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $data = $request->all();
            // $uuid = generate_uuid();
            $month = now()->format('m');
            $uuid = "QT" . '-' . $month . '-' . rand(1000000, 99999999);

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
                $document = SupportingDocument::create([
                    'uuid' => $uuid,
                    'type' => "Quote",
                    'file' => $data['supporting_document'],
                    // dd($data['supporting_document'])
                ]);
            } else {
                $data['supporting_document'] = null;
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
                        'debit_gl' => $data['debit_gl'][$index],
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
                        'type' => "Quote",
                        'amount' => $data['amount'][$index],
                        'quantity' => $data['quantity'][$index],
                        'tax_id' => $data['tax_id'][$index] ?? "",
                        'discount' => $data['discount'][$index] ?? "",
                        'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                    ]);

                }
            }
            $data['uuid'] = $uuid;
            $data['status'] = 0;
            $data['amount'] = $request->total_amount;
            $data['transaction_date'] = $request->transaction_date;
            $data['option_type'] = $request->option_type;
            $quote = Quotes::create($data);
            DB::commit();
            return respond(true, 'Quote created successfully', $quote, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
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
                'option_type' => 'required|in:product,service',
                'customer_id' => 'required|exists:customers,id',
                'document_number' => 'required|unique:quotes',
                'reference' => 'nullable',
                'date' => 'required|date',
                'transaction_date' => 'required|date',
                'expiring_date' => 'required|date',
                'sales_rep' => 'nullable|exists:sales_reps,id',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                // 'sales_rep' => 'nullable',
                'currency' => 'required|exists:currencies,id',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'required|array',
                'item.*' => 'required|exists:items,id',
                'tax_id' => 'nullable|array',
                'tax_id.*' => 'nullable|exists:taxes,id',
                // 'quantity' => 'required|array',
                // 'quantity.*' => 'required|numeric',
                'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
                'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
                'service_amount' => $request->option_type === 'service' ? 'nullable|array' : 'nullable|array',
                'service_amount.*' => $request->option_type === 'service' ? 'nullable|numeric' : 'nullable|numeric',
                'total_amount' => 'required|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
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


            $data = $request->all();
            // $uuid = generate_uuid();
            $month = now()->format('m');
            $uuid = "QT" . '-' . $month . '-' . rand(1000000, 99999999);

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
                $document = SupportingDocument::create([
                    'uuid' => $uuid,
                    'type' => "Quote",
                    'file' => $data['supporting_document'],
                    // dd($data['supporting_document'])
                ]);
            } else {
                $data['supporting_document'] = null;
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
                    'type' => "Quote",
                    'amount' => $data['amount'][$index],
                    'service_amount' => $data['service_amount'][$index] ?? '0',
                    'description' => $data['description'][$index] ?? "",
                    'quantity' => $data['quantity'][$index] ?? "",
                    'tax_id' => $data['tax_id'][$index] ?? "",
                    'discount' => $data['discount'][$index] ?? "",
                    'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                ]);

            }
            $data['uuid'] = $uuid;
            $data['status'] = 0;
            $data['amount'] = $request->total_amount;
            $data['transaction_date'] = $request->transaction_date;
            $quote = Quotes::create($data);
            DB::commit();
            return respond(true, 'Quote created successfully', $quote, 200);
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
                'id' => 'required|exists:quotes,id',
                'customer_id' => 'nullable|exists:customers,id',
                // 'document_number' => 'required|unique:quotes,document_number',
                'document_number' => [
                    'nullable',
                    'string',
                    Rule::unique('quotes', 'document_number')->ignore($request->id),
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
                'description' => 'nullable|array',
                'description.*' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $quote = Quotes::find($id);
            $olduuid = $quote->uuid;

            $data = $request->all();

            // Check if customer_id has changed, and if so, generate a new UUID
            if ($request->customer_id && $quote->customer_id !== $request->customer_id) {
                $month = now()->format('m');
                $newUuid = "QT" . '-' . $month . '-' . rand(1000000, 99999999);

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
                    'amount' => $data['amount'][$index] ?? '0',
                    'service_amount' => $data['service_amount'][$index] ?? '0',
                    'description' => $data['description'][$index] ?? "",
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
            $quote->amount = $request->total_amount;
            $quote->total_price = $request->total_price ?? $quote->total_price;
            $quote->status = 0;
            $quote->save();

            DB::commit();
            return respond(true, 'Quote updated successfully', $quote, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function update2(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:quotes,id',
                'customer_id' => 'nullable|exists:customers,id',
                // 'document_number' => 'required|unique:quotes,document_number',
                'document_number' => [
                    'nullable',
                    'string',
                    Rule::unique('quotes', 'document_number')->ignore($request->id),
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
                'account' => 'nullable|array',
                'account.*' => 'nullable|exists:accounts,id',
                'quantity' => 'nullable|array',
                'quantity.*' => 'nullable|numeric',
                'total_amount' => 'nullable|numeric',
                'amount' => 'nullable|array',
                'amount.*' => 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
                'service_amount' => 'nullable|array',
                'service_amount.*' => 'nullable|numeric',
                'total_service_amount' => 'nullable|array',
                'total_service_amount.*' => 'nullable|numeric',
                'service_description' => 'nullable|array',
                'service_description.*' => 'nullable|string',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $quote = Quotes::find($id);
            $invoiceUuid = GeneralInvoice::where('uuid', $quote->uuid)->pluck('account_id')->toArray();
            $olduuid = $quote->uuid;

            $data = $request->all();

            // Check if customer_id has changed, and if so, generate a new UUID
            if ($request->customer_id && $quote->customer_id !== $request->customer_id) {
                $month = now()->format('m');
                $newUuid = "QT" . '-' . $month . '-' . rand(1000000, 99999999);

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
            // dd('here');

            if ($quote->option_type === 'service') {
                foreach ($invoiceUuid as $index => $accountID) {

                    // Retrieve item name
                    $account = Account::find($accountID);
                    if (!$account) {
                        return respond(false, 'Account not found', null, 400);
                    }

                    GeneralInvoice::create([
                        'account_id' => $accountID,
                        'invoice_number' => $quote->uuid,
                        'uuid' => $quote->uuid,
                        'type' => "Quote",
                        'service_amount' => $data['service_amount'][$index],
                        'total_service_amount' => $data['total_service_amount'][$index] ?? 0,
                        'service_description' => $data['service_description'][$index] ?? "",
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
                        'invoice_number' => $quote->uuid,
                        'uuid' => $quote->uuid,
                        'type' => "Quote",
                        'amount' => $data['amount'][$index],
                        'quantity' => $data['quantity'][$index],
                        'tax_id' => $data['tax_id'][$index] ?? "",
                        'discount' => $data['discount'][$index] ?? "",
                        'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                    ]);
                }
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
            $quote->amount = $request->total_amount;
            $quote->total_price = $request->total_price ?? $quote->total_price;
            $quote->status = 0;
            $quote->save();

            DB::commit();
            return respond(true, 'Quote updated successfully', $quote, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function update1(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:quotes,id',
                'customer_id' => 'nullable|exists:customers,id',
                'document_number' => 'nullable',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'nullable|exists:items,id',
                'tax_id' => 'required|exists:taxes,id',
                'reference' => 'nullable',
                'date' => 'nullable|date',
                'amount' => 'nullable|numeric',
                'quantity' => 'nullable|numeric',
                'discount' => 'nullable|numeric',
                'expiring_date' => 'nullable|date',
                'sales_rep' => 'nullable|exists:sales_reps,id',

            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $data = $request->all();

            // Find the existing quote by ID
            $quote = Quotes::find($request->id);

            if ($request->hasFile('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->file('supporting_document'), "supporting_document");
            } else {
                $data['supporting_document'] = $quote->supporting_document;
            }

            // Get sales rep name if sales_rep is provided
            // $salesRepName = null;
            // if ($request->sales_rep) {
            //     $salesRep = SalesRep::find($request->sales_rep);
            //     if ($salesRep) {
            //         $salesRepName = $salesRep->name;
            //     }
            // } else {
            //     $salesRepName = $quote->sales_rep;
            // }

            // Check if customer_id has changed, and if so, generate a new UUID
            if ($request->customer_id && $quote->customer_id !== $request->customer_id) {
                $month = now()->format('m');
                $uuid = "QT" . '-' . $month . '-' . rand(1000000, 99999999);
                $quote->uuid = $uuid;
            }


            // Update the quote with the new values, only if they are provided
            $quote->customer_id = $request->customer_id ?? $quote->customer_id;
            $quote->document_number = $request->document_number ?? $quote->document_number;
            $quote->reference = $request->reference ?? $quote->reference;
            $quote->date = $request->date ?? $quote->date;
            $quote->sales_rep = $request->sales_rep ?? $quote->sales_rep;
            $quote->expiring_date = $request->expiring_date ?? $quote->expiring_date;
            // $quote->sales_rep = $salesRepName;
            // $quote->currency = $currencyName;
            $quote->supporting_document = $data['supporting_document'];
            $quote->quantity = $request->quantity ?? $quote->quantity;
            $quote->amount = $request->amount ?? $quote->amount;
            $quote->discount = $request->discount ?? $quote->discount;
            $quote->status = 0;

            // Update item if provided
            if ($request->item) {
                $item = Item::find($request->item);
                if (!$item) {
                    return respond(false, 'Item not found', null, 400);
                }
                $quote->item = $item->name;
            }

            $quote->save();

            return respond(true, 'Quote updated successfully', $quote, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function sendQuoteEmail(Request $request)
    {
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'quote_id' => 'required|exists:quotes,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            // Fetch the quote and related customer
            $quoteId = $request->quote_id;
            $quote = Quotes::find($quoteId);
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
            $quotes = Quotes::where('uuid', $quote->uuid)->get();
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

            // Send the email
            Mail::to($customerEmail)->send(new SendQuoteDetailsMail($emailData, $name));

            return respond(true, 'Quote email sent successfully', $emailData, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function fetchSoftdelete()
    {
        $deleted = Quotes::where('company_id', auth()->user()->company_id)
            ->onlyTrashed()->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Data fetched successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        $department = Quotes::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->restore();
            return respond(true, 'Archieved quote restored successfully!', $department, 200);
        } elseif ($department) {
            return respond(false, 'Quote is not yet archived!', null, 400);
        } else {
            return respond(false, 'Quote not found!', null, 404);
        }
    }

    public function restoreSoftdelete()
    {
        $deletedDepartments = Quotes::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved quotes found to restore!', null, 404);
        }
        Quotes::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();

        return respond(true, 'All archieved quotes restored successfully!', $deletedDepartments, 200);
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        $department = Quotes::withTrashed()->find($request->id);

        if ($department && $department->trashed()) {
            $department->forceDelete();
            return respond(true, 'Archieved quotes permanently deleted successfully!', null, 200);
        } elseif ($department) {
            return respond(false, 'Archieved quotes is not soft-deleted!', null, 400);
        } else {
            return respond(false, 'Archieved quotes not found!', null, 404);
        }
    }

    public function deleteSoftdelete()
    {
        $deletedDepartments = Quotes::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        if ($deletedDepartments->isEmpty()) {
            return respond(false, 'No archieved quotes found to permanently delete!', null, 404);
        }
        Quotes::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
        return respond(true, 'All archieved quotes permanently deleted successfully!', null, 200);
    }

}
