<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class Deduction extends Model implements AuditableContract
{
    use Auditable;
    use AuditDescription;
    use HasFactory;
    protected $fillable = [
        "staff_id",
        'company_id',
        "deduction_type_id",
        "year",
        "month",
        "amount",
        "created_at",
        "updated_at"
    ];

    public function Staff()
    {
        return $this->belongsTo('App\Models\Staff', 'staff_id');
    }
    public function DeductionType()
    {
        return $this->belongsTo('App\Models\DeductionType', 'deduction_type_id');
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
