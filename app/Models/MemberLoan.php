<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class MemberLoan extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory;
    use Auditable, AuditDescription;
    protected $table = 'member_loans';
    protected $fillable = [
        'employee_id',
        'loan_name',
        'prefix',
        'principal_amount',
        'interest_amount',
        'total_repayment',
        'duration',
        'monthly_deduction',
        'loan_interest',
        'bank',
        'receipt_number',
        'balance',
        'status',
        'company_id',
        'disbursed',
        'disbursed_amount',
        'approved',
        'total_loan',
        'month',
        'action_by',
        'approver_list',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'approval_status',
        'last_approved_date',
        'reason',
        'uuid',
        'declined_by',
        'declined_date',
        'transaction_date',
        'cheque_number',
        'file',
    ];

    public function roles()
    {
        return $this->belongsTo('App\Role', 'approval_order')->withDefault(['name' => '']);
    }
    public function mbm()
    {
        return $this->belongsTo('App\Models\Employees', 'employee_id');
    }
    public function ln()
    {
        return $this->belongsTo('App\Models\NominalLedger', 'loan_name');
    }
    public function loan()
    {
        return $this->belongsTo('App\Models\NominalLedger', 'loan_name');
    }

    public function bnk()
    {
        return $this->belongsTo('App\Models\Account', 'bank')->withDefault(['name' => '']);
    }
    public function bankName()
    {
        return $this->belongsTo('App\Models\BankAccount', 'bank')->withDefault(['name' => '']);
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    

    // MemberLoan Model

    public function beneficiary()
    {
        return $this->belongsTo('App\Customers', 'employee_id');
    }

    public function report()
    {
        return $this->belongsTo('App\Models\Account', 'report_to');
    }

    public function MemberRecord()
    {
        $member = Employees::where('id', $this->employee_id)->first();

        if ($member) {
            $memberRecord = $member->firstname . ' ' . $member->lastname . ' ' . $member->othername;
            // $memberRecord = $member->name;
        } else {
            $memberRecord = ' ';
        }

        return $memberRecord;
    }

    public function repayment()
    {
        $monthlyDeduction = IndividualMemberLedger::where('member_id', $this->employee_id)->where('account_id', $this->loan_name)->where('is_reversed', '!=', 1)->get();
        $total = $monthlyDeduction->sum('credit');
        return $total;
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            // Set the value for the 'status' column
            $payment->created_by = Auth::user()->id;
            $payment->company_id = Auth::user()->company_id;
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

}
