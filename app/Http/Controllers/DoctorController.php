<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Queue;

class DoctorController extends Controller
{
    public function index()
    {
        $doctor = Auth::guard('clinic')->user();

        // ambil semua pesakit yang assigned ke room doktor ni
        $queues = Queue::where('room_id', $doctor->room_id)
            ->whereIn('status', ['waiting', 'in_consultation'])
            ->orderBy('created_at')
            ->get();

        return view('doctor.dashboard', compact('doctor', 'queues'));
    }

    public function startConsultation($id)
    {
        $queue = Queue::findOrFail($id);
        $queue->status = 'in_consultation';
        $queue->save();

        return back()->with('success', 'Consultation started for patient ' . $queue->queue_number);
    }

    public function completeConsultation($id)
    {
        $queue = Queue::findOrFail($id);
        $queue->status = 'completed';
        $queue->save();

        return back()->with('success', 'Consultation completed for patient ' . $queue->queue_number);
    }
}
