<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class TaxDeduction extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];

    public function beneficiary()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'contractor_id')->withDefault(['name' => '']);
    }
    public function voucher()
    {
        return $this->belongsTo('App\Models\PaymentVoucher', 'voucher_id')->withDefault(['name' => '']);
    }
    public function tax()
    {
        return $this->belongsTo('App\Models\Tax', 'tax_id')->withDefault(['name' => '']);
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
