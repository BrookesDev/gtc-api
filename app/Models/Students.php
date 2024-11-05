<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Students extends Model implements AuditableContract
{
      protected $connection = 'mysql2';
      use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'role',
        'blood_group',
        'religion',
        'email',
        'class',
        'section',
        'phone',
        'user_id',
        'address',
        'sub_class',
        'short_bio',
        'student_id',
        'image'
    ];

    public function classes() {
        return $this->belongsTo( 'App\Models\Classes', 'class' )->withDefault( ['description' => 'Anonymous'] );
    }

    public function subClass() {
        return $this->belongsTo( 'App\Models\Classes', 'sub_class' )->withDefault( ['description' => 'Anonymous'] );
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
