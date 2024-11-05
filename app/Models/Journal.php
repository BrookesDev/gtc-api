<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use App\Models\Account;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Journal extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $fillable = [
        'uuid',
        'details',
        'gl_code',
        // 'amount',
        'currency_amount',
        'debit',
        'continent_id',
        'region_id',
        'province_id',
        'credit'
    ];

    public function deductions()
    {
        return $this->hasMany('App\Models\MonthlyDeduction', 'uuid', 'uuid')->with('employee');
    }
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'gl_code');
    }


    public function openingBalance($date)
    {
        $totalDebit = getFilterJournal()
            ->where('gl_code', $this->gl_code)
            ->whereDate('transaction_date', '<', $date)->sum('debit');
        $totalCredit = getFilterJournal()
            ->where('gl_code', $this->gl_code)
            ->whereDate('transaction_date', '<', $date)->sum('credit');
        $getAccount = Account::find($this->gl_code);
        $classId = $getAccount->class_id;
        if (in_array($classId, [1, 5])) {
            if ($this->sub_sub_category_id == 64) {
                $amount = $totalCredit - $totalDebit;
            } else {

                $amount = $totalDebit - $totalCredit;
            }
        } else {
            $amount = $totalCredit - $totalDebit;
        }
        return $amount;
        // return abs($totalDebit - $totalCredit);
    }

    // protected $casts = [
    //     'transaction_date' => 'datetime:Y-m-d H:i A','modified' => 'datetime:Y-m-d H:i A'
    // ];
    public function getAccountName()
    {
        $name = '';
        $account = Account::where('id', $this->gl_code)->first();
        if (!empty($account)) {
            $name = $account->gl_name;
        }

        return $name;

    }
    public function direction()
    {
        $name = '';
        $account = Account::where('id', $this->gl_code)->first();
        if (!empty($account)) {
            $name = $account->direction;
        }

        return $name;

    }

    public function getAccountCode()
    {
        $name = '';
        $account = Account::where('id', $this->gl_code)->first();
        if (!empty($account)) {
            $name = $account->gl_code;
        }
        return $name;
    }
    public function getDebitTrialBalance($start, $end)
    {
        // dd($start);
        $value = '';
        if ($start == null || $end == null || $end == "null" || $start == "null") {
            // dd("here");
            $journal = Journal::where('gl_code', $this->gl_code)->get();
            // $journal = Journal::where('gl_code',$this->gl_code)->get();
            // dd($this->gl_code);
            $debit = $journal->sum('debit');
            $credit = $journal->sum('credit');
            $balance = $debit - $credit;
            if ($balance < 0) {
                // dd($balance);
                // $value = $balance;
                $value = $credit - $debit;
                // dd($debit);
            }
        } else {
            $start_date = Carbon::parse($start)
                ->toDateTimeString();
            $end_date = Carbon::parse($end)
                ->toDateTimeString();
            $journal = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $this->gl_code)->get();
            // $journal = Journal::where('gl_code',$this->gl_code)->get();
            // dd($this->gl_code);
            $debit = $journal->sum('debit');
            $credit = $journal->sum('credit');
            $balance = $debit - $credit;
            if ($balance < 0) {
                // dd($balance);
                // $value = $balance;
                $value = $credit - $debit;
                // dd($debit);
            }
        }
        // dd($credit);
        return $value;
    }

    public function getContinentName()
    {
        $continent = Continent::find($this->continent_id);
        return $continent->description;
    }

    public function getCreditTrialBalance($start, $end)
    {
        $value = '';
        if ($start == null || $end == null || $end == "null" || $start == "null") {
            $journal = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $this->gl_code)->get();
            // dd($journal);
            $debit = $journal->sum('debit');
            $credit = $journal->sum('credit');
            $balance = $debit - $credit;
            if ($balance > 0) {
                // dd($balance);
                $value = $balance;
            }
        } else {
            $value = '';
            $start_date = Carbon::parse($start)
                ->toDateTimeString();
            $end_date = Carbon::parse($end)
                ->toDateTimeString();
            $journal = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $this->gl_code)->get();
            // dd($journal);
            $debit = $journal->sum('debit');
            $credit = $journal->sum('credit');
            $balance = $debit - $credit;
            if ($balance > 0) {
                // dd($balance);
                $value = $balance;
            }
        }
        return $value;
    }
    public function getDebitIncomeBalance($start, $end)
    {
        // dd($start);
        $value = '';
        $start_date = Carbon::parse($start)
            ->toDateTimeString();
        $end_date = Carbon::parse($end)
            ->toDateTimeString();
        $journal = Journal::whereBetween('transaction_date', [
            $start_date,
            $end_date
        ])->where('gl_code', $this->gl_code)->get();
        // $journal = Journal::where('gl_code',$this->gl_code)->get();
        // dd($this->gl_code);
        $debit = $journal->sum('debit');
        $credit = $journal->sum('credit');
        $balance = $debit - $credit;
        if ($balance < 0) {
            // dd($balance);
            // $value = $balance;
            $value = $credit - $debit;
            // dd($debit);
        }
        // dd($credit);
        return $value;
    }

    public function getCreditIncomeBalance($start, $end)
    {
        $value = '';
        $start_date = Carbon::parse($start)
            ->toDateTimeString();
        $end_date = Carbon::parse($end)
            ->toDateTimeString();
        $journal = Journal::whereBetween('transaction_date', [
            $start_date,
            $end_date
        ])->where('gl_code', $this->gl_code)->get();
        // dd($journal);
        $debit = $journal->sum('debit');
        $credit = $journal->sum('credit');
        $balance = $debit - $credit;
        if ($balance > 0) {
            // dd($balance);
            $value = $balance;
        }
        return $value;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($journal) {
            // Set the value for the 'status' column
            $journal->created_by = Auth::user()->id;
            $journal->company_id = Auth::user()->company_id;
        });
        // static::saving(function ($post) {
        //         $post->updateBalance();
        // });
    }

    // protected function updateBalance(){
    //     $account = Account::where('id', $this->gl_code)->first();
    //     $balance = $account->balance;
    //     if($this->debit < 1){
    //         $account->update(['balance' => $balance + $this->credit]);
    //     }else{
    //         $account->update(['balance' => $balance - $this->debit]);
    //     }
    // }

    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }

}
