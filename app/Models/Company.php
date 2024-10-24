<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';
    protected $primaryKey = 'id_company';

    protected $fillable = [
        'id_company',
        'name',
        'type',
        'address',
        'city',
        'country',
        'nit',
        'phone',
    ];
}
