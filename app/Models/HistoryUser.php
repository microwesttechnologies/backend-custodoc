<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryUser extends Model
{
    use HasFactory;

    protected $table = 'history_users';

    protected $fillable = [
        'id_user',
        'id_company',
        'nameDocument',
        'routeDocument',
        'description',
        'dateUpload',
        'dateDelete',
    ];

    // Relación con User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relación con Company
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }
}
