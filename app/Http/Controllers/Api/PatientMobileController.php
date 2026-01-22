<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class PatientMobileController extends Controller
{
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'firebase_uid' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        $patient = Patient::where('firebase_uid', $request->firebase_uid)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.',
            ], 404);
        }

        $patient->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
        ]);
    }
}
