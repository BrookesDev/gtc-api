<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Cashbook extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory;
    use Auditable, AuditDescription;
    protected $table = 'cashbooks';
    protected $fillable = [
        'pbank',
        'pcash',
        'bank',
        'cash',
        'details',
        'particular',
        'transaction_date',
        'gl_code',
        'chq_teller',
        'continent_id',
        'region_id',
        'province_id',
        'currency_amount',
        'uuid'
    ];

    public function getAccountName()
    {
        $name = '';
        $account = Account::where('id',$this->gl_code)->first();
        if (!empty($account)) {
            $name = $account->gl_name;
        }

        return $name;

    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cashbook) {
            // Set the value for the 'status' column
            $cashbook->continent_id = Auth::user()->continent_id;
            $cashbook->region_id = Auth::user()->region_id;
            $cashbook->province_id = Auth::user()->province_id;
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
