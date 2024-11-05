<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LGA extends Model
{
    use HasFactory;
    protected $table = 'local_govt';
    protected $fillable = [
        'local_govt',
    ];

    protected $primaryKey = 'id_no';

}
