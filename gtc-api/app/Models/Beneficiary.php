<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Beneficiary extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            $budget->company_id = Auth::user()->company_id;
            $budget->created_by = Auth::user()->id;
        });
    }

    public function createUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');

    }

    public function banks()
    {
        return $this->hasmany('App\Models\BeneficiaryAccount', 'beneficiary_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo('App\Models\'PaymentVoucher', 'beneficary_id');

    }
    public function ledgers() {
        return $this->hasMany(SupplierPersonalLedger::class, 'supplier_id');
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
