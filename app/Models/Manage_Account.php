<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;


class Manage_Account extends Model implements AuditableContract
{
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $table = 'manage_accounts';

    protected $fillable =[
    'recepient_name',
    'transaction_date',
    'description',
    'teller_no',
    'cash_account',
    'uu_id'
    ];
    use HasFactory;

    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
