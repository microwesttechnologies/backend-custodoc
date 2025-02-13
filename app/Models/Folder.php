<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $table = 'folders';
    protected $primaryKey = 'id_folder';

    protected $fillable = [
        'id_folder',
        'name',
        'parent',
        'id_company',
    ];
}
