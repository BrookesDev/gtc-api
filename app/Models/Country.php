<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class Country extends Model implements AuditableContract
{
    use Auditable;
    use AuditDescription;
    use HasFactory;
    protected $table = 'country';
    protected $fillable = [
        'id',
        'iso',
        'name',
        'nicename',
        'iso3',
        'numcode',
        'phoecode'

    ];
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;
        return $data;
    }
}
