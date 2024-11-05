<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Rating extends Model implements Auditable
{
    use HasFactory;use \OwenIt\Auditing\Auditable;
    protected $guarded = [];

    public function rate()
    {
        return $this->belongsTo('App\Models\User','customer_id');
    }
    public function item()
    {
        return $this->belongsTo('App\Models\Item','product_id');
    }
}
