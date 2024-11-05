<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubSubCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'sub_sub_category_id', 'id');
    }
    public function last()
    {
        return $this->hasMany('App\Models\CategoryAccount', 'sub_sub_category_id', 'id');
    }
}
