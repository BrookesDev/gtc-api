<?php

namespace App\Models;

use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyTransactions extends Model implements AuditableContract
{
    use Auditable, AuditDescription;
    use SoftDeletes;
    use HasFactory;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($journal) {
            // Set the value for the 'status' column
            $journal->action_by = Auth::user()->id;
            $journal->company_id = Auth::user()->company_id;
        });
    }

    protected $appends = ['age_report'];

    public function getAgeReportAttribute()
    {
        return Carbon::parse($this->created_at)->diffInDays(Carbon::now());
    }
    public function items()
    {
        return $this->hasMany(GeneralInvoice::class, 'invoice_number', 'invoice_number')->with('item', 'tax');
    }
    public function teller()
    {
        return $this->belongsTo('App\Models\PurchaseInvoice', 'uuid', 'invoice_number')->with('supplier');
    }
    public function prepared()
    {
        return $this->belongsTo('App\Models\User', 'prepared_by');
    }
    public function memberLoan()
    {
        return $this->belongsTo('App\Models\MemberLoan', 'uuid', 'uuid'); // Assuming uuid is the common key
    }
    public function paymentBank()
    {
        return $this->belongsTo('App\Models\Payment_Bank', 'payment_bank')->with('report_gl');
    }
    public function supplier()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'supplier_id')->with('banks');
    }
    public function to()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'supplier_id');
    }
    public function customer()
    {
        return $this->belongsTo('App\Customers', 'customer_id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'action_by');
    }
    public function currency()
    {
        return $this->belongsTo('App\Models\Currency', 'currency');
    }
    public function debitAccount()
    {
        return $this->belongsTo('App\Models\Account', 'debit_gl_code');
    }
    public function creditAccount()
    {
        return $this->belongsTo('App\Models\Account', 'credit_gl_code');
    }
    public function bankLodged()
    {
        return $this->belongsTo('App\Models\Account', 'bank_lodged');
    }
    public function receivableType()
    {
        return $this->belongsTo('App\Models\ReceivableType', 'receivable_type');
    }
    public function loanname()
    {
        return $this->belongsTo('App\Models\NominalLedger', 'payable_type');
    }
    public function lodgedBy()
    {
        return $this->belongsTo('App\Models\User', 'lodged_by');
    }
    public function mode()
    {
        return $this->belongsTo('App\Models\Mode_of_Saving', 'payment_mode');
    }
    public function salesinvoice()
    {
        return $this->belongsTo('App\Models\SaleInvoice', 'uuid', 'uuid')->with(['items', 'customer']);
    }
    public function savings()
    {
        return $this->belongsTo('App\Models\MemberSavings', 'uuid')->with(['SavingType', 'customer','membername','ModeOfSavings','DebitAccount']);
    }
    public function purchaseinvoice()
    {
        return $this->belongsTo('App\Models\PurchaseInvoice', 'uuid', 'uuid')->with(['items', 'supplier']);
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
