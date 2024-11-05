<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class AssetDisposal extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    //protected $guarded = [];

    protected $fillable = [
        'asset_id',
        'date_disposed',
        'disposed_by',
        'amount_disposed',
        'created_by',
        'continent_id',
        'region_id',
        'province_id',
        'zone_id',
        'area_id',
        'parish_id'


    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($asset) {
            $asset->created_by = auth()->id();
            $asset->disposed_by = auth()->id();
            $asset->province_id = Auth::user()->company_id;
        });
    }
    public function asset()
    {
        return $this->belongsTo('App\Models\Fixed_Asset_Register', 'asset_id')->with(['assetCategory']);
    }
    public function name()
    {
        return $this->belongsTo('App\Models\Fixed_Asset_Register', 'description');
    }
}
