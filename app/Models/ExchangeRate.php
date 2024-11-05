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
        'currency',
        'rate',
        'specified_by',
        'company_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($exchangerate) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            // $budget->created_by = Auth::user()->id;
            // $budget->company_id = Auth::user()->company_id;
            $exchangerate->company_id = Auth::user()->company_id;
            $exchangerate->specified_by = Auth::user()->id;
            
        });
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency', 'currency');
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
