<?php

// app/Http/Controllers/VisitHistoryController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;

class VisitHistoryController extends Controller
{
    public function index(Request $request)
    {
        $uid = $request->query('firebase_uid');
        if (!$uid) {
            return response()->json(['success' => false, 'message' => 'firebase_uid is required'], 422);
        }

        $patient = Patient::where('firebase_uid', $uid)->first();
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        // Ambil history — join room & clinic untuk nama (fallback kalau tiada)
        $rows = DB::table('queues')
            ->leftJoin('rooms', 'rooms.id', '=', 'queues.room_id')
            ->leftJoin('clinics', 'clinics.clinic_id', '=', 'queues.clinic_id')
            ->where('queues.patient_id', $patient->id)
            ->orderByDesc('queues.updated_at')
            ->get([
                'queues.queue_id',
                'queues.queue_number',
                'queues.status',
                'queues.room_id',
                'rooms.name as room_name',
                'rooms.doctor_name as doctor_name',
                'queues.clinic_id',
                'clinics.name as clinic_name',
                'queues.created_at',
                'queues.updated_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}



