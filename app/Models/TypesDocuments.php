<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypesDocuments extends Model
{
    use HasFactory;

    protected $table = 'types_document';
    protected $primaryKey = 'id_document';

    protected $fillable = [
        'id_document',
        'name'
    ];
}
