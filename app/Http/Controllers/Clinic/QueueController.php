<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Queue;
use App\Models\Patient;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Firestore\FirestoreClient;
use App\Http\Controllers\Api\FCMController;
use App\Services\SupabaseService;
use Carbon\Carbon;
use App\Jobs\DeleteQueueFromFirestore;

class QueueController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function index()
    {
        $inactiveStatuses = ['completed', 'cancelled', 'auto_cancelled', 'timeout', 'left_geofence'];

        $activeQueues = Queue::with(['patient', 'room'])
            ->where('clinic_id', 1) // buat sementara
            ->whereDate('created_at', today())
            ->whereNotIn('status', $inactiveStatuses)
            ->oldest()
            ->get();

        $completedQueues = Queue::with(['patient', 'room'])
            ->where('clinic_id', 1) // buat sementara
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->latest()
            ->get();

        $patients = Patient::all();
        $rooms = Room::where('status', 'on')
            ->whereNotNull('doctor_name')
            ->where('doctor_name', '!=', '')
            ->get();

        return view('clinic.queue.index', compact('activeQueues', 'completedQueues', 'patients', 'rooms'));
    }

    public function history(Request $request)
    {
        $dateFilter = $request->query('date_filter', 'last7');
        $statusFilter = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $cancelStatuses = ['cancelled', 'auto_cancelled', 'timeout', 'left_geofence'];

        $query = Queue::with(['patient', 'room'])
            ->where('clinic_id', 1) // buat sementara
            ->whereIn('status', array_merge(['completed'], $cancelStatuses));

        if ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($statusFilter === 'cancelled') {
            $query->whereIn('status', $cancelStatuses);
        }

        $startDate = null;
        $endDate = null;

        if ($dateFilter === 'yesterday') {
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
        } elseif ($dateFilter === 'last7') {
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($dateFilter === 'custom') {
            try {
                if ($dateFrom) {
                    $startDate = Carbon::parse($dateFrom)->startOfDay();
                }
                if ($dateTo) {
                    $endDate = Carbon::parse($dateTo)->endOfDay();
                }
            } catch (\Throwable $e) {
                $startDate = null;
                $endDate = null;
            }
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($search !== '') {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $queues = $query->orderByDesc('created_at')->get();

        return view('clinic.queue.history', compact(
            'queues',
            'dateFilter',
            'statusFilter',
            'search',
            'dateFrom',
            'dateTo'
        ));
    }

    public function nextPhase($id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->phase === 'consultation') {
            $queue->phase = 'pharmacy';
            $queue->save();
            $this->syncQueueToFirestore($queue);
        }

        return back()->with('success', 'Queue moved to Pharmacy.');
    }

    public function markAsDone($id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->phase === 'pharmacy') {
            $queue->phase = 'done';
            $queue->save();
        }

        return back()->with('success', 'Queue marked as done.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'status' => 'required|string',
        ]);

        $queue = Queue::findOrFail($id);
        $queue->update([
            'patient_id' => $request->patient_id,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Queue updated successfully!');
    }

    public function destroy($id)
    {
        $queue = Queue::findOrFail($id);
        $queue->delete();

        return back()->with('success', 'Queue deleted successfully.');
    }

    // ' STAFF: panggil patient ambil ubat
    public function callPatient($id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status === 'serving') {
            $queue->status = 'called';
            $queue->save();

            $this->syncQueueToFirestore($queue);

            // '" Hantar notifikasi kepada patient bila dipanggil ke farmasi
            FCMController::sendToPatient(
                $queue->patient_id,
                'Pharmacy',
                'Please proceed to the pharmacy counter to collect your medication.'
            );

            return back()->with('success', 'Patient has been called to collect medication.');
        }

        return back()->with('error', 'Invalid action. Only patients with serving status can be called.');
    }


    // '" STAFF: tanda ubat dah diambil
    public function markDone($id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status === 'called') {
            $queue->status = 'completed';
            $this->syncQueueToFirestore($queue);

            FCMController::sendToPatient(
                $queue->patient_id,
                'Queue Completed',
                'Your queue is now complete. Thank you for using MediQTrack!'
            );
            $queue->save();

            // ' Delete dari Firestore (supaya real-time queue kosong)
            //$firestore = new \App\Services\FirestoreService();
            //$firestore->deleteQueueRecordByUid($queue->patient->firebase_uid);

           if (!empty($queue->patient->firebase_uid)) {
    DeleteQueueFromFirestore::dispatch($queue->patient->firebase_uid);
}

            return back()->with('success', 'Medication process completed.');
        }

        return back()->with('error', 'Invalid action. Only called patients can be marked as done.');
    }

    // '" STAFF: Reset Now Serving setiap pagi
    public function resetNowServing()
    {
        try {
            $firestore = new \Google\Cloud\Firestore\FirestoreClient([
                'projectId' => env('FIREBASE_PROJECT_ID'),
                'keyFilePath' => storage_path('app/firebase/service-account.json'),
            ]);

            $clinicId = 'CL01'; // ' tukar ikut klinik sebenar
            $docRef = $firestore
                ->collection('clinics')
                ->document($clinicId)
                ->collection('live')
                ->document('now_serving');

            // Set semula field guna camelCase (ikut Firestore sebenar)
            $docRef->set([
                'nowServingSeq'   => 0,
                'nowServingLabel' => '-',
                'updatedAt'       => (new \DateTime())->format('c'),
            ]);

            return back()->with('success', 'Now Serving has been reset for today.');
        } catch (\Exception $e) {
            Log::error('Firestore reset error: ' . $e->getMessage());
            return back()->with('error', 'Failed to reset Now Serving: ' . $e->getMessage());
        }
    }


    public function nowServing($room_id)
    {
        // cari patient yang tengah consultation
        $current = Queue::where('room_id', $room_id)
            ->where('status', 'in_consultation')
            ->with(['patient', 'room'])
            ->first();

        // check kalau ada waiting patients
        $waitingExists = Queue::where('status', 'waiting')->exists();

        // kalau takde consultation sekarang
        if (!$current) {
            return response()->json([
                'patient' => null,
                'hasWaiting' => $waitingExists ? true : false, // pastikan boolean
            ]);
        }

        // kalau ada patient in consultation
        return response()->json([
            'id' => $current->queue_id,
            'queue_number' => $current->queue_number,
            'patient' => $current->patient->name ?? null,
            'room' => $current->room->name ?? null,
            'doctor' => $current->room->doctor_name ?? null,
            'hasWaiting' => $waitingExists ? true : false,
        ]);
    }

    public function completeConsultation($id)
    {
        $queue = Queue::findOrFail($id);
        $queue->status = 'serving';
        $queue->save();
        $this->supabase->logConsultationEnd($queue);
        $this->syncQueueToFirestore($queue);
        // Doctor completes consultation: clear now_serving so patient view hides it
        $this->clearNowServing($queue->clinic_id ?? 1, $queue->room_id);

        // '" Hantar notifikasi kepada patient bila consultation dah selesai
        FCMController::sendToPatient(
            $queue->patient_id,
            'Consultation Complete',
            'Please wait at the pharmacy for your medication.'
        );

        // check kalau masih ada patient waiting
        $waitingCount = Queue::where('status', 'waiting')->count();

        return response()->json([
            'success' => true,
            'message' => 'Patient marked as serving.',
            'hasNext' => $waitingCount > 0 // true kalau masih ada waiting
        ]);
    }


    
    public function nextPatient($room_id)
{
    Log::info('nextPatient() triggered for room_id: ' . $room_id);

    // Ambil patient pertama yang masih waiting
    $next = Queue::where('status', 'waiting')
                ->orderBy('queue_seq', 'asc')
                ->first();

    Log::info('nextPatient() raw debug count:', [
        'waiting_count' => Queue::where('status', 'waiting')->count(),
        'found_next' => $next ? $next->queue_number : null,
    ]);

    // ' Kalau tak jumpa
    if (!$next) {
        return response()->json([
            'success' => false,
            'message' => 'No waiting patients found (raw debug mode)',
        ]);
    }

    // '" Update status dan bilik
    $next->room_id = $room_id;
    $next->status = 'in_consultation';
    $logId = $this->supabase->logConsultationStart($next);
    if ($logId) {
        $next->supabase_log_id = $logId;
    }

    $next->save();

    // '" Hantar notifikasi kepada patient yang dipanggil
    FCMController::sendToPatient(
        $next->patient_id,
        'Now Serving',
        'Please proceed to ' . ($next->room->name ?? 'the consultation room') . '.'
    );

    Log::info('Next patient function triggered');

    // Sync ke Firestore
    try {
        $factory = (new Factory)->withServiceAccount(config('firebase.file'));
        $firestore = $factory->createFirestore()->database();

        $clinicId = $next->clinic_id ?? 1;

        $docRef = $firestore->collection('clinics')
            ->document('CL' . str_pad($clinicId, 2, '0', STR_PAD_LEFT))
            ->collection('live')
            ->document('now_serving');

        $roomKey = (string) $next->room_id;


        $docRef->update([
            ['path' => "counters.$roomKey.label", 'value' => $next->queue_number],
            ['path' => "counters.$roomKey.counter", 'value' => $next->room->name ?? 'Room'],
            ['path' => "counters.$roomKey.doctor", 'value' => $next->room->doctor_name ?? ''],
            ['path' => "counters.$roomKey.roomId", 'value' => $next->room_id],
            ['path' => "counters.$roomKey.updatedAt", 'value' => now()],
        ]);

        Log::info('Firestore updated successfully for now serving: ' . $next->queue_number);

    } catch (\Throwable $e) {
        Log::error('Firestore sync failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Next patient called successfully.',
        'next' => [
            'id' => $next->queue_id,
            'queue_number' => $next->queue_number,
            'patient' => $next->patient->name ?? 'Unknown',
            'room' => $next->room->name ?? '-',
        ]
    ]);
}

  public function joinQueue(Request $request)
{
    $patient = Patient::where('firebase_uid', $request->firebase_uid)->first();

    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'Patient not found.'
        ]);
    }

    // '" check existing active queue today
    $existing = Queue::where('patient_id', $patient->id)
        ->whereDate('created_at', today())
        ->whereIn('status', ['waiting', 'in_consultation', 'pharmacy'])
        ->first();

    if ($existing) {
        return response()->json([
            'success' => true,
            'message' => 'Already in queue.',
            'queue_number' => $existing->queue_number,
            'queue_seq' => $existing->queue_seq,
        ]);
    }

    // '" get clinic ID (default 1 if not set)
    $clinicId = $patient->clinic_id ?? 1;

    // '" get last queue today for that clinic
    $lastQueue = Queue::whereDate('created_at', today())
        ->where('clinic_id', $clinicId)
        ->orderByDesc('queue_seq')
        ->orderByDesc('queue_id')
        ->first();

    $nextSeq = $lastQueue ? $lastQueue->queue_seq + 1 : 1;
    $nextNumber = 'A' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

    // '" create new queue
    $queue = Queue::create([
        'clinic_id'    => $clinicId,
        'patient_id'   => $patient->id,
        'queue_seq'    => $nextSeq,
        'queue_number' => $nextNumber,
        'status'       => 'waiting',
        'room_id'      => null,
    ]);

    // '" sync to Firestore
    $this->syncQueueToFirestore($queue);

    // '" Hantar FCM notification ke patient
    FCMController::sendToPatient(
        $patient->id,
        'You have joined the queue',
        'Your queue number is ' . $queue->queue_number . '. Please wait for your turn.'
    );

    return response()->json([
        'success' => true,
        'message' => 'Joined queue successfully.',
        'queue_number' => $queue->queue_number,
        'queue_seq' => $queue->queue_seq,
        'status' => $queue->status,
    ]);
}




    // get current queue info
    public function myQueue($firebase_uid)
    {
        $patient = Patient::where('firebase_uid', $firebase_uid)->first();

        if (!$patient) {
            Log::info('myQueue: Patient not found', ['uid' => $firebase_uid]);
            return response()->json(['success' => false, 'message' => 'Patient not found.']);
        }

        $queue = Queue::where('patient_id', $patient->id)
            ->with('room')
            ->latest()
            ->first();

        if (!$queue) {
            Log::info('myQueue: No queue found', ['patient_id' => $patient->id]);
            return response()->json(['success' => false, 'message' => 'No queue found.']);
        }

        // Dapatkan now serving (firestore)
        $clinicId = $queue->clinic_id ?? 1;

        // Fix for gRPC DNS/TLS issues on Windows
        putenv('GRPC_ENABLE_FORK_SUPPORT=1');
        putenv('GRPC_DNS_RESOLVER=native');

        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials.file'));
        $db = $factory->createFirestore()->database();

        $nowDoc = $db->collection('clinics')->document('CL' . str_pad($clinicId, 2, '0', STR_PAD_LEFT))
            ->collection('live')->document('now_serving')->snapshot();

        $nowServingSeq = $nowDoc->exists() ? ($nowDoc->data()['nowServingSeq'] ?? 0) : 0;

        // kira direct dari DB ikut klinik, tarikh hari ini, dan status aktif
        $activeStatuses = ['waiting', 'in_consultation', 'serving', 'pharmacy'];

        $peopleAhead = Queue::where('clinic_id', $clinicId)
            ->whereDate('created_at', today())
            ->whereIn('status', $activeStatuses)
            ->where('queue_seq', '<', $queue->queue_seq)
            ->count();

        Log::info('myQueue calculated', [
            'patient' => $patient->id,
            'queue_seq' => $queue->queue_seq,
            'nowServingSeq' => $nowServingSeq,
            'peopleAhead' => $peopleAhead
        ]);

        return response()->json([
            'success' => true,
            'queue_id' => $queue->queue_id,
            'queue_number' => $queue->queue_number,
            'status' => $queue->status,
            'room_id' => $queue->room_id,
            'room_name' => $queue->room->name ?? null,
            'doctor_name' => $queue->room->doctor_name ?? null,
            'now_serving_seq' => $nowServingSeq,
            'people_ahead' => $peopleAhead
        ]);
    }

    private function clearNowServing(int $clinicId, ?int $roomId): void
    {
        if (!$roomId) {
            Log::warning('clearNowServing skipped: missing room_id', ['clinic_id' => $clinicId]);
            return;
        }

        try {
            $projectId = getenv('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT_ID');
            $keyPath   = base_path(getenv('FIREBASE_CREDENTIALS') ?: env('FIREBASE_CREDENTIALS'));

            $firestore = new \Google\Cloud\Firestore\FirestoreClient([
                'keyFilePath' => $keyPath,
                'projectId'   => $projectId,
            ]);

            $docRef = $firestore->collection('clinics')
                ->document('CL' . str_pad($clinicId, 2, '0', STR_PAD_LEFT))
                ->collection('live')
                ->document('now_serving');

            $roomKey = (string) $roomId;

            $docRef->update([
                ['path' => "counters.$roomKey.label", 'value' => ''],
                ['path' => "counters.$roomKey.updatedAt", 'value' => (new \DateTime())->format('c')],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to clear now_serving: ' . $e->getMessage());
        }
    }

    private function syncQueueToFirestore($queue)
    {
        try {
            if (!$queue->patient || !$queue->patient->firebase_uid) {
                Log::warning('Queue sync skipped: patient has no firebase_uid.');
                return;
            }

            // Baca file .env secara terus untuk bypass cache
            $projectId = getenv('FIREBASE_PROJECT_ID') ?: env('FIREBASE_PROJECT_ID');
            $keyPath   = base_path(getenv('FIREBASE_CREDENTIALS') ?: env('FIREBASE_CREDENTIALS'));

            Log::info('Firestore config check', [
                'projectId' => $projectId,
                'keyPath'   => $keyPath,
                'exists'    => file_exists($keyPath),
            ]);

            // ' Init Firestore
            $firestore = new \Google\Cloud\Firestore\FirestoreClient([
                'keyFilePath' => $keyPath,
                'projectId'   => $projectId,
            ]);

            $firestore->collection('queues')
                ->document($queue->patient->firebase_uid)
                ->set([
                    'firebase_uid' => $queue->patient->firebase_uid,
                    'queue_number' => $queue->queue_number,
                    'status'       => $queue->status,
                    'updated_at'   => now()->timestamp,
                ], ['merge' => true]);

            Log::info('Firestore synced for ' . $queue->queue_number . ' (' . $queue->status . ')');

        } catch (\Throwable $e) {
            Log::error('Firestore sync failed: ' . $e->getMessage());
        }
    }

   public function cancelQueue(Request $request)
{
    try {
        $request->validate([
            'uid' => 'required|string',
        ]);

        // 1'' Cari patient berdasarkan Firebase UID
        $patient = \App\Models\Patient::where('firebase_uid', $request->uid)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found for this UID',
            ]);
        }

        // 2'' Cari queue aktif milik patient tu
        $queue = \App\Models\Queue::where('patient_id', $patient->id)
            ->where('status', 'waiting')
            ->first();

        if (!$queue) {
            return response()->json([
                'success' => false,
                'message' => 'No active queue found for this patient',
            ]);
        }

        // 3'' Update status ke cancelled
        if ($request->reason === 'geofence') {
        $queue->status = 'auto_cancelled';
        } else {
            $queue->status = 'cancelled';
        }
        $queue->cancelled_at = now();
        $queue->save();

        // '" Hantar notifikasi kepada patient bila dia leave queue
        FCMController::sendToPatient(
            $patient->id,
            'Queue Cancelled',
            'You have left the queue. See you next time!'
        );

        return response()->json([
            'success' => true,
            'message' => 'Queue cancelled successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function countdownEnded(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|exists:queues,queue_id',
        ]);

        $queue = Queue::where('queue_id', $request->queue_id)->firstOrFail();

        // Safety: hanya cancel kalau masih waiting / in_consultation
        if (!in_array($queue->status, ['waiting', 'in_consultation'])) {
            return response()->json([
                'success' => false,
                'message' => 'Queue already completed.',
            ]);
        }

        // Cancel queue
        $queue->status = 'auto_cancelled';
        $queue->cancelled_at = now();
        $queue->save();

        return response()->json([
            'success' => true,
            'message' => 'Queue auto-cancelled after countdown.',
            'queue_status' => $queue->status,

        ]);
    }

}



