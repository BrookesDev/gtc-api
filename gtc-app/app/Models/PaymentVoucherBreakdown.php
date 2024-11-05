<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class PaymentVoucherBreakdown extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;

    protected $fillable = [
        'description',
        'particular',
        'teller_number',
        'date',
        'total_tax_amount',
        'total_amount',
        'contract_amount',
        'voucher_id',
        'pvnumber',
        'beneficiary_id',
        'invoice_id',
        'beneficiary_account_id',
        'prepared_by',
        'payment_status',
        'transaction_id',
        'approver_list',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'expense',
        'payable',
        'processed_date',
        'approval_date',
        'payment_date',
        'approval_status',
        'channel',
        'document',
        'initiate',
        'bank_lodged',
        'bank_name',
        'account_name',
        'balance',
        'amount_paid',
        'uuid',
        'account_number'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            // Set the value for the 'status' column
            $payment->created_by = Auth::user()->id;
            $payment->company_id = Auth::user()->company_id;
        });
        // static::saving(function ($post) {
        //         $post->updateBalance();
        // });
    }

    public function approver()
    {
        return $this->belongsTo('App\Models\USer', 'approved_by');
    }
    public function beneficiary()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'beneficiary_id');
    }
    public function expenses()
    {
        return $this->belongsTo('App\Models\Account', 'expense');
    }
    public function payables()
    {
        return $this->belongsTo('App\Models\Account', 'payable');
    }
    public function bank()
    {
        return $this->belongsTo('App\Models\Account', 'bank_lodged');
    }

    // public function banks()
    // {
    //     return $this->hasmany('App\Models\BeneficiaryAccount', 'beneficiary_id');
    // }


    public function beneficiariesAccount()
    {
        return $this->belongsTo('App\Models\BeneficiaryAccount', 'beneficiary_account_id');
    }

    public function PreparerDetail()
    {
        return $this->belongsTo('App\Models\User', 'prepared_by');
    }

    // use Role;
    public function roless()
    {
        return $this->belongsTo('Spatie\Permission\Models\Role', 'approval_order');
    }

    public function accountName()
    {
        $name = $this->description;
        $account = BeneficiaryAccount::where('id', $this->beneficiary_id)->first();
        if (!empty($account)) {
            $name = $account->account_name;
        }

        return $name;

    }
    public function accountNumber()
    {
        $name = '';
        $account = BeneficiaryAccount::where('id', $this->beneficiary_id)->first();
        if (!empty($account)) {
            $name = $account->bank_account;
        }

        return $name;

    }
    public function bankName()
    {
        $name = '';
        // dd("here");
        $account = BeneficiaryAccount::where('id', $this->beneficiary_account_id)->first();
        if (!empty($account)) {
            $name = $account->bank_name;
        }

        return $name;

    }

    public function getBalance($id)
    {
        $account = Account::where('id', $id)->first();
        $budget = Budget::where('gl_code', $account->gl_code)->first();
        return $budget->balance;
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
