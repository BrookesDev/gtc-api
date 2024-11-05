<?php

namespace App\Models;

use App\Models\Role;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\AuditDescription;

class ApprovalLevel extends Model implements AuditableContract
{
    use Auditable, AuditDescription;
    use SoftDeletes;
    protected $table = 'approval_level';
    protected $fillable = [
        'module',
        'list_of_approvers'
    ];
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            $budget->company_id = Auth::user()->company_id;
            // $budget->created_by = Auth::user()->id;
        });
    }

    public function name()
    {
        return $this->belongsTo('App\Models\Module', 'module');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Define the relationship with the Module model
    public function module()
    {
        return $this->belongsTo(Module::class);
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
