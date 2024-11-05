<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Sale;
use App\Models\SaleTransaction;
class SaleController extends Controller
{
    public function index(){
        $sales = getSales()->get();
        return respond(true, 'List of sales fetched!', $sales, 201);
    }
    public function getSaleTransactons(){
        $sales = getSaleTransactons()->get();
        return respond(true, 'List of sales fetched!', $sales, 201);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'total_amount' => 'required|numeric',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:items,id', // Assuming purchase_orders table has 'id' column
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:0',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'amount' => 'required|array',
            'amount.*' => 'required|numeric|min:0',

        ]);

        if ($validator->fails()) {
            return respond(false, 'Validation error!', $validator->errors(), 400);
        }
        try {
            DB::beginTransaction();
            $input = $request->all();
            $items = $input['item_id'];

            $orders = getSales()->get();

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
            $month = now()->format('m');
            $uuid = "INV" . '-' . $month . '-' . rand(1000, 9999) . '-' . $code;
            foreach ($items as $key => $item) {
                // check if product already exists
                $check = Stock::where('item_id', $item)->first();
                if(!$check){
                    return respond(false, 'Unavailable  Stock!.', null, 400);
                }
                if($check->quantity < $input['quantity'][$key]){
                    // dd($check->quantity, $input['quantity'][$key]);
                    return respond(false, 'Out Of Stock!', null, 400);
                }
            }
            $sum =0;
            foreach ($items as $key => $item) {
                // dd($item['name']);

                $format = $input['amount'][$key];
                $price = $input['price'][$key];
                $quantity = $input['quantity'][$key];
                $order = new SaleTransaction;
                $order->price = $price;
                $order->quantity = $quantity;
                $order->amount = $format;
                $order->item_id = $item;
                $order->receipt_number = $uuid;
                $order->transaction_date = now();
                $order->save();
                $oldQuantity = $check->quantity;
                $newQuantity = $oldQuantity - $quantity;
                $check->update(['quantity' => $newQuantity]);
                // post to stock inventory
                stockInventory($item, $oldQuantity, $newQuantity, $quantity, $check->id, $format);
                $sum = $sum + $quantity;
            }
            $sale = new Sale;
            $sale->quantity = $sum;
            $sale->total_amount = $request->total_amount;
            $sale->receipt_number = $uuid;
            $sale->transaction_date = now();
            $sale->save();

            DB::commit();
            return respond(true, 'Data Update successful!', $input, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            return respond(false, 'Error!', $exception->getMessage(), 500);
        }
    }
}
