<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

class AllowanceType extends Model implements AuditableContract
{
    use AuditDescription;
    use Auditable;
    use HasFactory;
    protected $fillable = [
        'description',
        'company_id',
        'created_by'
    ];

    public function CreatedBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
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
