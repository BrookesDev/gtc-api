<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Step extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    use AuditDescription;
    protected $fillable =[
        'description',
         'company_id',
        'created_by'
    ];
    public function CreatedBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by')->withDefault(['name' => '']);
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
