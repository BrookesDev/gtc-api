<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Parents extends Model implements AuditableContract
{
    protected $connection = 'mysql2';
    use SoftDeletes;
    use Auditable, AuditDescription;
    use HasFactory;
    protected $fillable = [
        'name',
        'occupation',
        'nationality',
        'religion',
        'email',
        'address',
        'phone',
        'user_id'
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
