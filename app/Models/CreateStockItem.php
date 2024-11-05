<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class CreateStockItem extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $table = 'create_stock_items';
    protected $fillable = [
        'classification',
        'name',
        'category_id',
        'stock_code',
        'stock_id',
        're_order_level',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo('App\StockCategory','category_id')->withDefault(['name'=>'Anonymous']);
    }

    public function class()
    {
        return $this->belongsTo('App\Models\ItemClassification','classification')->withDefault(['name'=>'Anonymous']);
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
