<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'doctor_name'
    ];

    public function queues()
    {
        return $this->hasMany(Queue::class, 'room_id', 'id');
    }
}
