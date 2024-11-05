<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Customers;
use App\Models\Beneficiary;
use App\Models\Booking;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;
use App\Models\SaleInvoice;
use App\Models\Item;
use App\Models\Stock;
use App\Models\PurchaseOrder;
use App\Models\GeneralInvoice;
use App\Models\PurchaseInvoice;
use App\Models\MyTransactions;
use App\Models\CustomerPersonalLedger;
use App\Models\Journal;
use App\Models\SupplierPersonalLedger;
use App\Models\Quotes;
use App\Models\SalesOrders;
use App\Models\Company;
use App\Models\Setting;
use App\Models\StockInventory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesInvoiceController extends Controller
{
    public function saveOld(Request $request)
    {
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
        $validator = Validator::make($data, [
            'description' => 'required',
            'invoice_number' => 'required|unique:sale_invoices,invoice_number',
            // 'teller_number' => 'required',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $amount = $input['amount'];
        $arrayAmount = $input['item_amount'];
        $sum = array_sum($arrayAmount);
        if ($sum != $amount) {
            return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        }
        // dd($input);
        $item = $input['item_id'];
        $input['uuid'] = $request->invoice_number;
        $count = array_count_values($item);
        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once , see row $sum !", [$sum, $amount], 400);
            }
            // dd($array);
            $getDetails = Item::find($array);
            $glcode = $getDetails->gl_code;
            if (!$glcode) {
                return respond(false, "Account to report to has not been specified for {{ $getDetails->name }}  , see row $sum !", [$sum, $amount], 400);
            }
        }
        //dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        try {
            foreach ($item as $key => $item) {
                // dd($input['amount'][$key]);
                GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'quantity' => $input['quantity'][$key],
                ]);
                $getDetails = Item::find($item);
                $glcode = $getDetails->gl_code;
                if ($glcode) {
                    //$glcode = $input['item_id'][$key];
                    $amount = $input['item_amount'][$key];
                    $uuid = $input['uuid'];
                    postDoubleEntries($uuid, $glcode, 0, $amount, $detail); // credit the  accounts
                }
            }
            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum');
            $input['amount'] = $request->amount;
            $glcode = $request->debit_gl_code;
            $amount = $input['amount'];
            // debit receiveable
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail);
            $input['balance'] = $input['amount'];
            $input['debit'] = $input['amount'];
            $input['transaction_date'] = now();
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            // $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            // dd($input);
            $receipt = SaleInvoice::create($input);
            // save to customer ledger
            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance);
            //post as receivable
            insertTransaction($amount, $amount, 0, now(), $detail, $uuid, 1, $uuid, now(), "Sales Invoice");

            DB::commit();
            return respond(true, 'Transaction successful!!', $receipt, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function save(Request $request)
    {
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
        $validator = Validator::make($data, [
            'option_type' => 'required|in:product,service',
            'description' => 'required',
            'invoice_number' => 'required|unique:sale_invoices,invoice_number|unique:purchase_invoices,invoice_number',
            'mode' => 'nullable|in:1,2,3',
            'order_id' => 'required_if:mode,1,2,3',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            // 'quantity' => 'required|array',
            // 'quantity.*' => 'required|numeric',
            'currency' => 'required|exists:currencies,id',
            'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
            'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
            'service_amount' => 'nullable|array',
            'service_amount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
            'reference' => 'nullable|array',
            'reference.*' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        // $amount = $input['amount'];
        // $arrayAmount = $input['item_amount'];
        // $sum = array_sum($arrayAmount);
        // if ($sum != $amount) {
        //     return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        // }
        // dd($input);
        $item = $input['item_id'];
        $input['uuid'] = $request->invoice_number;
        $count = array_count_values($item);
        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once , see row $sum !", [$sum], 400);
            }
            // dd($array);
            $getDetails = Item::find($array);
            $debitGl = $getDetails->account_receivable; //receivable account
            $creditGl = $getDetails->sales_gl; //sales income account
            if (!$debitGl) {
                return respond(false, "Account receivable has not been specified for $getDetails->name  , see row $key !", [$key], 400);
            }
            if (!$creditGl) {
                return respond(false, "Sales income account  has not been specified for $getDetails->name  , see row $key !", [$key], 400);
            }
        }
        //dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        // Handle case 3
                        $booking = Booking::where('id', $request->id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                    case '2':
                        // Handle case 3
                        $sale = SalesOrders::where('id', $request->order_id)->first();
                        $sale->update(['status' => 1]);

                        break;
                    case '3':
                        // Handle case 3
                        $quote = Quotes::where('id', $request->order_id)->first();
                        $quote->update(['status' => 1]);
                        break;
                }
            }
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + $request->amount]);
            foreach ($item as $key => $item) {
                // dd($input['amount'][$key]);
                $invoice = GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales Invoice",
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'balance' => $input['item_amount'][$key],
                    'service_amount' => $input['service_amount'][$key] ?? '0',
                    'quantity' => $input['quantity'][$key] ?? "",
                    'reference' => $input['reference'][$key] ?? "",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount' => $input['discount'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                ]);
                $getDetails = Item::find($item);
                $debitGl = $getDetails->account_receivable; //receivable account
                $creditGl = $getDetails->sales_gl; //sales income account
                if ($debitGl && $creditGl) {
                    //$glcode = $input['item_id'][$key];
                    $amount = $input['item_amount'][$key];
                    $uuid = $input['uuid'];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail, $request->transaction_date); // credit sales  income
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail, $request->transaction_date); // debit receivable  account
                    //post as receivable
                    insertReceivableCode($amount, $amount, 0, $request->transaction_date, $detail, $uuid, 1, $uuid, now(), "Sales Invoice", $getCustomer->id, $debitGl, $invoice->id);
                }
            }
            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum', 'discount', 'tax_id', 'discount_percentage', 'reference', 'service_amount');
            $input['amount'] = $request->amount;
            $amount = $input['amount'];
            $input['balance'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $input['debit'] = $input['amount'];

            // dd($input);
            $receipt = SaleInvoice::create($input);
            // save to customer ledger
            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance);


            DB::commit();
            return respond(true, 'Transaction successful!!', $receipt, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function saveNew(Request $request)
    {

        $companyId = auth()->user()->company_id;
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);

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
        $validator = Validator::make($data, [
            'option_type' => 'required|in:product,service',
            'description' => 'required',
            'invoice_number' => 'required|unique:sale_invoices,invoice_number|unique:purchase_invoices,invoice_number',
            'mode' => 'nullable|in:1,2,3',
            'order_id' => 'required_if:mode,1,2,3',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            // 'quantity' => 'required|array',
            // 'quantity.*' => 'required|numeric',
            'currency' => 'required|exists:currencies,id',
            'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
            'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
            'service_amount' => 'nullable|array',
            'service_amount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
            'reference' => 'nullable|array',
            'reference.*' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        foreach ($request->item_id as $itemId) {
            $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
            if (!$item) {
                return respond('error', 'One or more items do not belong to your company.', null, 403);
            }
        }
        // $amount = $input['amount'];
        // $arrayAmount = $input['item_amount'];
        // $sum = array_sum($arrayAmount);
        // if ($sum != $amount) {
        //     return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        // }
        // dd($input);
        $item = $input['item_id'];
        $input['uuid'] = $request->invoice_number;
        $count = array_count_values($item);
        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once , see row $sum !", [$sum], 400);
            }
            // dd($array);
            $getDetails = Item::find($array);
            $debitGl = $getDetails->account_receivable; //receivable account
            $creditGl = $getDetails->sales_gl; //sales income account
            $cOfGl = $getDetails->cost_of_good_gl; //cost of goods sold account
            $inventoryGl = $getDetails->purchase_gl; //inventory account
            $checkRow = $key + 1;
            // if (isset($input['discount'][$key])) {
            //     $discountGl = $getDetails->discount_gl; //discount account
            //     if (!$discountGl) {
            //         return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
            //     }
            // }
            // if (isset($input['tax_id'][$key])) {
            //     $tax = Tax::where('id', $input['tax_id'][$key])->first();
            //     $TaxGl = $tax->report_gl; //tax account
            //     if (!$TaxGl) {
            //         return respond(false, "Tax account  has not been specified for $tax->description  , see row $checkRow !", [$checkRow], 400);
            //     }
            // }
            if (!$debitGl) {
                return respond(false, "Account receivable has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
            }
            if (!$creditGl) {
                return respond(false, "Sales income account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
            }
            // if (!$cOfGl) {
            //     return respond(false, "Cost of goods account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
            // }
            // if (!$inventoryGl) {
            //     return respond(false, "Inventory account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
            // }
        }
        //dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        // Handle case 3
                        $booking = Booking::where('id', $request->id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                    case '2':
                        // Handle case 3
                        $sale = SalesOrders::where('id', $request->order_id)->first();
                        $sale->update(['status' => 1]);

                        break;
                    case '3':
                        // Handle case 3
                        $quote = Quotes::where('id', $request->order_id)->first();
                        $quote->update(['status' => 1]);
                        break;
                }
            }
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + $request->amount]);
            // Deduct quantities and log stock changes
            if ($request->option_type === 'product') {
                foreach ($input['item_id'] as $key => $productId) {
                    $product = Item::where('id', $productId)->first();
                    $stock = Stock::where('item_id', $productId)->first();
                    // dd($stock, $stock->quantity, $input['quantity'][$key]);
                    if (!$stock || $stock->quantity < $input['quantity'][$key]) {
                        return respond('error', 'Insufficient stock for item: ' . $product->name, null, 403);
                    }
                    // dd("here");
                    // Log the old and new quantities
                    $oldStockQuantity = $stock->quantity;
                    $newStockQuantity = $oldStockQuantity - $input['quantity'][$key];
                    // Update stock quantity
                    $stock->quantity = $newStockQuantity;
                    $stock->save();

                    // Check item quantity
                    $finditem = Item::where('id', $productId)->first();
                    // dd($finditem, $product);

                    // Log the old and new quantities
                    $oldItemQuantity = $finditem->quantity;
                    $newItemQuantity = $oldItemQuantity - $input['quantity'][$key];

                    // Update item quantity
                    $finditem->quantity = $newItemQuantity;
                    $finditem->save();

                    // Log the item inventory change
                    stockInventory($productId, $oldItemQuantity, $newItemQuantity, $input['quantity'][$key], $stock->id, $input['item_amount'][$key], 'Item updated for sales invoice: ' . $finditem->name);
                }
            }


            foreach ($item as $key => $item) {
                // dd($input['amount'][$key]);
                $invoice = GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales Invoice",
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'balance' => $input['item_amount'][$key],
                    'service_amount' => $input['service_amount'][$key] ?? '0',
                    'quantity' => $input['quantity'][$key] ?? "",
                    'reference' => $input['reference'][$key] ?? "",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount' => $input['discount'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                ]);
                $getDetails = Item::find($item);
                $debitGl = $getDetails->account_receivable; //receivable account
                $creditGl = $getDetails->sales_gl; //sales income account
                if ($debitGl && $creditGl) {
                    //$glcode = $input['item_id'][$key];
                    $amount = $input['item_amount'][$key];
                    $uuid = $input['uuid'];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail, $request->transaction_date); // credit sales  income
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail, $request->transaction_date); // debit receivable  account
                    //post as receivable
                    insertReceivableCode($amount, $amount, 0, $request->transaction_date, $detail, $uuid, 1, $uuid, now(), "Sales Invoice", $getCustomer->id, $debitGl, $invoice->id);
                }
                if (isset($input['discount'][$key])) {
                    $discountGl = $getDetails->discount_gl; //discount account
                    // credit the sales income
                    if ($discountGl) {
                        postDoubleEntries($uuid, $creditGl, 0, $input['discount'][$key], "discount for $detail", $request->transaction_date);
                        // debit the discount account
                        postDoubleEntries($uuid, $discountGl, $input['discount'][$key], 0, "discount for $detail", $request->transaction_date);
                        // if (!$discountGl) {
                        //     return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $key !", [$key], 400);
                    }
                }
                if (isset($input['tax_id'][$key])) {
                    $tax = Tax::where('id', $input['tax_id'][$key])->first();
                    $TaxGl = $tax->report_gl; //tax account
                    $costPrice = $getDetails->cost_price;
                    $gAmount = $input['quantity'][$key] * $costPrice;
                    if (isset($input['discount'][$key])) {
                        $gAmount -= $input['discount'][$key];
                    }
                    $taxAmount = $gAmount * $tax->rate / 100;
                    // $gAmount +=  $taxAmount ;
                    // credit the sales income
                    if( $TaxGl){
                    postDoubleEntries($uuid, $creditGl, 0, $taxAmount, "tax for $detail", $request->transaction_date);
                    // debit the tax account account
                    postDoubleEntries($uuid, $TaxGl, $taxAmount, 0, "tax for $detail", $request->transaction_date);
                    }
                }

            }
            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum', 'discount', 'tax_id', 'discount_percentage', 'reference', 'service_amount');
            $input['amount'] = $request->amount;
            $amount = $input['amount'];
            $input['balance'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $input['debit'] = $input['amount'];

            // dd($input);
            $receipt = SaleInvoice::create($input);
            // save to customer ledger
            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance + $input['amount']);


            DB::commit();
            return respond(true, 'Transaction successful!!', $receipt, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateSalesInvoice(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $input = $request->except('_token');
        $data = $request->all();
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
        $validator = Validator::make($data, [
            'option_type' => 'required|in:product,service',
            'id' => 'required|exists:sale_invoices,id',
            'description' => 'required',
            // 'invoice_number' => 'required|unique:sale_invoices,invoice_number|unique:purchase_invoices,invoice_number',
            'invoice_number' => [
                'nullable',
                'string',
                Rule::unique('sale_invoices', 'invoice_number')->ignore($request->id),
                Rule::unique('purchase_invoices', 'invoice_number')->ignore($request->id),
            ],
            'mode' => 'nullable|in:1,2,3',
            'order_id' => 'required_if:mode,1,2,3',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            'currency' => 'required|exists:currencies,id',
            'quantity' => $request->option_type === 'product' ? 'required|array' : 'nullable|array',
            'quantity.*' => $request->option_type === 'product' ? 'required|numeric' : 'nullable|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
            'service_amount' => 'nullable|array',
            'service_amount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
            'reference' => 'nullable|array',
            'reference.*' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        DB::beginTransaction();
        try {
            foreach ($request->item_id as $itemId) {
                $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
                if (!$item) {
                    return respond('error', 'One or more items do not belong to your company.', null, 403);
                }
            }
            $item = $input['item_id'];
            $input['uuid'] = $request->invoice_number;
            $count = array_count_values($item);
            foreach ($item as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond(false, "An Item exists more than once , see row $sum !", [$sum], 400);
                }
                // dd($array);
                $getDetails = Item::find($array);
                $debitGl = $getDetails->account_receivable; //receivable account
                $creditGl = $getDetails->sales_gl; //sales income account
                $cOfGl = $getDetails->cost_of_good_gl; //cost of goods sold account
                $inventoryGl = $getDetails->purchase_gl; //inventory account
                $checkRow = $key + 1;
                // if (isset($input['discount'][$key])) {
                //     $discountGl = $getDetails->discount_gl; //discount account
                //     if (!$discountGl) {
                //         return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                //     }
                // }
                // if (isset($input['tax_id'][$key])) {
                //     $tax = Tax::where('id', $input['tax_id'][$key])->first();
                //     $TaxGl = $tax->report_gl; //tax account
                //     if (!$TaxGl) {
                //         return respond(false, "Tax account  has not been specified for $tax->description  , see row $checkRow !", [$checkRow], 400);
                //     }
                // }
                if (!$debitGl) {
                    return respond(false, "Account receivable has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                }
                if (!$creditGl) {
                    return respond(false, "Sales income account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                }
                if (!$cOfGl) {
                    return respond(false, "Cost of goods account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                }
                if (!$inventoryGl) {
                    return respond(false, "Inventory account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                }
            }
            $saleInvoice = SaleInvoice::find($request->id);
            // dd($saleInvoice);
            if (!$saleInvoice) {
                return respond('error', 'Sale invoice not found', null, 404);
            }
            // Ensure no payments have been made on the invoice
            if ($saleInvoice->balance != $saleInvoice->amount) {
                return respond(false, 'Cannot be updated because there is already a payment on this invoice', null, 401);
            }
            // lets get the items formerly associated with this invoice
            $fItems = GeneralInvoice::where('uuid', $saleInvoice->uuid)->get();
            // now lets loop through to remove and restore the quantity back to the stock and delete posting
            foreach ($fItems as $fSingle) {
                //get each item quantity
                $fSItem = $fSingle->item_id; //the item id
                $fSQuantity = $fSingle->quantity; //the item quantity
                $fAmount = $fSingle->amount;
                // so lets restore the back to stock
                $stock = Stock::where('item_id', $fSItem)->first();
                // dd("here");
                // Log the old and new quantities
                $oldStockQuantity = $stock->quantity;
                $nQuantity = $oldStockQuantity + $fSQuantity;
                // Update stock quantity
                $stock->quantity = $stock->quantity + $fSQuantity;
                $stock->save();

                // Check item quantity
                $finditem = Item::where('id', $fSItem)->first();
                $finditem->quantity = $finditem->quantity + $fSQuantity;
                $finditem->save();
                // Log the item inventory change
                stockInventory($fSItem, $oldStockQuantity, $nQuantity, $fSQuantity, $stock->id, $fAmount, "$finditem->name updated for sales invoice ");
                $fSingle->forceDelete();
            }
            //get the customer id
            $customerID = $saleInvoice->customer_id;
            //total invoice amount
            $tIAmount = $saleInvoice->amount;
            // now deduct the amount in customer
            $getCustomer = Customers::find($customerID);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance - $tIAmount]);
            // now reverse the single amount in ledger
            saveCustomerLedger($customerID, $saleInvoice->uuid, $tIAmount, 0, "Update of sale invoice with invocie number $saleInvoice->uuid", $balance - $tIAmount);
            // delete all related journal postings and transactions
            $aJT = Journal::where('uuid', $saleInvoice->uuid)->forceDelete();
            $aMT = MyTransactions::where('uuid', $saleInvoice->uuid)->forceDelete();
            //now perform update function

            // $amount = $input['amount'];
            // $arrayAmount = $input['item_amount'];
            // $sum = array_sum($arrayAmount);
            // if ($sum != $amount) {
            //     return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
            // }
            // dd($input);

            //dd("stop");
            $uuid = $input['uuid'];
            $detail = $request->description;

            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        // Handle case 3
                        $booking = Booking::where('id', $request->id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                    case '2':
                        // Handle case 3
                        $sale = SalesOrders::where('id', $request->order_id)->first();
                        $sale->update(['status' => 1]);

                        break;
                    case '3':
                        // Handle case 3
                        $quote = Quotes::where('id', $request->order_id)->first();
                        $quote->update(['status' => 1]);
                        break;
                }
            }
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + $request->amount]);
            // Deduct quantities and log stock changes
            if ($request->option_type === 'product') {
                foreach ($input['item_id'] as $key => $productId) {
                    $product = Item::where('id', $productId)->first();
                    $stock = Stock::where('item_id', $productId)->first();
                    // dd($stock, $stock->quantity, $input['quantity'][$key]);
                    if (!$stock || $stock->quantity < $input['quantity'][$key]) {
                        return respond('error', 'Insufficient stock for item: ' . $product->name, null, 403);
                    }
                    // dd("here");
                    // Log the old and new quantities
                    $oldStockQuantity = $stock->quantity;
                    $newStockQuantity = $oldStockQuantity - $input['quantity'][$key];
                    // Update stock quantity
                    $stock->quantity = $newStockQuantity;
                    $stock->save();

                    // Check item quantity
                    $finditem = Item::where('id', $productId)->first();
                    // dd($finditem, $product);

                    // Log the old and new quantities
                    $oldItemQuantity = $finditem->quantity;
                    $newItemQuantity = $oldItemQuantity - $input['quantity'][$key];

                    // Update item quantity
                    $finditem->quantity = $newItemQuantity;
                    $finditem->save();

                    // Log the item inventory change
                    stockInventory($productId, $oldItemQuantity, $newItemQuantity, $input['quantity'][$key], $stock->id, $input['item_amount'][$key], 'Item updated for sales invoice: ' . $finditem->name);
                }
            }


            foreach ($item as $key => $item) {
                // dd($input['amount'][$key]);
                $invoice = GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales Invoice",
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'balance' => $input['item_amount'][$key],
                    'service_amount' => $input['service_amount'][$key] ?? '0',
                    'quantity' => $input['quantity'][$key] ?? "",
                    'reference' => $input['reference'][$key] ?? "",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount' => $input['discount'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                ]);
                $getDetails = Item::find($item);
                $debitGl = $getDetails->account_receivable; //receivable account
                $creditGl = $getDetails->sales_gl; //sales income account
                if ($debitGl && $creditGl) {
                    //$glcode = $input['item_id'][$key];
                    $amount = $input['item_amount'][$key];
                    $uuid = $input['uuid'];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail, $request->transaction_date); // credit sales  income
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail, $request->transaction_date); // debit receivable  account
                    //post as receivable
                    insertReceivableCode($amount, $amount, 0, $request->transaction_date, $detail, $uuid, 1, $uuid, now(), "Sales Invoice", $getCustomer->id, $debitGl, $invoice->id);
                }
                if (isset($input['discount'][$key])) {
                    $discountGl = $getDetails->discount_gl; //discount account
                    if ($discountGl) {
                        // credit the sales income
                        postDoubleEntries($uuid, $creditGl, 0, $input['discount'][$key], "discount for $detail", $request->transaction_date);
                        // debit the discount account
                        postDoubleEntries($uuid, $discountGl, $input['discount'][$key], 0, "discount for $detail", $request->transaction_date);
                        // if (!$discountGl) {
                        //     return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $key !", [$key], 400);
                    }

                }
                if (isset($input['tax_id'][$key])) {
                    $tax = Tax::where('id', $input['tax_id'][$key])->first();
                    $TaxGl = $tax->report_gl; //tax account
                    $costPrice = $getDetails->cost_price;
                    $gAmount = $input['quantity'][$key] * $costPrice;
                    if (isset($input['discount'][$key])) {
                        $gAmount -= $input['discount'][$key];
                    }
                    $taxAmount = $gAmount * $tax->rate / 100;
                    // $gAmount +=  $taxAmount ;
                    // credit the sales income
                    if ($TaxGl) {
                        postDoubleEntries($uuid, $creditGl, 0, $taxAmount, "tax for $detail", $request->transaction_date);
                        // debit the tax account account
                        postDoubleEntries($uuid, $TaxGl, $taxAmount, 0, "tax for $detail", $request->transaction_date);
                    }
                }
            }
            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum', 'discount', 'tax_id', 'discount_percentage', 'reference', 'service_amount');
            $input['amount'] = $request->amount;
            $amount = $input['amount'];
            $input['balance'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $input['debit'] = $input['amount'];

            // dd($input);
            $receipt = $saleInvoice->update($input);
            // save to customer ledger
            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance + $input['amount']);


            DB::commit();
            return respond(true, 'Transaction successful!!', $receipt, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function updateSalesInvoiceOld(Request $request)
    {
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
        $input = $request->except('_token');
        $data = $request->all();
        // $id = $request->id;

        $validator = Validator::make($data, [
            'option_type' => 'required|in:product,service',
            'id' => 'required|exists:sale_invoices,id',
            'description' => 'required',
            'transaction_date' => 'required|date',
            // 'invoice_number' => 'required|unique:sale_invoices,invoice_number,' . $id . '|unique:purchase_invoices,invoice_number',

            'invoice_number' => [
                'nullable',
                'string',
                Rule::unique('sale_invoices', 'invoice_number')->ignore($request->id),
                Rule::unique('purchase_invoices', 'invoice_number')->ignore($request->id),
            ],
            'mode' => 'nullable|in:1,2,3',
            'order_id' => 'required_if:mode,1,2,3',
            'customer_id' => 'nullable|exists:customers,id',
            'amount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'nullable|array',
            'item_id.*' => 'nullable|exists:items,id',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|numeric',
            'item_amount' => 'nullable|array',
            'item_amount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
            'currency' => 'required|exists:currencies,id',
            'reference' => 'nullable|array',
            'reference.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $saleInvoice = SaleInvoice::find($request->id);
        // dd($saleInvoice);
        if (!$saleInvoice) {
            return respond('error', 'Sale invoice not found', null, 404);
        }
        // Ensure no payments have been made on the invoice
        if ($saleInvoice->balance != $saleInvoice->amount) {
            return respond(false, 'Cannot be updated because there is already a payment on this invoice', null, 401);
        }
        foreach ($request->item_id as $itemId) {
            $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
            if (!$item) {
                return respond('error', 'One or more items do not belong to your company.', null, 403);
            }
        }

        $id = $request->id;

        $salesInvoice = SaleInvoice::find($id);
        if (!$salesInvoice) {
            return respond(false, 'Sales invoice not found!', null, 404);
        }

        $item = $input['item_id'];
        $input['uuid'] = $salesInvoice->invoice_number;
        $count = array_count_values($item);

        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once, see row $sum!", [$sum], 400);
            }

            $getDetails = Item::find($array);
            $debitGl = $getDetails->account_receivable;
            $creditGl = $getDetails->sales_gl;

            if (!$debitGl) {
                return respond(false, "Account receivable has not been specified for $getDetails->name, see row $key!", [$key], 400);
            }
            if (!$creditGl) {
                return respond(false, "Sales income account has not been specified for $getDetails->name, see row $key!", [$key], 400);
            }
        }

        $uuid = $input['uuid'];
        $detail = $request->description;

        DB::beginTransaction();
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        $booking = Booking::where('id', $request->id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                    case '2':
                        $sale = SalesOrders::where('id', $request->order_id)->first();
                        $sale->update(['status' => 1]);
                        break;
                    case '3':
                        $quote = Quotes::where('id', $request->order_id)->first();
                        $quote->update(['status' => 1]);
                        break;
                }
            }

            $oldInvoice = GeneralInvoice::where('uuid', $salesInvoice->uuid)->delete(); // Delete existing invoice items

            foreach ($item as $key => $item) {
                GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales Invoice",
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'quantity' => $input['quantity'][$key] ?? "0",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount' => $input['discount'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                    'reference' => $input['reference'][$key] ?? '0',
                ]);

                $getDetails = Item::find($item);
                $debitGl = $getDetails->account_receivable;
                $creditGl = $getDetails->sales_gl;
                if ($debitGl && $creditGl) {
                    $amount = $input['item_amount'][$key];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail);
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail);
                }
            }

            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum', 'discount', 'tax_id', 'discount_percentage', 'reference', 'service_amount');
            $input['amount'] = $request->amount;
            $input['balance'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $input['debit'] = $input['amount'];
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + ($input['amount'] - $salesInvoice->amount)]);

            $salesInvoice->update($input);

            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance);
            insertReceivable($input['amount'], $input['amount'], 0, now(), $detail, $uuid, 1, $uuid, now(), "Sales Invoice", $getCustomer->id);

            DB::commit();
            return respond(true, 'Invoice updated successfully!', $salesInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateSalesInvoice11(Request $request)
    {
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
        $input = $request->except('_token');
        $data = $request->all();
        // $id = $request->id;

        $validator = Validator::make($data, [
            'option_type' => 'required|in:product,service',
            'id' => 'required|exists:sale_invoices,id',
            'description' => 'required',
            'transaction_date' => 'required|date',
            // 'invoice_number' => 'required|unique:sale_invoices,invoice_number,' . $id . '|unique:purchase_invoices,invoice_number',

            'invoice_number' => [
                'nullable',
                'string',
                Rule::unique('sale_invoices', 'invoice_number')->ignore($request->id),
                Rule::unique('purchase_invoices', 'invoice_number')->ignore($request->id),
            ],
            'mode' => 'nullable|in:1,2,3',
            'order_id' => 'required_if:mode,1,2,3',
            'customer_id' => 'nullable|exists:customers,id',
            'amount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'nullable|array',
            'item_id.*' => 'nullable|exists:items,id',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|numeric',
            'item_amount' => 'nullable|array',
            'item_amount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
            'currency' => 'required|exists:currencies,id',
            'reference' => 'nullable|array',
            'reference.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        $saleInvoice = SaleInvoice::find($request->id);
        // dd($saleInvoice);
        if (!$saleInvoice) {
            return respond('error', 'Sale invoice not found', null, 404);
        }
        // Ensure no payments have been made on the invoice
        if ($saleInvoice->balance != $saleInvoice->amount) {
            return respond(false, 'Cannot be updated because there is already a payment on this invoice', null, 401);
        }
        foreach ($request->item_id as $itemId) {
            $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
            if (!$item) {
                return respond('error', 'One or more items do not belong to your company.', null, 403);
            }
        }

        $id = $request->id;

        $salesInvoice = SaleInvoice::find($id);
        if (!$salesInvoice) {
            return respond(false, 'Sales invoice not found!', null, 404);
        }

        $item = $input['item_id'];
        $input['uuid'] = $salesInvoice->invoice_number;
        $count = array_count_values($item);

        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once, see row $sum!", [$sum], 400);
            }

            $getDetails = Item::find($array);
            $debitGl = $getDetails->account_receivable;
            $creditGl = $getDetails->sales_gl;

            if (!$debitGl) {
                return respond(false, "Account receivable has not been specified for $getDetails->name, see row $key!", [$key], 400);
            }
            if (!$creditGl) {
                return respond(false, "Sales income account has not been specified for $getDetails->name, see row $key!", [$key], 400);
            }
        }

        $uuid = $input['uuid'];
        $detail = $request->description;

        DB::beginTransaction();
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        $booking = Booking::where('id', $request->id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                    case '2':
                        $sale = SalesOrders::where('id', $request->order_id)->first();
                        $sale->update(['status' => 1]);
                        break;
                    case '3':
                        $quote = Quotes::where('id', $request->order_id)->first();
                        $quote->update(['status' => 1]);
                        break;
                }
            }

            $oldInvoice = GeneralInvoice::where('uuid', $salesInvoice->uuid)->delete(); // Delete existing invoice items

            foreach ($item as $key => $item) {
                GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Sales Invoice",
                    'status' => 1,
                    'amount' => $input['item_amount'][$key],
                    'quantity' => $input['quantity'][$key] ?? "0",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount' => $input['discount'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                    'reference' => $input['reference'][$key] ?? '0',
                ]);

                $getDetails = Item::find($item);
                $debitGl = $getDetails->account_receivable;
                $creditGl = $getDetails->sales_gl;
                if ($debitGl && $creditGl) {
                    $amount = $input['item_amount'][$key];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail);
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail);
                }
            }

            $input = $request->except('amount', 'item_amount', 'item_id', 'quantity', 'particulars', 'all_sum', 'discount', 'tax_id', 'discount_percentage', 'reference', 'service_amount');
            $input['amount'] = $request->amount;
            $input['balance'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $input['debit'] = $input['amount'];
            $getCustomer = Customers::find($request->customer_id);
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + ($input['amount'] - $salesInvoice->amount)]);

            $salesInvoice->update($input);

            saveCustomerLedger($request->customer_id, $uuid, 0, $input['amount'], $detail, $balance);
            insertReceivable($input['amount'], $input['amount'], 0, now(), $detail, $uuid, 1, $uuid, now(), "Sales Invoice", $getCustomer->id);

            DB::commit();
            return respond(true, 'Invoice updated successfully!', $salesInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function savePurchaseInvoice(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
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
        $validator = Validator::make($data, [
            'description' => 'required',
            'invoice_number' => 'required|unique:purchase_invoices,invoice_number|unique:sale_invoices,invoice_number',
            'mode' => 'nullable|in:1,2,3',
            'transaction_date' => 'required|date',
            // 'order_id' => 'required_if:mode,1,2,3',
            'supplier_id' => 'required|exists:beneficiaries,id',
            'currency' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            // 'debit_gl_code' => 'required|numeric|exists:accounts,id',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }
        $amount = $input['amount'];
        $arrayAmount = $input['item_amount'];
        $sum = array_sum($arrayAmount);
        if ($sum != $amount) {
            return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        }
        // dd($input);
        $item = $input['item_id'];
        $input['uuid'] = $request->invoice_number;
        $count = array_count_values($item);
        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once , see row $sum !", [$sum, $amount], 400);
            }
            // dd($array);
            $getDetails = Item::find($array);
            $glcode = $getDetails->payable_gl;
            $purcahseGl = $getDetails->purchase_gl;
            if (!$glcode) {
                return respond(false, "Payable account has not been specified for $getDetails->name  , see row $sum !", [$sum, $amount], 400);
            }
            if (!$purcahseGl) {
                return respond(false, "Purcahse Account has not been specified for $getDetails->name  , see row $sum !", [$sum, $amount], 400);
            }
        }
        //dd("stop");
        $uuid = $input['uuid'];
        $detail = $request->description;
        DB::beginTransaction();
        // dd($detail);
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                // dd($mode);
                switch ($mode) {
                    case '1':
                        // Handle case 3
                        $booking = PurchaseOrder::where('id', $request->order_id)->first();
                        // dd($booking);
                        $booking->update(['invoice_status' => 1]);
                        break;
                }
            }
            $getCustomer = Beneficiary::find($request->supplier_id);
            // dd("here");
            foreach ($item as $key => $item) {
                // dd($input['amount'][$key]);
                $invoice = GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Purchase Invoice",
                    'status' => 2,
                    'amount' => $input['item_amount'][$key],
                    'balance' => $input['item_amount'][$key],
                    'quantity' => $input['quantity'][$key],
                    'discount' => $input['discount'][$key] ?? "",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                ]);
                $getDetails = Item::find($item);
                $debitGl = $getDetails->payable_gl; //payable account
                $creditGl = $getDetails->purchase_gl; //purchase account
                if ($debitGl && $creditGl) {
                    //$glcode = $input['item_id'][$key];
                    $amount = $input['item_amount'][$key];
                    $uuid = $input['uuid'];
                    postDoubleEntries($uuid, $debitGl, 0, $amount, $detail, $request->transaction_date); // credit payable  income
                    postDoubleEntries($uuid, $creditGl, $amount, 0, $detail, $request->transaction_date); // debit purchase  account
                    //post as payable
                    insertPayabaleCode($amount, $amount, 0, $request->transaction_date, $detail, $uuid, 2, $uuid, now(), "Purchase Invoice", $getCustomer->id, $debitGl, $invoice->id);
                }
            }
            $input = $request->except('item_amount', 'item_id', 'tax_id', 'quantity', 'particulars', 'all_sum', 'discount', 'discount_percentage');
            $input['amount'] = $request->amount;
            $input['balance'] = $request->amount;
            $input['paid'] = 0;
            // $glcode = $request->debit_gl_code;
            $amount = $input['amount'];
            // debit receiveable
            $input['balance'] = $input['amount'];
            // $input['debit'] = $input['amount'];
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;

            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            // insert ledger
            saveSupplierLedger($getCustomer->id, $uuid, $amount, 0, $detail, $balance);
            // dd($input);
            $receipt = PurchaseInvoice::create($input);


            DB::commit();
            return respond(true, 'Transaction successful!!', $receipt, 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updatePurchaseInvoice(Request $request)
    {
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
        $input = $request->except('_token');
        $data = $request->all();
        $id = $request->id;

        $validator = Validator::make($data, [
            'description' => 'required',
            'transaction_date' => 'required|date',
            'invoice_number' => 'required|unique:purchase_invoices,invoice_number,' . $id . '|unique:sale_invoices,invoice_number',
            'mode' => 'nullable|in:1,2,3',
            'supplier_id' => 'required|exists:beneficiaries,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric',
            'item_amount' => 'required|array',
            'item_amount.*' => 'required|numeric',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'discount_percentage' => 'nullable|array',
            'discount_percentage.*' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }

        if (!$id) {
            return respond(false, 'Invoice ID is required for update!', null, 400);
        }

        $purchaseInvoice = PurchaseInvoice::find($id);
        if (!$purchaseInvoice) {
            return respond(false, 'Purchase invoice not found!', null, 404);
        }

        $amount = $input['amount'];
        $arrayAmount = $input['item_amount'];
        $sum = array_sum($arrayAmount);
        if ($sum != $amount) {
            return respond(false, 'The Transaction Did Not Balance!', [$sum, $amount], 400);
        }

        $item = $input['item_id'];
        $input['uuid'] = $purchaseInvoice->invoice_number;
        $count = array_count_values($item);

        foreach ($item as $key => $array) {
            if ($count[$array] > 1) {
                $sum = $key + 1;
                return respond(false, "An Item exists more than once, see row $sum!", [$sum, $amount], 400);
            }

            $getDetails = Item::find($array);
            $glcode = $getDetails->payable_gl;
            if (!$glcode) {
                return respond(false, "Account to report to has not been specified for $getDetails->name, see row $sum!", [$sum, $amount], 400);
            }
        }

        $uuid = $input['uuid'];
        $detail = $request->description;

        DB::beginTransaction();
        try {
            if (isset($request->mode)) {
                $mode = $request->mode;
                switch ($mode) {
                    case '1':
                        $booking = PurchaseOrder::where('id', $request->order_id)->first();
                        $booking->update(['invoice_status' => 1]);
                        break;
                }
            }

            $purchaseInvoice->generalInvoices()->delete(); // Delete existing invoice items

            foreach ($item as $key => $item) {
                GeneralInvoice::create([
                    'item_id' => $item,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Purchase Invoice",
                    'status' => 2,
                    'amount' => $input['item_amount'][$key],
                    'quantity' => $input['quantity'][$key],
                    'discount' => $input['discount'][$key] ?? "",
                    'tax_id' => $input['tax_id'][$key] ?? "",
                    'discount_percentage' => $input['discount_percentage'][$key] ?? '0',
                ]);

                $getDetails = Item::find($item);
                $debitGl = $getDetails->payable_gl;
                $creditGl = $getDetails->purchase_gl;
                if ($debitGl && $creditGl) {
                    $amount = $input['item_amount'][$key];
                    postDoubleEntries($uuid, $creditGl, 0, $amount, $detail);
                    postDoubleEntries($uuid, $debitGl, $amount, 0, $detail);
                }
            }

            $input = $request->except('item_amount', 'item_id', 'tax_id', 'quantity', 'particulars', 'all_sum', 'discount', 'discount_percentage');
            $input['amount'] = $request->amount;
            $input['balance'] = $input['amount'];
            $input['paid'] = 0;
            $input['uuid'] = $uuid;
            $input['transaction_date'] = $request->transaction_date;
            $getSupplier = Beneficiary::find($request->supplier_id);
            $balance = $getSupplier->balance;
            $getSupplier->update(['balance' => $getSupplier->balance + ($input['amount'] - $purchaseInvoice->amount)]);

            $purchaseInvoice->update($input);

            saveSupplierLedger($getSupplier->id, $uuid, $input['amount'], 0, $detail, $balance);
            insertPayabale($input['amount'], $input['amount'], 0, now(), $detail, $uuid, 2, $uuid, now(), "Purchase Invoice", $getSupplier->id);

            DB::commit();
            return respond(true, 'Transaction updated successfully!', $purchaseInvoice, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }



    public function getPendingInvoiceByMode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $mode = $request->mode;

        if ($mode == 1) {
            $invoice = Booking::where('company_id', auth()->user()->company_id)->where('invoice_status', 0)->with('account', 'company', 'created_by')->orderBy('transaction_date', 'DESC')->get();
        } elseif ($mode == 2) {
            $invoice = SalesOrders::where('company_id', auth()->user()->company_id)->where('status', 0)->with('customer', 'salesRep', 'currency')->orderBy('transaction_date', 'DESC')->get();
        } elseif ($mode == 3) {
            $invoice = Quotes::where('company_id', auth()->user()->company_id)->where('status', 0)->with('customer', 'salesRep', 'currency')->orderBy('transaction_date', 'DESC')->get();
        }

        return respond(true, 'Invoice fetched successfully!!', $invoice, 201);
    }
    public function getPendingInvoiceByModeId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required',
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $mode = $request->mode;
        $id = $request->id;
        if ($mode == 1) {
            $invoice = Booking::where('id', $id)->where('invoice_status', 0)->with('account', 'company', 'created_by')->first();
        } elseif ($mode == 2) {
            $invoice = SalesOrders::where('id', $id)->where('status', 0)->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->first();
        } elseif ($mode == 3) {
            $invoice = Quotes::where('id', $id)->where('status', 0)->with('customer', 'company', 'currency', 'supporting_document', 'general_invoice')->first();
        }

        return respond(true, 'Invoice fetched successfully!!', $invoice, 201);
    }

    public function customerLedgers()
    {
        $ledgers = allTransactions()->where('type', 3)->with(['salesinvoice'])->orderBy('transaction_date', 'DESC')->get();

        return respond(true, 'Payments fetched successfully!!', $ledgers, 201);
    }
    public function myLedger()
    {
        $ledgers = allTransactions()->where('type', 4)->with(['purchaseinvoice'])->orderBy('transaction_date', 'DESC')->get();
        // Loop through each transaction and update the transaction date
        //  foreach ($ledgers as $transaction) {
        //     $uuid = Journal::where('uuid', $transaction->uuid)->latest()->first();
        //     if ($uuid) {
        //         // Update transaction date in allTransactions
        //         $transaction->update([
        //             'transaction_date' => $uuid->transaction_date
        //         ]);
        //     }
        // }
        return respond(true, 'Payments fetched successfully!!', $ledgers, 201);
    }

    public function paySalesInvoiceOld(Request $request)
    {
        $data = $request->all();
        $input = $request->all();
        $validator = Validator::make($data, [
            'id' => 'required|exists:sale_invoices,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors()->first(), null, 400);
        }
        try {
            DB::beginTransaction();
            $id = $input['id'];
            $sale = SaleInvoice::where('id', $id)->first();
            if (!$sale) {
                return respond('error', "Invalid id", null, 400);
            }
            // dd($sale);
            if ($input['amount'] > $sale->balance) {
                return respond('error', "Amount greater than balance!", null, 400);
            }
            $voucher = $uuid = $sale->invoice_number;
            $customer = $sale->customer_id;
            $balance = $sale->balance - $input['amount'];
            $sale->update(['balance' => $sale->balance - $input['amount']]);
            $amount = $input['amount'];
            $glcode = $input['debit_gl_code'];
            $code = $input['credit_gl_code'] ?? $sale->debit_gl_code;
            $detail = $sale->description;
            // debit the bank
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail);
            // credit the receivable
            postDoubleEntries($uuid, $code, 0, $amount, $detail);
            // save to customer ledger
            saveCustomerLedger($customer, $voucher, $input['amount'], 0, $detail, $balance);
            //post as receipt //
            insertTransaction($amount, 0, 0, now(), $detail, $uuid, 3, $uuid, now(), "Sales Invoice");
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function paySalesInvoice(Request $request)
    {
        $data = $request->all();
        $input = $request->all();
        $validator = Validator::make($data, [
            'transaction_date' => 'required|date',
            'id' => 'required|exists:sale_invoices,id',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:general_invoices,id',
            'debit_gl_code' => 'required|exists:accounts,id',
            'item_amount' => 'required|array',
            'item_amount. *' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        // dd("here");
        try {
            DB::beginTransaction();
            $id = $input['id'];
            $sale = SaleInvoice::where('id', $id)->first();
            // dd($sale);
            if ($input['amount'] > $sale->balance) {
                return respond('error', "Amount greater than balance!", null, 400);
            }
            $voucher = $uuid = $sale->invoice_number;
            $totalAmount = 0;
            $detail = $sale->description;
            foreach ($input['item_id'] as $index => $itemId) {
                $itemAmount = $input['item_amount'][$index];
                $totalAmount += $itemAmount;
            }
            if ($totalAmount != $input['amount']) {
                return respond('error', "Dubious Act Detected!", null, 400);
            }
            $checker = 0;
            foreach ($input['item_id'] as $index => $itemId) {
                $itemAmount = $input['item_amount'][$index];
                $generalInvoice = GeneralInvoice::where('id', $itemId)->first();
                $getSingle = $generalInvoice->item;
                if ($itemAmount > $generalInvoice->balance) {
                    return respond('error', "Amount greater than balance for item $getSingle->name !", null, 400);
                }
                $generalInvoice->update([
                    'balance' => $generalInvoice->balance - $itemAmount,
                    'paid' => $generalInvoice->paid + $itemAmount,
                ]);

                $code = $generalInvoice->item->account_receivable;
                $getSingle = MyTransactions::where('invoice_id', $generalInvoice->id)->where('type', 1)->first();
                // credit the receivable
                postDoubleEntries($uuid, $code, 0, $itemAmount, $generalInvoice->reference ?? $getSingle->name, $request->transaction_date);
                if ($getSingle) {
                    $checker = 1;
                    //post as receipt //
                    insertReceiptTransaction($itemAmount, 0, 0, $request->transaction_date, $detail, $uuid, 3, $uuid, now(), "Sales Invoice", $generalInvoice->debit_gl_code, $generalInvoice->id);
                    $prevBalance = $getSingle->balance;
                    $newBalance = $prevBalance - $itemAmount;
                    $prevPaid = $getSingle->amount_paid;
                    $newPaid = $prevPaid + $itemAmount;
                    $getSingle->update(['balance' => $newBalance, 'amount_paid' => $newPaid]);
                }

            }


            $amount = $input['amount'];

            $customer = $sale->customer_id;
            $oldBalance = $sale->balance;
            $balance = $sale->balance - $input['amount'];
            $sale->update(['balance' => $sale->balance - $input['amount']]);
            $sale->customer->update(['balance' => $sale->customer->balance - $input['amount']]);


            $glcode = $request->debit_gl_code;
            // debit the bank
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail, $request->transaction_date);
            // save to customer ledger
            saveCustomerLedger($customer, $voucher, $input['amount'], 0, $detail, $balance);
            if ($checker == 0) {
                //post as receipt //
                insertTransaction($itemAmount, 0, 0, $request->transaction_date, $detail, $uuid, 3, $uuid, now(), "Sales Invoice");
            }
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function deleteCustomerReceipt(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => 'required|exists:sale_invoices,id',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }

        try {
            DB::beginTransaction();

            $saleInvoiceId = $data['id'];
            $saleInvoice = SaleInvoice::where('id', $saleInvoiceId)->first();

            // Check if the sale invoice has been fully paid or has an outstanding balance
            if ($saleInvoice->balance < $saleInvoice->total_amount) {
                return respond('error', 'Cannot delete invoice with payments or balance.', null, 400);
            }

            // Reverse customer balance
            $customer = $saleInvoice->customer;
            $customer->update([
                'balance' => $customer->balance + $saleInvoice->total_amount
            ]);

            // Delete related general invoices
            $generalInvoices = GeneralInvoice::where('sale_invoice_id', $saleInvoiceId)->get();
            foreach ($generalInvoices as $generalInvoice) {
                $generalInvoice->delete();
            }

            // Delete the sale invoice
            $saleInvoice->delete();

            DB::commit();
            return respond(true, 'Sales Invoice deleted successfully', null, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function paySalesInvoice2(Request $request)
    {
        $data = $request->all();
        $input = $request->all();
        $validator = Validator::make($data, [
            'transaction_date' => 'required|date',
            'id' => 'required|exists:sale_invoices,id',
            'bank' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors()->first(), null, 400);
        }
        // dd("here");
        try {
            DB::beginTransaction();
            $id = $input['id'];
            $sale = SaleInvoice::where('id', $id)->first();
            if (!$sale) {
                return respond('error', "Invalid id", null, 400);
            }
            // dd($sale);
            if ($input['amount'] > $sale->balance) {
                return respond('error', "Amount greater than balance!", null, 400);
            }
            $voucher = $uuid = $sale->invoice_number;
            $customer = $sale->customer_id;
            $oldBalance = $sale->balance;
            $balance = $sale->balance - $input['amount'];
            $sale->update(['balance' => $sale->balance - $input['amount']]);
            $sale->customer->update(['balance' => $sale->customer->balance - $input['amount']]);
            $amount = $input['amount'];
            $detail = $sale->description;
            $glcode = $request->bank;
            // if ($input['amount'] == $oldBalance) {
            if (isset($sale->orders)) {
                if (count($sale->orders) > 0) {
                    // dd($sale->orders);
                    foreach ($sale->orders as $order) {
                        // dd("here");
                        $code = $order->item->account_receivable;

                        // credit the receivable
                        postDoubleEntries($uuid, $code, 0, $order->amount, $detail, $request->transaction_date);
                    }
                }
            }
            // dd("unavailable");
            // }
            // dd("overflow");
            // debit the bank
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail, $request->transaction_date);
            // save to customer ledger
            saveCustomerLedger($customer, $voucher, $input['amount'], 0, $detail, $balance);
            //post as receipt //
            insertTransaction($amount, 0, 0, $request->transaction_date, $detail, $uuid, 3, $uuid, now(), "Sales Invoice");
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function deliverOrder(Request $request)
    {
        $input = $request->all();
        $data = $request->all();
        $validator = Validator::make($data, [
            'invoice_number' => 'required|exists:general_invoices,invoice_number',
            'quantity_supplied' => 'required|array',
            'quantity_supplied.*' => 'required|numeric',
            'supplied_amount' => 'required|array',
            'supplied_amount.*' => 'required|numeric',
            'supplied_price' => 'required|array',
            'supplied_price.*' => 'required|numeric',
            'order_id' => 'required|array',
            'order_id.*' => 'required|exists:general_invoices,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $id = $invoice = $input['invoice_number'];
            $generalinvoice = PurchaseInvoice::where('invoice_number', $id)->first();
            $order = GeneralInvoice::where('invoice_number', $id)->count();
            $items = $input['order_id'];
            $countOrder = count($items);
            $countPrice = count($input['order_id']);
            $countAmount = count($input['supplied_price']);
            $countQuantity = count($input['supplied_amount']);
            // dd($order,$countOrder,$countPrice,$countAmount,$countQuantity);
            if ($order != $countOrder || $order != $countPrice || $order != $countAmount || $order != $countQuantity) {
                return respond('error', "Invalid Parameters", null, 400);
            }
            // check if order id is for invoice number
            $orderIds = $input['order_id'];
            foreach ($orderIds as $order) {
                $verify = GeneralInvoice::where('id', $order)->where('invoice_number', $id)->first();
                if (!$verify) {
                    return respond('error', "Invalid Order", null, 400);
                }
            }
            if ($generalinvoice->status == 1) {
                return respond('error', "Order already delivered", null, 400);
            }
            $sum = 0;
            foreach ($items as $key => $item) {
                $orderSuccessful = GeneralInvoice::where('id', $item)->first();
                // check if product already exists
                $check = Stock::where('item_id', $orderSuccessful->item_id)->first();
                // dd($item['name']);
                $format = str_replace(',', '', $input['supplied_amount'][$key]);
                if ($check) {
                    $oldQuantity = $check->quantity;
                    $newQuantity = $oldQuantity + $input['quantity_supplied'][$key];
                    $check->update(['quantity' => $newQuantity]);
                } else {
                    $oldQuantity = 0;
                    $newQuantity = $input['quantity_supplied'][$key];
                    $check = new Stock;
                    $check->item_id = $orderSuccessful->item_id;
                    $check->amount = $format;
                    $check->quantity = $input['quantity_supplied'][$key];
                    $check->save();
                }
                $detail = "purchase of" . ' ' . $check->item->name;
                $peramount = $input['supplied_amount'][$key] * $input['supplied_price'][$key];
                $price = $input['supplied_price'][$key];
                $sum += $peramount;
                if ($check) {
                    $check->item->update(['price' => $price, "quantity" => $newQuantity]);
                }
                stockInventory($orderSuccessful->item_id, $oldQuantity, $newQuantity, $input['quantity_supplied'][$key], $check->id, $format, $detail);
                $orderSuccessful->update([
                    'supplied_quantity' => $input['quantity_supplied'][$key],
                    'supplied_price' => str_replace(',', '', $input['supplied_price'][$key]),
                    'supplied_amount' => str_replace(',', '', $input['supplied_price'][$key]) * $input['quantity_supplied'][$key],
                ]);
            }
            $uuid = $generalinvoice->invoice_number;
            //post as payable
            insertTransaction($sum, $sum, 0, now(), $detail, $uuid, 2, $uuid, now(), "Purchase Invoice");
            $generalinvoice->update(['received_by' => auth()->user()->id, 'date_supplied' => now(), 'status' => 1, 'total_amount' => $sum]);
            // dd($sale);
            DB::commit();
            return respond(true, "Transaction successful", $generalinvoice, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function payPurchaseInvoice(Request $request)
    {
        $data = $request->all();
        $input = $request->all();
        $validator = Validator::make($data, [
            'transaction_date' => 'required|date',
            'id' => 'required|exists:purchase_invoices,id',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:general_invoices,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
            'item_amount' => 'required|array',
            'item_amount. *' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }
        try {
            DB::beginTransaction();
            $id = $input['id'];
            $sale = PurchaseInvoice::where('id', $id)->first();
            $detail = $sale->description;
            if (!$sale) {
                return respond('error', "Invalid id", $data, 400);
            }
            // dd($sale);
            if ($input['amount'] > $sale->balance) {
                return respond('error', "Amount greater than balance!", $data, 400);
            }
            $voucher = $uuid = $sale->invoice_number;
            $totalAmount = 0;
            foreach ($input['item_id'] as $index => $itemId) {
                $itemAmount = $input['item_amount'][$index];
                $totalAmount += $itemAmount;
            }
            if ($totalAmount != $input['amount']) {
                return respond('error', "Dubious Act Detected!", null, 400);
            }
            $checker = 0;
            foreach ($input['item_id'] as $index => $itemId) {
                $itemAmount = $input['item_amount'][$index];
                $generalInvoice = GeneralInvoice::where('id', $itemId)->first();
                $getSingle = $generalInvoice->item;
                if ($itemAmount > $generalInvoice->balance) {
                    return respond('error', "Amount greater than balance for item $getSingle->name !", null, 400);
                }
                $generalInvoice->update([
                    'balance' => $generalInvoice->balance - $itemAmount,
                    'paid' => $generalInvoice->paid + $itemAmount,
                ]);
                $code = $generalInvoice->item->payable_gl;
                $getSingle = MyTransactions::where('invoice_id', $generalInvoice->id)->where('type', 1)->first();
                // credit the payable
                postDoubleEntries($uuid, $code, 0, $itemAmount, $generalInvoice->description ?? $getSingle->name, $request->transaction_date);
                if ($getSingle) {
                    $checker = 1;
                    //post as expenses //
                    insertExpenseTransaction($itemAmount, 0, 0, $request->transaction_date, $detail, $uuid, 4, $uuid, now(), "Purchase Invoice", $generalInvoice->credit_gl_code, $generalInvoice->id);
                    $prevBalance = $getSingle->balance;
                    $newBalance = $prevBalance - $itemAmount;
                    $prevPaid = $getSingle->amount_paid;
                    $newPaid = $prevPaid + $itemAmount;
                    $getSingle->update(['balance' => $newBalance, 'amount_paid' => $newPaid]);
                }
            }

            $customer = $sale->supplier_id;
            $balance = $sale->balance - $input['amount'];
            $sale->update(['balance' => $sale->balance - $input['amount']]);
            $sale->update(['paid' => $sale->amount - $sale->balance]);
            $amount = $input['amount'];
            $glcode = $input['debit_gl_code'];

            // debit the bank
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail, $request->transaction_date);
            $getCustomer = Beneficiary::find($customer);
            $balance = $getCustomer->balance + $amount;
            $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            // save to customer ledger
            saveSupplierLedger($customer, $voucher, 0, $input['amount'], $detail, $balance);
            if ($checker == 0) {
                //post as expenses //
                insertTransaction($amount, 0, 0, now(), $detail, $uuid, 4, $uuid, now(), "Purchase Invoice");
            }
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function payPurchaseInvoiceOLd(Request $request)
    {
        $data = $request->all();
        $input = $request->all();
        $validator = Validator::make($data, [
            'transaction_date' => 'required|date',
            'id' => 'required|exists:purchase_invoices,id',
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }
        try {
            DB::beginTransaction();
            $id = $input['id'];
            $sale = PurchaseInvoice::where('id', $id)->first();
            if (!$sale) {
                return respond('error', "Invalid id", $data, 400);
            }
            // dd($sale);
            if ($input['amount'] > $sale->balance) {
                return respond('error', "Amount greater than balance!", $data, 400);
            }
            $voucher = $uuid = $sale->invoice_number;
            $customer = $sale->supplier_id;
            $balance = $sale->balance - $input['amount'];
            $sale->update(['balance' => $sale->balance - $input['amount']]);
            $sale->update(['paid' => $sale->amount - $sale->balance]);
            $amount = $input['amount'];
            $glcode = $input['debit_gl_code'];
            $detail = $sale->description;
            if (isset($sale->orders)) {
                if (count($sale->orders) > 0) {
                    // dd($sale->orders);
                    foreach ($sale->orders as $order) {
                        // dd("here");
                        $code = $order->item->payable_gl;

                        // credit the payable
                        postDoubleEntries($uuid, $code, 0, $order->amount, $detail, $request->transaction_date);
                    }
                }
            }
            // debit the bank
            postDoubleEntries($uuid, $glcode, $amount, 0, $detail, $request->transaction_date);
            $getCustomer = Beneficiary::find($customer);
            $balance = $getCustomer->balance + $amount;
            $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            // save to customer ledger
            saveSupplierLedger($customer, $voucher, 0, $input['amount'], $detail, $balance);
            //post as expenses //
            insertTransaction($amount, 0, 0, now(), $detail, $uuid, 4, $uuid, now(), "Purchase Invoice");
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function payOnClick(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => 'required|array',
            'id.*' => 'required|exists:purchase_invoices,id',
            'amount' => 'required|array',
            'amount.*' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'debit_gl_code' => 'required|numeric|exists:accounts,id',
            'transaction_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }

        try {
            DB::beginTransaction();

            foreach ($data['id'] as $index => $invoiceId) {
                $amount = $data['amount'][$index];
                $sale = PurchaseInvoice::find($invoiceId);

                if (!$sale) {
                    DB::rollback();
                    return respond('error', "Invalid invoice ID: $invoiceId", $data, 400);
                }

                if ($amount > $sale->balance) {
                    DB::rollback();
                    return respond('error', "Amount greater than balance for invoice ID: $invoiceId!", $data, 400);
                }

                $voucher = $uuid = $sale->invoice_number;
                $customer = $sale->supplier_id;
                $balance = $sale->balance - $amount;
                $sale->update([
                    'balance' => $balance,
                    'paid' => $sale->amount - $balance
                ]);

                $glcode = $data['debit_gl_code'];
                $detail = $sale->description;

                if (isset($sale->orders) && count($sale->orders) > 0) {
                    foreach ($sale->orders as $order) {
                        $code = $order->item->payable_gl;
                        // Credit the payable
                        postDoubleEntries($uuid, $code, 0, $order->amount, $detail);
                    }
                }
                // Debit the bank
                postDoubleEntries($uuid, $glcode, $amount, 0, $detail);

                $getCustomer = Beneficiary::find($customer);
                $newBalance = $getCustomer->balance + $amount;
                $getCustomer->update(['balance' => $newBalance]);

                // Save to customer ledger
                saveSupplierLedger($customer, $voucher, 0, $amount, $detail, $newBalance);

                // Post as expenses
                insertTransaction($amount, 0, 0, now(), $detail, $uuid, 4, $uuid, now(), "Purchase Invoice");
            }

            DB::commit();
            return respond(true, "Transactions successful", $data, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }



    public function salesInvoicePayment()
    {
        $payments = CustomerPersonalLedger::where('company_id', getCompanyid())->with(['invoice', 'customer'])->get();
        return respond(true, 'Sales invoice payment fetched successfully!', $payments, 201);
    }

    public function generateInvoiceCode()
    {
        $code = rand();
        return respond(true, 'Invoice code generated successfully!', $code, 201);
    }

    public function fetchPurchaseInvoice()
    {
        // dd($id);
        try {
            $purchases = getPurchaseInvoice()->with(['supplier', 'items'])->orderBy('transaction_date', 'DESC')->get();
            return respond(true, 'List of purchase invoices fetched!', $purchases, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function fetchDeletedPurchaseInvoice()
    {
        // dd($id);
        try {
            $purchases = getPurchaseInvoice()->onlyTrashed()->with(['supplier', 'items'])->orderBy('transaction_date', 'DESC')->get();
            return respond(true, 'List of purchase invoices fetched!', $purchases, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function fetchSupplierInvoice(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'supplier_id' => 'required|exists:beneficiaries,id',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }
        try {
            $purchases = getPurchaseInvoice()->where('supplier_id', $request->supplier_id)->where('balance', '>', 0)->select('id', 'invoice_number')->get();
            return respond(true, 'List of pending invoices fetched!', $purchases, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function fetchInvoiceItems(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'invoice_number' => 'required|exists:general_invoices,invoice_number',
        ]);
        if ($validator->fails()) {
            return respond('error', $validator->errors(), $data, 400);
        }
        try {
            $items = GeneralInvoice::where('invoice_number', $request->invoice_number)->with(['item'])->get();
            return respond(true, 'List of invoice items fetched!', $items, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }
    public function fetchAll()
    {
        // dd($id);
        try {
            $sales = allTransactions()->where('type', 1)->with([
                'customer',
                'items',
                'salesinvoice' => function ($query) {
                    $query->select('id', 'uuid', 'sub_total', 'total_vat', 'total_discount', 'total_price', 'transaction_date', 'option_type');
                }
            ])->orderBy('created_at', 'DESC')->get();

            // $sales = getSalesInvoice()->with(['customer', 'items'])->get();
            return respond(true, 'List of sales invoices fetched!', $sales, 201);
        } catch (\Exception $exception) {
            return respond('error', $exception->getMessage(), null, 400);
        }
    }


    public function fetchAllPending()
    {
        $sales = getSalesInvoice()->where('balance', '!=', 0)->with(['customer', 'items'])->orderBy('created_at', 'DESC')->get();

        return respond(true, 'List of pending sales invoices fetched!', $sales, 201);
    }
    public function fetchAllPaid()
    {
        $sales = getSalesInvoice()->where('balance', '=', 0)->with(['customer'])->get();

        return respond(true, 'List of pending sales invoices fetched!', $sales, 201);
    }

    public function pendingPayables()
    {
        $payables = payables()->where('balance', '>', 0)->with(['supplier'])->get();
        return respond(true, 'Pending payables fetched!', $payables, 201);
    }

    public function payPayables(Request $request)
    {
        $input = $request->except('_token');
        $data = $request->all();
        // dd($data);
        DB::beginTransaction();
        try {
            $validator = Validator::make($data, [
                'payable_id' => 'required|array',
                'payable_id.*' => 'required|exists:my_transactions,id',
                'payment_bank' => 'required|exists:accounts,id',
                'transaction_date' => 'date',
            ]);
            //debit bank
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $payables = $request->payable_id;
            $pvNumber = rand();
            $sum = 0;
            foreach ($payables as $payable) {
                $transaction = MyTransactions::find($payable);
                if ($transaction->balance < 1) {
                    return respond(false, "One of the transactions has been paid", null, 400);
                }
            }
            foreach ($payables as $payable) {
                $transaction = MyTransactions::find($payable);
                $amount = $transaction->amount;
                $sum += $amount;
                $uuid = $transaction->uuid;
                $detail = $transaction->description;
                $supplier = $transaction->supplier_id;
                $getCustomer = Beneficiary::find($supplier);
                $balance = $getCustomer->balance - $amount;
                $getCustomer->update(['balance' => $getCustomer->balance - $amount]);
                //track where the payable is coming from
                $narration = $transaction->narration;
                switch ($narration) {
                    case 'Purchase Invoice':
                        // Handle case 3
                        $invoice = PurchaseInvoice::where('uuid', $transaction->invoice_number)->first();
                        $invoice->update(['balance' => 0, 'paid' => $invoice->balance]);
                        if ($invoice->items->count() > 0) {
                            foreach ($invoice->items as $single) {
                                $getDetails = Item::find($single->item_id);
                                $glcode = $getDetails->payable_gl;
                                if ($glcode) {
                                    //$glcode = $input['item_id'][$key];
                                    $amount = $single->amount;
                                    $uuid = $uuid;
                                    //credit the item payable account
                                    postDoubleEntries($uuid, $glcode, $amount, 0, $detail); // credit the  accounts
                                }
                            }
                        }
                        break;
                    case '2':
                        // Handle case 3
                        // $sale = SalesOrders::where('id', $request->order_id)->first();
                        // $sale->update(['status' => 1]);

                        break;
                    case '3':
                        // Handle case 3
                        // $quote = Quotes::where('id', $request->order_id)->first();
                        // $quote->update(['status' => 1]);
                        break;
                }
                //insert ledger
                saveSupplierLedger($getCustomer->id, $uuid, $amount, 0, $detail, $balance);

                $transaction->update(['prepared_by' => auth()->user()->id, 'voucher_date' => now(), 'payment_bank' => $request->payment_bank, 'pv_number' => $pvNumber, 'amount_paid' => $amount, 'balance' => 0, "payment_description" => $request->payment_description]);
            }
            //get the account the bank is reporting to
            // $bank = Payment_Bank::find($request->payment_bank);
            $reportGl = $request->payment_bank;
            //debit the payment bank report to account
            postDoubleEntries($uuid, $reportGl, 0, $sum, "Payment Voucher $pvNumber");
            //post as expenses
            insertTransaction($sum, 0, 0, now(), "Payment Voucher $pvNumber", $uuid, 4, $uuid, now(), "Payment");
            DB::commit();
            return respond(true, 'Transaction successful!!', $amount, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function fetchAllPendingPurchasesInvoice()
    {
        $purchases = getPurchaseInvoice()->where('balance', '>', 0)->with(['supplier', 'items'])->orderBy('created_at', 'DESC')->get();

        return respond(true, 'List of pending purchases invoices fetched!', $purchases, 201);
    }
    public function fetchPaidPurchasesInvoice()
    {
        $purchases = getPurchaseInvoice()->where('balance', '=', 0)->with(['supplier'])->get();

        return respond(true, 'List of complete purchases invoices fetched!', $purchases, 201);
    }

    public function reverse(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'id' => 'required|exists:sale_invoices,id',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            $id = $data['id'];
            // get sales invoice with id
            $sales = getSalesInvoice()->where('id', $id)->first();
            $invoiceNumber = $uuid = $sales->invoice_number;
            // get journal with invoice number
            $journals = getJournalFilter()->where('uuid', $invoiceNumber)->get();
            foreach ($journals as $journal) {
                $glcode = $journal->gl_code;
                $detail = "reversal";
                if ($journal->credit == 0) {
                    $credit = $journal->debit;
                    $debit = 0;
                } else {
                    $debit = $journal->credit;
                    $credit = 0;
                }
                postDoubleEntries($uuid, $glcode, $debit, $credit, $detail);
            }
            $sales->delete();
            DB::commit();
            return respond(true, "Transaction successful", $detail, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond('error', $exception->getMessage(), null, 400);
        }
    }

    public function filterPaidSalesInvoice(Request $request)
    {
        $input = $request->all();
        // dd($request->all());
        $validator = Validator::make($input, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        // Parse the dates
        $start_date = Carbon::parse($request->start_date)->startOfDay()->toDateTimeString();
        $end_date = Carbon::parse($request->end_date)->endOfDay()->toDateTimeString();
        $sales = getSalesInvoice()->where('balance', '=', 0)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)
            ->with(['customer'])->get();
        // dd($sales);

        return respond(true, 'List of paid sales invoices fetched!', $sales, 200);
    }

    public function filterPaidPurchaseInvoice(Request $request)
    {
        $input = $request->all();
        // dd($request->all());
        $validator = Validator::make($input, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        // Parse the dates
        $start_date = Carbon::parse($request->start_date)->startOfDay()->toDateTimeString();
        $end_date = Carbon::parse($request->end_date)->endOfDay()->toDateTimeString();

        $purchases = getPurchaseInvoice()->where('balance', '=', 0)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)
            ->with(['supplier'])->get();

        return respond(true, 'List of complete purchases invoices fetched!', $purchases, 201);
    }


    public function calculate(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'unit_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'vat' => 'nullable|exists:taxes,id',
            // 'discount' => 'nullable|numeric',
            'percent_discount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // Retrieve request data
        $unitPrice = $request->unit_price;
        $quantity = $request->quantity;
        $vatId = $request->vat;
        // $fixedDiscount = $request->discount ?? 0;
        $percentDiscount = $request->percent_discount ?? 0;
        $discountAmount = $request->discount_amount ?? 0;

        // Retrieve the VAT rate from the taxes table using the provided VAT ID
        $vatRate = $vatId ? Tax::find($vatId)->rate : 0;

        // Perform calculations
        $subtotal = $unitPrice * $quantity;
        $totalBeforeDiscount = $subtotal;

        // Calculate discount
        if ($discountAmount > 0) {
            $percentDiscount = ($discountAmount / $totalBeforeDiscount) * 100;
            $discountAmount = $discountAmount; // Use the provided discountAmount
        } else {
            $discountAmount = $percentDiscount > 0 ? ($totalBeforeDiscount * $percentDiscount) / 100 : 0;
        }

        $totalAfterDiscount = $totalBeforeDiscount - $discountAmount;

        // Calculate VAT if applicable
        $vatAmount = $vatRate > 0 ? ($totalAfterDiscount * $vatRate) / 100 : 0;
        $totalPrice = $totalAfterDiscount + $vatAmount;

        $output = [
            'subtotal' => $subtotal,
            'vatAmount' => $vatAmount,
            'totalBeforeDiscount' => $totalBeforeDiscount,
            'discountAmount' => $discountAmount,
            'totalPrice' => round($totalPrice, 2),
            'percentDiscount' => round($percentDiscount, 2), // Round percent_discount to 2 decimal places
        ];
        return respond(true, 'Calculation completed successfully!', $output, 200);
    }

    public function getDeletedSalesInvoice1()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = allTransactions()->where('type', 1)->onlyTrashed()->with([
                'customer',
                'items',
                'salesinvoice' => function ($query) {
                    $query->select('id', 'uuid', 'sub_total', 'total_vat', 'total_discount', 'total_price', 'transaction_date', 'option_type');
                }
            ])->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Archived sales invoices fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function getDeletedSalesInvoice()
    {
        try {
            // Fetch all soft-deleted sales invoices with related soft-deleted data
            $deletedInvoice = allTransactions()
                ->where('type', 1)
                ->onlyTrashed()
                ->with([
                    'customer' => function ($query) {
                        $query->withTrashed(); // Include soft-deleted customers
                    },
                    'items' => function ($query) {
                        $query->withTrashed(); // Include soft-deleted items
                    },
                    'salesinvoice' => function ($query) {
                        $query->withTrashed()->select('id', 'uuid', 'sub_total', 'total_vat', 'total_discount', 'total_price', 'transaction_date', 'option_type'); // Include soft-deleted salesinvoice
                    }
                ])
                ->orderBy('created_at', 'DESC')
                ->get();

            // Return the soft-deleted sales invoices with related data
            return respond(true, 'Archived sales invoices fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreSalesInvoice(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sale_invoices,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = SaleInvoice::withTrashed()->find($request->id);
            $invoiceNumber = $invoice->invoice_number;
            $invoiceItems = GeneralInvoice::withTrashed()->where('invoice_number', $invoiceNumber)->get();

            $customerId = $invoice->customer_id;
            // dd($customerId);
            $amount = $invoice->amount;
            // dd($amount);

            // Find the customer and current balance
            $customer = Customers::find($customerId);
            if (!$customer) {
                return respond('error', 'Customer not found', null, 404);
            }

            $customer->update(['balance' => $customer->balance + $amount]);

            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::find($item->item_id);
                // dd($getDetails);
                $debitGl = $getDetails->account_receivable; // receivable account
                $creditGl = $getDetails->sales_gl; // sales income account
                // dd($creditGl);
                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $debitGl, $item->amount, 0, "Restored deleted invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $creditGl, 0, $item->amount, "Restored deleted invoice");  // reverse the debit
                    $item->restore();
                }
            }
            $invoice->restore();

            restoreReceivable($invoiceNumber);
            CustomerPersonalLedger::withTrashed()->where('invoice_number', $invoiceNumber)->restore();


            return respond(true, 'Sales invoice restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restoreSalesInvoiceNew(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sale_invoices,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            // Find the soft-deleted invoice by ID and restore it
            $invoice = SaleInvoice::withTrashed()->find($request->id);
            $invoiceNumber = $invoice->invoice_number;
            $salesInvoiceUUID = $invoice->uuid;

            $invoiceItems = GeneralInvoice::withTrashed()->where('invoice_number', $invoiceNumber)->get();
            $customerId = $invoice->customer_id;
            $amount = $invoice->amount;

            // Find the customer and update their balance
            $customer = Customers::find($customerId);
            if (!$customer) {
                return respond('error', 'Customer not found', null, 404);
            }

            // Update customer's balance by adding the restored amount
            $customer->update(['balance' => $customer->balance + $amount]);

            // Restore item quantities in related tables (Item, Stock, StockInventory)
            foreach ($invoiceItems as $item) {
                // Retrieve the item
                $getItem = Item::find($item->item_id);
                if (!$getItem) {
                    throw new \Exception("Item with ID {$item->item_id} not found.");
                }

                // Decrease the quantity in the Item table as it was reversed during deletion
                $getItem->quantity -= $item->quantity;
                $getItem->save();

                // Decrease the quantity in the Stock table
                $stock = Stock::where('item_id', $item->item_id)->first();
                if ($stock) {
                    $stock->quantity -= $item->quantity; // Subtract the restored quantity
                }
                // Get old and new quantities for stock inventory
                $stockInventory = Stock::where('item_id', $item->item_id)->first();
                $oldQuantity = $stockInventory->quantity;
                $newQuantity = $oldQuantity - $item->quantity;
                stockInventoryReversal(
                    $item->item_id, // Item
                    $oldQuantity,   // Old quantity
                    $newQuantity,   // New quantity
                    $item->quantity, // Quantity restored
                    $stock->id,     // Stock ID
                    $item->amount,  // Amount
                    "Restore of sale for invoice {$getItem->name}" // Description
                );
                // Restore the general invoice item
                $item->restore();
                $stock->save();
            }

            // Reverse the general ledger entries
            $allPostings = Journal::withTrashed()->where('uuid', $salesInvoiceUUID)->get();
            // dd($allPostings);
            foreach ($allPostings as $post) {
                $debit = $post->credit;
                $credit = $post->debit;
                $glToPost = $post->gl_code;
                postDoubleEntries($invoiceNumber, $glToPost, $debit, $credit, "Restored $post->details");
            }


            // Restore the sale invoice and its related records
            $invoice->restore();
            restoreReceivable($invoiceNumber);
            CustomerPersonalLedger::withTrashed()->where('invoice_number', $invoiceNumber)->restore();

            DB::commit();
            return respond(true, 'Sales invoice restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function forceDeleteSalesInvoice(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sale_invoices,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // if (auth()->user()->type != "Super Admin") {
        //     return respond(false, "Unauthorised", null, 403);
        // }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $saleInvoice = SaleInvoice::withTrashed()->findOrFail($id);
            if (!$saleInvoice) {
                return respond(false, 'Sale invoice not found', null, 404);
            }
            $invoiceNumber = $saleInvoice->invoice_number;

            $invoiceItems = GeneralInvoice::where('invoice_number', $invoiceNumber)->get();
            // dd($invoiceItems);
            $customerId = $saleInvoice->customer_id;

            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::withTrashed()->find($item->item_id);
                // dd($getDetails);
                $debitGl = $getDetails->account_receivable; // receivable account
                $creditGl = $getDetails->sales_gl; // sales income account
                // dd($creditGl);
                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $creditGl, $item->amount, 0, "Reversal of deleted invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $debitGl, 0, $item->amount, "Reversal of deleted invoice");  // reverse the debit
                    $item->forceDelete();
                }
            }

            // Permanently delete the user and related data
            $saleInvoice->forceDelete();

            forceDeleteReceivable($invoiceNumber);
            CustomerPersonalLedger::withTrashed()->where('invoice_number', $invoiceNumber)->forceDelete();

            // Return a success response
            return respond(true, 'Sales invoice data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function deleteSaleInvoice(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sale_invoices,id'
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $saleInvoice = SaleInvoice::find($request->id);
            if (!$saleInvoice) {
                return respond('error', 'Sale invoice not found', null, 404);
            }
            if ($saleInvoice->balance != $saleInvoice->amount) {
                return respond(false, 'Cannot be deleted because there is already a payment on this invoice', null, 401);
            }

            $invoiceNumber = $saleInvoice->invoice_number;
            // dd($invoiceNumber);

            // Retrieve related records
            $invoiceItems = GeneralInvoice::where('invoice_number', $invoiceNumber)->get();
            // dd($invoiceItems);
            $customerId = $saleInvoice->customer_id;
            // dd($customerId);
            $amount = $saleInvoice->amount;
            // dd($amount);

            // Find the customer and current balance
            $customer = Customers::find($customerId);
            if (!$customer) {
                return respond(false, 'Customer not found', null, 404);
            }

            // Reverse the customer balance adjustment
            $customer->update(['balance' => $customer->balance - $amount]);

            // Delete related general invoice records
            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::find($item->item_id);
                // dd($getDetails);
                $debitGl = $getDetails->account_receivable; // receivable account
                $creditGl = $getDetails->sales_gl; // sales income account
                // dd($creditGl);
                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $creditGl, $item->amount, 0, "Reversal of deleted invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $debitGl, 0, $item->amount, "Reversal of deleted invoice");  // reverse the debit

                    $item->delete();
                }
            }

            // Delete the sale invoice record
            $saleInvoice->delete();

            // Remove receivable record
            removeReceivable($invoiceNumber);
            CustomerPersonalLedger::where('invoice_number', $invoiceNumber)->delete();

            DB::commit();
            return respond(true, 'Sale Invoice archived successfully', $saleInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteSaleInvoiceNew(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:sale_invoices,id'
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            // Find the sale invoice by ID
            $saleInvoice = SaleInvoice::find($request->id);
            //dd($saleInvoice);
            if (!$saleInvoice) {
                return respond('error', 'Sale invoice not found', null, 404);
            }

            // Ensure no payments have been made on the invoice
            if ($saleInvoice->balance != $saleInvoice->amount) {
                return respond(false, 'Cannot be deleted because there is already a payment on this invoice', null, 401);
            }

            $invoiceNumber = $saleInvoice->invoice_number;
            $salesInvoiceUUID = $saleInvoice->uuid;
            // dd($salesInvoiceUUID);

            // Retrieve the sales items and related general invoice records
            $salesItems = GeneralInvoice::where('uuid', $salesInvoiceUUID)->get();
            $invoiceItems = GeneralInvoice::where('invoice_number', $invoiceNumber)->get();
            // dd($salesItems);
            $customerId = $saleInvoice->customer_id;
            $amount = $saleInvoice->amount;

            // Find the customer and update their balance
            $customer = Customers::find($customerId);
            if (!$customer) {
                return respond(false, 'Customer not found', null, 404);
            }

            $customer->update(['balance' => $customer->balance - $amount]);

            // Reversal of item quantities in related tables (Item, Stock, StockInventory)
            foreach ($salesItems as $item) {
                // Retrieve the item
                $getItem = Item::find($item->item_id);
                if (!$getItem) {
                    throw new \Exception("Item with ID {$item->item_id} not found.");
                }

                // Reverse the quantity in the Item table
                $getItem->quantity += $item->quantity;
                $getItem->save();

                // Reverse the quantity in the Stock table
                $stock = Stock::where('item_id', $item->item_id)->first();
                if ($stock) {
                    $stock->quantity += $item->quantity; // Add the reversed quantity
                }
                // Get old and new quantities for stock inventory
                $stockInventory = Stock::where('item_id', $item->item_id)->first();
                $oldQuantity = $stockInventory->quantity;
                $newQuantity = $oldQuantity + $item->quantity;

                // Call stockInventory helper function
                stockInventoryReversal(
                    $item->item_id, // Item
                    $oldQuantity,   // Old quantity
                    $newQuantity,   // New quantity
                    $item->quantity, // Quantity reversed
                    $stock->id,     // Stock ID
                    $item->amount,  // Amount
                    "Reversal of deleted sale for invoice {$getItem->name}" // Description
                );
                $stock->save();
                $item->delete();
            }

            $allPostings = Journal::where('uuid', $salesInvoiceUUID)->get();
            foreach ($allPostings as $post) {
                $credit = $post->debit;
                $debit = $post->credit;
                $glToPost = $post->gl_code;
                postDoubleEntries($invoiceNumber, $glToPost, $debit, $credit, "Reversal of $post->details");

            }
            // Delete the sale invoice
            $saleInvoice->delete();

            // Remove the receivable and ledger entries
            removeReceivable($invoiceNumber);
            CustomerPersonalLedger::where('invoice_number', $invoiceNumber)->delete();

            DB::commit();
            return respond(true, 'Sale Invoice archived successfully', $saleInvoice, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function deletePurchaseInvoice(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate request input
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_invoices,id'
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            // Find the purchase invoice
            $purchaseInvoice = PurchaseInvoice::find($request->id);
            if (!$purchaseInvoice) {
                return respond('error', 'Purchase invoice not found', null, 404);
            }

            $invoiceNumber = $purchaseInvoice->invoice_number;

            // Retrieve related records
            $invoiceItems = GeneralInvoice::where('invoice_number', $invoiceNumber)->get();
            $supplierId = $purchaseInvoice->supplier_id;
            $amount = $purchaseInvoice->amount;

            // Find the supplier and current balance
            $supplier = Beneficiary::find($supplierId);
            if (!$supplier) {
                return respond('error', 'Supplier not found', null, 404);
            }

            // Reverse the supplier balance adjustment
            $supplier->update(['balance' => $supplier->balance - $amount]);

            // Delete related general invoice records
            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::find($item->item_id);
                $debitGl = $getDetails->payable_gl; // payable account
                $creditGl = $getDetails->purchase_gl; // purchase account

                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $debitGl, 0, $item->amount, "Reversal of deleted Purchase Invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $creditGl, $item->amount, 0, "Reversal of deleted Purchase Invoice");  // reverse the debit
                    $item->delete();
                }
            }

            // Delete the purchase invoice record
            $purchaseInvoice->delete();

            // Remove payable record
            removePayable($invoiceNumber);

            SupplierPersonalLedger::where('invoice_number', $invoiceNumber)->delete();


            DB::commit();
            return respond(true, 'Purchase Invoice archived successfully', $purchaseInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function forceDeletePurchaseInvoice(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate request input
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_invoices,id'
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            // Find the purchase invoice
            $purchaseInvoice = PurchaseInvoice::withTrashed()->find($request->id);
            if (!$purchaseInvoice) {
                return respond('error', 'Purchase invoice not found', null, 404);
            }

            $invoiceNumber = $purchaseInvoice->invoice_number;

            // Retrieve related records
            $invoiceItems = GeneralInvoice::withTrashed()->where('invoice_number', $invoiceNumber)->get();
            $supplierId = $purchaseInvoice->supplier_id;
            $amount = $purchaseInvoice->amount;

            // Find the supplier and current balance
            $supplier = Beneficiary::find($supplierId);
            if (!$supplier) {
                return respond('error', 'Supplier not found', null, 404);
            }

            // Reverse the supplier balance adjustment
            $supplier->update(['balance' => $supplier->balance - $amount]);

            // Delete related general invoice records
            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::find($item->item_id);
                $debitGl = $getDetails->payable_gl; // payable account
                $creditGl = $getDetails->purchase_gl; // purchase account

                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $debitGl, 0, $item->amount, "Reversal of deleted Purchase Invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $creditGl, $item->amount, 0, "Reversal of deleted Purchase Invoice");  // reverse the debit
                    $item->forceDelete();
                }
            }

            // Delete the purchase invoice record
            $purchaseInvoice->forceDelete();

            // Remove payable record
            forceDeletePayable($invoiceNumber);

            SupplierPersonalLedger::withTrashed()->where('invoice_number', $invoiceNumber)->forceDelete();


            DB::commit();
            return respond(true, 'Data permanently deleted successfully', $purchaseInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }




    public function getDeletedPurchaseInvoices()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = PurchaseInvoice::where('company_id', auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Data fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restorePurchaseInvoice1(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_invoices,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = PurchaseInvoice::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function restorePurchaseInvoice(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate request input
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_invoices,id'
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            // Find the purchase invoice
            $purchaseInvoice = PurchaseInvoice::withTrashed()->find($request->id);
            if (!$purchaseInvoice) {
                return respond('error', 'Purchase invoice not found', null, 404);
            }

            $invoiceNumber = $purchaseInvoice->invoice_number;

            // Retrieve related records
            $invoiceItems = GeneralInvoice::withTrashed()->where('invoice_number', $invoiceNumber)->get();
            $supplierId = $purchaseInvoice->supplier_id;
            $amount = $purchaseInvoice->amount;

            // Find the supplier and current balance
            $supplier = Beneficiary::find($supplierId);
            if (!$supplier) {
                return respond('error', 'Supplier not found', null, 404);
            }

            // Reverse the supplier balance adjustment
            $supplier->update(['balance' => $supplier->balance + $amount]);

            // Delete related general invoice records
            foreach ($invoiceItems as $item) {
                // Retrieve item details
                $getDetails = Item::find($item->item_id);
                $debitGl = $getDetails->payable_gl; // payable account
                $creditGl = $getDetails->purchase_gl; // purchase account

                if ($debitGl && $creditGl) {
                    // Reverse the general ledger entries
                    postDoubleEntries($invoiceNumber, $creditGl, 0, $item->amount, "Restored Purchase Invoice"); // reverse the credit
                    postDoubleEntries($invoiceNumber, $debitGl, $item->amount, 0, "Restored Purchase Invoice");  // reverse the debit
                    $item->restore();
                }
            }

            // Delete the purchase invoice record
            $purchaseInvoice->restore();

            // Remove payable record
            restorePayable($invoiceNumber);

            SupplierPersonalLedger::withTrashed()->where('invoice_number', $invoiceNumber)->restore();


            DB::commit();
            return respond(true, 'Purchase Invoice archived successfully', $purchaseInvoice, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function restoreAllPurchaseInvoices(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = PurchaseInvoice::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeletePurhcaseInvoice1(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:purchase_invoices,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = PurchaseInvoice::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Invoice not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function forceDeleteAllPurchaseInvoices()
    {

        try {

            $accounts = PurchaseInvoice::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }


}
