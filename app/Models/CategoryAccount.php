<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'category_accounts';
    protected $guarded = [];
    public function journals()
    {
        return $this->hasMany(Journal::class, 'gl_code', 'id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'sub_category_id', 'id');//->where('company_id', auth()->user()->province_id);
    }
}
