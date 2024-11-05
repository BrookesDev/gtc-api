<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class StoreProduct extends Model implements Auditable
{
    use HasFactory;  use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'store_id',
        'product_id',
        'quantity'
    ];
    public function store()
    {
        return $this->belongsTo('App\Models\Store','store_id');
    }
    public function product()
    {
        return $this->belongsTo('App\Models\Item','product_id');
    }

    public function checkOrderLevel(){
        $product = Item::where('id', $this->product_id)->first();
        $order = $product->minimum_quantity;
        if($this->quantity < $order){
            $case = true;
        }else{
            $case = false;
        }
        return $case;
    }
}
