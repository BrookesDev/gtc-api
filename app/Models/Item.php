<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Stock;

class Item extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($item) {
            // Set the value for the 'status' column
            $item->created_by = Auth::user()->id;
            $item->company_id = Auth::user()->company_id;
        });
    }
    // public function stock()
    // {
    //     $stock = Stock::where('item_id', $this->id)->first();
    //     if ($stock) {
    //         $value = $stock->quantity;
    //     } else {
    //         $value = 0;
    //     }
    //     return $value;
    // }
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id','id');
    }
    public function measurement()
    {
        return $this->belongsTo('App\Models\Unit', 'unit');
    }
    public function product_categories()
    {
        return $this->belongsTo('App\Models\ProductCategories', 'category_id','id');
    }
    public function sales()
    {
        return $this->belongsTo('App\Models\Account', 'sales_gl','id');
    }

    public function purchase_gl()
    {
        return $this->belongsTo('App\Models\Account', 'purchase_gl','id');
    }
    public function account_receivables()
    {
        return $this->belongsTo('App\Models\Account', 'account_receivable','id');
    }
    public function account_payables()
    {
        return $this->belongsTo('App\Models\Account', 'payable_gl','id');
    }
    public function advance_payments()
    {
        return $this->belongsTo('App\Models\Account', 'advance_payment_gl','id');
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'gl_code');
    }
    public function cost_of_good_gl()
    {
        return $this->belongsTo('App\Models\Account', 'cost_of_good_gl');
    }
    public function discount_gl()
    {
        return $this->belongsTo('App\Models\Account', 'discount_gl');
    }
   
    public function stock()
    {
        return $this->belongsTo('App\Models\Stock', 'id', 'item_id');
    }
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
