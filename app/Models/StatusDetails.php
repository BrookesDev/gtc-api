<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class StatusDetails extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable, AuditDescription;
    protected $fillable =[
        'recepient_name',
        'transaction_date',
        'description',
        'teller_no',
        'cash_account',
        'uu_id'
    ];
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
