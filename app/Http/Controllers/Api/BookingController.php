<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingExpense;
use App\Models\BookingPayment;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Journal;
use App\Models\BookingLaborExpense;
use App\Models\Item;
use App\Models\GeneralInvoice;
use App\Models\MyTransactions;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

define("MERCHANTID", "2547916");
define("SERVICETYPEID", "4430731");
define("APIKEY", "1946");
define("GATEWAYURL", "https://remitademo.net/remita/exapp/api/v1/send/api/echannelsvc/merchant/api/paymentinit");
define("GATEWAYRRRPAYMENTURL", "https://remitademo.net/remita/ecomm/finalize.reg");
define("CHECKSTATUSURL", "https://remitademo.net/remita/ecomm");
define("PATH", 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
class BookingController extends Controller
{
    function generateServiceTypeId($type)
    {
        switch ($type) {
            case 1: //transcript
                return "968999908";
            case 2: //notification of result
                return "1023773151";
            case 7: //Student Transcript
                return "1023773151";
            case 4: //verification
                return "2913751810";
            case 5: //certificate
                return "2913751810";
            case 6: //convocation gown
                return "2913747776";
            case 15: //medium of instruction
                return "979996671";
            case 9: //course synopsis
                return "979996671";
            default:
                return "979996671";
        }
    }
    public function index()
    {
        validateBooking();
        $id = getCompanyid();
        // dd($id);
        $booking = Booking::where('company_id', $id)->with('expenses', 'labors', 'services')->orderBy('created_at', 'DESC')->get();
        return respond(true, 'Booking fetched successfully!', $booking, 201);
    }

    public function redirectToRemita(Request $request)
    {
        //	dd("here");
        // $request = MyRequest::where("uuid", Session::get("request_id"))->first();

        $totalAmount = $request->amount;

        $this->merchantId = MERCHANTID;

        $timestamp = DATE("dmyHis");

        $orderID = $timestamp;  //$request->id;//

        $hash_string = MERCHANTID . SERVICETYPEID . $orderID . $totalAmount . APIKEY;

        $hash = hash('sha512', $hash_string);

        $itemTimestamp = $timestamp;
        $payerName = $request->name;
        $phone = $request->phone;
        $email = $request->email;
        $responseurl = PATH . "/sample-receipt-page.php";


        $content = '
			{"serviceTypeId":"' . SERVICETYPEID . '"' . "," . '"amount":"' . $request->amount . '"' . "," . '
			  "hash":"' . $hash . '"' . "," . '
			  "orderId":"' . $orderID . '"' . "," . '
			  "payerName":"' . $payerName . " " . $payerName . '"' . "," . '
			  "payerEmail":"' . $email . '"' . ",
			  " . '"payerPhone":"' . $phone . '"
			}';
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => GATEWAYURL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $content,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: remitaConsumerKey=$this->merchantId,remitaConsumerToken=$hash",
                    "Content-Type: application/json",
                    "cache-control: no-cache"
                ),
            )
        );

        $json_response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $jsonData = substr($json_response, 7, -1);

        $response = json_decode($jsonData, true);

        // dd($json_response);
        $statuscode = $response['statuscode'];

        $statusMsg = $response['status'];

        if ($statuscode == '025') {
            $rrr = trim($response['RRR']);

            $new_hash_string = MERCHANTID . $rrr . APIKEY;

            $new_hash = hash('sha512', $new_hash_string);

            $data = [
                "gatewayURL" => GATEWAYRRRPAYMENTURL,
                "merchantID" => MERCHANTID,
                "responseURL" => $responseurl,
                "hash" => $new_hash,
                "rrr" => $rrr,
            ];
            return respond(true, 'Make payment!', $data, 201);
            return view("launch-remita", $data);
        } else {
            return respond(false, "Error Generating RRR - " . $statusMsg, null, 400);
            // Redirect::back()
            // 	->withErrors(
            // 		"Error Generating RRR - " . $statusMsg
            // 	);
        }
    }

    public function deleteFewRecords(Request $request)
    {
        $id = $request->id;
        $bookings = Booking::where('company_id', $id)->get();
        foreach ($bookings as $booking) {
            $uuid = $booking->uuid;
            Journal::where("uuid", $uuid)->delete();
            $booking->delete();
        }
        BookingPayment::where('company_id', $id)->delete();
        return respond(true, 'Booking deleted successfully!', $id, 201);
    }

    public function add()
    {
        try {
            $category = Category::where('name', 'LIKE', '%Receivables%')->whereNull('category_id')->first();
            if ($category) {
                $getOtherAssets = $category->allChildCategoryIds;
            } else {
                $getOtherAssets = [];
            }
            $expenses = Category::where('name', 'LIKE', '%booking%')->pluck('id')->toArray();
            // dd($getOtherAssts);
            // assets accounts;
            $data['assets'] = Account::where('company_id', getCompanyid())->whereIn('category_id', $getOtherAssets)->orderBy('created_at', 'desc')->get();
            // booking accounts;
            $data['bookings'] = Account::where('company_id', getCompanyid())->whereIn('category_id', $expenses)->orderBy('created_at', 'desc')->get();
            $data['items'] = Item::where('company_id', auth()->user()->event_id)->get();
            return respond(true, 'Datas needed for creating a booking successfully!', $data, 201);
            // dd($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function generateBookingOrder()
    {
        $countAllOrders = Booking::where('company_id', getCompanyid())->distinct('booking_order')->count('booking_order');
        $newcount = $countAllOrders + 10;
        $paymentLength = strlen($newcount);
        if ($paymentLength == 1) {
            $sku = "00" . $newcount;
        }
        if ($paymentLength == 2) {
            $sku = "01" . $newcount;
        }
        if ($paymentLength == 3) {
            $sku = $newcount;
        }
        if ($paymentLength >= 4) {
            $sku = $newcount;
        }
        $var = convertToUppercase(auth()->user()->name);
        $generateOrderId = $var . '' . $sku;

        return $generateOrderId;
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'particulars' => 'required',
                'description' => 'required',
                'event_date' => 'required|date',
                'start_hour' => 'required|date_format:H:i',
                'end_hour' => 'required|date_format:H:i|after:start_hour',
                'amount' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                // 'asset_account' => 'required|numeric|exists:accounts,id',
                // 'booking_account' => 'required|numeric|exists:accounts,id',
                // 'income_account' => 'required|numeric|exists:accounts,id',
                // 'product_id' => 'nullable|array',
                // 'product_id.*' => 'nullable|exists:items,id',
                // 'quantity' => 'nullable|array',
                // 'unit_price' => 'nullable|array',
                // 'amounts' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $input = $request->all();
            $newEventDate = $request->input('event_date');
            $newStartTime = $request->input('start_hour');
            $newEndTime = $request->input('end_hour');
            $overlappingEvents = Booking::where('company_id', auth()->user()->company_id)->where('event_date', $newEventDate)
                ->where(function ($query) use ($newStartTime, $newEndTime) {
                    $query->where(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newStartTime)
                            ->where('end_hour', '>', $newStartTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '>=', $newStartTime)
                            ->where('end_hour', '<=', $newEndTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newEndTime)
                            ->where('end_hour', '>', $newEndTime);
                    });
                })->get();

            if ($overlappingEvents->count() > 0) {
                return respond('error', "There is an event booked for chosen date", null, 400);
            }
            $input['uuid'] = $uuid = generate_uuid();
            if ($request->has('product_id')) {
                $input = $request->all();
                $arrays = $input['product_id'];
                $count = array_count_values($arrays);
                foreach ($arrays as $key => $array) {
                    if ($count[$array] > 1) {
                        $sum = $key + 1;
                        return respond('error', "A product exists more than once , see row $sum ", null, 400);
                    }
                }
                foreach ($input['product_id'] as $key => $product) {
                    $order = new BookingExpense;
                    $order->item_id = $product;
                    $order->price = str_replace(',', '', $input['unit_price'][$key]);
                    $order->quantity = $input['quantity'][$key];
                    $order->amount = str_replace(',', '', $input['amounts'][$key]);
                    $order->uuid = $uuid;
                    $order->save();

                }
            }
            $input = $request->except('product_id', 'quantity', 'unit_price', 'amounts');
            $input['booking_order'] = $this->generateBookingOrder();

            // dd(generateBookingOrder());
            $detail = $input['particulars'];
            $amount = $input['amount'];
            $input['balance'] = $amount;
            $input['uuid'] = $uuid;
            $assetAccount = $input['asset_account'];
            $bookingAccount = $input['booking_account'];
            $description = $input['description'];
            $input['invoice_status'] = 0;
            // dd($input);

            // dd($assetAccount, $bookingAccount);
            $input['asset_account'] = "A1";
            $input['service_id'] = "00/2024";
            $input['amount'] = $input['amount'] ?? '0.00';
            $event = Booking::create($input);

            // dd($input);
            // $input = $request->all();
            if ($request->has('item')) {
                $items = $request->item;
                foreach ($items as $key => $array) {
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
                foreach ($items as $key => $item) {
                    // dd($input['amount'][$key]);
                    GeneralInvoice::create([
                        'item_id' => $item,
                        'invoice_number' => $uuid,
                        'uuid' => $uuid,
                        'type' => "Booking",
                        'status' => 1,
                        'amount' => $input['item_amount'][$key],
                        'quantity' => $input['quantity'][$key],
                        'tax_id' => $input['tax_id'][$key] ?? "",
                        'discount' => $input['discount'][$key] ?? "",
                    ]);
                    $getDetails = Item::find($item);
                    $debitGl = $getDetails->account_receivable; //receivable account
                    $creditGl = $getDetails->sales_gl; //sales income account
                    if ($debitGl && $creditGl) {
                        //$glcode = $input['item_id'][$key];
                        $amount = $input['item_amount'][$key];
                        $uuid = $input['uuid'];
                        postDoubleEntries($uuid, $creditGl, 0, $amount, $detail); // credit sales  income
                        postDoubleEntries($uuid, $debitGl, $amount, 0, $detail); // debit receivable  account
                    }
                }
            }
            // debit asset account
            postDoubleEntries($uuid, $assetAccount, $amount, 0, $detail);
            // credit booking account
            postDoubleEntries($uuid, $bookingAccount, 0, $amount, $detail);
            //post to receivable
            insertTransaction($amount, $amount, 0, now(), $detail, $input['booking_order'], 1, $uuid, $request->event_date, "Booking");
            validateBooking();
            DB::commit();
            return respond(true, 'New Booking added successfully!', $event, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function createNew(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'particulars' => 'required',
                'description' => 'required',
                'event_date' => 'required|date',
                'start_hour' => 'required|date_format:H:i',
                'end_hour' => 'required|date_format:H:i|after:start_hour',
                'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                // 'asset_account' => 'required|numeric|exists:accounts,id',
                'service_id' => 'required|numeric|exists:items,id',
                'amount_to_pay' => 'nullable|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'payment_mode' => 'nullable|exists:mode_of_savings,id',
                'bank' => 'nullable|exists:accounts,id',
                'teller_number' => 'nullable',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $input = $request->all();
            // dd($input);
            $newEventDate = $request->input('event_date');
            $newStartTime = $request->input('start_hour');
            $newEndTime = $request->input('end_hour');
            $overlappingEvents = Booking::where('company_id', auth()->user()->company_id)->where('event_date', $newEventDate)
                ->where(function ($query) use ($newStartTime, $newEndTime) {
                    $query->where(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newStartTime)
                            ->where('end_hour', '>', $newStartTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '>=', $newStartTime)
                            ->where('end_hour', '<=', $newEndTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newEndTime)
                            ->where('end_hour', '>', $newEndTime);
                    });
                })->get();

            if ($overlappingEvents->count() > 0) {
                return respond('error', "There is an event booked for chosen date and time", null, 400);
            }
            //check if necessary params has been specified for the incoming product
            $incomingProductId = $request->service_id;
            $incomingProduct = Item::find($incomingProductId);
            if (!$incomingProduct->advance_payment_gl) {
                return respond('error', "Advance payment account has not been specified for selected service!", null, 400);
            }
            if (!$incomingProduct->sales_gl) {
                return respond('error', "Sales account has not been specified for selected service!", null, 400);
            }
            if (!$incomingProduct->account_receivable) {
                return respond('error', "Receivable account has not been specified for selected service!", null, 400);
            }
            $input['uuid'] = $uuid = generate_uuid();
            if ($request->has('product_id')) {
                $input = $request->all();
                $arrays = $input['product_id'];
                $count = array_count_values($arrays);
                foreach ($arrays as $key => $array) {
                    if ($count[$array] > 1) {
                        $sum = $key + 1;
                        return respond('error', "A product exists more than once , see row $sum ", null, 400);
                    }
                }
                foreach ($input['product_id'] as $key => $product) {
                    $order = new BookingExpense;
                    $order->item_id = $product;
                    $order->price = str_replace(',', '', $input['unit_price'][$key]);
                    $order->quantity = $input['quantity'][$key];
                    $order->amount = str_replace(',', '', $input['amounts'][$key]);
                    $order->uuid = $uuid;
                    $order->save();

                }
            }
            $input = $request->except('product_id', 'quantity', 'unit_price', 'amounts', 'amount_to_pay', 'payment_mode', 'teller_number', 'bank');
            $input['booking_order'] = $this->generateBookingOrder();

            // dd(generateBookingOrder());
            $detail = $input['particulars'];
            $amount = $input['amount'];
            $input['balance'] = $amount;
            $input['uuid'] = $uuid;
            $input['asset_account'] = $incomingProduct->account_receivable;
            $input['booking_account'] = $advanceAccount = $incomingProduct->advance_payment_gl;
            $input['income_account'] = $incomingProduct->sales_gl;
            // $bookingAccount = $input['booking_account'];
            $description = $input['description'];
            $input['invoice_status'] = 0;
            // dd($input);
            // Calculate partial payment if amount_to_pay is provided
            if ($request->has('amount_to_pay') && $request->input('amount_to_pay') != null) {
                $amountToPay = $request->input('amount_to_pay');
                $input['paid'] = $amountToPay;
                $input['balance'] = $amount - $amountToPay;
            } else {
                $input['paid'] = 0;
                $input['balance'] = $amount;
            }
            if ($request->has('bank')) {
                $input['bank'] = $bankAccount = $request->input('bank');
            }
            if ($request->has('teller_number')) {
                $input['teller_number'] = $request->input('teller_number');
            }

            // dd($assetAccount, $bookingAccount);
            $event = Booking::create($input);
            // dd($input);
            // $input = $request->all();
            if ($request->has('item')) {
                $items = $request->item;
                foreach ($items as $key => $array) {
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
                foreach ($items as $key => $item) {
                    // dd($input['amount'][$key]);
                    GeneralInvoice::create([
                        'item_id' => $item,
                        'invoice_number' => $uuid,
                        'uuid' => $uuid,
                        'type' => "Booking",
                        'status' => 1,
                        'amount' => $input['item_amount'][$key],
                        'quantity' => $input['quantity'][$key],
                        'tax_id' => $input['tax_id'][$key] ?? "",
                        'discount' => $input['discount'][$key] ?? "",
                    ]);
                    $getDetails = Item::find($item);
                    $debitGl = $getDetails->account_receivable; //receivable account
                    $creditGl = $getDetails->sales_gl; //sales income account
                    if ($debitGl && $creditGl) {
                        //$glcode = $input['item_id'][$key];
                        $amount = $input['item_amount'][$key];
                        $uuid = $input['uuid'];
                        postDoubleEntries($uuid, $creditGl, 0, $amount, $detail); // credit sales  income
                        postDoubleEntries($uuid, $debitGl, $amount, 0, $detail); // debit receivable  account
                    }
                }
            }
            if ($request->has('amount_to_pay') && $request->input('amount_to_pay') != null) {
                $amount = $request->input('amount_to_pay');
                // debit bank account
                postDoubleEntries($uuid, $bankAccount, $amount, 0, $detail);
                // credit booking advance
                postDoubleEntries($uuid, $advanceAccount, 0, $amount, $detail);
                // generate invoice for this payment
                insertOnBookingPayments($amount, $event->id, $request->bank, $request->payment_mode, $request->teller_number);
            }
            validateBooking();
            DB::commit();
            return respond(true, 'New Booking added successfully!', $event, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }


    public function update(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'booking_id' => 'required|exists:bookings,id',
                'product_id' => 'nullable|array',
                'product_id.*' => 'nullable|exists:items,id',
                'quantity' => 'nullable|array',
                'unit_price' => 'nullable|array',
                'amounts' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors()->first(), null, 400);
            }
            $input = $request->all();
            $currentDateTime = Carbon::now();
            $booking = Booking::where('id', $request->booking_id)->first();
            $eventDate = $booking->event_date;
            $uuid = $booking->uuid;
            // dd($currentDateTime->toDateString());
            $input = $request->all();
            $arrays = $input['product_id'];
            $count = array_count_values($arrays);
            foreach ($arrays as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond('error', "A product exists more than once , see row $sum ", null, 400);
                }
            }
            $seeIfExists = BookingExpense::where('uuid', $uuid)->get();
            foreach ($seeIfExists as $exist) {
                $item = Item::find($exist->item_id);
                $detail = $item->name . ' ' . "refunded for $booking->description";
                $checkM = Stock::where('item_id', $exist->item_id)->first();
                $oldQuantity = $checkM->quantity;
                $newQuantity = $oldQuantity + $exist->quantity;
                $checkM->update(['quantity' => $newQuantity]);
                stockInventory($exist->item_id, $oldQuantity, $newQuantity, $exist->quantity, $checkM->id, $exist->amount, $detail);
                $exist->delete();
            }
            $sum = 0;
            foreach ($input['product_id'] as $key => $product) {
                $sum += str_replace(',', '', $input['amounts'][$key]);
                // update stock
                $item = Item::find($product);
                $detail = $item->name . ' ' . "used during $booking->description";
                $checkM = Stock::where('item_id', $product)->first();
                $oldQuantity = $checkM->quantity;
                $newQuantity = $oldQuantity - $input['quantity'][$key];
                $checkM->update(['quantity' => $newQuantity]);
                stockInventory($product, $oldQuantity, $newQuantity, $input['quantity'][$key], $checkM->id, $input['amounts'][$key], $detail);
                // $checkM->item->update(["quantity" => $newQuantity]);
                $order = new BookingExpense;
                $order->item_id = $product;
                $order->price = str_replace(',', '', $input['unit_price'][$key]);
                $order->quantity = $input['quantity'][$key];
                $order->amount = str_replace(',', '', $input['amounts'][$key]);
                $order->uuid = $uuid;
                $order->save();
            }
            $prev = $booking->total_labor;
            $balance = $prev + $sum;
            $booking->update(['total_item_cost' => $sum, "total_cost" => $balance]);
            DB::commit();
            return respond(true, 'Expenses Added Successfully!', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function updateNew(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'id' => 'required|exists:bookings,id',
                'service_id' => 'required|numeric|exists:items,id',

            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $incomingProductId = $request->service_id;
            $incomingProduct = Item::find($incomingProductId);
            if (!$incomingProduct->advance_payment_gl) {
                return respond('error', "Advance payment account has not been specified!", null, 400);
            }
            if (!$incomingProduct->sales_gl) {
                return respond('error', "Sales account has not been specified!", null, 400);
            }
            if (!$incomingProduct->account_receivable) {
                return respond('error', "Receivable account has not been specified!", null, 400);
            }
            $input = $request->all();
            $booking = Booking::find($request->id);
            $input['asset_account'] = $incomingProduct->account_receivable;
            $input['booking_account'] = $advanceAccount = $incomingProduct->advance_payment_gl;
            $input['income_account'] = $incomingProduct->sales_gl;
            // dd($input);
            $booking->update($input);

            DB::commit();
            return respond(true, 'Booking Added Successfully!', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function addLaborExpenses(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'booking_id' => 'required|exists:bookings,id',
                'description' => 'required|array',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'transaction_type' => 'required|array',
                'transaction_type.*' => 'required',
                'transaction_date' => 'required|array',
                'transaction_date.*' => 'required|date',
                'account_id' => 'required|array',
                'account_id.*' => 'required|exists:accounts,id',
                'total_credit' => 'required|numeric',
                'total_debit' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $input = $request->all();
            $booking = Booking::where('id', $request->booking_id)->first();
            $uuid = $booking->uuid;
            $input = $request->all();
            $arrays = $input['description'];
            $arrayAmount = $input['amount'];
            $count = array_count_values($arrays);
            if (count($arrayAmount) != count($arrays)) {
                return respond('error', "Invalid parameters", null, 400);
            }
            if ($request->total_credit != $request->total_debit) {
                return respond('error', "Transaction not balance!", null, 400);
            }
            foreach ($arrays as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond('error', "A description exists more than once , see row $sum ", null, 400);
                }
            }
            $seeIfExists = BookingLaborExpense::where('uuid', $uuid)->get();
            foreach ($seeIfExists as $exist) {
                $exist->delete();
            }
            $sum = 0;
            foreach ($input['description'] as $key => $product) {
                $sum += $input['amount'][$key];
                $labor = new BookingLaborExpense;
                $labor->description = $product;
                $labor->amount = str_replace(',', '', $input['amount'][$key]);
                $labor->transaction_date = $input['transaction_date'][$key];
                $labor->transaction_type = $input['transaction_type'][$key];
                $labor->account_id = $input['account_id'][$key];
                $labor->uuid = $uuid;
                $labor->booking_id = $booking->id;
                $labor->save();
                if ($input['transaction_type'][$key] == 1) {
                    $cAmount = 0;
                    $dAmount = $input['amount'][$key];
                } else {
                    $cAmount = $input['amount'][$key];
                    $dAmount = 0;
                }
                postDoubleEntries($uuid, $input['account_id'][$key], $dAmount, $cAmount, $product, $input['transaction_date'][$key]);
            }
            $prev = $booking->total_item_cost;
            $balance = $prev + $sum;
            $booking->update(['total_labor' => $sum, "total_cost" => $balance]);

            DB::commit();
            return respond(true, 'Labor expenses Added Successfully!', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function addNewExpenses(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            DB::beginTransaction();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|exists:bookings,id',
                'item_id' => 'required|array',
                'item_id.*' => 'required|exists:items,id',
                'price' => 'required|array',
                'price.*' => 'required|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'total_amount' => 'required|numeric',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric',
                'inventory_gl' => 'required|array',
                'inventory_gl.*' => 'required|exists:accounts,id',
                'cost_of_good_gl' => 'required|array',
                'cost_of_good_gl.*' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $input = $request->all();
            foreach ($request->item_id as  $key => $itemId) {
                $item = Item::where('id', $itemId)->where('company_id', $companyId)->first();
                if (!$item) {
                    return respond('error', 'One or more items do not belong to your company.', null, 400);
                }
                $stock = Stock::where('item_id', $itemId)->first();
                if (!$stock || $stock->quantity < $input['quantity'][$key]) {
                    return respond('error', "Insufficient stock for item $item->name" , null, 400);
                }
            }
            $booking = Booking::where('id', $request->booking_id)->first();
            $uuid = $booking->uuid;

            $incomingProduct = $request->item_id;

            // Deduct quantities and log stock changes
            foreach ($incomingProduct as $key => $product) {
                $item = Item::find($product);
                $stock = Stock::where('item_id', $product)->first();
                // Log the old and new quantities
                $oldStockQuantity = $stock->quantity;
                $newStockQuantity = $oldStockQuantity - $input['quantity'][$key];
                // Update stock quantity
                $stock->quantity = $newStockQuantity;
                $stock->save();

                // Check item quantity
                $finditem = Item::where('id', $product)->first();

                // Log the old and new quantities
                $oldItemQuantity = $finditem->quantity;
                $newItemQuantity = $oldItemQuantity - $input['quantity'][$key];

                // Update item quantity
                $finditem->quantity = $newItemQuantity;
                $finditem->save();

                // Log the item inventory change
                stockInventory($product, $oldItemQuantity, $newItemQuantity, $input['quantity'][$key], $stock->id, $input['price'][$key], 'Item updated for item: ' . $finditem->name);
                $dbGl = $input['inventory_gl'][$key];
                $crGl = $input['cost_of_good_gl'][$key];
                $individualAmount = $input['amount'][$key];
                $buildDescription = $input['quantity'][$key] . ' ' . "quantity of $finditem->name used for booking($booking->particulars)";
                //debit inventory gl
                postDoubleEntries($uuid, $dbGl, $individualAmount, 0, $buildDescription);
                //credit cost of goods gl
                postDoubleEntries($uuid, $crGl, 0, $individualAmount, $buildDescription);

                // Insert expenses into BookingExpense table
                $order = new BookingExpense;
                $order->item_id = $product;
                $order->price = str_replace(',', '', $input['price'][$key]);
                $order->quantity = $input['quantity'][$key];
                $order->inventory_gl = $dbGl;
                $order->cost_of_good_gl = $crGl;
                $order->amount = str_replace(',', '', $input['amount'][$key]);
                $order->total_amount = $request->total_amount;
                $order->uuid = $uuid;
                $order->save();
            }


            DB::commit();
            return respond(true, 'Expenses Added Successfully!', $request->all(), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function addNewExpensesOld(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'booking_id' => 'required|exists:bookings,id',
                'item_id' => 'required|array',
                'item_id.*' => 'required|exists:items,id',
                'price' => 'required|array',
                'price.*' => 'required|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'total_amount' => 'required|numeric',
                'quantity' => 'required|array',
                'quantity.*' => 'required',
                'inventory_gl' => 'required|array',
                'inventory_gl.*' => 'required|exists:accounts,id',
                'cost_of_good_gl' => 'required|array',
                'cost_of_good_gl.*' => 'required|exists:accounts,id',

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

            $input = $request->all();
            $booking = Booking::where('id', $request->booking_id)->first();
            $uuid = $booking->uuid;
            $input = $request->all();
            $incomingProduct = $request->item_id;
            foreach ($incomingProduct as $key => $product) {
                $order = new BookingExpense;
                $order->item_id = $product;
                $order->price = str_replace(',', '', $input['price'][$key]);
                $order->quantity = $input['quantity'][$key];
                $order->inventory_gl = $input['inventory_gl'][$key];
                $order->cost_of_good_gl = $input['cost_of_good_gl'][$key];
                $order->amount = str_replace(',', '', $input['amount'][$key]);
                $order->total_amount = $request->total_amount;
                $order->uuid = $uuid;
                $order->save();
            }

            DB::commit();
            return respond(true, 'Expenses Added Successfully!', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function addNewExpensesOld1(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            DB::beginTransaction();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|exists:bookings,id',
                'item_id' => 'required|array',
                'item_id.*' => 'required|exists:items,id',
                'price' => 'required|array',
                'price.*' => 'required|numeric',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',
                'total_amount' => 'required|numeric',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric',
                'inventory_gl' => 'required|array',
                'inventory_gl.*' => 'required|exists:accounts,id',
                'cost_of_good_gl' => 'required|array',
                'cost_of_good_gl.*' => 'required|exists:accounts,id',
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
            $booking = Booking::where('id', $request->booking_id)->first();
            $uuid = $booking->uuid;
            $input = $request->all();
            $incomingProduct = $request->item_id;

            foreach ($incomingProduct as $key => $product) {
                $stock = Stock::where('item_id', $product)->first();
                if (!$stock || $stock->quantity < $input['quantity'][$key]) {
                    return respond('error', 'Insufficient stock for item ID: ' . $product, null, 403);
                }
                $stock->quantity -= $input['quantity'][$key];
                $stock->save();
            }
            foreach ($incomingProduct as $key => $product) {
                $finditem = Item::where('id', $product)->first();
                if (!$finditem || $finditem->quantity < $input['quantity'][$key]) {
                    return respond('error', 'Insufficient stock for item ID: ' . $product, null, 403);
                }
                $finditem->quantity -= $input['quantity'][$key];
                $finditem->save();
            }

            // Insert expenses into BookingExpense table
            foreach ($incomingProduct as $key => $product) {
                $order = new BookingExpense;
                $order->item_id = $product;
                $order->price = str_replace(',', '', $input['price'][$key]);
                $order->quantity = $input['quantity'][$key];
                $order->inventory_gl = $input['inventory_gl'][$key];
                $order->cost_of_good_gl = $input['cost_of_good_gl'][$key];
                $order->amount = str_replace(',', '', $input['amount'][$key]);
                $order->total_amount = $request->total_amount;
                $order->uuid = $uuid;
                $order->save();
            }

            DB::commit();
            return respond(true, 'Expenses Added Successfully!', $request->all(), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }



    public function addLaborExpenses1(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            $validator = Validator::make($data, [
                'booking_id' => 'required|exists:bookings,id',
                'description' => 'required|array',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric',

            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }
            $input = $request->all();
            $booking = Booking::where('id', $request->booking_id)->first();
            $uuid = $booking->uuid;
            $input = $request->all();
            $arrays = $input['description'];
            $arrayAmount = $input['amount'];
            $count = array_count_values($arrays);
            if (count($arrayAmount) != count($arrays)) {
                return respond('error', "Invalid parameters", null, 400);
            }
            foreach ($arrays as $key => $array) {
                if ($count[$array] > 1) {
                    $sum = $key + 1;
                    return respond('error', "A description exists more than once , see row $sum ", null, 400);
                }
            }
            $seeIfExists = BookingLaborExpense::where('uuid', $uuid)->get();
            foreach ($seeIfExists as $exist) {
                $exist->delete();
            }
            $sum = 0;
            foreach ($input['description'] as $key => $product) {
                $sum += $input['amount'][$key];
                $labor = new BookingLaborExpense;
                $labor->description = $product;
                $labor->amount = str_replace(',', '', $input['amount'][$key]);
                $labor->uuid = $uuid;
                $labor->booking_id = $booking->id;
                $labor->save();
            }
            $prev = $booking->total_item_cost;
            $balance = $prev + $sum;
            $booking->update(['total_labor' => $sum, "total_cost" => $balance]);

            DB::commit();
            return respond(true, 'Labor expenses Added Successfully!', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function UpdateBooking(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|exists:bookings,id',
                'particulars' => 'required',
                'description' => 'required',
                'event_date' => 'required|date',
                'start_hour' => 'required|date_format:H:i',
                'end_hour' => 'required|date_format:H:i|after:start_hour',
                'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'asset_account' => 'required|numeric|exists:accounts,id',
                'booking_account' => 'required|numeric|exists:accounts,id',
                'product_id' => 'nullable|array',
                'product_id.*' => 'nullable|exists:items,id',
                'quantity' => 'nullable|array',
                'unit_price' => 'nullable|array',
                'amounts' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 404);
            }
            $input = $request->all();
            $newEventDate = $request->input('event_date');
            $newStartTime = $request->input('start_hour');
            $newEndTime = $request->input('end_hour');
            $overlappingEvents = Booking::where('company_id', auth()->user()->event_id)->where('event_date', $newEventDate)
                ->where(function ($query) use ($newStartTime, $newEndTime) {
                    $query->where(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newStartTime)
                            ->where('end_hour', '>', $newStartTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '>=', $newStartTime)
                            ->where('end_hour', '<=', $newEndTime);
                    })->orWhere(function ($query) use ($newStartTime, $newEndTime) {
                        $query->where('start_hour', '<', $newEndTime)
                            ->where('end_hour', '>', $newEndTime);
                    });
                })->get();

            if ($overlappingEvents->count() > 0) {
                return api_request_response(
                    "error",
                    "There is an event booked for chosen date",
                    bad_response_status_code()
                );
            }
            $input['uuid'] = $uuid = generate_uuid();
            if ($request->has('product_id')) {
                $input = $request->all();
                $arrays = $input['product_id'];
                $count = array_count_values($arrays);
                foreach ($arrays as $key => $array) {
                    if ($count[$array] > 1) {
                        $sum = $key + 1;
                        return api_request_response(
                            'error',
                            "A product exists more than once , see row $sum ",
                            bad_response_status_code()
                        );
                    }
                }
                foreach ($input['product_id'] as $key => $product) {
                    $order = new BookingExpense;
                    $order->item_id = $product;
                    $order->price = str_replace(',', '', $input['unit_price'][$key]);
                    $order->quantity = $input['quantity'][$key];
                    $order->amount = str_replace(',', '', $input['amounts'][$key]);
                    $order->uuid = $uuid;
                    $order->save();
                }
            }
            $input = $request->except('product_id', 'quantity', 'unit_price', 'amounts');
            $input['booking_order'] = $this->generateBookingOrder();
            $detail = $input['particulars'];
            $amount = $input['amount'];
            $input['balance'] = $amount;
            $input['uuid'] = $uuid;
            $assetAccount = $input['asset_account'];
            $bookingAccount = $input['booking_account'];
            $description = $input['description'];

            $event = Booking::update($input);
            // debit asset account
            postDoubleEntries($uuid, $assetAccount, $amount, 0, $detail);
            // credit booking account
            postDoubleEntries($uuid, $bookingAccount, 0, $amount, $detail);
            // dd($last);
            validateBooking();
            DB::commit();
            return respond(true, 'Booking updated successfully!', $event, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }


    public function pending(Request $request)
    {

        $data = Booking::where('company_id', getCompanyid())->where('balance', '!=', 0)->get();
        return respond(true, 'Pending bookings fetched successfully!', $data, 201);
    }

    public function details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:bookings,id',
        ]);
        if ($validator->fails()) {
            return respond(false, 'Error!', $validator->errors(), 400);
        }
        $id = $request->id;
        $booking = Booking::find($id);
        return respond(true, 'Booking details fetched successfully!', $booking, 201);
    }


    public function makePayment(Request $request)
    {
        validateBooking();
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:bookings,id',
                'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/|min:1',
                'debit' => 'required|numeric|exists:accounts,id',
                'transaction_date' => 'required|date',
                'teller_number' => 'nullable',
                'payment_mode' => 'nullable|exists:mode_of_savings,id',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $id = $request->id;
            $amount = $request->amount;
            $booking = Booking::find($id);
            // get balance amount
            $balance = $booking->balance;
            $uuid = $booking->uuid;
            if (!$booking->service_id) {
                return respond(false, "No service selected for this booking", null, 400);
            }
            if ($amount > $balance) {
                return respond(false, "You can't pay more than your balance !", $amount, 400);
            }
            $debit = $request->debit;
            $credit = $booking->booking_account;
            $income = $booking->income_account;
            $detail = $booking->description;

            $booking->update(['paid' => $amount + $booking->paid, "balance" => $balance - $amount]);
            insertOnBookingPayments($amount, $booking->id, $request->debit, $request->payment_mode, $request->teller_number, $request->transaction_date);
            // debit bank account
            postDoubleEntries($uuid, $debit, $amount, 0, $detail, $request->transaction_date);
            //post to receipt
            insertTransaction($amount, 0, 0, $request->transaction_date, $detail, $booking->booking_order, 3, $uuid, $booking->event_date, "Booking");
            if ($booking->status = 'completed') {
                // credit income account
                postDoubleEntries($uuid, $income, 0, $amount, $detail, $request->transaction_date);
            } else {
                // credit advance booking account
                postDoubleEntries($uuid, $credit, 0, $amount, $detail, $request->transaction_date);
               // check receivale and update
                $receivable = MyTransactions::where('uuid', $uuid)->where('type', 1)->first();
                if ($receivable) {
                    $receivable->update(['paid' => $amount + $receivable->amount_paid, "balance" => $receivable->balance - $amount]);
                }
            }
            DB::commit();
            return respond(true, 'Transaction successful!', $amount, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function getPayments()
    {
        $id = getCompanyid();
        // dd($id);
        $booking = BookingPayment::where('company_id', $id)->with(['booking'])->orderBy('created_at','DESC')->get();
        return respond(true, 'Booking payments fetched successfully!', $booking, 201);
    }


    public function fetchSoftdelete()
    {
        $deleted = Booking::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
        return respond(true, 'Fetch archieved bookings successfully!', $deleted, 201);
    }
    public function restoreSingleSoftdelete(Request $request)
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            $department = Booking::withTrashed()->find($request->id);
            $bookingUUID = $department->uuid;

            $bookingExpenses = BookingLaborExpense::where('uuid', $bookingUUID)->withTrashed()->restore();
            // dd($bookingExpenses);

            if ($department && $department->trashed()) {
                $department->restore();
                DB::commit();
                return respond(true, 'Archieved bookings restored successfully!', $department, 200);
            } elseif ($department) {
                return respond(false, 'Archieved bookings is not deleted!', null, 400);
            } else {
                return respond(false, 'Archieved bookings not found!', null, 404);
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction if any error occurs
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function restoreSoftdelete()
    {
        DB::beginTransaction();

        try {
            $deletedDepartments = Booking::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
            if ($deletedDepartments->isEmpty()) {
                return respond(false, 'No archieved bookings found to restore!', null, 404);
            }
            Booking::where('company_id', auth()->user()->company_id)->onlyTrashed()->restore();
            foreach ($deletedDepartments as $department) {
                $bookingUUID = $department->uuid;
                BookingLaborExpense::where('uuid', $bookingUUID)->onlyTrashed()->restore();
            }
            // dd($bookingUUID);
            BookingLaborExpense::where('uuid', $bookingUUID)->onlyTrashed()->restore();
            DB::commit();
            return respond(true, 'All archieved bookings restored successfully!', $deletedDepartments, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }
    public function deleteSingleSoftdelete(Request $request)
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            $department = Booking::withTrashed()->find($request->id);
            $bookingUUID = $department->uuid;

            // $bookingExpenses = BookingLaborExpense::where('uuid', $bookingUUID)->trashed->forceDelete();
            $bookingExpenses = BookingLaborExpense::withTrashed()->where('uuid', $bookingUUID)->forceDelete();


            if ($department && $department->trashed()) {
                $department->forceDelete();
                DB::commit();
                return respond(true, 'Archieved bookings permanently deleted successfully!', $department, 200);
            } elseif ($department) {
                return respond(false, 'Archieved bookings is not soft-deleted!', null, 400);
            } else {
                return respond(false, 'Archieved bookings not found!', null, 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteSoftdelete()
    {
        DB::beginTransaction();

        try {
            $deletedDepartments = Booking::where('company_id', auth()->user()->company_id)->onlyTrashed()->get();
            if ($deletedDepartments->isEmpty()) {
                return respond(false, 'No archieved bookings found to permanently delete!', null, 404);
            }
            foreach ($deletedDepartments as $department) {
                $bookingUUID = $department->uuid;
                BookingLaborExpense::where('uuid', $bookingUUID)->onlyTrashed()->forceDelete();
            }
            Booking::where('company_id', auth()->user()->company_id)->onlyTrashed()->forceDelete();
            DB::commit();
            return respond(true, 'All archieved bookings permanently deleted successfully!', null, 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction if any error occurs
            return respond(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteBooking(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:bookings,id',
            ]);
            if ($validator->fails()) {
                return respond('error', $validator->errors(), null, 400);
            }

            $book = Booking::find($request->id);
            if (auth()->user()->company_id !== $book->company_id) {
                return respond('error', 'You do not have permission to delete this booking', null, 403);
            }
            if ($book->paid > 0) {
                return respond(false, 'You cannot delete a booking with payment.', null, 400);
            }

            $bookingUUID = $book->uuid;
            // dd($bookingUUID, count(BookingExpense::all()));
            $bookingExpenses = BookingLaborExpense::where('uuid', $bookingUUID)->get();
            // dd($bookingExpenses);

            foreach ($bookingExpenses as $expense) {
                $expense->delete();
            }
            $book->delete();
            DB::commit();
            return respond(true, 'Booking archived successfully', $book, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return respond(false, $e->getMessage(), null, 400);
        }
    }




}
