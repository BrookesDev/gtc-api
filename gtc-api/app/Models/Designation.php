<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Designation extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use AuditDescription;
    protected $guarded = [];
    protected $table = 'designations';
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($designation) {
            // Set the value for the 'status' column
            $designation->company_id = auth()->user()->company_id;
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
