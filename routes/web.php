<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Clinic\QueueController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\Clinic\QueueController as ClinicQueueController;
use App\Http\Controllers\KKMVerifierController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Http;
use App\Models\Patient;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ClinicAuthController;
use App\Models\Queue;
use App\Http\Controllers\Clinic\ClinicDashboardController;
use App\Http\Controllers\Clinic\RoomController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| System-wide Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => view('welcome'));
Route::get('/force-logout', fn() => view('logout'));
Route::get('/uitest', fn() => view('views.admin.dashboards.dashboard'));
Route::get('/test', fn() => 'Route working!');
Route::get('/kkm-test', [KKMVerifierController::class, 'checkGovClinic']);
Route::get('admin/clinics/check-verification', [KKMVerifierController::class, 'checkGovClinic']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Public: login & logout
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

    // Protected: hanya boleh akses lepas login admin
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // User Management
        Route::get('/users', [AdminController::class, 'manageUsers'])->name('users.index');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{id}', [AdminController::class, 'viewUser'])->name('users.view');
        Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');

        // Clinic Management
        Route::get('/clinics', [AdminController::class, 'manageClinics'])->name('clinics.index');
        Route::get('/clinics/create', [AdminController::class, 'createClinic'])->name('clinics.create');
        Route::post('/clinics', [AdminController::class, 'storeClinic'])->name('clinics.store');
        Route::get('/clinics/pending', [AdminController::class, 'pendingClinics'])->name('clinics.pending');
        Route::get('/clinics/{id}', [AdminController::class, 'viewClinic'])->name('clinics.view');
        Route::get('/clinics/{id}/edit', [AdminController::class, 'editClinic'])->name('clinics.edit');
        Route::put('/clinics/{id}', [AdminController::class, 'updateClinic'])->name('clinics.update');
        Route::delete('/clinics/{id}', [AdminController::class, 'deleteClinic'])->name('clinics.delete');

        //Route::get('/clinics/pending', [AdminController::class, 'pendingClinics'])->name('clinics.pending');
        Route::put('/clinics/{id}/approve', [AdminController::class, 'approveClinic'])->name('clinics.approve');
        Route::put('/clinics/{id}/reject', [AdminController::class, 'rejectClinic'])->name('clinics.reject');
        Route::put('/clinics/{id}/reject', [AdminController::class, 'rejectClinic'])->name('clinics.reject');

    });
});



/*
|--------------------------------------------------------------------------
| Patient Routes
|--------------------------------------------------------------------------
*/

Route::prefix('patient')->name('patient.')->group(function () {
    Route::get('/login', [PatientController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [PatientController::class, 'login']);
    Route::post('/logout', [PatientController::class, 'logout'])->name('logout');

    //Route::get('/register', [PatientController::class, 'showRegisterForm'])->name('register.form');
    //Route::post('/register', [PatientController::class, 'register'])->name('register');

    // OTP Verification
    Route::get('/verify-otp/{id}', [PatientController::class, 'showOtpForm'])->name('otp.form');
    Route::post('/verify-otp/{id}', [PatientController::class, 'verifyOtp'])->name('verify-otp.submit');

    Route::get('/clinics/nearby', [App\Http\Controllers\ClinicController::class, 'nearby'])->name('clinics.nearby');



    // Protected routes
    Route::middleware(['auth:patient'])->group(function () {
        Route::get('/home', [PatientController::class, 'index'])->name('home');
        Route::post('/queue/join', [QueueController::class, 'join'])->name('queue.join');
        Route::post('/queue/{id}/cancel', [QueueController::class, 'cancelQueue'])->name('queue.cancel');

        Route::get('/history', [PatientController::class, 'history'])->name('history');
        Route::get('/settings', [PatientController::class, 'settings'])->name('settings');
        Route::post('/settings', [PatientController::class, 'updateSettings'])->name('settings.update');
    });
});

/*
|--------------------------------------------------------------------------
| Clinic Routes
|--------------------------------------------------------------------------
*/

Route::get('/clinics/nearby', [ClinicController::class, 'nearby'])->name('clinics.nearby');

Route::prefix('clinic')->name('clinic.')->group(function () {
    Route::get('/register', [ClinicController::class, 'showRegister'])->name('register');
    Route::post('/register', [ClinicController::class, 'register']);
    Route::get('/login', [ClinicController::class, 'showLogin'])->name('login');
    Route::post('/login', [ClinicController::class, 'login']);
    Route::post('/logout', [ClinicController::class, 'logout'])->name('logout');

    Route::middleware(['auth:clinic'])->group(function () {
        Route::get('/dashboard', [ClinicQueueController::class, 'index'])->name('dashboard');
        Route::post('/queue/{queue}/next', [ClinicQueueController::class, 'nextPhase'])->name('queue.nextPhase');
        Route::post('/queue/{queue}/done', [ClinicController::class, 'markAsDone'])->name('queue.done');

        Route::get('/queue', [ClinicController::class, 'showQueue'])->name('queue');
        Route::post('/queue/update/{id}', [ClinicController::class, 'updateQueue'])->name('queue.update');
        Route::post('/queue/{id}/next', [ClinicController::class, 'nextPhase'])->name('queue.next');
        Route::post('/queue/{id}/complete', [ClinicController::class, 'markAsDone'])->name('queue.complete');
        Route::post('/queue/auto-assign', [ClinicController::class, 'autoAssignNext'])->name('auto.assign');
        Route::post('/queue/auto-assign-all', [ClinicController::class, 'autoAssignAll'])->name('autoAssignAll');
    });
});

/*
|--------------------------------------------------------------------------
| Google OAuth Routes (Optional)
|--------------------------------------------------------------------------
*/

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google-auth');
Route::get('auth/google/call-back', [GoogleController::class, 'handleGoogleCallback']);


/*Route::get('/admin/clinics/pending', function () {
    return 'ROUTE TEST OK';
});*/

Route::get('/__testpending', function () {
    return 'PENDING TEST WORKS';
});


Route::get('/verify-email', [AuthController::class, 'showVerifyForm'])->name('verify.email.form');
Route::post('/verify-email', [AuthController::class, 'verifyEmailCode'])->name('verify.email');

// ✅ Webhook endpoint untuk Telegram (gunakan Controller)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

// ✅ Route untuk test hantar mesej secara manual (untuk debug)
Route::get('/test-telegram', function () {
    Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage", [
        'chat_id' => '884087762', // Gantikan dengan chat ID anda
        'text' => '🚀 Test message from Laravel bot!',
    ]);

    return 'Test message sent!';
});

//ROUTE UNTUK DOCTOR 
Route::middleware(['auth:clinic'])->group(function () {
    Route::get('/doctor/dashboard', [DoctorController::class, 'index'])->name('doctor.dashboard');
    Route::post('/doctor/start/{id}', [DoctorController::class, 'startConsultation'])->name('doctor.start');
    Route::post('/doctor/complete/{id}', [DoctorController::class, 'completeConsultation'])->name('doctor.complete');
});

// Papar borang daftar
Route::get('/clinic/register', [ClinicAuthController::class, 'showRegisterForm'])
    ->name('clinic.showRegister');

// Proses borang daftar
Route::post('/clinic/register', [ClinicAuthController::class, 'register'])
    ->name('clinic.register');


Route::get('/clinic/login', [ClinicAuthController::class, 'showLoginForm'])->name('clinic.login');
Route::post('/clinic/login', [ClinicAuthController::class, 'login']);

Route::get('/clinic/logout', [ClinicAuthController::class, 'logout'])->name('clinic.logout');

// selepas login, nanti kita buat dashboard:
Route::get('/clinic/dashboard', function () {
    // Ambil semua queue dari database
    $queues = Queue::with('patient')->latest()->get();

    return view('clinic.dashboard', compact('queues'));
})->name('clinic.dashboard');

// Doctor call next patient
Route::post('/clinic/queue/call-next', [ClinicController::class, 'callNextPatient'])
    ->name('clinic.queue.callNext');

    Route::prefix('clinic')->group(function () {
    Route::get('/dashboard', [ClinicDashboardController::class, 'index'])->name('clinic.dashboard');
});

Route::prefix('clinic')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index'])->name('clinic.rooms.index');
    Route::post('/rooms', [RoomController::class, 'store'])->name('clinic.rooms.store');
    Route::patch('/rooms/{id}/toggle', [RoomController::class, 'toggleStatus'])->name('clinic.rooms.toggle');
});

Route::patch('/rooms/{id}', [RoomController::class, 'update'])->name('clinic.rooms.update');
Route::delete('/rooms/{id}', [RoomController::class, 'destroy'])->name('clinic.rooms.destroy');

Route::prefix('clinic')->name('clinic.')->group(function () {
    // page lain (dashboard, rooms dsb)
    
    Route::get('/clinic/queue', [QueueController::class, 'index'])->name('queue.index');
    Route::patch('/clinic/queue/{id}', [QueueController::class, 'update'])->name('queue.update');
    Route::delete('/clinic/queue/{id}', [QueueController::class, 'destroy'])->name('queue.destroy');
});

Route::patch('/clinic/queue/{id}/call', [QueueController::class, 'callPatient'])->name('clinic.queue.callPatient');
Route::patch('/clinic/queue/{id}/done', [QueueController::class, 'markDone'])->name('clinic.queue.markDone');
Route::patch('/clinic/queue/now-serving/{room_id}', [QueueController::class, 'nowServing'])
->name('clinic.queue.nowServing');

Route::get('/clinic/queue/now-serving/{room_id}', [QueueController::class, 'nowServing'])
    ->name('clinic.queue.nowServing');

Route::patch('/clinic/queue/{id}/complete', [QueueController::class, 'completeConsultation'])
    ->name('clinic.queue.completeConsultation');

Route::patch('/clinic/queue/next/{room_id}', [QueueController::class, 'nextPatient']);

Route::get('/clinic/queue/history', [QueueController::class, 'history'])
    ->name('clinic.queue.history');

Route::prefix('clinic')->name('clinic.')->group(function () {
    // Patient Management
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::patch('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show'); // optional view details
});

Route::get('/logtest', function () {
    Log::info('🧩 Laravel manual log test');
    return 'Log test triggered!';
});

Route::get('/clinic/queue/reset-now-serving', [QueueController::class, 'resetNowServing'])->name('queue.resetNowServing');

Route::get('/clinic/dashboard', [ClinicDashboardController::class, 'index'])
     ->name('clinic.dashboard');

Route::prefix('clinic')->middleware('auth:clinic')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('clinic.reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportPdf'])->name('clinic.reports.export');
});