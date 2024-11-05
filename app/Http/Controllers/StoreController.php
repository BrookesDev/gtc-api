<?php

namespace App\Http\Controllers;
use function App\Helpers\api_request_response;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
use App\Models\Store;
use App\Models\User;
use App\Models\StoreUser;
use App\Models\Pincard;
use App\Models\StoreProduct;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\Validator;
use App\Traits\PincardTrait;
use App\Models\ReceiveOrder;
use App\Models\ApprovalLevel;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
class StoreController extends Controller
{
    use PincardTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function stockDeliverable()
    {
        $data['stocks'] = ReceiveOrder::orderBy('created_at', 'asc')->get();
        return view('admin.store.deliverable', $data);
    }
    public function stockMovement()
    {
        $data['stocks'] = Pincard::orderBy('created_at', 'asc')->get();
        return view('admin.store.movement', $data);
    }
    public function index()
    {
        $data['stores'] = Store::get();
        $data['mains'] = Store::whereNull('store_id')->get();
        return view('admin.store.index', $data);
    }
    public function outlet()
    {
        $data['stores'] = Store::whereNotNull('store_id')->get();
        $data['main'] = Store::whereNull('store_id')->get();
        return view('admin.store.outlet', $data);
    }

    public function mainProductsTores()
    {
        // check if user is assigned to a store
        $userId = Auth::user()->id;
        $storeUser = StoreUser::where('user_id', $userId)->first();
        // dd($storeUser);
        if ($storeUser) {
            $stores = StoreProduct::where('store_id', $storeUser->store_id)->with(['store'])->get(['store_id']);

        } else {
            $stores = StoreProduct::with(['store'])->get(['store_id']);
        }

        // dd($stores);

        $data['stores'] = $stores->pluck('store')->unique();

        $data['title'] = "Store Stocks";
        return view('admin.store.product_stores', $data);
    }
    public function mainProducts(Request $request)
    {
        // check if user is assigned to a store
        $userId = Auth::user()->id;
        $storeUser = StoreUser::where('user_id', $userId)->first();
        // dd($storeUser);
        if ($storeUser) {
            $data['items'] = StoreProduct::where('store_id', $storeUser->store_id)->get();
        } else {
            $data['items'] = StoreProduct::where('store_id', $request->id)->get();
        }
        $data['title'] = "Store Stocks";
        return view('admin.store.product', $data);
    }
    public function outletProducts()
    {
        $store = Store::whereNotNull('store_id')->pluck('id')->toArray();
        $data['items'] = StoreProduct::whereIn('store_id', $store)->get();
        $data['title'] = "Outlet Store Products";
        return view('admin.store.product', $data);
    }

    public function getStoreOutlet(Request $request)
    {
        $pp['data'] = Store::where('store_id', $request->id)->get();
        return json_encode($pp);
    }

    public function getProductByStore(Request $request)
    {
        try {
            $value = StoreProduct::where('store_id', $request->id)->with(['product'])->get();
            return api_request_response(
                "ok",
                "Search Complete!",
                success_status_code(),
                [$value]
            );
        } catch (\Exception $exception) {
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }
    public function getStoreByType(Request $request)
    {
        // dd($request->all());
        try {
            switch ($request->id) {
                case "Main-Main":
                    $main = Store::whereNull('store_id')->get();
                    $outlet = Store::whereNull('store_id')->get();
                    break;
                case "Outlet-Main":
                    $main = Store::whereNotNull('store_id')->get();
                    $outlet = Store::whereNull('store_id')->get();
                    break;
                case "Outlet-Outlet":
                    $outlet = Store::whereNotNull('store_id')->get();
                    $main = Store::whereNotNull('store_id')->get();
                    break;
                case "Main-Outlet":
                    $main = Store::whereNull('store_id')->get();
                    $outlet = Store::whereNotNull('store_id')->get();
                    break;
                default:
                    $main = Store::whereNull('store_id')->get();
                    $outlet = Store::whereNotNull('store_id')->get();
                    break;
            }
            return api_request_response(
                "ok",
                "Search Complete!",
                success_status_code(),
                [$main, $outlet]
            );
        } catch (\Exception $exception) {
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }

    public function mainToOutlet()
    {
        $data['stores'] = Store::whereNull('store_id')->get();
        $data['outlets'] = Store::whereNotNull('store_id')->get();
        return view('admin.store.main_to_outlet', $data);
    }
    // public function transferStock(){
    //     $model = new ReceiveOrder();
    //     // Get the table name and column name for the enum field
    //     $table = $model->getTable();
    //     $column = 'type'; // Replace 'type' with the actual column name in your database
    //     $enumValues = DB::select("SHOW COLUMNS FROM $table WHERE Field = '$column'")[0]->Type;
    //     preg_match('/^enum\((.*)\)$/', $enumValues, $matches);
    //     $enumValues = explode(',', $matches[1]);
    //     $enumValues = array_map(function ($value) {
    //         return trim($value, "'");
    //     }, $enumValues);

    //     // Skip the first two elements and get the rest
    //     $data['enums'] = array_slice($enumValues, 2);

    //     return view('admin.store.transfer', $data);
    // }

    public function transferStock(Request $request)
    {
        // Instantiate ReceiveOrder model and retrieve the table name and enum field
        $model = new ReceiveOrder();
        $table = $model->getTable();
        $column = 'type'; // Specify the actual column name in your database

        // Get enum values from the column
        $enumValues = DB::select("SHOW COLUMNS FROM $table WHERE Field = '$column'")[0]->Type;
        preg_match('/^enum\((.*)\)$/', $enumValues, $matches);
        $enumValues = explode(',', $matches[1]);
        $enumValues = array_map(function ($value) {
            return trim($value, "'");
        }, $enumValues);

        // Exclude the first two enum values
        $data = [
            'enums' => array_slice($enumValues, 2),
        ];

        return respond(true, 'Stock transfer enum data retrieved successfully', $data, 200);

    }

    public function storeRequestIndex()
    {
        $data['orders'] = StoreOrder::select('order_id', 'is_supplied', 'store_id', 'created_at', 'approval_status')
            ->orderBy('created_at', 'desc')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->groupBy('order_id', 'is_supplied', 'store_id', 'created_at', 'approval_status')
            ->where('approval_status', 1)
            ->distinct()
            ->get();
        $data['pageName'] = 'Manage your request';
        // dd($data);
        return view('admin.store.request-list', $data);
    }
    public function pendingStoreRequisiton()
    {
        $data['orders'] = StoreOrder::select('order_id', 'is_supplied', 'store_id', 'created_at', 'approval_status', 'approval_order')
            ->orderBy('created_at', 'desc')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->groupBy('order_id', 'is_supplied', 'store_id', 'created_at', 'approval_status', 'approval_order')
            ->where('approval_status', 0)
            ->distinct()
            ->get();
        $data['pageName'] = 'Pending Request Approval';
        // dd($data);
        return view('admin.store.request-list', $data);
    }

    public function approvePurchaseOrder(Request $request)
    {


        $items = StoreOrder::where('order_id', $request->id)->get();
        // approval level
        // dd($item, $re);
        $item = $items->first();

        //do validation
        if (auth()->user()->roles[0]->id != $item->approval_order) {
            return api_request_response(
                "error",
                "You are not the right approver!",
                bad_response_status_code()
            );
        }
        $level_of_approval = json_decode($item->approval_level);
        $approver_list = json_decode($item->approver_list);
        $names_of_approver = json_decode($item->approved_by);
        $get_remaining_approvers = json_decode(
            $item->approver_reminant
        );

        if (!empty($get_remaining_approvers)) {
            // dd($get_remaining_approvers);
            // move to the next approver
            $next_to_approve = array_shift($get_remaining_approvers);
            $remaining_approvers = $get_remaining_approvers;
            array_push(
                $approver_list,
                Auth::user()->id
            );


            foreach ($items as $key => $item) {
                # code...
                $item->update([
                    'approver_list' => json_encode($approver_list),
                    'approval_order' => $next_to_approve,
                    'approver_reminant' => json_encode($remaining_approvers)
                ]);

                $description = auth()->user()->name . " approved requisition";
                myaudit('approved', $item, $description);
            }

        } else {
            //approve
            $remaining_approvers = $get_remaining_approvers;

            $update_approver_list = array_push(
                $approver_list,
                auth()->user()->id
            );


            foreach ($items as $key => $item) {
                # code...
                $item->update([
                    'approval_status' => 1,
                    'approval_date' => now(),
                    'approver_list' => json_encode($approver_list)
                ]);
                $description = auth()->user()->name . " approved requisition";
                myaudit('approved', $item, $description);
            }

        }


        return api_request_response(
            "ok",
            "Requisiton approved Successfully!",
            success_status_code(),
            null
        );
    }


    public function pendingRequisitionByReference(Request $request)
    {
        try {
            // dd($request->id);
            $value = StoreOrder::where('order_id', $request->id)->with(['store', 'product'])->get();
            $stores = Store::where('id', '!=', $value[0]->store_id)->get();
            return api_request_response(
                "ok",
                "Search Complete!",
                success_status_code(),
                [$value, $stores]
            );
        } catch (\Exception $exception) {
            return api_request_response(
                'error',
                $exception->getMessage(),
                bad_response_status_code()
            );
        }
    }

    public function saveReceivedRequisition(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $storeProducts = $input['id'];
            $products = $input['item'];
            $user_id = Auth::user()->id;
            $from = $input['from'];
            $to = $input['store_id'];
            $fromStore = Store::where('id', $request->from)->first();
            $type = "$fromStore->type-Outlet";
            // dd($type);
            $generateOrderId = $request->order_id;
            foreach ($products as $key => $pro) {
                $itemProduct = Item::where('id', $pro)->first();
                $check = StoreProduct::where('product_id', $pro)->where('store_id', $from)->first();
                if ($check) {
                    if ($check->quantity < $input['quantity_supplied'][$key]) {
                        return api_request_response(
                            "error",
                            "Store doesn't have enough $itemProduct->name to move from",
                            bad_response_status_code(),
                        );
                    }
                } else {
                    return api_request_response(
                        "error",
                        "Store doesn't have $itemProduct->name product",
                        bad_response_status_code(),
                    );
                }
            }
            // dd($type);
            foreach ($storeProducts as $key => $item) {
                $requisition = StoreOrder::where('id', $item)->first();
                $requisition->update([
                    'date_supplied' => now(),
                    'quantity_supplied' => $input['quantity_supplied'][$key],
                    'supplied_price' => str_replace(',', '', $input['supplied_price'][$key]),
                    'supplied_amount' => str_replace(',', '', $input['supplied_amount'][$key]),
                    'is_supplied' => 1,
                    'received_by' => $user_id,
                ]);
                $orderSuccessful = StoreProduct::where('store_id', $from)->where('product_id', $requisition->item)->first();
                $prev = $orderSuccessful->quantity;
                $incoming = $input['quantity_supplied'][$key];
                // save new received order
                $details = new ReceiveOrder;
                $details->quantity = $input['quantity_supplied'][$key];
                $details->amount = str_replace(',', '', $input['supplied_price'][$key]) * $input['quantity_supplied'][$key];
                $details->price = str_replace(',', '', $input['supplied_price'][$key]);
                $details->order_id = $generateOrderId;
                $details->user_id = Auth::user()->id;
                $details->product_id = $orderSuccessful->product_id;
                $details->type = $type;
                $details->to = $to;
                $details->from = $from;
                $details->save();

                $product_id = $orderSuccessful->product_id;
                // get where quantity is coming from
                $orderSuccessful->update(['quantity' => $prev - $incoming]);

                // check if store has this product
                $verify = StoreProduct::where('store_id', $to)->where('product_id', $product_id)->first();
                if ($verify) {
                    $start = $verify->quantity;
                    $end = $verify->quantity + $input['quantity_supplied'][$key];
                    $verify->update(['quantity' => $end]);
                } else {
                    $start = 0;
                    $end = $input['quantity_supplied'][$key];
                    $verify = StoreProduct::create([
                        "store_id" => $request->store_id,
                        "quantity" => $end,
                        "product_id" => $product_id
                    ]);
                }
                $store = Store::where('id', $request->store_id)->first();
                $changes = $incoming;
                // get data into pincard;
                $stock_id = $product_id;
                $user_id = Auth::user()->id;
                $status = 4;
                // dd($input);
                switch ($type) {
                    case "Supplier-Main":
                        $description = "Purchase Order To" . ' ' . $orderSuccessful->supplier->name . ' ' . "Delivered To Main Store" . ' ' . $store->store_name ?? "";
                        break;
                    case "Supplier-Outlet":
                        $description = "Purchase Order To" . ' ' . $orderSuccessful->supplier->name . ' ' . "Delivered To Store Outlet" . ' ' . $store->store_name ?? "";
                        break;
                    case "Outlet-Main":
                        $description = "Item Moved From Store Outlet" . ' ' . $details->sender->store_name . ' ' . "To Main Store" . ' ' . $store->store_name;
                        break;
                    case "Outlet-Outlet":
                        $description = "Item Moved From Store Outlet" . ' ' . $details->sender->store_name . ' ' . "To Store Outlet" . ' ' . $store->store_name;
                        break;
                    case "Main-Main":
                        $description = "Item Moved From Main Store" . ' ' . $details->sender->store_name . ' ' . "To Main Store" . ' ' . $store->store_name;
                        break;
                    case "Main-Outlet":
                        $description = "Item Moved From Main Store" . ' ' . $details->sender->store_name . ' ' . "To Store Outlet" . ' ' . $store->store_name;
                        break;
                    default:
                        $description = "Item Moved From Store Outlet" . ' ' . $orderSuccessful->supplier->name . ' ' . "Delivered";
                        break;
                }

                $this->pincardFunction($stock_id, $user_id, $status, $prev, $changes, $orderSuccessful->quantity, $description, $request->from);
                $this->pincardFunction($stock_id, $user_id, $status, $start, $changes, $end, $description, $request->store_id);

            }

            DB::commit();

            return api_request_response(
                "ok",
                "Order Received Successfully!",
                success_status_code(),
                $incoming
            );
        } catch (\Exception $exception) {
            DB::rollback();
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code(),
            );
        }
    }
    public function pendingRequisition()
    {
        $data['pendings'] = StoreOrder::select('order_id', 'store_id')->whereNull('is_supplied')->where('approval_status', 1)
            ->distinct()
            ->get();
        return view('admin.order.receive-request', $data);
    }

    public function createNewOrder(Request $request)
    {
        // dd($request->all());
        $input = $request->all();
        DB::beginTransaction();
        $supplier = $input['store_id'];
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
        try {
            $countAllOrders = StoreOrder::distinct('order_id')->count('order_id');
            $newcount = $countAllOrders + 1;
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
            $generateOrderId = "TRX" . '' . $sku;


            //let's handle approval level
            $approval_level = ApprovalLevel::where('module', 'Requisition Approval')->first();
            if (!$approval_level) {
                return api_request_response(
                    "error",
                    'Please specify approval level for Requisition Module',
                    bad_response_status_code()
                );
            }
            $input['approver_list'] = '[]';
            $a = json_decode($approval_level->list_of_approvers);
            $input['approval_level'] = json_encode($a);
            $firstapprover = array_shift($a);
            $remainingapprover = ($a);
            $input['approval_order'] = $firstapprover;
            $input['approver_reminant'] = json_encode($remainingapprover);
            $input['approved_by'] = '[]';
            $input['approval_status'] = 0;



            foreach ($input['product_id'] as $key => $product) {
                $order = new StoreOrder;
                $order->store_id = $supplier;
                $order->item = $product;
                $order->price = $input['unit_price'][$key];
                $order->total = $input['quantity'][$key];
                $order->quantity = $input['quantity'][$key];
                $order->amount = str_replace(',', '', $input['amount'][$key]);
                $order->order_id = $generateOrderId;
                $order->approver_list = $input['approver_list'];
                $order->approval_order = $input['approval_order'];
                $order->approver_reminant = $input['approver_reminant'];
                $order->approved_by = $input['approved_by'];
                $order->approval_status = $input['approval_status'];
                $order->save();
            }

            DB::commit();
            return api_request_response(
                "ok",
                "Order Made Successfully!",
                success_status_code(),
                $order
            );
        } catch (\Exception $exception) {
            DB::commit();
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }

    public function storeRequest()
    {
        $data['stores'] = Store::whereNotNull('store_id')->get();
        $data['items'] = Item::all();
        return view('admin.store.requisition', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $input = $request->all();
        $file = "store";
        if ($request->exists("image")) {
            $input['image'] = uploadImage($request->image, $file);
        }
        // dd($input);
        try {
            if ($request->type == "Outlet") {
                $input['type'] = $input['type'];
                $input['store_id'] = $input['store_id'];
                $validator = Validator::make($request->all(), [
                    'store_id' => 'required|string',
                ]);
                if ($validator->fails()) {
                    return api_request_response(
                        'error',
                        $validator->errors(),
                        bad_response_status_code()
                    );
                }
            } else {
                $input['type'] = $input['type'];
                $input['store_id'] = NULL;
            }

            if ($request->has('id')) {
                $store = Store::where('id', $request->id)->first();
                $store->update($input);
                return api_request_response(
                    "ok",
                    "Store Update Successful!",
                    success_status_code(),
                    $store
                );
            } else {
                $store = Store::create($input);
                return api_request_response(
                    "ok",
                    "Store Created successful!",
                    success_status_code(),
                    $store
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
    public function details(Request $request)
    {
        $data = Store::where('id', $request->id)->first();
        return response()->json($data);
    }
    public function requisitionDetails(Request $request)
    {
        $data['details'] = StoreOrder::where('order_id', $request->id)->get();
        return view('admin.store.details', $data);
    }
    public function assignDetails(Request $request)
    {
        $data = StoreUser::where('id', $request->id)->first();
        return response()->json($data);
    }
    public function assignUser(Request $request)
    {
        $input = $request->all();
        // dd($input);
        try {
            if ($request->has('id')) {
                $store_id = $input['store_id'];
                $user_id = $input['user_id'];
                $id_to_exclude = $request->id;
                // Check for a duplicate entry based on store_id or user_id
                $has_duplicate_entry = StoreUser::where(function ($query) use ($store_id, $user_id, $id_to_exclude) {
                    $query->where('store_id', $store_id)
                        ->orWhere('user_id', $user_id);
                })->where('id', '!=', $id_to_exclude)->first();
                if ($has_duplicate_entry) {
                    return api_request_response(
                        "error",
                        "Duplicate Entry",
                        bad_response_status_code()
                    );
                }
                $store = StoreUser::where('id', $request->id)->first();
                $store->update($input);
                return api_request_response(
                    "ok",
                    "Record Update Successful!",
                    success_status_code(),
                    $store
                );
            } else {
                if (StoreUser::where('store_id', $input['store_id'])->first() || StoreUser::where('user_id', $input['user_id'])->first()) {
                    return api_request_response(
                        "error",
                        "Duplicate Entry",
                        bad_response_status_code()
                    );
                }
                $store = StoreUser::create($input);
                return api_request_response(
                    "ok",
                    "User Assigned To Store Successful!",
                    success_status_code(),
                    $store
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        $roleToExclude = 'Super Admin'; // Replace with the name of the role to exclude
        $data['officers'] = User::whereDoesntHave('roles', function ($query) use ($roleToExclude) {
            $query->where('name', $roleToExclude);
        })->get();
        $data['users'] = StoreUser::all();
        $data['stores'] = Store::all();
        // $data['officers'] = User::where('user_type', "admin")->get();
        return view('admin.store.user', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Store  $store
     * @return \Illuminate\Http\Response
     */
    public function destroyData(Request $request)
    {
        try {
            $store = StoreUser::where('id', $request->id)->first();
            $store->delete();
            return api_request_response(
                "ok",
                "Record Deleted Successfully!",
                success_status_code(),
                $store
            );
        } catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }
    public function destroy(Request $request)
    {
        try {
            $store = Store::where('id', $request->id)->first();
            $store->delete();
            return api_request_response(
                "ok",
                "Store Deleted Successfully!",
                success_status_code(),
                $store
            );
        } catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }

    public function createStore(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'store_name' => 'required|string|max:255',
                'store_address' => 'required|string|max:255',
                'phone_number' => 'required|numeric',
                'type' => 'required|in:Main,Outlet',
                'contact_email' => 'nullable|email',
                'contact_address' => 'nullable|string',
                'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048', // Max size: 2MB
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $phoneValidation = formatPhoneNumber($request->phone_number);
            if (!$phoneValidation['status']) {
                return respond(false, $phoneValidation['message'], null, 400);
            }
            $input = $request->all();
            $input["phone"] = $input['phone_number'];
            // dd("here");
            if ($request->has('image')) {
                $file = "store";
                $mainImagePath = uploadImage($request->image, $file);
                $input['image'] = $mainImagePath;
            }
            if ($request->type == "Outlet") {
                $validator = Validator::make($request->all(), [
                    'store_id' => 'required|exists:stores,id',
                ]);
                if ($validator->fails()) {
                    return respond(false, $validator->errors(), null, 400);
                }
                $input['store_id'] = $request->store_id;
            }
            $store = Store::create($input);
            DB::commit();
            return respond(true, 'New store saved successfully!', $store, 201);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', 'Error creating store, please try again.', 400);
        }
    }

}
