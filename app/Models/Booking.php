<?php

namespace App\Models;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Booking extends Model implements AuditableContract
{
    use HasFactory ,Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($book) {
            // Set the value for the 'status' column
            $book->created_by = Auth::user()->id;
            $book->company_id = Auth::user()->company_id;
        });
    }

    public function expenses()
    {
        return $this->hasmany('App\Models\BookingExpense', 'uuid', 'uuid')->with('item');
    }
    public function labors()
    {
        return $this->hasmany('App\Models\BookingLaborExpense', 'uuid', 'uuid')->with('account');
    }
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }
    public function supporting_document()
    {
        return $this->hasMany(GeneralInvoice::class, 'uuid', 'uuid');
    }
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function account()
    {
        return $this->belongsTo(Account::class, 'booking_account');
    }
    public function currency()
    {
        return $this->belongsTo(Account::class, 'booking_account');
    }
    public function services()
    {
        return $this->belongsTo(Item::class, 'service_id');
    }

    public function color(){
        $color = "info";
        if($this->status == "pending"){
            $color = "warning";
        }elseif($this->status == "completed"){
            $color = "success";
        }else{
            $color = "primary";
        }
        return $color;
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
