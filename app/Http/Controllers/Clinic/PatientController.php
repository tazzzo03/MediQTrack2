<?php

// app/Http/Controllers/Clinic/PatientController.php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient; // pastikan model betul
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $patients = Patient::query()
            ->when($q, function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('ic_number', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('clinic.patients.index', compact('patients'));
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'ic_number'    => ['required','string','max:50', Rule::unique('patients','ic_number')->ignore($patient->id)],
            'dob'          => ['nullable','date'],
            'email'        => ['nullable','email','max:255', Rule::unique('patients','email')->ignore($patient->id)],
            'phone_number' => ['nullable','string','max:50'],
            'gender'       => ['nullable','string','in:male,female,other'],
        ]);

        $patient->update($data);

        return redirect()->route('clinic.patients.index')->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        if (!empty($patient->firebase_uid)) {
            try {
                $serviceAccount = config('firebase.file');
                if (is_string($serviceAccount) && file_exists($serviceAccount)) {
                    $auth = (new Factory)->withServiceAccount($serviceAccount)->createAuth();
                    $auth->deleteUser($patient->firebase_uid);
                } else {
                    Log::warning('Firebase delete skipped: missing service account file', [
                        'patient_id' => $patient->id,
                        'firebase_uid' => $patient->firebase_uid,
                        'path' => $serviceAccount,
                    ]);
                }
            } catch (AuthException | FirebaseException $e) {
                Log::warning('Firebase delete failed', [
                    'patient_id' => $patient->id,
                    'firebase_uid' => $patient->firebase_uid,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Firebase delete failed (unexpected)', [
                    'patient_id' => $patient->id,
                    'firebase_uid' => $patient->firebase_uid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $patient->delete(); // hard delete
        return redirect()->route('clinic.patients.index')->with('success', 'Patient deleted successfully.');
    }

    public function show(Patient $patient)
    {
        // Optional: buat page details
        return view('clinic.patients.show', compact('patient'));
    }

    public function getProfile($firebase_uid)
    {
        $patient = \App\Models\Patient::where('firebase_uid', $firebase_uid)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone_number,
            'ic_number' => $patient->ic_number,
            'dob' => $patient->dob,
            'gender' => $patient->gender,
            'clinic_id' => $patient->clinic_id,
        ]
        ]);
    }

    public function deleteAccount($firebase_uid)
    {
        $patient = \App\Models\Patient::where('firebase_uid', $firebase_uid)->first();

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found']);
        }

        try {
            $patient->delete();
            return response()->json(['success' => true, 'message' => 'Patient deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request, $firebase_uid)
    {
        $patient = \App\Models\Patient::where('firebase_uid', $firebase_uid)->first();
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found']);
        }

        $patient->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return response()->json(['success' => true, 'message' => 'Profile updated']);
    }

    public function register(Request $request)
{
    try {
        // Validate input
        $validated = $request->validate([
            'firebase_uid' => 'required|string|unique:patients,firebase_uid',
            'name'         => 'required|string|max:255',
            'ic_number'    => 'required|string|max:20',
            'dob'          => 'required|string',
            'gender'       => 'required|string',
            'phone_number' => 'required|string|max:20',
            'email'        => 'required|string|email|max:255|unique:patients,email',
            'password'     => 'required|string',
        ]);

        // Save to MySQL
        $patient = \App\Models\Patient::create([
            'firebase_uid' => $validated['firebase_uid'],
            'name'         => $validated['name'],
            'ic_number'    => $validated['ic_number'],
            'dob'          => $validated['dob'],
            'gender'       => $validated['gender'],
            'phone_number' => $validated['phone_number'],
            'email'        => $validated['email'],
            'password'     => bcrypt($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patient registered successfully.',
            'patient' => $patient,
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
