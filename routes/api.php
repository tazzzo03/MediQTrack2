<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\InvalidToken;

use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Clinic\QueueController;
use App\Http\Controllers\Clinic\RoomController;
use App\Http\Controllers\QueueController as PublicQueueController;
use App\Http\Controllers\VisitHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PatientMobileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FCMController;
use App\Http\Controllers\Api\UserSyncController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/ping', function () {
    return response()->json([
        'ok'   => true,
        'app'  => 'MediQTrack API',
        'time' => now()->toISOString(),
    ]);
});

Route::get('/firebase-test', function () {
    try {
        $firebase = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase-admin.json'))
            ->createAuth();

        return response()->json(['ok' => true, 'message' => 'Firebase connected!']);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/test-public', function () {
    return response()->json(['message' => 'This is a public route']);
});

Route::middleware('firebase.auth')->get('/test-protected', function (Illuminate\Http\Request $request) {
    return response()->json([
        'message' => 'You are authenticated!',
        'uid' => $request->firebase_uid
    ]);
});


Route::post('/verify-token', function (Request $request) {
    try {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['ok' => false, 'error' => 'Missing token']);
        }

        $firebase = (new Factory)
            ->withServiceAccount(base_path('firebase-admin.json'))
            ->createAuth();

        $verifiedIdToken = $firebase->verifyIdToken($idToken);
        $uid = $verifiedIdToken->claims()->get('sub');

        return response()->json([
            'ok' => true,
            'uid' => $uid,
            'message' => 'Token verified successfully',
        ]);
    } catch (\Kreait\Firebase\Exception\AuthException $e) {
        return response()->json(['ok' => false, 'error' => 'Invalid or expired token']);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()]);
    }
});

Route::middleware(['firebase.auth'])->group(function () {
    Route::get('/user-info', function (Request $request) {
        return response()->json([
            'ok' => true,
            'firebase_uid' => $request->firebase_uid,
        ]);
    });

    // Tambah mana-mana route lain yang nak dilindungi:
    // Route::get('/profile', [UserController::class, 'profile']);
    // Route::post('/queue', [QueueController::class, 'store']);
});

Route::post('/register-patient', [PatientController::class, 'register']);

Route::post('/join-queue', [QueueController::class, 'joinQueue']);

Route::post('/process-queue-rules', [PublicQueueController::class, 'processQueueRules']);

Route::post('/queue/call-next', [QueueController::class, 'callNext']);

Route::get('/my-queue/{firebase_uid}', [QueueController::class, 'myQueue']);

Route::post('/next-patient/{room_id}', [QueueController::class, 'nextPatient']);

Route::get('/patient/profile/{firebase_uid}', [PatientController::class, 'getProfile']);

Route::delete('/patient/delete/{firebase_uid}', [PatientController::class, 'deleteAccount']);

Route::put('/patient/update/{firebase_uid}', [PatientController::class, 'updateProfile']);

Route::get('/visit-history', [VisitHistoryController::class, 'index']);

Route::get('/rooms/active-count', [RoomController::class, 'activeCount']);

Route::get('/profile',  [ProfileController::class, 'show']);   // ?firebase_uid=...
Route::put('/profile',  [ProfileController::class, 'update']); // JSON body

Route::post('/update-fcm-token', [PatientMobileController::class, 'updateFcmToken']);
Route::post('/queue/cancel', [QueueController::class, 'cancelQueue']);

Route::post('/test-fcm', function () {
    // ubah id ikut patient_id sebenar
    $ok = FCMController::sendToPatient(60, 'Now Serving 🏥', 'Sila ke Room 3 sekarang.');
    return response()->json(['success' => $ok]);
});


Route::post('/send-fcm', [FCMController::class, 'sendToToken']);


Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications', [NotificationController::class, 'store']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

Route::post('/sync-user', [UserSyncController::class, 'syncUser']);

Route::post('/queue/countdown-ended', [QueueController::class, 'countdownEnded']);

