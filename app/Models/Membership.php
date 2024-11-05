<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Membership extends Model implements AuditableContract
{
    use SoftDeletes;
    use HasFactory;
    use Auditable, AuditDescription;
    protected $table = 'memberships';
    protected $fillable = [
        'member_id',
        'firstname',
        'lastname',
        'othername',
        'phone_no',
        'home_address',
        'email',
        'sex',
        'religion',
        'account_number',
        'account_name',
        'bank_name',
        'company_id',
        'action_by',
        'approver_list',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'approval_status',
        'last_approved_date',
        'unit',
        'department',
        'rank',
        'step',
        'grade_level',
        'reason',
        'declined_by',
        'declined_date',
    ];

    public function bnk()
    {
        return $this->belongsTo('App\Models\Bank', 'bank_name')->withDefault(['name'=> '']);
    }

    public function roles()
    {
        return $this->belongsTo('App\Role', 'approval_order')->withDefault(['name'=>'']);
    }

    public function MemberRecord()
    {
        $member = Membership::where('company_id', Auth::user()->company_id)->where('member_id', $this->member_id)->first();

        if ($member) {
            $memberRecord = $member->firstname.' '.$member->lastname.' '.$member->othername;
        } else {
            $memberRecord = ' ';
        }

        return $memberRecord;
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
