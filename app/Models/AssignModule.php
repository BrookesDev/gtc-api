<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignModule extends Model
{
    use SoftDeletes;
    protected $table = 'assign_modules';
    use HasFactory;
    protected $guarded = [];
}
