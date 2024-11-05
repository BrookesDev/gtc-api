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
class CustomerPersonalLedger extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            $receipt->company_id = Auth::user()->company_id;
            $receipt->created_by = Auth::user()->id;
        });
    }

    public function customer() {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
    public function loandata() {
        return $this->belongsTo(MemberLoan::class, 'invoice_number', 'prefix');
    }    
    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function invoice() {
        return $this->belongsTo(SaleInvoice::class, 'invoice_number', 'invoice_number');
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
