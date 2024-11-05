<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use App\Traits\AuditDescription;

class WorkingMonth extends Model implements AuditableContract
{
    use HasFactory, AuditDescription;
    use \OwenIt\Auditing\Auditable;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {

            $budget->initiated_by = Auth::user()->id;

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
