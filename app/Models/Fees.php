<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Fees extends Model implements AuditableContract
{
    // protected $connection = 'mysql2';
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $fillable = [
        'account_id',
        'class_id',
        'amount',
        'session',
        'term_id'
    ];

    public function account() {
        return $this->belongsTo( 'App\Models\Account', 'account_id');
    }

    public function class() {
        return $this->belongsTo( 'App\Models\Classes', 'class_id');
    }

    public function session() {
        return $this->belongsTo( 'App\Models\Session', 'session');
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
