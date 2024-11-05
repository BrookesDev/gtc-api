<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class LoanDisapprovalComment extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($description) {
            // Set the value for the 'status' column
            $description->disapproved_by = Auth::user()->id;

        });

    }

}
