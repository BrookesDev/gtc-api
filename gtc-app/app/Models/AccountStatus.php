<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\SoftDeletes;
class AccountStatus extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory;
    use Auditable, AuditDescription;
    protected $fillable = [
        'uuid',
        'account_name',
        'account_code',
        'amount',
        'voucher_number'
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_name')->withDefault(['name'=>'Anonymous']);
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
