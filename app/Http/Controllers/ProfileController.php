<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $uid = $request->query('firebase_uid');
        if (!$uid) return response()->json(['success'=>false,'message'=>'firebase_uid is required'], 422);

        $patient = Patient::where('firebase_uid', $uid)->first();
        if (!$patient) return response()->json(['success'=>true,'data'=>null]); // kosong kalau belum link

        return response()->json([
            'success' => true,
            'data' => [
                'id'          => $patient->id,
                'name'        => $patient->name,
                'phone_number'       => $patient->phone_number ?? null,
                'email'       => $patient->email ?? null,   // jika ada column email
                //'avatar_url'  => $patient->avatar_url ?? null,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'firebase_uid' => 'required',
            'name'         => 'required|string|min:3',
            'phone_number'        => 'nullable|string|max:30',
            //'avatar_url'   => 'nullable|string',
            // 'email'     => 'nullable|email' // kalau nak simpan di patients
        ]);

        $patient = Patient::where('firebase_uid', $data['firebase_uid'])->first();
        if (!$patient) return response()->json(['success'=>false,'message'=>'Patient not found'], 404);

        $patient->name       = $data['name'];
        $patient->phone_number      = $data['phone_number'] ?? $patient->phone_number;
        //$patient->avatar_url = $data['avatar_url'] ?? $patient->avatar_url;
        // $patient->email   = $request->email ?? $patient->email;
        $patient->save();

        return response()->json(['success'=>true, 'message'=>'Profile updated']);
    }
}
