<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\AuditDescription;


class Asset extends Model implements AuditableContract
{
    use SoftDeletes;
    use  HasFactory, Auditable, AuditDescription;
    protected $table = 'assets';
    protected $fillable = [
        'description',
        'continent_id',
        'region_id',
        'province_id',
        
    ];

    public function astype()
    {
        return $this->belongsTo('App\Models\AssetsType', 'type_id');
    }

    public function descrip()
    {
        return $this->belongsTo('App\Models\Assets_Classification', 'description')->withDefault(['name'=> '']);
    }

    public function classify()
    {
        return $this->belongsTo('App\Models\Assets_Classification', 'description')->withDefault(['name'=> '']);
    }

    public function sup()
    {
        return $this->belongsTo('App\Models\Asset_Supplier', 'supplier_id')->withDefault(['name'=> '']);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($receipt) {
            // Set the value for the 'status' column
            $receipt->continent_id = Auth::user()->continent_id;
            $receipt->region_id = Auth::user()->region_id;
            $receipt->province_id = Auth::user()->province_id;
            $receipt->created_by = Auth::user()->id;
        });
    }
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
