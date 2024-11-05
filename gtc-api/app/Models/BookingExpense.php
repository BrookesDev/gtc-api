<?php

namespace App\Models;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
class BookingExpense extends Model  implements AuditableContract
{
    use HasFactory,Auditable, AuditDescription;
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

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking', 'uuid', 'uuid');
    }
    public function beneficiary()
    {
        return $this->belongsTo('App\Models\Beneficiary', 'beneficiary_id');
    }
    public function cash()
    {
        return $this->belongsTo('App\Models\Account', 'cash_account');
    }
    public function cost_of_good_gl()
    {
        return $this->belongsTo('App\Models\Account', 'cost_of_good_gl');
    }
    public function inventory_gl()
    {
        return $this->belongsTo('App\Models\Account', 'inventory_gl');
    }
    public function expense()
    {
        return $this->belongsTo('App\Models\Account', 'expense_account');
    }
    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id')->with('cost_of_good_gl','purchase_gl');
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
