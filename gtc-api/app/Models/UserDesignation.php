<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class UserDesignation extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set the value for the 'status' column
            $user->assigned_by = auth()->user()->id;
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
