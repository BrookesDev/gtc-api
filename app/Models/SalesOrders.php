<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;


class SalesOrders extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'document_number',
        'reference',
        'date',
        'amount',
        'status',
        'expiring_date',
        'sales_rep',
        'company_id',
        'uuid',
        'total_price',
        'total_discount',
        'total_vat',
        'sub_total',
        'transaction_date',
        'option_type',
        'currency',

    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($input) {
            // Set the value for the 'status' column
            $input->company_id = Auth::user()->company_id;

        });
    }
    public function customer()
    {
        return $this->belongsTo('App\Customers', 'customer_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(SalesRep::class, 'sales_rep');
    }

    public function general_invoice()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid')->with('item', 'account', 'tax');
    }
    public function supporting_document()
    {
        return $this->hasMany(SupportingDocument::class, 'uuid', 'uuid');
    }
    public function items()
    {
        return $this->belongsTo(Item::class, 'name');
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
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
