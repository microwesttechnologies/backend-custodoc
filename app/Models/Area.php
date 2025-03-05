<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';
    protected $primaryKey = 'id_area';

    protected $fillable = [
        'id_area',
        'name',
        'id_company',
    ];
}
