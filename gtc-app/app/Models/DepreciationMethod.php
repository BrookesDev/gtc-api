<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepreciationMethod extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($input) {
            // Set the value for the 'status' column
            $input->company_id = Auth::user()->company_id;

        });
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company','company_id');
    }
}
