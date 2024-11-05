<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Veriment extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use AuditDescription;

    protected $guarded = [];

    public function from()
    {
        $name = '';
        $account = Account::where('id',$this->from)->first();
        if (!empty($account)) {
            $name = $account->gl_name;
        }

        return $name;

    }

    public function to()
    {
        $name = '';
        $account = Account::where('id',$this->to)->first();
        if (!empty($account)) {
            $name = $account->gl_name;
        }

        return $name;

    }

    public function roles()
    {
        return $this->belongsTo('App\Role', 'approval_order')->withDefault(['name'=>'']);
    }


    public function user()
    {
        $name = '';
        $user = User::where('id',$this->made_by)->first();
        if (!empty($user)) {
            $name = $user->name;
        }

        return $name;

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
