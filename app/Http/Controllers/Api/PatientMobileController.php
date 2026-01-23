<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class PatientMobileController extends Controller
{
    public function updateFcmToken(Request $request)
    {
        $payload = $request->all();
        $firebaseUid = $request->input('firebase_uid') ?: $request->input('uid');
        if (!$firebaseUid && $request->has('firebase_uid') === false) {
            $firebaseUid = $request->attributes->get('firebase_uid');
        }

        $fcmToken = $request->input('fcm_token') ?: $request->input('fcmToken') ?: $request->input('token');

        $request->validate([
            'firebase_uid' => 'nullable|string',
            'fcm_token' => 'nullable|string',
            'fcmToken' => 'nullable|string',
            'token' => 'nullable|string',
        ]);

        if (!$firebaseUid || !$fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'Missing firebase_uid or fcm_token.',
                'received' => [
                    'firebase_uid' => $firebaseUid,
                    'has_token' => !empty($fcmToken),
                ],
            ], 422);
        }

        $patient = Patient::where('firebase_uid', $request->firebase_uid)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.',
            ], 404);
        }

        $patient->update(['fcm_token' => $fcmToken]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
        ]);
    }
}
