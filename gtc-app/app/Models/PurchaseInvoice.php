<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            $receipt->company_id = Auth::user()->company_id;
            $receipt->created_by = Auth::user()->id;
        });
    }
    public function supplier()
    {
        return $this->belongsTo(Beneficiary::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(GeneralInvoice::class, 'invoice_number', 'uuid')->with('item', 'tax');
    }
    public function tax()
    {
        return $this->hasMany(GeneralInvoice::class, 'invoice_number', 'invoice_number');
    }

    public function general_invoice()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid')->with('item', 'tax');
    }

    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }

    public function orders()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid')->with('item');
    }


}
