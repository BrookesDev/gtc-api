<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Payable_Type extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $table = 'payable_types';

    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            //$budget->company_id = Auth::user()->company_id;
            $budget->created_by = Auth::user()->id;
            $budget->province_id = Auth::user()->company_id;
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
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'province_id');
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
