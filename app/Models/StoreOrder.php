<?php

namespace App\Models;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Role;

class StoreOrder extends Model implements Auditable
{
    use HasFactory; use \OwenIt\Auditing\Auditable;
    protected $fillable =[
        'order_id',
        'store_id',
        'total',
        'quantity',
        'item',
        'is_supplied',
        'date_supplied',
        'quantity_supplied',
        'action_by',
        'price',
        'amount',
        'supplied_price',
        'approver_list',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'approved_date',
        'processed_date',
        'approval_status',
        'supplied_amount',
        'received_by',
        'reason'
    ];
    public function store()
    {
        return $this->belongsTo('App\Models\Store', 'store_id');
    }

    public function RightApprover()
    {

        return $this->belongsTo(Role::class, 'approval_order')->withDefault( ['name' => ' ']);
    }


    public function product()
    {
        return $this->belongsTo('App\Models\Item', 'item');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Set the value for the 'action_by' column
            $order->action_by = Auth::user()->id;
        });
    }

    public function getSender(){
        $id = $this->order_id;
        $store = ReceiveOrder::where('order_id', $id)->first();
        return $store->sender->store_name;
    }
    public function getReceiver(){
        $id = $this->order_id;
        $store = ReceiveOrder::where('order_id', $id)->first();
        return $store->receiver->store_name;
    }
}
