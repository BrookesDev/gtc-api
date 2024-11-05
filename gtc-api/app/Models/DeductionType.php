<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class DeductionType extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    protected $fillable = [
        'description',
        'company_id',
        'created_by'
    ];

    public function CreatedBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function transformAudit(array $data): array
    {
       
        $data['user_id'] = Auth::id();
        return $data;
    }

}
