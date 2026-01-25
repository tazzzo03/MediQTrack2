<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Queue;
use App\Models\Clinic;
use App\Models\Patient;
use App\Services\QueueRuleEngine;
use App\Services\QueueActionHandler;
class QueueController extends Controller
{

    //  Patient join queue (API)
    public function joinQueue(Request $request)
    {
         Log::info('Incoming data:', $request->all()); // ' Tambah ni

        $firebaseUid = $request->firebase_uid;
        Log::info('Firebase UID received: ' . $firebaseUid); // ' Tambah ni

        $patient = \App\Models\Patient::where('firebase_uid', $firebaseUid)->first();

        if (!$patient) {
            Log::error('No patient found for UID: ' . $firebaseUid); // ' Tambah ni
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $firebaseUid = $request->firebase_uid;
        $patient = Patient::where('firebase_uid', $firebaseUid)->first();

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $patientId = $patient->id;
        $clinicId = 1;

        // Check existing queue
        $existing = Queue::where('patient_id', $patientId)
            ->whereIn('status', ['waiting', 'in_consultation', 'waiting_pharmacy'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in the queue.',
                'queue_number' => $existing->queue_number,
            ]);
        }

        $lastQueue = Queue::whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->first();

        $newNum = $lastQueue ? intval(substr($lastQueue->queue_number, 1)) + 1 : 101;
        $queueNumber = 'A' . $newNum;

        $queue = Queue::create([
            'queue_number' => $queueNumber,
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'status' => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'queue_number' => $queueNumber,
        ]);
    }

    public function getMyQueue($firebaseUid)
    {
        // Cari patient berdasarkan Firebase UID
        $patient = \App\Models\Patient::where('firebase_uid', $firebaseUid)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        // Ambil queue aktif (waiting / in_consultation)
        $queue = \App\Models\Queue::where('patient_id', $patient->id)
            ->whereIn('status', ['waiting', 'in_consultation'])
            ->latest()
            ->first();

        if (!$queue) {
            return response()->json([
                'success' => false,
                'message' => 'No active queue found.'
            ]);
        }

        return response()->json([
            'success' => true,
            'queue_id' => $queue->queue_id,
            'queue_number' => $queue->queue_number,
            'status' => $queue->status,
            'room_id' => $queue->room_id, // kalau ada
            'created_at' => $queue->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    public function callNext(Request $request)
    {
        try {
            // 1 Dapatkan room_id daripada request (atau default 1)
            $roomId = $request->get('room_id') ?? 1;

            // 2 Cari patient paling awal yang masih waiting
            $nextQueue = Queue::where('status', 'waiting')
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in the queue.',
                ]);
            }

            // 3 Update queue info
            $nextQueue->status = 'in_consultation';
            $nextQueue->room_id = $roomId;
            $nextQueue->save();

            // 4 Return response
            return response()->json([
                'success' => true,
                'message' => 'Next patient called successfully.',
                'queue' => [
                    'queue_number' => $nextQueue->queue_number,
                    'patient_id' => $nextQueue->patient_id,
                    'status' => $nextQueue->status,
                    'room_id' => $nextQueue->room_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calling next patient: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function myQueue($firebase_uid)
    {
        try {
            // Cari patient berdasarkan Firebase UID
            $patient = Patient::where('firebase_uid', $firebase_uid)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.',
                ]);
            }

            // Cari queue paling baru untuk patient tu
            $queue = Queue::where('patient_id', $patient->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$queue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active queue found.',
                ]);
            }

            // Return data queue semasa
            return response()->json([
                'success' => true,
                'queue_id' => $queue->queue_id,
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
                'room_id' => $queue->room_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching queue info: ' . $e->getMessage(),
            ], 500);
        }
    }


   public function join(Request $request)
{
    $request->validate([
        'clinic_id' => 'required|exists:clinics,clinic_id',
    ]);

    $patient = Auth::guard('patient')->user();

    //  Check if patient already in queue (ACTIVE queue only)
    $existingQueue = Queue::where('patient_id', $patient->id)
        ->whereIn('status', ['pending', 'active'])
        ->whereIn('phase', ['waiting', 'consultation', 'pharmacy'])
        ->first();

    if ($existingQueue) {
        return redirect()->route('patient.home')->with('error', 'You are already in the queue.');
    }

    //  Get clinic info
    $clinic = Clinic::findOrFail($request->clinic_id);

    //  Generate queue number
    $queueNumber = 'Q' . rand(100, 999);

    //  Save to database
    $queue = Queue::create([
        'queue_number' => $queueNumber,
        'status' => 'pending',
        'phase' => 'waiting',
        'patient_id' => $patient->id,
        'clinic_id' => $clinic->clinic_id,
    ]);

    return redirect()->route('patient.home')->with('success', 'Queue joined successfully.');
}

    //  Doctor/clinic side - call next patient in queue
    public function nextPatient($room_id)
    {
        $next = Queue::where('status', 'pending')
            ->where('phase', 'waiting')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$next) {
            return response()->json(['success' => false, 'message' => 'No patient in queue.']);
        }

        $next->status = 'in_progress';
        $next->phase = 'consultation';
        $next->room_id = $room_id;
        $next->save();

        return response()->json([
            'success' => true,
            'message' => 'Patient called.',
            'queue' => $next,
        ]);
    }

    //  Patient cancel queue via API
    public function cancelQueue(Request $request)
    {
        $request->validate([
            'uid' => 'required',
        ]);

        $patient = Patient::where('firebase_uid', $request->uid)->first();

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found']);
        }

        //  Find patient queue
        $queue = Queue::where('patient_id', $patient->id)
            ->whereIn('status', ['pending', 'in_progress', 'waiting'])
            ->whereIn('phase', ['waiting', 'consultation', 'pharmacy'])
            ->latest()
            ->first();

        if (!$queue) {
            return response()->json(['success' => false, 'message' => 'Queue not found']);
        }

        //  Update queue status
        $queue->status = 'cancelled';
        $queue->phase = 'completed';
        $queue->cancelled_at = now();
        $queue->save();

        return response()->json(['success' => true]);
    }

    //  Patient auto cancel queue due to geofence
    public function autoCancelQueue(Request $request)
    {
        $request->validate([
            'uid' => 'required',
        ]);

        $patient = Patient::where('firebase_uid', $request->uid)->first();

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found']);
        }

        $queue = Queue::where('patient_id', $patient->id)
            ->whereIn('status', ['pending', 'in_progress', 'waiting'])
            ->whereIn('phase', ['waiting', 'consultation', 'pharmacy'])
            ->latest()
            ->first();

        if (!$queue) {
            return response()->json(['success' => false, 'message' => 'Queue not found']);
        }

        $queue->status = 'cancelled';
        $queue->phase = 'completed';
        $queue->cancelled_at = now();
        $queue->save();

        return response()->json(['success' => true]);
    }

    //  Patient update queue status (for debugging / live status)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'uid' => 'required',
            'status' => 'required',
        ]);

        $patient = Patient::where('firebase_uid', $request->uid)->first();

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found']);
        }

        $queue = Queue::where('patient_id', $patient->id)
            ->latest()
            ->first();

        if (!$queue) {
            return response()->json(['success' => false, 'message' => 'Queue not found']);
        }

        $queue->status = $request->status;
        $queue->save();

        return response()->json(['success' => true]);
    }



    //  Tambah fungsi cancel
    public function cancelQueueWeb($id)
    {
        $patient = auth()->guard('patient')->user();

        $queue = Queue::where('queue_id', $id)
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['waiting', 'in_progress']) // boleh cancel kalau belum selesai
            ->first();

        if (!$queue) {
            return redirect()->route('patient.home')->with('error', 'Queue not found or cannot be cancelled.');
        }

        //  Update kedua-dua status dan phase
        $queue->status = 'cancelled';
        $queue->phase = 'completed';
        $queue->save();

        return redirect()->route('patient.home')->with('success', 'You have successfully cancelled your queue.');
    }

    public function processQueueRules(
        Request $request,
        QueueRuleEngine $ruleEngine,
        QueueActionHandler $actionHandler
    ) {
        $queue = Queue::where('queue_id', $request->input('queue_id'))->firstOrFail();


        // Build runtime context from request + current queue state
        $context = [
            'ewt' => $request->input('ewt'),
            'distance' => $request->input('distance'),
            'inside_geofence' => $request->boolean('inside_geofence'),
            'countdown_ended' => (bool) DB::table('queue_countdowns')
                ->where('queue_id', $queue->queue_id)
                ->where('is_active', false)
                ->whereNotNull('end_time')
                ->where('end_time', '<=', now())
                ->value('end_time'),
        ];

        // Evaluate rules and dispatch actions
        $actionCode = $ruleEngine->evaluate($context);
        if ($actionCode) {
            $actionHandler->handle($actionCode, $queue);
        }

        $actionConfig = null;

        if ($actionCode) {
            $actionConfig = DB::table('queue_actions')
                ->where('action_code', $actionCode)
                ->first();
        }

        return response()->json([
            'success' => true,
            'action_code' => $actionCode,
            'action_config' => $actionConfig,
        ]);
    }

    
}
