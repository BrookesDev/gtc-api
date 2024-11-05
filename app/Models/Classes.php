<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Classes extends Model implements AuditableContract
{
    use SoftDeletes;
    protected $connection = 'mysql';
    use HasFactory, AuditDescription, Auditable;
    protected $table = "classes";
    protected $fillable = [
        'description',
        'created_by'
    ];

    public function categories()
    {
        return $this->hasMany(Category::class, 'class_id', 'id');
    }
    public function catAccounts()
    {
        return $this->hasMany(SubCategory::class, 'class_id', 'id');
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
