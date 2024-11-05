<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model implements AuditableContract
{
    use HasFactory;
    use SoftDeletes;
    use Auditable, AuditDescription;
    protected $fillable=[
        'description',
        'particulars',
        'transaction_date',
        'total_tax_amount',
        'contract_amount',
        'total_amount',
        'pvnumber',
        'channel',
        'beneficiary_id',
        'invoice_id',
        'beneficiary_account',
        'gl_account',
        'approval_status',
        'prepared_by',
        'approval_level',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'approver_list',
        'transaction_id',
        'processed_date',
        'approval_date'
    ];

    public function beneficiary(){
        return $this->belongsTo('App\Models\Beneficiary', 'beneficiary_id')->withDefault(['name' => '']);
    }

    public function PreparerDetail(){
        return $this->belongsTo('App\Models\User', 'prepared_by')->withDefault(['name' => '']);

    }

    public function ApprovedBy()
    {
        $approver_list = json_decode($this->approved_by);
        $lastApproverID = end($approver_list);
        // dd($lastApproverID);
        $approver= "";
        if($lastApproverID){

            $approverRecord = User::where('id', $lastApproverID)->first();
            $approver= $approverRecord->name;
        }else{
            $approver ="";

        }
        // dd($approver);


        return $approver;

    }

    public function preparedBy()
    {
        $user = "";
        $userRecord = User::where('id', $this->prepared_by)->first();
        $user = $userRecord->name;
        return $user;
    }
    // use Role;
    public function roless(){
        return $this->belongsTo('Spatie\Permission\Models\Role', 'approval_order')->withDefault(['name' => '']);

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
