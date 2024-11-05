<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class ExchangeRate extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $fillable = [
        'currency_type',
        'exchange_rate',
        'specified_by',
        'continent_id',
        'region_id',
        'province_id',
        'date',

    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            // $budget->created_by = Auth::user()->id;
            // $budget->company_id = Auth::user()->company_id;
            $budget->continent_id = Auth::user()->continent_id;
            $budget->region_id = Auth::user()->region_id;
            $budget->province_id = Auth::user()->province_id;
        });
    }
    public function province()
    {
        return $this->belongsTo('App\Models\Province', 'province_id');
    }
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'region_id');
    }
    public function continent()
    {
        return $this->belongsTo('App\Models\Continent', 'continent_id');
    }
    public function currency()
    {
        return $this->belongsTo('App\Models\Currency', 'currency_type');
    }
    public function specify_by()
    {
        return $this->belongsTo('App\Models\User', 'specified_by');
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
