<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class StaffAllowance extends Model implements AuditableContract
{
    use Auditable;
    use AuditDescription;
    use HasFactory;
    protected $guarded = [];

    public function AllowanceDetail()
    {
        return $this->belongsTo('App\Models\AllowanceType', 'allowance_id')->withDefault(['name' => '']);
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