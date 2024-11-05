<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Category;
use App\Models\Sale;
use App\Models\StoreProduct;
use App\Models\User;
use App\Models\State;
use App\Models\LGA;
use App\Models\Rating;
use App\Traits\PincardTrait;
use App\Traits\Payment;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use function App\Helpers\generate_uuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Mail\OTPMail;

class RequestController extends Controller
{
    use PincardTrait;
    function respond($status, $message, $data, $code)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    public function sendOtp(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $email = $request->email;
        $check = User::where('email', $request->email)->first();
        if (!$check) {
            return $this->respond('error', "No record with provided email", null, 400);
        }
        $otp = mt_rand(10000, 99999); //Str::random(6);
        $encrypt = Hash::make($otp);
        // $decrypt = decrypt($encrypt);
        try {
            Mail::to($email)->send(new OTPMail($otp));
            DB::table('password_resets')->where('email', $email)->delete();
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $encrypt,
                'created_at' => now()
            ]);
            return $this->respond('success', "Token sent to email", $email, 201);
        } catch (\Exception $e) {
            return $this->respond('error', ['message' => $e->getMessage()], null, 400);
        }
    }

    public function verifyOtp(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'otp' => 'required|',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $email = $request->email;
        $otp = $request->otp;
        $user =  DB::table('password_resets')->where('email', $email)->latest()->first();
        if (!$user) {
            return $this->respond('error', "No record with provided email", null, 400);
        }
        if (!Hash::check($request->otp, $user->token)) {
            return $this->respond('error', "Invalid otp provided", null, 400);
        }
        return $this->respond('success', "Token validated successfully! ", $otp, 201);
    }

    public function changePasswordOtp(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required|email',
            'otp' => 'required|',
            'password_confirmation' => 'required',
            'password' => ['required',  'confirmed', 'string', 'min:8'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
        $email = $request->email;
        $otp = $request->otp;
        $user =  DB::table('password_resets')->where('email', $email)->latest()->first();
        if (!$user) {
            return $this->respond('error', "No record with provided email", null, 400);
        }
        if (!Hash::check($request->otp, $user->token)) {
            return $this->respond('error', "Invalid otp provided", null, 400);
        }
        try {
            $userUpdate = User::where('email', $email)->first();
            $userUpdate->update(['password' => bcrypt($request->password), 'is_first_time' => 0]);
            DB::table('password_resets')->where('email', $email)->delete();
            return response(
                [
                    "status" => "success",
                    "data" => $userUpdate,
                    "message" => 'Password Changed Successfully! Kindly proceed to login'
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changePassword(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password_confirmation' => 'required',
            'password' => ['required',  'confirmed', 'string', 'min:8'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
        $email = $request->email;
        $user =  DB::table('password_resets')->where('email', $email)->latest()->first();
        if (!$user) {
            return $this->respond('error', "You are not validated", null, 400);
        }
        try {
            $userUpdate = User::where('email', $email)->first();
            $userUpdate->update(['password' => bcrypt($request->password), 'is_first_time' => 0]);
            DB::table('password_resets')->where('email', $email)->delete();
            return response(
                [
                    "status" => "success",
                    "data" => $userUpdate,
                    "message" => 'Password Changed Successfully! Kindly proceed to login'
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function userId()
    {
        return Auth::User()->id;
    }
    public function category()
    {
        $categories = Category::all();
        return $this->respond(true, 'Categories Data Fetched Successfully', $categories, 201);
    }
    public function getStores()
    {
        $stores = Store::all();
        return $this->respond(true, 'Stores Data Fetched Successfully', $stores, 201);
    }

    public function getCategoryProducts(Request $request)
    {
        $category = Category::with(['products'])->get();
        return $this->respond(true, 'Categories with their respective products fetched successfully', $category, 201);
    }

    public function getItemDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $product = Item::where('id', $request->id)->with(['images'])->first();
        $product['otherProducts'] = Item::where('category', $product->category)->where('approval_status', 1)->with(['images'])->get();
        return $this->respond(true, 'product details with its respective products category fetched successfully', $product, 201);
    }
    public function getCategoryByStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $id = $request->store_id;
        $store = Store::where('id', $id)->first();
        if (!$store) {
            return $this->respond('error', "No data for the supplied store", null, 400);
        }
        $getStoreProducts = StoreProduct::where('store_id', $id)->pluck('product_id')->toArray();
        $getProductCategories = Item::whereIn('id', $getStoreProducts)->where('approval_status', 1)->pluck('category')->toArray();
        $categories = Category::whereIn('id', $getProductCategories)->get();
        $categories['store'] = $store;
        return $this->respond(true, "$store->store_name Category Data Fetched Successfully", $categories, 201);
    }
    public function getProductsByStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $id = $request->store_id;
        $store = Store::where('id', $id)->first();
        if (!$store) {
            return $this->respond('error', "No data for the supplied store", null, 400);
        }
        $products = StoreProduct::where('store_id', $id)->with(['product'])->get();
        $products['store'] = $store;
        return $this->respond(true, "$store->store_name Product Data Fetched Successfully", $products, 201);
    }
    public function getProductsByCategoryStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
            'category_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $id = $request->store_id;
        $category_id = $request->category_id;
        $store = Store::where('id', $id)->first();
        $category = Category::where('id', $category_id)->first();
        if (!$store) {
            return $this->respond('error', "No data for the supplied store", null, 400);
        }
        // dd($category);
        $getProductCategories = Item::where('approval_status', 1)->where('category', $category_id)->pluck('id')->toArray();
        // dd($getProductCategories);
        $products = StoreProduct::whereIn('product_id', $getProductCategories)->where('store_id', $id)->with(['product'])->get();
        $products['store'] = $store;
        $products['cat'] = $category;
        return $this->respond(true, "$store->store_name, $category->name Category Data Fetched Successfully", $products, 201);
    }
    public function customerCart()
    {
        $twoHoursAgo = Carbon::now()->subHours(2);
        $sale = Sale::where('is_cart', 1)->where('created_at', '<', $twoHoursAgo)->delete();
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 1)->with(['product'])->get();
        // $tax = 0;
        // foreach($cart as $order){
        //     $quantity = $order->quantity;
        //     $product = Item::where('id', $order->product_id)->first();
        //     $taxProduct = $product->tax;
        //     $price = $product->discount_price;
        //     $value = $taxProduct / 100 * $price ;
        //     $tax +=  $value * $quantity;
        // }
        // $cart['tax'] =  $tax ;
        return $this->respond(true, 'My Cart Data Fetched Successfully', $cart, 201);
    }
    public function customerWishList()
    {
        $twoHoursAgo = Carbon::now()->subHours(2);
        $sale = Sale::where('is_cart', 1)->where('created_at', '<', $twoHoursAgo)->delete();
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 2)->with(['product'])->get();
        return $this->respond(true, 'My Wish List Data Fetched Successfully', $cart, 201);
    }

    public function clearCart()
    {
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 1)->delete();
        $user = auth()->user();
        $description = $user->name . " cleared all items in cart";
        myaudit('cleared cart ', $user, $description);
        return $this->respond(true, 'My Cart Cleared Successfully', [], 201);
    }

    public function profile()
    {
        $user = User::where('id', Auth::user()->id)->first();
        return $this->respond(true, 'My Profile Data', $user, 201);
    }

    public function profileUpdate(Request $request)
    {
        $input = $request->except('og_number', 'firstname', 'lastname', 'user_type', 'pin');
        try {
            $user = User::where('id', Auth::user()->id)->first();
            $user->update($input);
            $description = $user->name . " updated profile";
            myaudit('update ', $user, $description);
            return $this->respond(true, 'Profile Updated Successfully', $user, 201);
        } catch (\Exception $e) {
            return $this->respond("error", $e->getMessage(), null, 500);
        }
    }
    public function customerDeliveredOrders()
    {
        $cart = Sale::where('customer_id', $this->userId())->where('status', "Completed")->with(['product', 'store'])->get();
        return $this->respond(true, 'My Delivered Orders Fetched Successfully', $cart, 201);
    }
    public function paidPendingOrders()
    {
        $ordersWithItems = Sale::where('customer_id', $this->userId())
            ->where('payment_status', "Paid")->where('status', 'Pending')
            ->select('reference_no', 'payment_status', 'status', 'delivery_fee', 'mode_of_delivery', 'note', 'delivery_address', 'store_id')
            ->selectRaw('SUM(amount) as total_amount, reference_no')
            ->groupBy('reference_no', 'payment_status', 'status', 'delivery_fee', 'mode_of_delivery', 'note', 'delivery_address', 'store_id')
            ->distinct()
            ->with(['order', 'store'])
            // ->orderBy("created_at", "desc")
            ->get(['reference_no', 'total_amount', 'payment_status']);
        return $this->respond(true, 'My Pending Orders Fetched Successfully', $ordersWithItems, 201);
    }
    public function paidDeliveredOrders()
    {
        $ordersWithItems = Sale::where('customer_id', $this->userId())
            ->where('payment_status', "Paid")->where('status', 'Completed')
            ->select('reference_no', 'payment_status', 'status', 'delivery_fee', 'mode_of_delivery', 'note', 'delivery_address', 'store_id')
            ->selectRaw('SUM(amount) as total_amount, reference_no')
            ->groupBy('reference_no', 'payment_status', 'status', 'delivery_fee', 'mode_of_delivery', 'note', 'delivery_address', 'store_id')
            ->with(['order', 'store'])
            // ->orderBy("created_at", "desc")
            ->get(['reference_no', 'total_amount', 'payment_status']);
        return $this->respond(true, 'My Delivered Orders Fetched Successfully', $ordersWithItems, 201);
    }
    public function customerPaidOrders()
    {
        $cart = Sale::where('customer_id', $this->userId())->where('payment_status', "Paid")->with(['product', 'store'])->get();
        return $this->respond(true, 'My Paid Orders Fetched Successfully', $cart, 201);
    }
    public function brand()
    {
        $brands = Brand::all();
        return $this->respond(true, 'Brands Data Fetched Successfully', $brands, 201);
    }
    public function item()
    {
        $items = Item::where('approval_status', 1)->with(['categories', 'brands', 'images'])->paginate(25);
        return $this->respond(true, 'Items Data Fetched Successfully', $items, 201);
    }
    public function getCategoryItems(Request $request)
    {
        $items = Item::where('approval_status', 1)->where('category', $request->id)->with(['categories', 'brands', 'images'])->get();
        return $this->respond(true, 'Items By Category Data Fetched Successfully', $items, 201);
    }
    public function getBrandWithItems(Request $request)
    {
        $items = Brand::with(['products'])->paginate(25);
        return $this->respond(true, 'Brands with Items Data Fetched Successfully', $items, 201);
    }
    public function getProductsByBrand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $items = Item::where('approval_status', 1)->where('brand', $request->brand_id)->with(['categories', 'brands', 'images'])->paginate(25);
        return $this->respond(true, 'Items By Brand Data Fetched Successfully', $items, 201);
    }

    public function addToCart(Request $request)
    {
        $validValues = $request->all();
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'quantity' => 'required',
            // 'store_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        DB::beginTransaction();
        $product = Item::find($request->product_id);
        $input = $request->all();
        try {
            DB::commit();
            // check if product already exists for this user
            $check = Sale::where('customer_id', $this->userId())->where('product_id', $request->product_id)->where('is_cart', 1)->first();
            $input['customer_id'] = $this->userId();
            $input['is_cart'] = 1;
            $input['amount'] = $product->discount_price * $request->quantity;
            $taxProduct = $product->tax;
            $price = $product->discount_price;
            $value = $taxProduct / 100 * $price;
            $input['tax_amount'] = $value * $request->quantity;
            if ($check) {
                $check->update(['quantity' => $request->quantity, 'amount' =>  $input['amount'], 'tax_amount' => $input['tax_amount']]);
            } else {
                $check = Sale::create($input);
            }
            $user = auth()->user();
            $description = $user->name . ' ' . "added"   . ' ' . $check->product->name . ' ' .  "to cart";
            myaudit('add to cart ', $user, $description);
            $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 1)->with(['product'])->get();
            return $this->respond(true, "Product Added To Cart Successfully", $cart, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond("error", $e->getMessage(), null, 500);
        }
    }
    public function addToWishList(Request $request)
    {
        $validValues = $request->all();
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            // 'quantity' => 'required',
            // 'store_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        DB::beginTransaction();
        $product = Item::find($request->product_id);
        $input = $request->all();
        try {
            DB::commit();
            // check if product already exists for this user
            $check = Sale::where('customer_id', $this->userId())->where('product_id', $request->product_id)->where('is_cart', 2)->first();
            $input['customer_id'] = $this->userId();
            $input['is_cart'] = 2;
            $input['quantity'] = 1;
            $input['amount'] = $product->discount_price * 1;
            $taxProduct = $product->tax;
            $price = $product->discount_price;
            $value = $taxProduct / 100 * $price;
            $input['tax_amount'] = $value * 1;
            if ($check) {
                $check->update(['quantity' => 1, 'amount' =>  $input['amount'], 'tax_amount' => $input['tax_amount']]);
            } else {
                $check = Sale::create($input);
            }
            $user = auth()->user();
            $description = $user->name . ' ' . "added"   . ' ' . $check->product->name . ' ' .  "to wish list";
            myaudit('add to wishlist ', $user, $description);
            $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 2)->with(['product'])->get();
            return $this->respond(true, "Product Added To Wish List Successfully", $cart, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond("error", $e->getMessage(), null, 500);
        }
    }

    public function deleteWishList(Request $request)
    {
        $validValues = $request->all();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $sale = Sale::find($request->id)->delete();
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 2)->with(['product'])->get();
        return $this->respond(true, "Product Removed From Wish List Successfully", $cart, 201);
    }
    public function addToCartWishList(Request $request)
    {
        $validValues = $request->all();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $sale = Sale::find($request->id)->update(['is_cart' => 1]);
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 2)->with(['product'])->get();
        return $this->respond(true, "Product Added To Cart Successfully", $cart, 201);
    }
    public function deleteCartItem(Request $request)
    {
        $validValues = $request->all();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors(), null, 400);
        }
        $sale = Sale::find($request->id)->delete();
        $cart = Sale::where('customer_id', $this->userId())->where('is_cart', 1)->with(['product', 'store'])->get();
        return $this->respond(true, "Product Removed From Cart Successfully", $cart, 201);
    }

    public function makeOrder(Request $request)
    {
        $validValues = $request->all();

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|numeric',
            'product_id' => 'required|array',
            'product_id.*' => 'required|numeric',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric',
            'amount' => 'required|array',
            'amount.*' => 'required|numeric',
            'mode_of_delivery' => 'required',
            'payment_method' => 'required',
            // 'store_id.*' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }
        if ($request->mode_of_delivery == "Door Delivery") {
            $validator = Validator::make($request->all(), [
                'delivery_address' => 'required',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'store_id' => 'required|numeric',
            ]);
        }
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }

        // check if customer id has data in user table
        $user = User::where('id', $request->customer_id)->first();
        if (!$user) {
            return $this->respond('error', "No User Record with customer id param", null, 400);
        }
        $input = $request->all();
        $data = json_decode($request->getContent(), true);

        $productCount = count($data['product_id']);
        $quantityCount = count($data['quantity']);
        $amountCount = count($data['amount']);
        if ($productCount !== $quantityCount || $quantityCount !== $amountCount) {
            return $this->respond("error", "Invalid data! check your params", null, 500);
        }
        DB::beginTransaction();
        try {

            // $amountSum = sum($data['amount']);
            $countAllOrders = Sale::distinct('reference_no')->count('reference_no');
            $uuid = generate_uuid();

            $newcount = $countAllOrders + 1;
            $paymentLength = strlen($newcount);
            if ($paymentLength == 1) {
                $sku = "00" . $newcount;
            }
            if ($paymentLength == 2) {
                $sku = "01" . $newcount;
            }
            if ($paymentLength == 3) {
                $sku =  $newcount;
            }
            if ($paymentLength >= 4) {
                $sku =  $newcount;
            }
            $customer = $input['customer_id'];
            $generateOrderId = "GTC" . '' . $sku;

            //let's connect to payment gateway and generate paymentcode
            // $paymentDetail = $this->generateInvoice($amountSum, $uuid, $customer);

            foreach ($input['product_id'] as $key => $product) {
                $NewProduct = Sale::where('product_id', $product)->where('customer_id', $customer)->where('is_cart', 1)->first();
                if ($NewProduct) {
                    $item = Item::where('id', $product)->first();
                    $taxProduct = $item->tax;
                    $price = $item->discount_price;
                    $value = $taxProduct / 100 * $price;
                    $input['tax_amount'] = $value * $input['quantity'][$key];
                    if ($item) {
                        $NewProduct->customer_id = $customer;
                        $NewProduct->product_id = $product;
                        $NewProduct->price = $item->discount_price;
                        $NewProduct->quantity = $input['quantity'][$key];
                        $NewProduct->amount = $input['quantity'][$key] * $item->discount_price;
                        $NewProduct->reference_no = $generateOrderId;
                        $NewProduct->is_cart = 0;
                        $NewProduct->tax_amount = $input['tax_amount'];
                        $NewProduct->store_id = $request->store_id;
                        $NewProduct->mode_of_delivery = $request->mode_of_delivery;
                        $NewProduct->note = $request->note;
                        $NewProduct->delivery_address = $request->delivery_address;
                        $NewProduct->payment_status = "Paid";
                        $NewProduct->update();
                    }
                }
            }


            DB::commit();
            return $this->respond(true, "Order Made Successfully", $generateOrderId, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respond("error", $e->getMessage(), null, 400);
        }
    }

    public function generateInvoice($amountSum, $uuid, $customer)
    {


        $description = "Sales Bill";
        // dd($desc);

        $payer = User::find($customer);
        $PayerName = $payer->lastname . ' ' . $payer->firstname;
        $input['customer_first_name'] = $payer->lastname;
        $input['customer_last_name'] = $payer->firstname;
        $input['customer_email'] = $this->email = $payer->email;
        $input['customer_phone'] = $payer->phone_number;
        $input['customer_address'] = $payer->address;
        $input['bill_description'] = $description;
        $input['billed_amount'] = $amountSum;
        $input['overwrite_existing'] = false;
        $input['service_id'] = 11;

        $apiInput['allow_part_payment'] = true;

        $input['demand_notices'] = [

            ["name" => $description, "amount" => $amountSum, "revenue_code" => "1100010111011101"],
        ];

        //dd($input);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer $2y$10$q1iGX.uZVLlE6h.8.KQS5OrAXC/1xmKJsb4Bv8tIEMhYj8UfD4VM.',
            'Accept' => 'application/json',
        ])->post('https://bpms.ogunstate.gov.ng/api/v1/mda-integration/generate-bill', $input);

        $jsondata = json_decode($response);

        $statuscode = $jsondata->status_code;

        if ($statuscode != 200) {
            return $this->respond("error", $jsondata->message, null, 400);
            // return redirect()->back()->withErrors(["exception" => $jsondata->message]);
        }

        // dd($jsondata);

        $data['routeurl'] = $this->routeurl = $jsondata->data->payment_url;

        $data["payment_code"] = $paycode = $this->payment_code = $jsondata->data->payment_code;
        $data['amount'] = $this->amount = $amountSum;
        $data['description'] = $this->description = $description;
        $data['name'] = $this->company_name = $PayerName;
        $data['email'] = $this->email = $payer->email;
        $data['invoice_date'] = now();
        if ($statuscode == '200') {

            //dd("here");

            $payment = new Payment();

            $payment->rrr = '';
            $payment->order_id = '';
            $payment->details = $PayerName;
            $payment->description = $description;
            $payment->payment_status = 0;
            $payment->uuid = $uuid = generate_uuid();
            $payment->payment_code = $paycode;
            $payment->bill_date = now();
            $payment->tax_payer_id = $payer->id;
            $payment->revenue_id = $request->revenue;

            //    $payment->payment_date ='';
            $payment->amount_paid = 0;
            $payment->balance = 0;
            $payment->amount = $amountSum;

            // dd($payment);

            // try {

            $inserted = $payment->save();

            return $payment;
        }
        return $this->respond("error", "cannot process payment as at now", null, 400);
    }


    public function localGovt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'state_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }
        $localGovt = LGA::where('state_id', $request->state_id)->get();
        return $this->respond(true, "Local Govt fetched successfully.", $localGovt, 201);
    }

    public function nigState(Request $request)
    {

        $states = State::get();
        return $this->respond(true, "State fetched successfully.", $states, 201);
    }

    public function changeDefaultAddress(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'address' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }

        //get this customer
        $customer = auth()->user();

        // check the actual address

        if ($customer->address_1 == $request->address) {
            //let's update address and address 1
            $updateCustomer = $customer->update(
                [
                    'address' => $request->address,
                    'address_1' => $customer->address
                ]
            );
        } else {
            $updateCustomer = $customer->update(
                [
                    'address' => $request->address,
                    'address_2' => $customer->address
                ]
            );
        }

        return $this->respond(true, "Address set to default successfully.", null, 201);
    }

    public function reviewProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            // 'rating' => 'required',
            // 'review' => 'required',
            'reference_no' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->respond('error', $validator->errors()->first(), null, 400);
        }
        try {
            // verify if customer has once purchase product before 
            $verify = Sale::where('customer_id', $this->userId())->where('reference_no', $request->reference_no)->where('product_id', $request->product_id)->where('is_cart', 0)->first();
            if (!$verify) {
                return $this->respond('error', "You haven't made order for this product", null, 400);
            }
            // check rating for this product 
            $check = Rating::where('customer_id', $this->userId())->where('reference_no', $request->reference_no)->where('product_id', $request->product_id)->first();
            if (!$request->has('review') && !$request->has('rating')) {
                return $this->respond('error', "You are not sending review nor rating", null, 400);
            } elseif ($request->has('review') && !$request->has('rating')) {
                if ($check) {
                    if ($check->review != "") {
                        return $this->respond('error', "You already reviewed this product", null, 400);
                    }
                }
            } elseif (!$request->has('review') && $request->has('rating')) {
                if ($check) {
                    if ($check->rating != "") {
                        return $this->respond('error', "You already rated this project", null, 400);
                    }
                }
            } elseif ($request->has('review') && $request->has('rating')) {
                if ($check) {
                    // dd($check);
                    if ($check->rating != "" && $check->review != "") {
                        return $this->respond('error', "You already rated and reviewed this project", null, 400);
                    }
                }
            }
            $input = $request->all();
            $input['customer_id'] =  $this->userId();
            if ($check) {
                $check->update($input);
            } else {
                Rating::create($input);
            }
            return $this->respond(true, "Rating executed successfully!.", null, 201);
        } catch (\Exception $e) {
            return $this->respond('error', ['message' => $e->getMessage()], null, 400);
        }
    }
}
