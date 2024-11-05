<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Invoices extends Model implements AuditableContract
{
    use SoftDeletes;
    protected $connection = 'mysql2';
    use Auditable, AuditDescription;
    use HasFactory;
    protected $fillable = [
        'payment_by',
        'description',
        'amount',
        'transaction_id',
        'on_behalf_of',
        'session',
        'class_id',
        'status',
        'term_id',
        'sub_class',
        'paystack_id',
        'approved_by',
        'approved_date',
        'receipt_number',
        'approval_status',
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
