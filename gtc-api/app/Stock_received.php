<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock_received extends Model
{
    use SoftDeletes;
    protected $table ='stock_received';
    protected $fillable = [
        'user_id',
        'customer_id',
        // 'supplier_id',
        'purchase_order',
        'total_quantity'

    ];

    public function srecieved()
    {
        return $this->belongsTo('App\Models\Supplier', 'customer_id')->withDefault(['name'=> '']);
    }

}
