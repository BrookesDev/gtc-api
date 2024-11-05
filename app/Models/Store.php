<?php

namespace App\Models;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Store extends Model implements Auditable
{
    use HasFactory; use \OwenIt\Auditing\Auditable;
    protected $fillable = ['store_name','phone','store_address','contact_email','contact_address','type','created_by','store_id','image'];

    public function getImageAttribute($image)
    {
        if($image != NULL){
            return env('APP_URL') ."/". $image;
        }
    }
    public function store()
    {
        return $this->belongsTo('App\Models\Store','store_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Set the value for the 'status' column
            $item->created_by = Auth::user()->id;
        });
    }
}
