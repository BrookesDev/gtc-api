<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model implements AuditableContract
{
    use SoftDeletes;
    protected $connection = 'mysql';
    use HasFactory, Auditable, AuditDescription;
    // SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    protected $fillable =[
        'description',
        'category_id',
        'has_child',
        'class_id',
        'category_parent',
        'category_code',
        'code'
    ];

    public function items()
    {
        return $this->hasMany(Category::class, 'category_id');
    }

    public function getAllChildCategoryIdsAttribute()
    {
        $childIds = $this->childServices->pluck('id')->flatten()->toArray();
        return array_merge([$this->id], $childIds);
    }

    // recursive relationship
    public function childServices()
    {
        return $this->hasMany(Category::class, 'category_id')->with('items');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'id');
    }
    public function last()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'id');
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getParentsNames() {
        if($this->parent) {
            return $this->parent->getParentsNames(). " > " . $this->description;
        } else {
            return $this->description;
        }
    }
    public function accounts() {
        return $this->hasMany(Account::class, 'category_id', 'id')->where('company_id', auth()->user()->company_id);
    }

    public function class() {
        return $this->belongsTo(Classes::class, 'class_id', 'id');
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
