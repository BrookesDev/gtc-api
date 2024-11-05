<?php

namespace App\Models;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Receipt extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $table = 'receipts';
    protected $fillable = [
        'uuid',
        'particulars',
        'description',
        'teller_number',
        'voucher_number',
        'transaction_date',
        'lodgement_status',
        'amount',
        'bank_lodged',
        'date_lodged',
        'currency',
        'payment_mode',
        'created_by',
        'gl_code',
        'initial_amount',
        'bank_id',
        'currency_symbol',
        'currency_id',
        'continent_id',
        'region_id',
        'province_id',
        'company_id',
        'type',
        'currency_amount',
        'lodge_by'
        ];

    public function nuban()
    {
        return $this->belongsTo('App\Models\Bank', 'bank_id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function bank()
    {
        return $this->belongsTo('App\Models\Account', 'bank_lodged')->withTrashed();
    }
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'gl_code')->withTrashed();;
    }

    public function lodger()
    {
        return $this->belongsTo('App\Models\User', 'lodge_by');
    }

    public function getCash($start,$end){
        if (is_int($start) || $start == "" || is_numeric($start) ) {
            $cash = Receipt::where('bank_lodged', $this->bank_lodged)->where('currency_symbol', $this->currency_symbol)->where('currency_id', $this->currency_id)->where('lodgement_status', 1)->where('payment_mode', 'cash')->get();
            if($cash->count() < 1){
                $value = "";
            }else{
                $total = $cash->sum('amount');
                $formattedNumber = number_format($total, 2);
                $value = $this->currency_symbol.''.$formattedNumber;
            }
        }else{
            $start_date = Carbon::parse($start)
            ->toDateTimeString();
            $end_date = Carbon::parse($end)
            ->toDateTimeString();
            $cash = Receipt::where('bank_lodged', $this->bank_lodged)->where('currency_symbol', $this->currency_symbol)->where('currency_id', $this->currency_id)->where('lodgement_status', 1)->where('payment_mode', 'cash')->whereBetween('created_at', [
                $start_date, $end_date
            ])->get();
            if($cash->count() < 1){
                $value = "";
            }else{
                $total = $cash->sum('amount');
                $formattedNumber = number_format($total, 2);
                $value = $this->currency_symbol.''.$formattedNumber;
            }
        }
        return $value ;
    }

    public function getCheques($start,$end){
        if (is_int($start) || $start == "" || is_numeric($start) ) {
            $cash = Receipt::where('bank_lodged', $this->bank_lodged)->where('currency_symbol', $this->currency_symbol)->where('currency_id', $this->currency_id)->where('lodgement_status', 1)->where('payment_mode', 'cheque')->get();
            if($cash->count() < 1){
                $value = "";
            }else{
                $total = $cash->sum('amount');
                $formattedNumber = number_format($total, 2);
                $value = $this->currency_symbol.''.$formattedNumber;
            }
        }else{
            $start_date = Carbon::parse($start)
            ->toDateTimeString();
            $end_date = Carbon::parse($end)
            ->toDateTimeString();
            $cash = Receipt::where('bank_lodged', $this->bank_lodged)->where('currency_symbol', $this->currency_symbol)->where('currency_id', $this->currency_id)->where('lodgement_status', 1)->where('payment_mode', 'cheque')->whereBetween('created_at', [
                $start_date, $end_date
            ])->get();
            if($cash->count() < 1){
                $value = "";
            }else{
                $total = $cash->sum('amount');
                $formattedNumber = number_format($total, 2);
                $value = $this->currency_symbol.''.$formattedNumber;
            }
        }
        // dd($cash);
        
        return $value ;
    }

    public function getTotalCash($start,$end){
        // dd($start, $end);
        if (is_int($start) || $start == "" || is_numeric($start) ) {
            // dd("here");
            $value = Receipt::where('payment_mode', 'cash')->where('lodgement_status', 1)->sum('amount');
            
            // Now, you can use $startDate and $endDate in your model logic
        }else{
            // dd($start,$end);
            $start_date = Carbon::parse($start)
            ->toDateTimeString();
            $end_date = Carbon::parse($end)
            ->toDateTimeString();
            $value = Receipt::where('payment_mode', 'cash')->where('lodgement_status', 1)->whereBetween('created_at', [
                $start_date, $end_date
            ])->sum('amount');
        }
        return $value ;
    }
    public function getTotalCheques($start,$end){
        // dd($start);
        if (is_int($start) || $start == "" || is_numeric($start) ) {
            $value = Receipt::where('payment_mode', 'cheque')->where('lodgement_status', 1)->sum('amount');
            // dd($start,$end);
           
        }else{
            // dd($start,$end);
            $start_date = Carbon::parse($start)
            ->toDateTimeString();
            $end_date = Carbon::parse($end)
            ->toDateTimeString();
            $value = Receipt::where('payment_mode', 'cheque')->where('lodgement_status', 1)->whereBetween('created_at', [
                $start_date, $end_date
            ])->sum('amount');
        }
        return $value ;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            // Set the value for the 'status' column
            $receipt->created_by = Auth::user()->id;
            $receipt->company_id = Auth::user()->company_id;
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
