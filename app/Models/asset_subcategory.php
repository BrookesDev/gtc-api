<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
class asset_subcategory extends Model implements Auditable
{
    use HasFactory;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    //latest infor
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($asset) {
            $asset->created_by = auth()->id();


        });
    }

    public function assetCategory()
    {
        return $this->belongsTo('App\Models\asset_category', 'category_id');
    }

    

    
}
