<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class InvoicesDetails extends Model implements AuditableContract
{
    use SoftDeletes;
    protected $connection = 'mysql2';
    use Auditable, AuditDescription;
    use HasFactory;
    protected $fillable = [
        'amount',
        'parent_id',
        'student_id',
        'transaction_id',
        'class_id',
        'account_id',
        'status',
        'session',
        'sub_class'
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
