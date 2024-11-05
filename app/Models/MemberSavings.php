<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class MemberSavings extends Model implements AuditableContract
{
    use SoftDeletes;
    use Auditable, AuditDescription;
    use HasFactory;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($journal) {
            // Set the value for the 'status' column
            $journal->created_by = Auth::user()->id;
            $journal->company_id = Auth::user()->company_id;
        });
    }

    public function beneficiary()
    {
        return $this->belongsTo('App\Customers', 'member_id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function report()
    {
        return $this->belongsTo('App\Models\Account', 'report_to');
    }
    public function savings()
    {
        return $this->belongsTo('App\Models\MemberSavings', 'saving_name');
    }

    public function SavingType()
    {
        return $this->belongsTo('App\Models\NominalLedger', 'savings_type');
    }

    public function ModeOfSavings()
    {
        return $this->belongsTo('App\Models\Mode_of_Saving', 'mode_of_savings');
    }

    public function DebitAccount()
    {
        return $this->belongsTo('App\Models\Account', 'debit_account');
    }

    public function membername()
    {
        return $this->belongsTo('App\Customers', 'member_id');
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
