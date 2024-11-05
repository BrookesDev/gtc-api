<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;

class AuditTrail extends Model
{
    use HasFactory;
    use SoftDeletes;
    // use Auditable, AuditDescription;
    protected $guarded = [];
    public function user() {
        return $this->belongsTo( 'App\Models\User', 'user_id' );
    }
    // public function transformAudit(array $data): array
    // {

    //     $user = $this->getUser(Auth::id());
    //     $description = $this->generateDescription($data, $user);
    //     $data['user_id'] = Auth::id();
    //     $data['description'] = $description;

    //     return $data;
    // }
}
