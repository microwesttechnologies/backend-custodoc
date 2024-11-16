<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routes extends Model
{
    use HasFactory;

    protected $table = 'routes';
    protected $primaryKey = 'id_route';

    protected $fillable = [
        'id_route',
        'name',
        'icon',
        'parent',
        'path'
    ];
}
