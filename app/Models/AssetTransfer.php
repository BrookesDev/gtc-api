<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class AssetTransfer extends Model implements Auditable
{
    use HasFactory;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($transfer) {
            // dd('Event Fired');
            // Set the value for the 'status' column
            $transfer->transfer_by = Auth::user()->id;
            $transfer->company_id = Auth::user()->company_id;
        });
    }
    public function asset()
    {
        return $this->belongsTo('App\Models\Fixed_Asset_Register', 'asset_id');
    }
    public function area()
    {
        return $this->belongsTo('App\Models\Area', 'previous_area');
    }
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }
    public function province()
    {
        return $this->belongsTo('App\Models\Province', 'previous_province');
    }
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'previous_region');
    }
    public function zone()
    {
        return $this->belongsTo('App\Models\Zone', 'previous_zone');
    }
    public function parish()
    {
        return $this->belongsTo('App\Models\Parish', 'previous_parish');
    }

    public function continent()
    {
        return $this->belongsTo('App\Models\Continent', 'previous_continent');
    }
    public function toarea()
    {
        return $this->belongsTo('App\Models\Area', 'new_area');
    }
    public function toprovince()
    {
        return $this->belongsTo('App\Models\Province', 'new_province');
    }
    public function toregion()
    {
        return $this->belongsTo('App\Models\Region', 'new_region');
    }
    public function tozone()
    {
        return $this->belongsTo('App\Models\Zone', 'new_zone');
    }
    public function toparish()
    {
        return $this->belongsTo('App\Models\Parish', 'new_parish');
    }

    public function tocontinent()
    {
        return $this->belongsTo('App\Models\Continent', 'new_continent');
    }
    public function transfer()
    {
        return $this->belongsTo('App\Models\User', 'transfer_by');
    }
}
