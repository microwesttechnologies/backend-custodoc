<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';
    protected $primaryKey = 'id_history';

    protected $fillable = [
        'id_history',
        'user_identification',
        'identification',
        'name',
        'path',
        'description',
        'id_area',
        'id_company',
        'id_folder'
    ];
}
