<?php

namespace App\Models;

use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class ReceivableType extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable, AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            //$budget->company_id = Auth::user()->company_id;
            $budget->created_by = Auth::user()->id;
            $budget->continent_id = Auth::user()->continent_id;
            $budget->region_id = Auth::user()->region_id;
            $budget->province_id = Auth::user()->province_id;
        });
    }

    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'created_by');

    }
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'gl_code');

    }
    public function continent()
    {
        return $this->belongsTo('App\Models\Continent', 'continent_id');

    }
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'region_id');

    }
    public function province()
    {
        return $this->belongsTo('App\Models\Province', 'province_id');

    } public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
