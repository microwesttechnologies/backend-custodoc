<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'nameUser',
        'emailUser',
        'passUser',
        'role',
        'state',
        'id_company',
    ];

    // Relación con HistoryUser
    // public function historyUsers()
    // {
    //     return $this->hasMany(HistoryUser::class, 'idUser');
    // }
}
