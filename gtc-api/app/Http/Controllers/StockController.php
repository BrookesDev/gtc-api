<?php

namespace App\Http\Controllers;

use App\Customers;
use App\Mail\Message;
use App\Mail\Notification;
use App\Mail\OrderNotification;
use App\Models\ApprovalLevel;
use App\Models\Company;
use App\Models\Department;
use App\Models\GeneralInvoice;
use App\Models\Item;
use App\Models\Pincard;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Quotes;
use App\Models\Requisition;
use App\Models\RequisitionComment;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\StockInventory;
use App\Models\SupportingDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StockController extends Controller
{
    public function index()
    {
        $purchaseorder = getPurchaseOrder()->get();
        return respond(true, 'Purchase orders fetched successfully', $purchaseorder, 200);
    }
    public function pendingPurchaseOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'nullable|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        if ($request->input('mode') == 1) {

            $purchaseorder = PurchaseOrder::where('company_id', auth()->user()->company_id)->where('invoice_status', 0)
                ->with('supplier')->orderBy('created_at', 'DESC')->get();
        } else {
            $purchaseorder = [];
        }
        return respond(true, 'Purchase orders fetched successfully', $purchaseorder, 200);
    }
    public function pendingPurchaseOrdersID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:purchase_orders,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $id = $request->id;

        $purchaseorder = getPurchaseOrder()->where("id", $id)->where('status', 0)->get();
        return respond(true, 'Purchase orders fetched successfully', $purchaseorder, 200);
    }
    public function completedPurchaseOrders()
    {
        $purchaseorder = getPurchaseOrder()->where('status', 1)->get();
        return respond(true, 'Purchase orders fetched successfully', $purchaseorder, 200);
    }
    public function getStockList()
    {
        $stocks = Stock::all();
        return respond(true, 'Stocks fetched successfully', $stocks, 200);
    }
    public function getStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:stocks,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $data = Stock::find($request->id);

        if (!$data) {
            return respond(false, 'Stock not found', null, 404);
        }

        return respond(true, 'Stock fetched successfully', $data, 200);
    }
    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            //'unit_price' => 'required|numeric|min:0',
            //'classification' => 'required',
            //'category_id' => 'required|integer',
            'unit_of_measurement' => 'required',
            // 'quantity' => 'required|numeric|min:0',
            //'re_order_level' => 'required|integer|min:0',
            //'created_by' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $stock = Stock::create($request->all());
            return respond(true, 'Stock created successfully', $stock, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function updatePurchaseOrder(Request $request)
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
                'id' => 'required|exists:purchase_orders,id',
                'supplier_id' => 'nullable|exists:beneficiaries,id',
                // 'document_number' => 'required|unique:quotes,document_number',
                'document_number' => [
                    'nullable',
                    'string',
                    Rule::unique('purchase_orders', 'document_number')->ignore($request->id),
                ],
                'reference' => 'nullable',
                'date_supplied' => 'nullable|date',
                'expiring_date' => 'nullable|date',
                'transaction_date' => 'nullable|date',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'nullable|array',
                'item.*' => 'required|exists:items,id',
                'tax_id' => 'nullable|array',
                'tax_id.*' => 'nullable|exists:taxes,id',
                'quantity' => 'nullable|array',
                'quantity.*' => 'nullable|numeric',
                'total_amount' => 'nullable|numeric',
                'amount' => 'nullable|array',
                'amount.*' => 'nullable|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $purchaseOrder = PurchaseOrder::find($id);
            $olduuid = $purchaseOrder->uuid;

            $data = $request->all();

            // Check if customer_id has changed, and if so, generate a new UUID
            if ($request->supplier_id && $purchaseOrder->supplier_id !== $request->supplier_id) {
                $month = now()->format('m');
                $newUuid = "PO" . '-' . $month . '-' . rand(1000000, 99999999);

                $purchaseOrder->uuid = $newUuid;
                $purchaseOrder->supplier_id = $request->supplier_id;

                // Update related entries with the new UUID
                GeneralInvoice::where('invoice_number', $purchaseOrder->uuid)->update(['uuid' => $newUuid]);
                SupportingDocument::where('uuid', $purchaseOrder->uuid)->update(['uuid' => $newUuid]);
            }

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
                SupportingDocument::where('uuid', $olduuid)->update([
                    'uuid' => $purchaseOrder->uuid,
                    'file' => $data['supporting_document'],
                ]);
            } else {
                SupportingDocument::where('uuid', $olduuid)->update([
                    'uuid' => $purchaseOrder->uuid,
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
                    'invoice_number' => $purchaseOrder->uuid,
                    'uuid' => $purchaseOrder->uuid,
                    'type' => "Purchase",
                    'amount' => $data['amount'][$index],
                    'quantity' => $data['quantity'][$index],
                    'tax_id' => $data['tax_id'][$index] ?? "",
                    'discount' => $data['discount'][$index] ?? "",
                    'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                ]);
            }

            // Update the quote with new data
            $purchaseOrder->document_number = $request->document_number ?? $purchaseOrder->document_number;
            $purchaseOrder->reference = $request->reference ?? $purchaseOrder->reference;
            $purchaseOrder->date_supplied = $request->date ?? $purchaseOrder->date_supplied;
            $purchaseOrder->transaction_date = $request->transaction_date ?? $purchaseOrder->transaction_date;
            $purchaseOrder->expiring_date = $request->expiring_date ?? $purchaseOrder->expiring_date;
            $purchaseOrder->sub_total = $request->sub_total ?? $purchaseOrder->sub_total;
            $purchaseOrder->total_vat = $request->total_vat ?? $purchaseOrder->total_vat;
            $purchaseOrder->total_discount = $request->total_discount ?? $purchaseOrder->total_discount;
            $purchaseOrder->total_price = $request->total_price ?? $purchaseOrder->total_price;
            $purchaseOrder->amount = $request->total_amount;
            $purchaseOrder->status = 0;
            $purchaseOrder->save();

            DB::commit();
            return respond(true, 'Purchase order updated successfully', $purchaseOrder, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function updateStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'unit_price' => 'required|numeric|min:0',
            //'classification' => 'required',
            //'category_id' => 'required|integer',
            'unit_of_measurement' => 'required',
            'quantity' => 'required|numeric|min:0',
            //'re_order_level' => 'required|integer|min:0',
            //'created_by' => 'required',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            $stock = Stock::findOrFail($id);
            $stock->update($request->all());
            return respond(true, 'Stock updated successfully', $stock, 200);
        } catch (\Exception $exception) {
            return respond(false, 'Failed to update stock', null, 500);
        }
    }
    public function deleteStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:stocks,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $id = $request->id;
        $stock = Stock::find($id);

        if (!$stock) {
            return respond(false, 'Stock not found', null, 404);
        }

        $stock->delete();
        return respond(true, 'Stock archived successfully', $stock, 200);
    }

    public function getDeletedStocks()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Stock::where('company_id', auth()->user()->company_id)
                ->onlyTrashed()->orderBy('created_at', 'DESC')->get();

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

    public function restoreDeletedStocks(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:stocks,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = Stock::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllDeletedStocks(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = Stock::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeleteStock(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:stocks,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = Stock::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Stock not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function forceDeleteAllStocks()
    {

        try {

            $accounts = Stock::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function createPurchaseOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'beneficiary_id' => 'required|exists:beneficiaries,id',
            'customer_id' => 'required|exists:customers,id',
            'item' => 'required|array',
            'item.*' => 'required|exists:items,id',
            'unit_price' => 'required|array',
            'unit_price.*' => 'required|numeric',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $input = $request->all();
        $user = User::whereHas("roles", function ($q) {
            $q->where("name", "Admin");
        })->first();
        try {
            $input = $request->all();
            $orders = PurchaseOrder::select('order_id', 'supplier_id')->distinct()->get();

            $count = count($orders);
            $figure = $count + 1;
            $length = strlen($figure);
            if ($length == 1) {
                $code = "000" . $figure;
            }
            if ($length == 2) {
                $code = "00" . $figure;
            }
            if ($length == 3) {
                $code = "0" . $figure;
            }
            if ($length == 4) {
                $code = $figure;
            }
            $item = $input['item'];
            $supplier_id = $input['customer_id'];
            $month = now()->format('m');
            $uuid = "PO" . '-' . $month . '-' . rand(1000, 9999) . '-' . $code;
            // $approval_level = ApprovalLevel::where('module', 'Purchase Order')->first();
            // $approval_level = ApprovalLevel::where('module', 1)->where('company_id', getCompanyid())->first();
            // if (!$approval_level) {
            //     return respond(false, 'Approval level not found for this company.', null, 400);
            // }
            // $input['approver_list'] = $approval_level->list_of_approvers;
            // $a = json_decode($approval_level->list_of_approvers);
            // $firstapprover = array_shift($a);
            // $remainingapprover = ($a);
            // $input['approval_order'] = $firstapprover;
            // $input['approver_reminant'] = json_encode($remainingapprover);
            // $input['approved_by'] = "[]";
            foreach ($item as $key => $item) {
                $order = new PurchaseOrder;
                $order->supplier_id = $supplier_id;
                // $order->approver_list = $input['approver_list'];
                // $order->approval_order = $input['approval_order'];
                // $order->approver_reminant = $input['approver_reminant'];
                // $order->approved_by = $input['approved_by'];
                $order->item = $input['item'][$key];
                $order->quantity = $input['quantity'][$key];
                $order->price = $input['unit_price'][$key];
                $order->amount = $input['quantity'][$key] * $input['unit_price'][$key];
                $order->order_id = $uuid;
                $order->total = $request->all_sum;
                $order->order_by = Auth::user()->id;
                $order->save();
            }
            // Mail::to($user->email)->send(
            //     new OrderNotification(
            //         $user->name,
            //         $user->email
            //     )
            // );

            DB::commit();
            // return redirect()->back()->with('message', 'Purchase order generated successfully!');
            return respond(true, 'Purchase order generated successfully!', $order, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function createPurchaseOrderNew(Request $request)
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

            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:beneficiaries,id',
                'transaction_date' => 'required|date',
                'document_number' => 'required|unique:purchase_orders',
                'reference' => 'nullable',
                'date_supplied' => 'required|date',
                'expiring_date' => 'required|date',
                'tax_id' => 'nullable',
                'total_amount' => 'required|numeric',
                // 'currency' => 'required|exists:currencies,id',
                'sub_total' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_vat' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_discount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'total_price' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'supporting_document' => 'nullable|mimes:pdf,png,jpg,xlsx',
                'item' => 'required|array',
                'item.*' => 'required|exists:items,id',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'discount' => 'nullable|array',
                'discount.*' => 'nullable|numeric',
                'discount_percentage' => 'nullable|array',
                'discount_percentage.*' => 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            // dd($request->all());
            $data = $request->all();
            // $uuid = generate_uuid();
            $month = now()->format('m');
            $uuid = "PO" . '-' . $month . '-' . rand(1000000, 99999999);

            if ($request->has('supporting_document')) {
                $data['supporting_document'] = uploadImage($request->supporting_document, "supporting_document");
            } else {
                $data['supporting_document'] = null;
            }
            if ($request->has('supporting_document')) {
                $document = SupportingDocument::create([
                    'uuid' => $uuid,
                    'type' => "Purchase",
                    'file' => $data['supporting_document'],
                ]);
            }
            // dd($data);
            foreach ($data['item'] as $index => $itemId) {
                // Retrieve item name
                $item = Item::find($itemId);
                if (!$item) {
                    return respond(false, 'Item not found', null, 400);
                }
                // dd($data);
                GeneralInvoice::create([
                    'item_id' => $itemId,
                    'invoice_number' => $uuid,
                    'uuid' => $uuid,
                    'type' => "Purchase",
                    'amount' => $data['amount'][$index],
                    'quantity' => $data['quantity'][$index],
                    'tax_id' => $data['tax_id'][$index] ?? "",
                    'discount' => $data['discount'][$index] ?? '0',
                    'discount_percentage' => $data['discount_percentage'][$index] ?? '0',
                ]);
                // dd($data);
            }
            $data['status'] = 0;
            $data['uuid'] = $uuid;
            $data['amount'] = $request->total_amount;
            $purchase = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'uuid' => $uuid,
                'status' => $data['status'],
                'expiring_date' => $request->expiring_date,
                'document_number' => $request->document_number,
                'reference' => $request->reference,
                'date_supplied' => $request->date_supplied,
                'sub_total' => $request->sub_total,
                'total_vat' => $request->total_vat,
                'total_discount' => $request->total_discount,
                'total_price' => $request->total_price,
                'transaction_date' => $request->transaction_date,
                'amount' => $data['amount'],
            ]);
            DB::commit();
            return respond(true, 'Purchase Order created successfully', $purchase, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function deletePurchaseOrder(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_orders,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            // Fetch the purchase order
            $purchaseOrder = PurchaseOrder::find($request->id);
            if (!$purchaseOrder) {
                return respond(false, 'Purchase Order not found', null, 404);
            }

            $uuid = $purchaseOrder->uuid;

            // Delete related general invoice records
            $generalInvoices = GeneralInvoice::where('invoice_number', $uuid)->get();
            foreach ($generalInvoices as $invoice) {
                $invoice->delete();
            }

            // Check if a supporting document exists for the purchase order
            $supportingDocument = SupportingDocument::where('uuid', $uuid)->first();
            if ($supportingDocument) {
                // Delete the supporting document if it exists
                $supportingDocument->delete();
            }

            // Remove the purchase order itself
            $purchaseOrder->delete();

            DB::commit();

            return respond(true, 'Purchase Order deleted successfully', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function pendingOrders(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',

            ]);

            if ($validator->fails()) {
                return respond(false, 'Validation error!', $validator->errors(), 400);
            }

            $customerId = $request->customer_id;


            $purchaseOrders = PurchaseOrder::whereNull('is_supplied')->where('supplier_id', $customerId)->select('order_id')->distinct()->get();

            return respond(true, 'Search Complete!', $purchaseOrders, 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return respond(false, 'Error!', $exception->getMessage(), 500);
        }
    }


    public function OrderDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'order_id' => 'required|string', // Assuming order_id is a string
            ]);

            if ($validator->fails()) {
                return respond(false, 'Validation error!', $validator->errors(), 400);
            }

            $customerId = $request->customer_id;
            $orderId = $request->order_id;

            $value = PurchaseOrder::where('supplier_id', $customerId)
                ->where('order_id', $orderId)
                ->with(['stock'])
                ->get();
            return respond(true, 'Search Complete!', $value, 200);

        } catch (\Exception $exception) {
            return respond(false, 'Error!', $exception->getMessage(), 500);
        }
    }

    public function stockDeliveryOld(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'purchase_order_id' => 'required|array',
            'purchase_order_id.*' => 'required|exists:purchase_orders,id', // Assuming purchase_orders table has 'id' column
            'quantity_supplied' => 'required|array',
            'quantity_supplied.*' => 'required|numeric|min:0',
            'supplied_price' => 'required|array',
            'supplied_price.*' => 'required|numeric|min:0',
            'supplied_amount' => 'required|array',
            'supplied_amount.*' => 'required|numeric|min:0',
            // 'bank' => 'required|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $input = $request->all();
            $order = $input['purchase_order_id'];
            $user_id = Auth::user()->id;
            $supplier_id = $input['customer_id'];
            // $input['purchase_order'] = $input['order_id'];

            $sum = 0;
            foreach ($order as $key => $item) {
                $orderSuccessful = PurchaseOrder::where('id', $item)->first();
                if ($orderSuccessful->is_supplied == 1) {
                    return respond(false, 'Error!', 'The order has already been initially placed.', 400);
                }
                $uuid = $orderSuccessful->order_id;
                // check if product already exists
                $check = Stock::where('item_id', $orderSuccessful->item)->first();
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
                    $check->item_id = $orderSuccessful->item;
                    $check->amount = $format;
                    $check->quantity = $input['quantity_supplied'][$key];
                    $check->save();
                }
                $detail = "purchase of" . ' ' . $check->item->name;
                $peramount = $input['supplied_amount'][$key];
                $price = $input['supplied_price'][$key];
                $sum += $peramount;
                if ($check) {
                    $check->item->update(['price' => $price, "quantity" => $newQuantity]);
                }
                // debit the report to account in item
                // postDoubleEntries($uuid, $check->item->gl_code,  $input['supplied_amount'][$key], 0, $detail);
                // post to stock inventory
                stockInventory($orderSuccessful->item, $oldQuantity, $newQuantity, $input['quantity_supplied'][$key], $check->id, $format, $detail);
                $orderSuccessful->update([
                    'date_supplied' => now(),
                    'quantity_supplied' => $input['quantity_supplied'][$key],
                    'supplied_price' => str_replace(',', '', $input['supplied_price'][$key]),
                    'supplied_amount' => str_replace(',', '', $input['supplied_amount'][$key]),
                    'is_supplied' => 1,
                    'received_by' => $user_id,
                ]);


            }
            $detail = "payment on purchase order $uuid";
            // credit the bank
            // postDoubleEntries($uuid, $request->bank, 0, $sum,  $detail);
            DB::commit();
            return respond(true, 'Data Update successful!', $input, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function stockDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:beneficiaries,id',
            'purchase_order_id' => 'required|exists:purchase_invoices,id', // Assuming purchase_orders table has 'id' column
            'quantity_supplied' => 'required|array',
            'quantity_supplied.*' => 'required|numeric|min:0',
            'supplied_price' => 'required|array',
            'supplied_price.*' => 'required|numeric|min:0',
            'supplied_amount' => 'required|array',
            'supplied_amount.*' => 'required|numeric|min:0',
            'invoice_id' => 'required|array',
            'invoice_id.*' => 'required|exists:general_invoices,id',
            // 'bank' => 'required|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            DB::beginTransaction();
            $input = $request->all();
            $order = $request->invoice_id;
            $user_id = Auth::user()->id;
            $supplier_id = $input['supplier_id'];
            // $input['purchase_order'] = $input['order_id'];
            // return respond(false, $validator->errors(), $input, 400);
            $sum = 0;

            $orderSuccessful = PurchaseInvoice::where('id', $request->purchase_order_id)->first();
            if ($orderSuccessful->is_supplied == 1) {
                return respond(false, 'Error!', 'The order has already been initially placed.', 400);
            }
            $sQ = 0;
            $sP = 0;
            // $sA = 0;
            foreach ($order as $key => $item) {
                $generalInvoice = GeneralInvoice::where('id', $item)->first();

                $uuid = $generalInvoice->item_id;
                // $uuid = $orderSuccessful->order_id;
                // check if product already exists
                $check = Stock::where('item_id', $generalInvoice->item_id)->first();
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
                    $check->item_id = $orderSuccessful->item;
                    $check->amount = $format;
                    $check->quantity = $input['quantity_supplied'][$key];
                    $check->save();
                }
                $detail = "purchase of" . ' ' . $check->item->name;
                $peramount = $input['supplied_amount'][$key];
                $price = $input['supplied_price'][$key];
                $sum += $peramount;
                $sP += $price;
                $sQ += $input['quantity_supplied'][$key];
                if ($check) {
                    $check->item->update(['price' => $price, "quantity" => $newQuantity]);
                }
                // debit the report to account in item
                // postDoubleEntries($uuid, $check->item->gl_code,  $input['supplied_amount'][$key], 0, $detail);
                // post to stock inventory
                stockInventory($generalInvoice->item_id, $oldQuantity, $newQuantity, $input['quantity_supplied'][$key], $check->id, $format, $detail);



            }
            $orderSuccessful->update([
                'date_supplied' => now(),
                'received_by' => $user_id,
                'quantity_supplied' => $sQ,
                'supplied_price' => $sP,
                'supplied_amount' => $sum,
                'is_supplied' => 1,
            ]);
            $detail = "payment on purchase order $uuid";
            // credit the bank
            // postDoubleEntries($uuid, $request->bank, 0, $sum,  $detail);
            DB::commit();
            return respond(true, 'Data Update successful!', $input, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 500);
        }
    }





    public function CreatestockRequest(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'narration' => 'required',
            'total_quantity' => 'required',
            'department_id' => 'required|exists:departments,id',
            'stock_name' => 'required|array',
            'stock_name.*' => 'required|exists:items,id',
            //'department' => 'required|exists:departments,name',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:0',

        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), $request->all(), 400);
        }


        $input = $request->all();
        // $user = User::whereHas("roles", function($q){ $q->where("name", "Admin"); })->first();
        try {
            DB::beginTransaction();
            $input = $request->all();
            $orders = Requisition::select('order_id', 'department', 'quantity')->distinct()->get();

            $count = count($orders);
            $figure = $count + 1;
            $length = strlen($figure);
            if ($length == 1) {
                $code = "000" . $figure;
            }
            if ($length == 2) {
                $code = "00" . $figure;
            }
            if ($length == 3) {
                $code = "0" . $figure;
            }
            if ($length == 4) {
                $code = $figure;
            }
            // dd($input);
            $item = $input['stock_name'];

            $user_id = Auth::user()->id;
            $month = now()->format('m');
            // dd($salesBy);
            // $supplier_id['name'] = Department::all();
            $supplier_id = $input['department_id'];
            $department = Department::where('id', $request->department_id)->first();
            $words = explode(" ", $department->name);
            $acronym = "";
            foreach ($words as $w) {
                $acronym .= $w[0];
            }
            $uuid_order = "RCCG" . '/' . $acronym . '/' . $month . '/' . rand(1000, 9999) . '/' . $code;
            $approval_level = ApprovalLevel::where('module', 2)->where('company_id', getCompanyid())->first();
            if (!$approval_level) {
                return respond(false, 'Approval level not set for this company', null, 400);
            }
            $input['approver_list'] = $approval_level->list_of_approvers;
            $a = json_decode($approval_level->list_of_approvers);
            $firstapprover = array_shift($a);
            $remainingapprover = ($a);
            $input['approval_order'] = $firstapprover;
            $input['approver_reminant'] = json_encode($remainingapprover);
            $input['approved_by'] = "[]";
            $uuid = Str::random(10);
            foreach ($item as $key => $item) {
                $stock_item = new Requisition;
                //approval level
                $stock_item->department = $supplier_id;
                $stock_item->narration = $request->narration;
                $stock_item->stock_name = $input['stock_name'][$key];
                // dd($stock_item->stock_name);
                $stock_item->quantity = $stock_request = $input['quantity'][$key];
                // $stock_item->approver_list = $stockupdate = $input['approver_list'];
                // $stock_item->approval_order = $input['approval_order'];
                // $stock_item->approver_reminant = $input['approver_reminant'];
                // $stock_item->approved_by = $input['approved_by'];
                $stock_item->request_id = $uuid;
                $stock_item->order_id = $uuid_order;
                $stock_item->request_by = Auth::user()->id;
                $stock_item->save();

                // update balance for this Stock
                // $stock = Stock::where('id',  $stock_item->stock_name)->first();
                // $stock_quantity = $stock->quantity - $stock_item->quantity;
                // $stock->update(['quantity' => $stock_quantity]);

            }

            // Mail::to($user->email)->send(new Notification(
            //     $user->name,
            //     $user->email
            // ));
            DB::commit();
            return respond(true, 'Stock Request created successfully! please wait for approval', $input, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 401);
        }
    }
    public function createRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_quantity' => 'required',
            'stock_name' => 'required|array',
            'stock_name.*' => 'required|exists:items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:0',
            'department' => 'required|exists:departments,id',
            'narration' => 'required|string',





        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), $request->all(), 400);
        }

        $input = $request->all();

        try {
            DB::beginTransaction();
            $orders = Requisition::select('order_id', 'department', 'quantity')->distinct()->get();

            $count = count($orders);
            $figure = $count + 1;
            $length = strlen($figure);
            if ($length == 1) {
                $code = "000" . $figure;
            } elseif ($length == 2) {
                $code = "00" . $figure;
            } elseif ($length == 3) {
                $code = "0" . $figure;
            } else {
                $code = $figure;
            }

            $item = $input['stock_name'];
            $user_id = Auth::user()->id;
            $month = now()->format('m');

            // Retrieve the company name and extract the first four letters
            $company = Company::find(getCompanyid());
            $company_name = $company->name;
            $company_acronym = substr($company_name, 0, 4);

            $uuid_order = $company_acronym . '/' . $month . '/' . rand(1000, 9999) . '/' . $code;
            $approval_level = ApprovalLevel::where('module', 2)->where('company_id', getCompanyid())->first();
            if (!$approval_level) {
                return respond(false, 'Approval level not set for this company', null, 400);
            }
            $input['approver_list'] = $approval_level->list_of_approvers;
            $a = json_decode($approval_level->list_of_approvers);
            $firstapprover = array_shift($a);
            $remainingapprover = ($a);
            $input['approval_order'] = $firstapprover;
            $input['approver_reminant'] = json_encode($remainingapprover);
            $input['approved_by'] = "[]";
            $uuid = Str::random(10);

            foreach ($item as $key => $item) {
                $stock_item = new Requisition;
                $stock_item->narration = $request->narration ?? '';
                $stock_item->department = $request->department;
                $stock_item->stock_name = $input['stock_name'][$key];
                $stock_item->quantity = $input['quantity'][$key];
                $stock_item->request_id = $uuid;
                $stock_item->order_id = $uuid_order;
                $stock_item->request_by = Auth::user()->id;
                $stock_item->save();
            }

            DB::commit();
            return respond(true, 'Stock Request created successfully! Please wait for approval', $input, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, $exception->getMessage(), null, 400);
        }
    }


    public function approveRequisition(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:requisition,request_id',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 400);
        }

        try {
            // Fetch the requisition by request_id
            $requisition = Requisition::where('request_id', $request->request_id)->first();


            // Check if the requisition is already approved
            if ($requisition->approval_status == 1) {
                return respond(false, 'The requisition is already approved!', null, 400);
            }

            // Begin a database transaction
            DB::beginTransaction();
            $requisition = Requisition::where('request_id', $request->request_id)->get();
            // Update the requisition status to approved
            $requisition->update(['approval_status' => 1]);

            // Commit the transaction
            DB::commit();

            // Return a success response
            return respond(true, 'Requisition approved successfully!', $requisition, 200);
        } catch (\Exception $exception) {
            // Rollback the transaction if an error occurs
            DB::rollback();

            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function deleteRequisition(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:requisition,id',
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $id = $request->id;

            $requisition = Requisition::find($id);

            $requisition->delete();

            // Return a success response
            return respond(true, 'Requisition deleted successfully!', $requisition, 200);

        } catch (\Exception $exception) {
            // Rollback the transaction if an error occurs
            DB::rollback();

            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }

    }

    public function getDeletedRequisition()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = Requisition::onlyTrashed()->orderBy('created_at', 'DESC')->get();

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

    public function restorePurchaseInvoice(Request $request)
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

    public function forceDeletePurhcaseInvoice(Request $request)
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


    // public function disapproveRequisition(Request $request)
    // {
    //     // Validate the request data
    //     $validator = Validator::make($request->all(), [
    //         'request_id' => 'required|exists:requisition,request_id',
    //     ]);
    // }
    public function disapproveRequisition(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:requisition,request_id',
            'description' => 'required',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors()->first(), null, 400);
        }

        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Get the requisition
            $requisition = getRequisition()->where('request_id', $request->request_id)->first();
            // $requisition = getRequisition()->with('description')
            //     ->where('request_id', $request->request_id)
            //     ->first();
            if ($requisition->approval_status == 2) {
                // Return a message indicating that the requisition is already disapproved
                return respond(false, 'Requisition is already disapproved.', null, 400);
            }

            // Update the requisition status to disapproved
            getRequisition()->where('request_id', $request->request_id)
                ->update(['approval_status' => 2]);

            // Create a requisition comment
            RequisitionComment::create([
                'request_id' => $request->request_id,
                'description' => $request->description,
            ]);

            // Commit the transaction
            DB::commit();

            // Return a success response
            return respond(true, 'Requisition disapproved successfully!', $requisition, 200);
        } catch (\Exception $exception) {
            // Rollback the transaction if an error occurs
            DB::rollback();

            // Return an error response
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    // public function disapproveRequisition(Request $request)
// {
//     // Validate the request data
//     $validator = Validator::make($request->all(), [
//         'request_id' => 'required|exists:requisition,request_id',
//         'description' => 'required',
//     ]);

    // // Check for validation errors
//     if ($validator->fails()) {
//         return respond(false, $validator->errors(), null, 400);
//     }

    //     try {
//         // Begin a database transaction
//         DB::beginTransaction();


    //             // Check if the requisition is already disapproved
//             $requisition = getRequisition()->where('request_id', $request->request_id)->first();
//             if ($requisition->approval_status === 0) {
//                 // Return a message indicating that the requisition is already disapproved
//                 return respond(false, 'Requisition is already disapproved.', null, 400);
//             }
//         // Check if the requisition is already disapproved
//         if ($requisition->approval_status === 2) {
//             // Return a message indicating that the requisition is already disapproved
//             return respond(false, 'Requisition is already disapproved.', null, 400);
//         }

    //             // Update the requisition status to disapproved
//             getRequisition()->where('request_id', $request->request_id)
//                 ->update(['approval_status' => 0]);
//         // Update the requisition status to disapproved
//         $requisition->update(['approval_status' => 2, 'approval_date' => null,
//         'approved_by' => null,]);

    //         RequisitionComment::create([
//             'request_id' => $RequestId,
//             'description' => $request->description,
//         ]);

    //             // Commit the transaction
//             DB::commit();

    //             // Return a success response
//             return respond(true, 'Requisition disapproved successfully!', null, 200);
//         } catch (\Exception $exception) {
//             // Rollback the transaction if an error occurs
//             DB::rollback();

    //             // Return an error response
//             return respond(false, $exception->getMessage(), null, 400);
//         }

    //         // Return an error response
//         return respond(false, $exception->getMessage(), null, 400);
//     }

    // }



    public function getStockRequests()
    {
        try {

            // ~$stockRequests = getRequisition()->get();
            $request = getRequisition()->select('request_id', 'request_by', 'department', 'approval_status', 'is_released', 'order_id')->distinct()->get();
            $request->map(function ($single) use ($request) {
                $value = Requisition::where('request_id', $single->request_id)->get();
                $total = $value->sum('quantity');
                $single->total = $total;
                $single->date = $value[0]->created_at;
            });
            // Check if any stock requests are found
            // if ($stockRequests->isEmpty()) {
            // return respond(false, 'No stock requests found', null, 400);
            // }

            // Return success response with stock requests
            return respond(true, 'Stock requests fetched successfully', $request, 200);
        } catch (\Exception $exception) {

            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function getArequisition(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|exists:requisition,request_id',

            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 400);
            }

            $requestID = $request->request_id;

            $requests = getRequisition()->where('request_id', $requestID)->get();


            return respond(true, 'Stock fetched successfully', $requests, 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return respond(false, 'Error!', $exception->getMessage(), 500);
        }
    }
    public function createStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'department' => 'required|exists:departments,name',
            'narration' => 'nullable',
            'Item Description' => 'required',
            'quantity' => 'required',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 401);
        }

        try {

            $stocks = Stock::all();
            $requisitions = Requisition::all();
            $requisitions = Requisition::create($request->all());
            return respond(true, 'Requisition created succesfully', $requisitions, 201);
        } catch (\Exception $exception) {
            return respond(false, $validator->errors(), null, 401);
        }
    }

    public function deleteOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:purchase_orders,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            // $order = PurchaseOrder::findOrFail();
            $orderId = $request->input('order_id');
            $order = PurchaseOrder::findOrFail($orderId);

            if ($order->is_supplied == 1) {
                return respond(false, 'Error!', 'Completed orders can\'t be deleted.', 400);
            }

            // If not supplied, delete the order
            $order->delete();

            DB::commit();
            return respond(true, 'Order archived successfully!', null, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }

    public function getDeletedPurchaseOrder1()
    {
        try {
            // Fetch all soft-deleted users

            $deletedInvoice = PurchaseOrder::where('company_id', auth()->user()->company_id)->onlyTrashed()->orderBy('created_at', 'DESC')->get();

            // // Check if any soft-deleted users exist
            // if ($deletedUsers->isEmpty()) {
            //     return respond(false, 'No deleted users found.', null, 404);
            // }

            // Return the soft-deleted users
            return respond(true, 'Purchase orders fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function getDeletedPurchaseOrder()
    {
        try {
            // Fetch all soft-deleted sales invoices with related soft-deleted data
            $deletedInvoice = PurchaseOrder::onlyTrashed()
                ->with([
                    'supplier',
                    'company',
                    'supporting_document' => function ($query) {
                        $query->withTrashed(); // Include soft-deleted items
                    },
                    'general_invoice' => function ($query) {
                        $query->withTrashed(); // Include soft-deleted salesinvoice
                    }
                ])
                ->orderBy('created_at', 'DESC')
                ->get();

            // Return the soft-deleted sales invoices with related data
            return respond(true, 'Archived purchase orders fetched successfully.', $deletedInvoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function restorePurchaseOrder(Request $request)
    {

        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:purchase_orders,id',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }


            // Find the soft-deleted user by ID and restore it
            $invoice = PurchaseOrder::onlyTrashed()->findOrFail($request->id);
            $invoice->restore();


            return respond(true, 'Data restored successfully.', $invoice, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function restoreAllPurchaseOrders(Request $request)
    {

        try {
            // Validate the request data


            // Find the soft-deleted user by ID and restore it
            $accounts = PurchaseOrder::onlyTrashed()->get();

            foreach ($accounts as $account) {
                $account->restore();
            }


            return respond(true, 'Data restored successfully.', $accounts, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function forceDeletePurchaseOrder(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:purchase_orders,id',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        try {
            $id = $request->id;
            // Find the user by ID (including soft-deleted users)
            $account = PurchaseOrder::withTrashed()->findOrFail($id);
            if (!$account) {
                return respond(false, 'Purchase order not found', null, 404);
            }

            $account->forceDelete();

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }
    public function forceDeleteAllPurchaseOrders()
    {

        try {

            $accounts = PurchaseOrder::withTrashed()->get();
            foreach ($accounts as $account) {
                $account->forceDelete();
            }

            return respond(true, 'Data permanently deleted successfully', null, 200);
        } catch (\Exception $exception) {
            // Handle any exceptions that occur during the deletion process
            return respond(false, $exception->getMessage(), null, 500);
        }
    }

    public function pendingPurchaseOrder()
    {

        $orders = getPurchaseOrders()->whereNull('is_supplied')->select('order_id', 'supplier_id', 'total', 'approval_status', 'approval_order')->distinct()->get();
        $orders->map(function ($single) use ($orders) {
            $value = getPurchaseOrders()->where('order_id', $single->order_id)->get();
            $single->date = $value[0]->created_at;
            $single->status = "Pending";
        });
        $data['orders'] = $orders;

        return respond(true, 'Pending Purchase Order', $data, 200);
    }

    public function deliveredPurchaseOrder()
    {
        $orders = getPurchaseOrders()->whereNotNull('is_supplied')->select('order_id', 'supplier_id', 'quantity_supplied', 'supplied_price', 'supplied_amount', 'total', 'is_supplied')->distinct()->get();

        $orders->map(function ($single) {
            $value = PurchaseOrder::where('order_id', $single->order_id)->first();
            $single->date = $value->created_at;
        });


        $data['orders'] = $orders;


        return respond(true, 'Delivered Purchase Orders', $data, 200);
    }

    public function pincardDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }
        $itemdata = StockInventory::where('item_id', $request->item_id)->get();
        return respond(true, 'Pincard details fetch successfully', $itemdata, 200);
    }

    public function releaseRequisition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:requisition,request_id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }
        $existingRequisition = Requisition::where('request_id', $request->request_id)->first();
        if ($existingRequisition->is_released == 1) {
            return respond(false, 'Requisition has already been released!', null, 400);
        }

        try {
            DB::beginTransaction();
            $allRequest = Requisition::where('request_id', $request->id)->get();
            foreach ($allRequest as $eachRequest) {
                $stock = Stock::where('item_id', $eachRequest->stock_name)->first();
                $start = $stock->quantity;
                $stock_quantity = $stock->quantity - $eachRequest->quantity;
                $stock->update(['quantity' => $stock_quantity]);

                $stock_id = $stock['id'];
                $stock_uuid = $stock['stock_uuid'];
                $user_id = Auth::user()->id;
                $status = 2;
                $changes = $eachRequest->quantity;
                $end = $stock_quantity;
                $description = 'Stock Released';
                stockInventory($eachRequest->stock_name, $start, $end, $changes, $stock_id, $stock->amount, $description);

            }
            Requisition::where('request_id', $request->request_id)->update(['is_released' => 1]);
            //Requisition::where('request_id', $request->id)->update(['stock_uuid' => $stock['stock_uuid']]);


            DB::commit();
            return respond(true, 'Requisition release successfully!', null, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 400);
        }
    }
    // public function fetchPurchaseOrder(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'beneficiary_id' => 'required|exists:beneficiaries,id',

    //         ]);

    //         if ($validator->fails()) {
    //             return respond(false, $validator->errors(), null, 400);
    //         }

    //         $beneficiary_id = $request->beneficiary_id;
    //         //$orderId = $request->order_id;

    //         $value = PurchaseOrder::where('supplier_id', $beneficiary_id)->with(['item', 'beneficiary'])
    //             ->get();
    //         return respond(true, 'Order Fetched Successfully!', $value, 200);

    //     } catch (\Exception $exception) {
    //         return respond(false, $exception->getMessage(), null, 400);
    //     }
    // }




}





