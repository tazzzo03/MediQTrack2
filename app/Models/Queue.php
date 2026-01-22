<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;
    
    protected $table = 'queues'; 

    protected $primaryKey = 'queue_id'; 

    protected $fillable = [
        'queue_number',
        'status',
        'patient_id',
        'queue_seq',
        'clinic_id',
        'room_id',
        'supabase_log_id',
        'start_time',
        'end_time',
    ];

    // dY`% ADD THIS
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function ($queue) {

            // IF status changed to 'in_consultation' ƒ+' set start_time
            if ($queue->isDirty('status') && $queue->status === 'in_consultation') {

                // Only set start_time once
                if ($queue->start_time === null) {
                    $queue->start_time = now();
                }
            }

            // IF status changed to 'serving' ƒ+' set end_time
            if ($queue->isDirty('status') && $queue->status === 'serving') {

                // Only set end_time once
                if ($queue->end_time === null) {
                    $queue->end_time = now();
                }
            }
        });
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'clinic_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }
}
