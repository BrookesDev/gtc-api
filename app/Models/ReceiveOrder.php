<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ReceiveOrder extends Model implements Auditable
{
    use HasFactory;  use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'supplier_id','product_id',
        'quantity',
        'amount',
        'order_id',
        'user_id',
        'price',     
        'to',     
        'from',     
        'type',     
    ];

    public function product()
    {
        return $this->belongsTo('App\Models\Item','product_id');
    }
    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier','supplier_id');
    }
    public function receiver()
    {
        return $this->belongsTo('App\Models\Store','to');
    }
    public function sender()
    {
        return $this->belongsTo('App\Models\Store','from');
    }

}
