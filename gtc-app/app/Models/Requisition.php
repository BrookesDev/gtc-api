<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;

class Requisition extends Model implements AuditableContract
{
    use HasFactory, Auditable, AuditDescription;
    use SoftDeletes;
    protected $table = 'requisition';
    protected $fillable = [
        'department',
        'stock_name',
        'quantity',
        'request_id',
        'request_by',
        'approval_order',
        'approver_reminant',
        'approved_by',
        'approver_list',
        'approval_status',
        'approved_date',
        'processed_date',
        'reason',
        'is_released',
        'narration',
        'order_id',
    ];



    public function user()
    {
        return $this->belongsTo('App\Models\User', 'request_by');
    }
    public function department()
    {
        return $this->belongsTo('App\Models\Department', 'department');
    }

    public function stocks()
    {
        return $this->belongsTo('App\Models\Item', 'stock_name');
    }
    // public function description()
    // {
    //     return $this->belongsTo('App\Models\RequisitionComment', 'description');
    // }
    public function description()
    {
        return $this->hasMany(RequisitionComment::class, 'request_id', 'request_id');
    }
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($budget) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            // $budget->created_by = Auth::user()->id;
            $budget->company_id = Auth::user()->company_id;
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

}
