<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
class Sale extends Model implements AuditableContract 
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($sale) {
            $sale->company_id = Auth::user()->company_id;
            $sale->action_by = Auth::user()->id;
        });
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
