<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
class PurchaseLedger extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription; 
    use SoftDeletes;
    protected $guarded = [];
    

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            $budget->continent_id = Auth::user()->continent_id;
            $budget->region_id = Auth::user()->region_id;
            $budget->province_id = Auth::user()->province_id;
        });
    }

    public function glname() {
        return $this->belongsTo(Account::class, 'credit_gl');
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
