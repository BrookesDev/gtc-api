<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Repayment extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($repayments) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            $repayments->action_by = Auth::user()->id;
            $repayments->company_id = Auth::user()->company_id;
        });
    }


    public function LoanAccount()
    {
        return $this->belongsTo('App\Models\MemberLoan', 'account_id')->with('loan');
    }

    public function SavingsAccount()
    {
        return $this->belongsTo('App\Models\MemberSavings', 'account_id')->with(['SavingType', 'ModeOfSavings', 'savings']);
    }
    public function Account()
    {
        return $this->belongsTo('App\Models\Account', 'bank');
    }
    public function Customer()
    {
        return $this->belongsTo('App\Customers', 'customer_id');
    }

    public function Created_by()
    {
        return $this->belongsTo('App\Models\User', 'action_by');
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
