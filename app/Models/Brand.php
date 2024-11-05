<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Brand extends Model implements Auditable
{
    use HasFactory; use \OwenIt\Auditing\Auditable;
    protected $fillable = ['name'];

    public function products()
    {
        return $this->hasMany(Item::class,'brand', 'id');
    }
}
