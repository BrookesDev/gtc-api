<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Department extends Model implements AuditableContract
{
   
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];
    

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($department) {
            // Set the value for the 'status' column
            $department->created_by = Auth::user()->id;
            $department->company_id = Auth::user()->company_id;
        });
        // static::saving(function ($post) {
        //         $post->updateBalance();
        // });
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
