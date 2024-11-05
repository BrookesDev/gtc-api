<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\ReceiveOrder;
use App\Traits\PincardTrait;
use Illuminate\Http\Request;
use App\Models\ApprovalLevel;
use Illuminate\Support\Facades\Auth;
use function App\Helpers\api_request_response;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\success_status_code;
class PurchaseOrderController extends Controller
{
    use PincardTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['orders'] = PurchaseOrder::select('order_id','is_supplied','supplier_id','created_at','approval_status')
        ->selectRaw('SUM(amount) as total_amount')
        ->selectRaw('SUM(quantity) as total_quantity')
        ->groupBy('order_id','is_supplied','supplier_id','created_at','approval_status')
        ->orderBy('created_at', 'desc')
        ->where('approval_status',1)
        ->distinct()
        ->get();
        $data['pageName']= "Manage your purchases";
        return view('admin.orders.index', $data);
    }

    public function pendingPurchaseOder()
    {
        $data['orders'] = PurchaseOrder::select('order_id','is_supplied','supplier_id','created_at','approval_status','approval_order')
        ->selectRaw('SUM(amount) as total_amount')
        ->selectRaw('SUM(quantity) as total_quantity')
        ->groupBy('order_id','is_supplied','supplier_id','created_at','approval_status','approval_order')
        ->orderBy('created_at', 'desc')
        ->where('approval_status',0)
        ->distinct()
        ->get();
        $data['pageName']= "Pending Approval Purchase List";

        return view('admin.orders.index', $data);
    }

    public function received(){
        // $data['orders'] = ReceiveOrder::whereNotNull('supplier_id')->orderBy('created_at', 'desc')->get();

        $order= ReceiveOrder::whereNotNull('supplier_id')->orderBy('created_at', 'desc')->get(['order_id', 'supplier_id','product_id','to','created_at']);
        $orderCOllectionGroup= $order->groupBy('order_id');
        $newOrder= $orderCOllectionGroup->map(function ($item) {
                $input['order_id']= $item->first()->order_id;
                $input['supplier_id']= $item->first()->supplier_id;
                $input['product_id']= $item->first()->product_id;
                $input['supplierName']= $item->first()->supplier->name;
                $input['receiverstoreName']= $item->first()->receiver->store_name;
                $input['created_at']= $item->first()->created_at;
            return $input;
        });

        $data['orders'] = collect($newOrder);
        // dd($order, $data);
        return view('admin.orders.received', $data);
    }
    public function receivedDetails(Request $request){
        $order= ReceiveOrder::whereNotNull('supplier_id')->where('order_id', $request->refno)->orderBy('created_at', 'desc')->get();
        $data['orders'] =$order;
        $data['purchaseOrder'] =$request->refno;

        return view('admin.orders.received_details', $data);
    }

    public function supplierPendingPurchaseOrder(Request $request){
        $pp['data']  = PurchaseOrder::whereNull('is_supplied')->where([['supplier_id', $request->id],['approval_status', 1]])->select('order_id')->distinct()->get();
        return json_encode($pp);
    }


    public function approvePurchaseOrder(Request $request){


        $items = PurchaseOrder::where('order_id', $request->id)->get();
        // approval level
        // dd($item);
        $item =$items->first();

        //do validation
        if(auth()->user()->roles[0]->id != $item->approval_order){
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

                $description = auth()->user()->name . " approved purchase order";
            myaudit('approved', $item, $description);
            }

        }else{
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
                $description = auth()->user()->name . " approved purchase order";
                myaudit('approved', $item, $description);
            }

        }


        return api_request_response(
            "ok",
            "Purchase Order approved Successfully!",
            success_status_code(),
            null
        );
    }

    public function purchaseDeliveryByOrderId(Request $request){
        try {
            $value = PurchaseOrder::where('supplier_id', $request->customerid)->where('order_id', $request->id)->with(['product'])->get();
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

    public function deliver(){
        $data['suppliers'] = Supplier::all();
        $data['stores'] = Store::whereNull('store_id')->get();
        return view('admin.orders.delivery', $data);
    }

    public function createNewOrder(Request $request){
        // dd($request->all());
        $input= $request->all();

        $supplier = $input['supplier_id'];
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
        if($request->has('order_id')){
            // dd($input);
            $generateOrderId = $request->order_id;
            PurchaseOrder::where('order_id', $generateOrderId)->delete();
            foreach($input['product_id'] as $key => $product){
                $order = new PurchaseOrder;
                $order->supplier_id = $supplier;
                $order->item = $product;
                $order->price = str_replace(',', '', $input['unit_price'][$key]);
                $order->total = $input['quantity'][$key];
                $order->quantity = $input['quantity'][$key];
                $order->amount = str_replace(',', '', $input['amount'][$key]) ;
                $order->order_id = $generateOrderId;
                $order->save();
                // dd("here");
                //audit
                $description = auth()->user()->name . " updated order $generateOrderId";
                myaudit('updated', $order, $description);
            }
            return api_request_response(
                "ok",
                "Order $generateOrderId Updated Successfully!",
                success_status_code(),
                $generateOrderId
            );
        }
        DB::beginTransaction();
        try {

            $countAllOrders = PurchaseOrder::distinct('order_id')->count('order_id');
            $newcount = $countAllOrders + 1;
            $paymentLength = strlen($newcount);
            if($paymentLength ==1){
                $sku = "00" . $newcount;
            }
            if($paymentLength ==2){
                $sku = "01" . $newcount;
            }
            if($paymentLength ==3){
                $sku =  $newcount;
            }
            if($paymentLength >= 4){
                $sku =  $newcount;
            }

            //approval level
             //let's handle approval level
             $approval_level = ApprovalLevel::where('module', 'Purchase Order Approval')->first();
             if(!$approval_level){
                 return api_request_response(
                     "error",
                     'Please specify approval level for Purchase Order Module',
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


            $generateOrderId = "GTCO".''.$sku;
            foreach($input['product_id'] as $key => $product){
                $order = new PurchaseOrder;
                $order->supplier_id = $supplier;
                $order->item = $product;
                $order->price = str_replace(',', '', $input['unit_price'][$key]);
                $order->total = $input['quantity'][$key];
                $order->quantity = $input['quantity'][$key];
                $order->amount = str_replace(',', '', $input['amount'][$key]) ;
                $order->order_id = $generateOrderId;
                $order->approver_list = $input['approver_list'];
                $order->approval_order = $input['approval_order'];
                $order->approver_reminant = $input['approver_reminant'];
                $order->approved_by = $input['approved_by'];
                $order->approval_status = $input['approval_status'];
                $order->save();


                //approval level
                    //audit
                    $description = auth()->user()->name . " created new order";
                    myaudit('created', $order, $description);
            }

            DB::commit();



            return api_request_response(
                "ok",
                "Order Made Successfully!",
                success_status_code(),
                $order
            );
        }catch (\Exception $exception) {
            DB::commit();
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }

    public function savePurchaseOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $order = $input['id'];
            $user_id = Auth::user()->id;
            $supplier_id = $input['customer_id'];
            $input['purchase_order'] =  $input['order_id'];

            foreach ($order as $key => $item) {
                $orderSuccessful = PurchaseOrder::where('id', $item)->first();
                $orderSuccessful->update([
                    'date_supplied' => now(),
                    'quantity_supplied' => $input['quantity_supplied'][$key],
                    'supplied_price' => str_replace(',', '', $input['supplied_price'][$key]),
                    'supplied_amount' =>  str_replace(',', '', $input['supplied_amount'][$key]),
                    'is_supplied' => 1,
                    'received_by' => $user_id,
                ]);
                // save new received order
                $details = new ReceiveOrder;
                $details->quantity = $input['quantity_supplied'][$key];
                $details->amount =  str_replace(',', '', $input['supplied_amount'][$key]);
                $details->price = str_replace(',', '', $input['supplied_price'][$key]);
                $details->order_id = $input['purchase_order'];
                $details->user_id = Auth::user()->id;
                if($request->has('customer_id')){
                    $details->supplier_id = $supplier_id;
                }
                $details->product_id = $orderSuccessful->item;
                $details->type = $request->type;
                $details->to = $request->store_id;
                if($request->has('from')){
                    $details->from = $request->from;
                }
                $details->save();

                $product_id = $orderSuccessful->item;
                // check if store has this product
                $verify = StoreProduct::where('store_id', $request->store_id)->where('product_id', $product_id)->first();
                if($verify){
                    $start = $verify->quantity;
                    $end = $verify->quantity + $input['quantity_supplied'][$key];
                    $verify->update(['quantity' => $end]);
                }else{
                    $start = 0;
                    $end = $input['quantity_supplied'][$key];
                    $verify = StoreProduct::create([
                        "store_id" => $request->store_id,
                        "quantity" => $end,
                        "product_id" => $product_id
                    ]);
                }
                $store = Store::where('id', $request->store_id)->first();
                $changes = $orderSuccessful['quantity_supplied'];
                // get data into pincard;
                $stock_id = $product_id;
                $user_id = Auth::user()->id;
                $status= 4;
                switch ($request->type) {
                    case "Supplier-Main":
                        $description = "Purchase Order To" .' '. $orderSuccessful->supplier->name .' '. "Delivered To Main Store" .' '.  $store->store_name ?? "";
                        break;
                    case "Supplier-Outlet":
                        $description = "Purchase Order To" .' '. $orderSuccessful->supplier->name .' '. "Delivered To Store Outlet" .' '.  $store->store_name ?? "";
                        break;
                    case "Outlet-Main":
                        $description = "Item Moved From Store Outlet" .' '. $details->sender->store_name ?? "" .' '. "To Main Store" .' '.  $store->store_name ?? "";
                        break;
                    case "Outlet-Outlet":
                        $description = "Item Moved From Store Outlet" .' '. $details->sender->store_name ?? "" .' '. "To Store Outlet" .' '. $store->store_name ?? "";
                        break;
                    case "Main-Outlet":
                        $description = "Item Moved From Main Store" .' '. $details->sender->store_name ?? "" .' '. "To Store Outlet" .' '. $store->store_name ?? "";
                        break;
                    default:
                    $description = "Item Moved From Store Outlet" .' '. $orderSuccessful->supplier->name ?? "" .' '. "Delivered";
                    break;
                }

                $this->pincardFunction($stock_id, $user_id, $status, $start, $changes, $end, $description,$request->store_id);

            }

            DB::commit();

            return api_request_response(
                "ok",
                "Order Received Successfully!",
                success_status_code(),
                $supplier_id
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

    public function getFirstTwo($value){
        // Get the input string from the request
        $inputString = $value;

        // Explode the input string into an array of words
        $words = explode('-', $inputString);

        // Initialize an empty string to hold the extracted letters
        $extractedLetters = '';

        // Iterate through each word and extract the first letter
        foreach ($words as $word) {
            // Remove any leading or trailing spaces
            $word = trim($word);

            // Skip empty words
            if (empty($word)) {
                continue;
            }

            // Get the first letter of the word and convert it to uppercase
            $firstLetter = strtoupper(substr($word, 0, 1));

            // Append the first letter to the extracted letters string
            $extractedLetters .= $firstLetter;
        }
        return $extractedLetters;
    }
    public function productTransfer(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $storeProducts = $input['id'];
            $user_id = Auth::user()->id;
            $from = $input['from'];
            $to = $input['store_id'];

            $countAllOrders = ReceiveOrder::where('type',$request->type)->count();
            $newcount = $countAllOrders + 1;
            $paymentLength = strlen($newcount);
            if($paymentLength ==1){
                $sku = "00" . $newcount;
            }
            if($paymentLength ==2){
                $sku = "01" . $newcount;
            }
            if($paymentLength ==3){
                $sku =  $newcount;
            }
            if($paymentLength >= 4){
                $sku =  $newcount;
            }
            $first_two =$this->getFirstTwo($input['type']);

            $generateOrderId = "TRF$first_two".''.$sku;
            foreach ($storeProducts as $key => $item) {
                $orderSuccessful = StoreProduct::where('id', $item)->first();
                $prev = $orderSuccessful->quantity;
                $incoming = $input['quantity_supplied'][$key];
                // save new received order
                $details = new ReceiveOrder;
                $details->quantity = $input['quantity_supplied'][$key];
                $details->amount =  $input['supplied_price'][$key] * $input['quantity_supplied'][$key];
                $details->price = $input['supplied_price'][$key];
                $details->order_id = $generateOrderId;
                $details->user_id = Auth::user()->id;
                $details->product_id = $orderSuccessful->product_id;
                $details->type = $request->type ?? "Main-Outlet";
                $details->to = $to;
                $details->from = $from;
                $details->save();

                $product_id = $orderSuccessful->product_id;
                // get where quantity is coming from
                $orderSuccessful->update(['quantity' => $prev - $incoming]);

                // check if store has this product
                $verify = StoreProduct::where('store_id', $to)->where('product_id', $product_id)->first();
                if($verify){
                    $start = $verify->quantity;
                    $end = $verify->quantity + $input['quantity_supplied'][$key];
                    $verify->update(['quantity' => $end]);
                }else{
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
                $status= 4;
                switch ($request->type) {
                    case "Supplier-Main":
                        $description = "Purchase Order To" .' '. $orderSuccessful->supplier->name .' '. "Delivered To Main Store" .' '.  $store->store_name ?? "";
                        break;
                    case "Supplier-Outlet":
                        $description = "Purchase Order To" .' '. $orderSuccessful->supplier->name .' '. "Delivered To Store Outlet" .' '.  $store->store_name ?? "";
                        break;
                    case "Outlet-Main":
                        $description = "Item Moved From Store Outlet" .' '. $details->sender->store_name .' '. "To Main Store" .' '.  $store->store_name ;
                        break;
                    case "Outlet-Outlet":
                        $description = "Item Moved From Store Outlet" .' '. $details->sender->store_name .' '. "To Store Outlet" .' '. $store->store_name ;
                        break;
                    case "Main-Main":
                        $description = "Item Moved From Main Store" .' '. $details->sender->store_name .' '. "To Main Store" .' '. $store->store_name ;
                        break;
                    case "Main-Outlet":
                        $description = "Item Moved From Main Store" .' '. $details->sender->store_name  .' '. "To Store Outlet" .' '. $store->store_name ;
                        break;
                    default:
                    $description = "Item Moved From Store Outlet" .' '. $orderSuccessful->supplier->name  .' '. "Delivered";
                    break;
                }

                $this->pincardFunction($stock_id, $user_id, $status, $prev, $changes, $orderSuccessful->quantity, $description,$request->from);
                $this->pincardFunction($stock_id, $user_id, $status, $start, $changes, $end, $description,$request->store_id);

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


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['suppliers'] = Supplier::all();
        $data['items'] = Item::all();
        return view('admin.orders.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function print(Request $request)
    {
        $data['details'] = PurchaseOrder::where('order_id', $request->id)->get();
        return view('admin.orders.print',$data);
    }
    public function details(Request $request)
    {
        $data['details'] = PurchaseOrder::where('order_id', $request->id)->get();
        return view('admin.orders.details',$data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['details'] = PurchaseOrder::where('order_id', $id)->get();
        $data['items'] = Item::all();
        return view('admin.orders.edit',$data);
        // dd($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try{
            $order = PurchaseOrder::where('order_id', $request->id)->delete();
            return api_request_response(
                "ok",
                "Order Deleted Successful!",
                success_status_code(),
                $order
            );
        }catch (\Exception $exception) {
            return api_request_response(
                "error",
                $exception->getMessage(),
                bad_response_status_code()
            );

        }
    }
}
