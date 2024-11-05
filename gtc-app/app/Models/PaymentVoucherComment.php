<?php

namespace App\Models;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;

class PaymentVoucherComment extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory, Auditable, AuditDescription;
    protected $guarded = [];
    protected $table = 'payment_voucher_comments';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($description) {
            // Set the value for the 'status' column
            $description->created_by = Auth::user()->id;
           $description->company_id = Auth::user()->company_id;
        });
        // static::saving(function ($post) {
        //         $post->updateBalance();
        // });
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
