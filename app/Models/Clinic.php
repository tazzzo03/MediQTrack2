<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Clinic extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'clinic_id';

    protected $fillable = [
        'clinic_name',
        'room_id',
        'name',
        'role',
        'email',
        'password',
        'phone',
        'address',
        'latitude',
        'longitude'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
