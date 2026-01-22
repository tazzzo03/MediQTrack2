<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class UserSyncController extends Controller
{
    public function syncUser(Request $request)
    {
        $request->validate([
            'firebase_uid' => 'required|string',
            'email' => 'required|email',
        ]);

        // cari patient berdasarkan UID atau email
        $patient = Patient::firstOrCreate(
            ['firebase_uid' => $request->firebase_uid],
            ['email' => $request->email]
        );

        return response()->json([
            'success' => true,
            'patient_id' => $patient->id,
        ]);
    }
}
