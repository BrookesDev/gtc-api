<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Fixed_Asset_Register extends Model implements Auditable
{
    use HasFactory;
    protected $guarded = [];
    use \OwenIt\Auditing\Auditable;
    protected $table = 'fixed_asset_registers';
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($asset) {
            // Set the value for the desired column
            $asset->province_id = Auth::user()->company_id;
            $asset->approved_by = Auth::user()->id;
            $asset->created_by = Auth::user()->id;

        });
    }

    public function documents()
    {
        return $this->hasMany('App\Models\AssetRegisterDocument', 'asset_id');
    }
    public function assetCategory()
    {
        return $this->belongsTo('App\Models\asset_category', 'category_id');
    }

    public function parish()
    {
        return $this->belongsTo('App\Models\Parish', 'parish_id');
    }
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'province_id');
    }
    public function continent()
    {
        return $this->belongsTo('App\Models\Continent', 'continent_id');
    }

    public function area()
    {
        return $this->belongsTo('App\Models\Area', 'area_id');
    }
    public function asset_gl()
    {
        return $this->belongsTo('App\Models\Account', 'asset_gl');
    }
    public function depre_expense_account()
    {
        return $this->belongsTo('App\Models\Account', 'depre_expenses_account');
    }
    public function depre_method()
    {
        return $this->belongsTo('App\Models\DepreciationMethod', 'depre_method');
    }

    public function zone()
    {
        return $this->belongsTo('App\Models\Zone', 'zone_id');
    }

    public function province()
    {
        return $this->belongsTo('App\Models\Province', 'province_id');
    }
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'region_id');
    }
    public function assetSubCategory()
    {
        return $this->belongsTo('App\Models\asset_subcategory', 'subcategory_id');
    }
    public function approved_by_checker()
    {
        return $this->belongsTo('App\Models\User', 'approved_by_checker');
    }

    public function approved_by()
    {
        return $this->belongsTo('App\Models\User', 'approved_by');
    }

    public function saveAssetDocument($assetId, $pdfFile)
    {
        // Implementation...
    }

    public function assetDocuments()
    {
        return $this->hasMany(AssetRegisterDocument::class);
    }



}
