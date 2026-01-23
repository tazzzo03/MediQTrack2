<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Queue;
use App\Models\Room;

class ClinicDashboardController extends Controller
{
    public function index()
    {
        $activeStatuses = ['waiting', 'serving', 'in_consultation', 'pharmacy', 'called'];
        $totalStatuses = ['completed', 'cancelled', 'auto_cancelled'];

        // ===== STAT CARDS =====
        $totalQueues = Queue::whereDate('created_at', today())
            ->whereIn('status', $totalStatuses)
            ->count();

        $activeQueues = Queue::whereDate('created_at', today())
            ->whereIn('status', $activeStatuses)
            ->count();

        $completedToday = Queue::where('status', 'completed')
                                ->whereDate('updated_at', today())
                                ->count();

        // rooms status on/off
        $activeRooms = Room::where('status', 'on')->count();

        // ===== DOCTOR LIST =====
        // Doctor stored inside rooms table as doctor_name
        $doctors = Room::all();

        // ===== PATIENT SUMMARY =====
        $totalPatientsToday = Queue::whereDate('created_at', today())->count();

        $servedPatients = Queue::where('status', 'completed')
                               ->whereDate('updated_at', today())
                               ->count();

        $waitingPatients = Queue::where('status', 'waiting')
            ->whereDate('created_at', today())
            ->count();

        // ===== QUEUE ACTIVITY (HOURLY) =====
        $queueActivity = Queue::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->whereDate('created_at', today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Format for chart
        $hours = [];
        $totals = [];

        foreach ($queueActivity as $row) {
            $hours[] = sprintf('%02d:00', $row->hour);
            $totals[] = $row->total;
        }

        // ===== WAITING TIME ANALYTICS =====
        // Waiting time = start_time - created_at
        $waitingTimes = Queue::whereNotNull('start_time')
            ->whereDate('created_at', today())
            ->get()
            ->map(function ($q) {
                return $q->start_time->diffInMinutes($q->created_at);
            });

        $avgWaiting = $waitingTimes->avg() ?? 0;
        $maxWaiting = $waitingTimes->max() ?? 0;
        $minWaiting = $waitingTimes->min() ?? 0;

        // ===== CONSULTATION TIME =====
        // end_time - start_time
        $consultTimes = Queue::whereNotNull('end_time')
            ->whereNotNull('start_time')
            ->whereDate('created_at', today())
            ->get()
            ->map(function ($q) {
                return $q->end_time->diffInMinutes($q->start_time);
            });

        $avgConsult = $consultTimes->avg() ?? 0;

        // ===== DOCTOR PERFORMANCE =====
        // Count completed queues per doctor (room)
        $doctorPerformance = Queue::whereNotNull('end_time')
            ->whereDate('end_time', today())
            ->join('rooms', 'queues.room_id', '=', 'rooms.id')
            ->select('rooms.doctor_name', Queue::raw('COUNT(*) as total'))
            ->groupBy('rooms.doctor_name')
            ->get();

        // Prepare arrays for chart
        $doctorNames = $doctorPerformance->pluck('doctor_name');
        $doctorTotals = $doctorPerformance->pluck('total');

        // ===== CANCELLATION SUMMARY =====
        $userCancelledToday = Queue::where('status', 'cancelled')
            ->whereDate('updated_at', today())
            ->count();

        $autoCancelledToday = Queue::whereIn('status', ['auto_cancelled', 'timeout', 'left_geofence'])
            ->whereDate('updated_at', today())
            ->count();

        $totalCancelledToday = $userCancelledToday + $autoCancelledToday;

       return view('clinic.dashboard', compact(
            'totalQueues',
            'activeQueues',
            'completedToday',
            'activeRooms',
            'doctors',
            'totalPatientsToday',
            'servedPatients',
            'waitingPatients',
            'hours',
            'totals',
            'avgWaiting',
            'maxWaiting',
            'minWaiting',
            'avgConsult',
            'doctorNames',
            'doctorTotals',
            'userCancelledToday',
            'autoCancelledToday',
            'totalCancelledToday'
        ));
    }
}
