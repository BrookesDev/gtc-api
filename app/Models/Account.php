<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Account extends Model implements AuditableContract
{
    // protected $connection = 'mysql2';
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $fillable = [
        'gl_name',
        'category_id',
        'balance',
        'direction',
        'created_by',
        'company_id',
        'gl_code',
        'sub_category_id',
        'class_id',
        'user_id',
    ];

    // public function __construct(array $attributes = [])
    // {
    //     parent::__construct($attributes);

    //     // Initialize the 'postings' property as an empty array
    //     $this->postings = [];
    // }

    public function transactions()
    {
        return $this->hasMany(MyTransactions::class, 'credit_gl_code', 'id')->with('to');
    }

    public function receivables()
    {
        return $this->hasMany(MyTransactions::class, 'debit_gl_code', 'id')->with('customer');
    }

    public function class()
    {
        return $this->belongsTo('App\Models\Classes', 'class_id');
    }
    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }
    public function journals()
    {
        return $this->hasMany(Journal::class, 'gl_code', 'id');
    }
    public function Subcategory()
    {
        return $this->belongsTo('App\Models\SubCategory', 'sub_category_id');
    }

    public function getSummary($month)
    {
        $selectedMonth = Carbon::createFromFormat('F Y', $month);

        // Extract the month and year
        $month = $selectedMonth->month;
        $year = $selectedMonth->year;
        $amount = Journal::where('gl_code', $this->id)->whereMonth('transaction_date', $month)->sum('credit');
        return $amount;
    }

    public function totalBalance($start, $end)
    {
        $totalDebit = getFilterJournal()
            ->where('gl_code', $this->id)
            ->whereDate('transaction_date', '>=', $start)->whereDate('transaction_date', '<=', $end)->sum('debit');
        $totalCredit = getFilterJournal()
            ->where('gl_code', $this->id)
            ->whereDate('transaction_date', '>=', $start)->whereDate('transaction_date', '<=', $end)->sum('credit');
        // dd($totalDebit,$totalCredit,$this->gl_code);
        return abs($totalDebit - $totalCredit);
    }

    public function openingBalance($date)
    {
        $totalDebit = getFilterJournal()
            ->where('gl_code', $this->id)
            ->whereDate('transaction_date', '<', $date)->sum('debit');
        $totalCredit = getFilterJournal()
            ->where('gl_code', $this->id)
            ->whereDate('transaction_date', '<', $date)->sum('credit');

        return abs($totalDebit - $totalCredit);
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            $budget->created_by = Auth::user()->id ?? $budget->created_by;
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
}
