<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;
class IndividualMemberLedger extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory;
    use Auditable, AuditDescription;
    protected $table = 'individual_member_ledgers';

    protected $fillable = [
        'member_id',
        'account_id',
        'description',
        'bank',
        'date',
        'teller_number',
        "debit",
        "credit",
        'is_loan',
        'is_repayment',
        'uuid',
        'company_id',
        'is_reversed',
        'balance',
        'month',
        'action_by'
    ];

    public function member()
    {
        return $this->belongsTo('App\Models\Employees', 'member_id')->withDefault(['lastname' => '']);
    }

    public function code()
    {
        return $this->belongsTo('App\Models\AccountLoan', 'account_id')->withDefault(['description' => '']);
    }
    public function bankcode()
    {
        return $this->belongsTo('App\Models\Account', 'bank')->withDefault(['gl_name' => '']);
    }

    public function memberRecord()
    {
        $member = Employees::where('id', $this->member_id)->first();

        if ($member) {
            $memberRecord = $member->firstname . ' ' . $member->lastname . ' ' . $member->othername;
        } else {
            $memberRecord = ' ';
        }

        return $memberRecord;
    }

    public function bank()
    {
        $bank = Account::where('id', $this->bank)->first();

        if ($bank) {
            $memberRecord = $bank->gl_name;
        } else {
            $memberRecord = ' ';
        }

        return $memberRecord;
    }

    public function account()
    {
        $account = Account::where('id', $this->account_id)->first();

        if ($account) {
            $memberRecord = $account->description;
        } else {
            $memberRecord = ' ';
        }

        return $memberRecord;
    }
    public function insertIntoLedger($data)
    {
        try {
            // Insert data into the individual_member_ledger table
            IndividualMemberLedger::create($data);
            return true;
        } catch (\Exception $e) {
            // Handle the exception (e.g., log error, return false, etc.)
            return false;
        }
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
