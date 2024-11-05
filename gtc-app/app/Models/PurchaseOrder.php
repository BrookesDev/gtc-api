<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class PurchaseOrder extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $table = 'purchase_orders';

    protected $fillable = [
        'order_id',
        'supplier_id',
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
        'reason',
        'company_id',
        'reference',
        'expiring_date',
        'status',
        'uuid',
        'document_number',
        'sub_total',
        'total_vat',
        'total_discount',
        'total_price',
        'invoice_status',
        'invoice_status',
        'transaction_date',
    ];

    public function supplier()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'supplier_id')->withDefault(['name' => '']);
    }
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }

    public function stock()
    {
        return $this->belongsTo('App\Models\Item', 'item');
    }
    public function roles()
    {
        return $this->belongsTo('App\Role', 'approval_order')->withDefault(['name' => '']);
    }
    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item');
    }
    public function beneficiary()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'supplier_id');
    }
    public function general_invoice()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid')->with('item', 'tax');
    }
    public function supporting_document()
    {
        return $this->hasMany(SupportingDocument::class, 'uuid', 'uuid');
    }

    public function suppliers()
    {
        return $this->belongsTo('App\Customers', 'supplier_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            // Set the value for the 'status' column
            $receipt->created_by = Auth::user()->id;
            $receipt->company_id = Auth::user()->company_id;
        });
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
