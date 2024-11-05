<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;

class SubCategory extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($input) {
            // Set the value for the 'status' column
            $input->company_id = Auth::user()->company_id;

        });
    }
    public function last()
    {
        return $this->hasMany(CategoryAccount::class, 'sub_category_id', 'id');
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'sub_category_id', 'id')->where('company_id', auth()->user()->company_id);
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
