<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Queue;
use App\Services\GoogleSheetClinicVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;


class ClinicController extends Controller
{
    // Show register form
    public function showRegister()
    {
        return view('clinic.register');
    }

    // Handle register
    public function register(Request $request)
    {
        $request->validate([
            'clinic_name' => 'required',
            'email' => 'required|email|unique:clinics,email',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string',
            'password' => 'required|string|min:6',
            'phone' => 'required',
            'license_no' => 'required',
            'license_file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        //  Semak nama klinik dalam Google Sheet rasmi KKM
        if (!GoogleSheetClinicVerifier::verify($request->clinic_name)) {
            return back()->withErrors([
                'clinic_name' => 'Clinic not found in official KKM list.'
            ])->withInput();
        }

        //  Simpan fail lesen
        $path = $request->file('license_file')->store('licenses', 'public');

        //  Simpan ke database
        $clinic = Clinic::create([
            'clinic_name' => $request->clinic_name,
            'email' => $request->email,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'radius' => 13,
            'license_no' => $request->license_no,
            'license_file' => $path,
            'is_approved' => false, // Klinik baru akan tunggu kelulusan admin
        ]);

        return redirect()->route('clinic.login')
            ->with('success', 'Registration successful! Please wait for admin approval.');
    }

    public function callNextPatient(Request $request)
    {
        // Ambil patient pertama yang status masih 'waiting'
        $nextPatient = Queue::where('status', 'waiting')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$nextPatient) {
            return redirect()->back()->with('error', 'No patients waiting in the queue.');
        }

        // Update status jadi in_consultation
        $nextPatient->update([
            'status' => 'in_consultation',
            'room_id' => Auth::guard('clinic')->user()->room_id ?? null, // kalau nanti ada bilik
        ]);

        return redirect()->back()->with('success', 'Next patient called successfully!');
    }


    // Show login form
    public function showLogin()
    {
        return view('clinic.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $clinic = Clinic::where('email', $credentials['email'])->first();

        if (!$clinic || !Hash::check($credentials['password'], $clinic->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        if (!$clinic->is_approved) {
            return back()->withErrors(['email' => 'Your registration is pending approval by admin.']);
        }

        Auth::guard('clinic')->login($clinic); // Authenticate clinic
        return redirect()->intended('/clinic/dashboard');
    }
    // Handle logout
    public function logout()
    {
        session()->forget('clinic_id');
        return redirect()->route('clinic.login');
    }

    // Clinic dashboard
    public function dashboard()
{
    $clinic = Auth::guard('clinic')->user();

    if (!$clinic) {
        return redirect()->route('clinic.login')->withErrors(['msg' => 'Please login first.']);
    }

    $queues = Queue::with('patient')
        ->where('clinic_id', $clinic->clinic_id)
        ->orderBy('created_at', 'asc')
        ->get();

    return view('clinic.dashboard', compact('clinic', 'queues'));
}

    public function queueList()
    {
        $queues = Queue::where('clinic_id', session('clinic_id'))->orderBy('created_at')->get();
        return view('clinic.queue', compact('queues'));
    }

    public function updatePhase(Request $request, $id)
{
    Log::info(" MASUK updatePhase(). Request next_phase: " . $request->next_phase);

    $queue = Queue::with('patient', 'clinic')->findOrFail($id);
    $queue->phase = trim($request->next_phase);
    $queue->status = $queue->phase === 'completed' ? 'done' : 'in_progress';

    //  Notifikasi bila masuk consultation
    if ($queue->phase === 'consultation' && !$queue->notified_consultation) {
        Log::info("Consultation phase detected for queue ID {$queue->id}");

        session()->flash('alert', 'Your consultation turn has arrived!');
        $queue->notified_consultation = true;

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            Log::info("Sending consultation message to chat ID: {$queue->patient->telegram_chat_id}");

            try {
                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => " Hi {$queue->patient->name}, your queue number *{$queue->queue_number}* at *{$queue->clinic->clinic_name}* is now being served at the *Consultation Counter*.",
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Exception $e) {
                Log::error(" Telegram (Consultation) Error: " . $e->getMessage());
            }
        } else {
            Log::warning(" No chat ID for consultation phase (patient ID: {$queue->patient->id})");
        }
    }

    // ' Notifikasi bila masuk pharmacy
    if ($queue->phase === 'pharmacy' && !$queue->notified_pharmacy) {
        Log::info("' Pharmacy phase detected for queue ID {$queue->id}");

        session()->flash('alert', 'Its your turn at the pharmacy counter!');
        $queue->notified_pharmacy = true;

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            Log::info("Sending pharmacy message to chat ID: {$queue->patient->telegram_chat_id}");

            try {
                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => "' Hi {$queue->patient->name}, it's your turn at the *Pharmacy Counter* for queue number *{$queue->queue_number}* at *{$queue->clinic->clinic_name}*.",
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Exception $e) {
                Log::error(" Telegram (Pharmacy) Error: " . $e->getMessage());
            }
        } else {
            Log::warning(" No chat ID for pharmacy phase (patient ID: {$queue->patient->id})");
        }
    }

    $queue->save();

    return back()->with('success', 'Queue updated.');
}


    public function showQueue()
    {
        $queues = Queue::where('clinic_id', session('clinic_id'))
            ->orderBy('created_at')
            ->get();

        return view('clinic.queue', compact('queues'));
    }

    public function updateQueue(Request $request, $id)
{
    $queue = Queue::with('patient', 'clinic')->findOrFail($id);

    $queue->phase = trim(strtolower($request->input('next_phase')));
    $queue->status = $queue->phase === 'completed' ? 'done' : 'in_progress';

    Log::info(" Queue ID: {$queue->id} | Phase: [{$queue->phase}] | notified_pharmacy: {$queue->notified_pharmacy} | notified_consultation: {$queue->notified_consultation}");

    // ' Notifikasi bila masuk pharmacy
    if ($queue->phase === 'pharmacy' && !$queue->notified_pharmacy) {
        Log::info("' Triggering pharmacy notification for queue ID: {$queue->id}");

        $queue->notified_pharmacy = true;

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            try {
                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => "' Hi {$queue->patient->name}, it's your turn at the *Pharmacy Counter* for queue number *{$queue->queue_number}* at *{$queue->clinic->clinic_name}*.",
                    'parse_mode' => 'Markdown',
                ]);
                Log::info(" Pharmacy message sent to {$queue->patient->name}");
            } catch (\Exception $e) {
                Log::error(" Telegram (Pharmacy) Error: " . $e->getMessage());
            }
        } else {
            Log::warning(" No chat ID for pharmacy phase (patient ID: {$queue->patient->id})");
        }
    }

    //  Notifikasi bila masuk consultation
    if ($queue->phase === 'consultation' && !$queue->notified_consultation) {
        Log::info(" Triggering consultation notification for queue ID: {$queue->id}");

        $queue->notified_consultation = true;

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            try {
                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => " Hi {$queue->patient->name}, your queue number *{$queue->queue_number}* at *{$queue->clinic->clinic_name}* is now being served at the *Consultation Counter*.",
                    'parse_mode' => 'Markdown',
                ]);
                Log::info(" Consultation message sent to {$queue->patient->name}");
            } catch (\Exception $e) {
                Log::error(" Telegram (Consultation) Error: " . $e->getMessage());
            }
        } else {
            Log::warning(" No chat ID for consultation phase (patient ID: {$queue->patient->id})");
        }
    }

    $queue->save();

    return redirect()->back()->with('success', 'Queue updated successfully.');
}


   /* public function nextPhase($id)
{
    $queue = Queue::with('patient', 'clinic')->findOrFail($id);

    \Log::info(" nextPhase() dipanggil untuk queue ID: {$queue->queue_id}, current phase: {$queue->phase}");

    if ($queue->phase === 'consultation') {
        $queue->phase = 'pharmacy';
        $queue->save();

        \Log::info(" Phase ditukar ke pharmacy untuk {$queue->queue_number}");

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            try {
                \Log::info("" Sending pharmacy notification to chat ID: {$queue->patient->telegram_chat_id}");

                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));

                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => "' Hi {$queue->patient->name}, it's your turn at the *Pharmacy Counter* for queue number *{$queue->queue_number}* at *{$queue->clinic->clinic_name}*.",
                    'parse_mode' => 'Markdown',
                ]);

                \Log::info(" Message sent to {$queue->patient->name}");
            } catch (\Exception $e) {
                \Log::error(" Telegram Error: " . $e->getMessage());
            }
        } else {
            \Log::warning(" No Telegram chat ID for patient ID: {$queue->patient->id}");
        }

        return back()->with('success', 'Moved to pharmacy phase.');
    }

    return back()->with('error', 'Queue is not in consultation phase.');
}*/

   public function markAsDone($id)
{
    $queue = Queue::with('patient', 'clinic')->findOrFail($id);

    if ($queue->phase === 'pharmacy') {
        $queue->phase = 'completed'; //  FIXED
        $queue->status = 'done';
        $queue->save();

        if ($queue->patient && $queue->patient->telegram_chat_id) {
            try {
                $bot = new \Telegram\Bot\Api(env('TELEGRAM_BOT_TOKEN'));
                $bot->sendMessage([
                    'chat_id' => $queue->patient->telegram_chat_id,
                    'text' => " Thank you for visiting *{$queue->clinic->clinic_name}*! Please take care of your health and we hope to see you well again. ",
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Exception $e) {
                Log::error(" Telegram (Done) Error: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Queue marked as completed.');
    }

    return back()->with('error', 'Queue is not in pharmacy phase.');
}
   /*public function autoAssignNext()
    {
        $activeCounters = Queue::where('phase', 'consultation')->pluck('counter_number')->toArray();
        $availableCounters = collect([1, 2, 3])->diff($activeCounters);

        if ($availableCounters->isEmpty()) {
            return back()->with('error', 'All counters are currently busy.');
        }

        $nextPatient = Queue::with('patient', 'clinic')->where('phase', 'waiting')->orderBy('created_at')->first();

        if (!$nextPatient) {
            return back()->with('error', 'No patient is waiting.');
        }

        // Assign
        $nextPatient->counter_number = $availableCounters->first();
        $nextPatient->phase = 'consultation';
        $nextPatient->status = 'in_progress';
        $nextPatient->save();

        //  Hantar Telegram jika patient ada chat_id
        $patient = $nextPatient->patient;

        if ($patient && $patient->telegram_chat_id) {
            $bot = new Api(env('TELEGRAM_BOT_TOKEN'));

            $bot->sendMessage([
                'chat_id' => $patient->telegram_chat_id,
                'text' => " Hi {$patient->name}, your queue number *{$nextPatient->queue_number}* at *{$nextPatient->clinic->clinic_name}* is now being served at *Counter {$nextPatient->counter_number}*.",
                'parse_mode' => 'Markdown',
            ]);
        }

        return back()->with('success', 'Assigned ' . $patient->name . ' to Counter ' . $nextPatient->counter_number);
    }*/

    public function autoAssignAll()
    {
        $activeCounters = Queue::where('phase', 'consultation')->pluck('counter_number')->toArray();
        $availableCounters = collect([1, 2, 3])->diff($activeCounters)->values();

        $waitingPatients = Queue::with('patient', 'clinic')
            ->where('phase', 'waiting')
            ->orderBy('created_at')
            ->get();

        $assigned = [];

        foreach ($waitingPatients as $patient) {
            if ($availableCounters->isEmpty()) {
                break;
            }

            $counter = $availableCounters->shift(); // ambil counter pertama
            $patient->counter_number = $counter;
            $patient->phase = 'consultation';
            $patient->status = 'in_progress';
            $patient->save();

            $assigned[] = $patient->patient->name . ' Counter ' . $counter;

            //  Hantar Telegram kalau patient ada chat_id
            if ($patient->patient && $patient->patient->telegram_chat_id) {
                $bot = new Api(env('TELEGRAM_BOT_TOKEN'));

                $bot->sendMessage([
                    'chat_id' => $patient->patient->telegram_chat_id,
                    'text' => " Hi {$patient->patient->name}, your queue number *{$patient->queue_number}* at *{$patient->clinic->clinic_name}* is now being served at *Counter {$counter}*.",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }

        if (empty($assigned)) {
            return back()->with('error', 'No available counters or no waiting patients.');
        }

        return back()->with('success', 'Assigned: ' . implode(', ', $assigned));
    }

    public function nearby(Request $request)
{
    $lat = $request->lat;
    $lng = $request->lng;

    $clinics = Clinic::select('*')
        ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(latitude)) 
                        * cos(radians(longitude) - radians(?)) 
                        + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                        [$lat, $lng, $lat])
        ->having('distance', '<=', 20)
        ->orderBy('distance')
        ->get();

    return response()->json($clinics);
}


private function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371; // KM
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

}
