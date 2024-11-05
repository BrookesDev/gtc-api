<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

class AllowanceAmount extends Model implements AuditableContract
{
    use AuditDescription;
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [

        'allowance_id',
        'lower_level',
        'upper_level',
        'percentage',
        'fixed_amount',
        'spec_type',
        'company_id',
        'created_by'
    ];
    public function Allowance()
    {
        return $this->belongsTo('App\Models\AllowanceType', 'allowance_id');
    }
    public function UpperLevel()
    {
        return $this->belongsTo('App\Models\Level', 'upper_level');
    }
    public function LowerLevel()
    {
        return $this->belongsTo('App\Models\Level', 'lower_level');
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
