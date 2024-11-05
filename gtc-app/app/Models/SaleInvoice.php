<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Customers;

class SaleInvoice extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable, AuditDescription;
    use SoftDeletes;
    protected $table = 'sale_invoices';
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            $receipt->company_id = Auth::user()->company_id;
            $receipt->created_by = Auth::user()->id;
        });
    }
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
    public function glname()
    {
        return $this->belongsTo(Account::class, 'debit_gl_code');
    }
    public function items()
    {
        return $this->hasMany(GeneralInvoice::class, 'invoice_number', 'invoice_number')->with('item', 'tax');
    }
    public function orders()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid')->with('item');
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
