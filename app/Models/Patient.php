<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Patient extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $table = 'patients'; // Explicitly using the singular table name

    protected $fillable = [
        'name',
        'ic_number',
        'dob',
        'email',
        'phone_number',
        'gender',
        'email_verified_at',
        'password',
        'otp',
        'otp_expires_at',
        'is_verified',
        'google_id',
        'firebase_uid',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    

     

public function queues()
{
    return $this->hasMany(Queue::class, 'patient_id', 'id');
}

public function notifications()
{
    return $this->hasMany(Notification::class, 'patient_id');
}




}
