<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class BookingLaborExpense extends Model  implements AuditableContract
{
    use HasFactory,Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($book) {
            // Set the value for the 'status' column
            $book->action_by = Auth::user()->id;
            $book->company_id = Auth::user()->company_id;
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

    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_id');
    }

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking', 'uuid', 'uuid');
    }
}
