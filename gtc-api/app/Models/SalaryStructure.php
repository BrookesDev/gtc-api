<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class SalaryStructure extends Model implements AuditableContract
{
    use Auditable;
    use AuditDescription;
    use HasFactory;
    protected $guarded =[];

    public function Level()
    {
        return $this->belongsTo('App\Models\Level', 'level')->withDefault(['name' => '']);;
    }
    public function Step()
    {
        return $this->belongsTo('App\Models\Step', 'step')->withDefault(['name' => '']);;
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
