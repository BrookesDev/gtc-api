<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class AssetRegisterDocument extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $guarded = [];

    public function fixedAssetRegister()
    {
        return $this->belongsTo(Fixed_Asset_Register::class, 'asset_id');
    }
}
