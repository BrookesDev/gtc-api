<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPersonalLedger extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];


    public function supplier() {
        return $this->belongsTo(Beneficiary::class, 'supplier_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Set the value for the 'status' column
            $item->created_by = Auth::user()->id;
            $item->company_id = Auth::user()->company_id;
        });
    }
    // public function company() {
    //     return $this->belongsTo(Company::class, 'company_id');
    // }
}
