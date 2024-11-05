<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Auth;
class StoreUser extends Model implements Auditable
{
    use HasFactory; use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'store_id',
        'user_id',
        'assigned_by'
    ];
    public function store()
    {
        return $this->belongsTo('App\Models\Store','store_id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }
    public function creator()
    {
        return $this->belongsTo('App\Models\User','assigned_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            // Set the value for the 'status' column
            $store->assigned_by = Auth::user()->id;
        });
    }
}
