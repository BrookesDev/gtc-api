<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class NominalLedger extends Model implements AuditableContract
{
    use Auditable;
    use AuditDescription;
    use SoftDeletes;
    use HasFactory;
    protected $guarded = [];

    public function report()
    {
        return $this->belongsTo('App\Models\Account', 'report_to');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function beneficiary()
    {
        return $this->belongsTo('App\Models\Customer', 'name');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($input) {
            // Set the value for the 'status' column
            $input->created_by = Auth::user()->id;
            $input->company_id = Auth::user()->company_id;

        });
    }
    // public function __construct()
// {
//     $this->middleware('auth');
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
