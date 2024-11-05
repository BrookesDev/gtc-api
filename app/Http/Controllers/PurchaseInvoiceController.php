<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
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
use App\Models\MyTransactions;
use App\Models\CustomerPersonalLedger;
use App\Models\Journal;
use App\Models\SupplierPersonalLedger;
use App\Models\Quotes;
use App\Models\SalesOrders;
use App\Models\Company;
use App\Models\Setting;
use App\Models\StockInventory;
use Carbon\Carbon;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        foreach ($request->item_id as $itemId) {
            $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
            if (!$item) {
                return respond('error', 'One or more items do not belong to your company.', null, 403);
            }
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
            $getDetails = Item::find($array);
            $debitGl = $getDetails->account_receivable; //receivable account
            $creditGl = $getDetails->sales_gl; //sales income account
            $cOfGl = $getDetails->cost_of_good_gl; //cost of goods sold account
            $inventoryGl = $getDetails->purchase_gl; //inventory account
            $checkRow = $key + 1;
            if (isset($input['discount'][$key])) {
                $discountGl = $getDetails->discount_gl; //discount account
                if (!$discountGl) {
                    return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                }
            }
            if (isset($input['tax_id'][$key])) {
                $tax = Tax::where('id', $input['tax_id'][$key])->first();
                $TaxGl = $tax->report_gl; //tax account
                if (!$TaxGl) {
                    return respond(false, "Tax account  has not been specified for $tax->description  , see row $checkRow !", [$checkRow], 400);
                }
            }
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
            $balance = $getCustomer->balance;
            $getCustomer->update(['balance' => $getCustomer->balance + $amount]);
            // dd("here");
            //Deduct quantities and save changes
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
                if (isset($input['discount'][$key])) {
                    $discountGl = $getDetails->discount_gl; //discount account
                    // credit the sales income
                    postDoubleEntries($uuid, $creditGl, 0, $input['discount'][$key], "discount for $detail", $request->transaction_date);
                    // debit the discount account
                    postDoubleEntries($uuid, $discountGl, $input['discount'][$key], 0, "discount for $detail", $request->transaction_date);
                    // if (!$discountGl) {
                    //     return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $key !", [$key], 400);
                    // }
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
                    postDoubleEntries($uuid, $creditGl, 0, $taxAmount, "tax for $detail", $request->transaction_date);
                    // debit the tax account account
                    postDoubleEntries($uuid, $TaxGl, $taxAmount, 0, "tax for $detail", $request->transaction_date);
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

    public function updateSalesInvoiceNew(Request $request)
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
                if (isset($input['discount'][$key])) {
                    $discountGl = $getDetails->discount_gl; //discount account
                    if (!$discountGl) {
                        return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $checkRow !", [$checkRow], 400);
                    }
                }
                if (isset($input['tax_id'][$key])) {
                    $tax = Tax::where('id', $input['tax_id'][$key])->first();
                    $TaxGl = $tax->report_gl; //tax account
                    if (!$TaxGl) {
                        return respond(false, "Tax account  has not been specified for $tax->description  , see row $checkRow !", [$checkRow], 400);
                    }
                }
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
                    // credit the sales income
                    postDoubleEntries($uuid, $creditGl, 0, $input['discount'][$key], "discount for $detail", $request->transaction_date);
                    // debit the discount account
                    postDoubleEntries($uuid, $discountGl, $input['discount'][$key], 0, "discount for $detail", $request->transaction_date);
                    // if (!$discountGl) {
                    //     return respond(false, "Discount account  has not been specified for $getDetails->name  , see row $key !", [$key], 400);
                    // }
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
                    postDoubleEntries($uuid, $creditGl, 0, $taxAmount, "tax for $detail", $request->transaction_date);
                    // debit the tax account account
                    postDoubleEntries($uuid, $TaxGl, $taxAmount, 0, "tax for $detail", $request->transaction_date);
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



}
