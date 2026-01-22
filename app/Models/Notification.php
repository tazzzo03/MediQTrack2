<?php

// app/Models/Notification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = true;

    protected $fillable = [
        'patient_id', 'title', 'body', 'type', 'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function scopeForPatient($q, $id)
    {
        return $q->where('patient_id', $id);
    }
}
