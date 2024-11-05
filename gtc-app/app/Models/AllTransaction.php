<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;
class AllTransaction extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            $budget->action_by = Auth::user()->id ?? $budget->action_by;
            $budget->company_id = Auth::user()->company_id ?? $budget->company_id;
        });
    }
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }

    public function savings()
    {
        return $this->belongsTo('App\Models\MemberSavings', 'transaction_number','uuid')->with(['membername','ModeOfSavings','DebitAccount']);
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }
}
