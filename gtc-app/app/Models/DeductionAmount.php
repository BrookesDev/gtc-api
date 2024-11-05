<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class DeductionAmount extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    protected $fillable = [

        'deduction_id',
        'lower_level',
        'upper_level',
        'percentage',
        'company_id',
        'fixed_amount',
        'created_by'
    ];
    public function Deduction()
    {
        return $this->belongsTo('App\Models\DeductionType', 'deduction_id');
    }
    public function UpperLevel()
    {
        return $this->belongsTo('App\Models\Level', 'upper_level');
    }
    public function LowerLevel()
    {
        return $this->belongsTo('App\Models\Level', 'lower_level');
    }
    public function transformAudit(array $data): array
    {
       
        $data['user_id'] = Auth::id();
        return $data;
    }
}
