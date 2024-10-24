<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $primaryKey = 'identification';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'name',
        'id_company',
        'id_document',
        'identification',
        'phone'
    ];
}
