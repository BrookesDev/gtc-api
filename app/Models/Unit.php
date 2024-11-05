<?php

namespace App\Models;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;

class Unit extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Set the value for the 'status' column
            $item->created_by = Auth::user()->id;
            $item->company_id = Auth::user()->company_id;
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // public function account()
     // {
    //     return $this->belongsTo(Account::class);
    // }
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'gl_code');
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
