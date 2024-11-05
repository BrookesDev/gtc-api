<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Assets_Classification extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $table = 'assets_classifications';
    protected $fillable = [
        'description',
        'depreciation_method',
        'depreciation_rate',
        'account',
    ];   
    public function method()
    {
        return $this->belongsTo('App\Models\DepreciationMethod', 'depreciation_method');
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