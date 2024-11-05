<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Asset_Supplier extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $table = 'asset_suppliers';
    protected $fillable = [
        'company_name',
        'company_address',
        'company_email',
        'company_phone',
        'supplier_personalname',
        'supplier_personalphone',
        'supplier_personalemail',
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
