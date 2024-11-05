<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
    use SoftDeletes;
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'address',
        'office_address',
        'phone',
        'email',
        'balance',
        'created_by',
        'company_id',
        'ippis_no',
        'employee_no',
        'department',
        'phone_no',
        'amount',
        'member_no',
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($customer) {
            // Set the value for the 'status' column
            $customer->company_id = Auth::user()->company_id;
            $customer->created_by = Auth::user()->id;
        });
    }
    public function ledgers()
    {
        return $this->hasmany('App\Models\CustomerPersonalLedger', 'customer_id');
    }
}
