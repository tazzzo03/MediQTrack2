<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['patient_id' => 'required|integer']);

        $data = Notification::where('patient_id', $request->patient_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $notif = Notification::create([
            'patient_id' => $request->patient_id,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type ?? 'info',
            'is_read' => 0,
        ]);

        return response()->json(['success' => true, 'data' => $notif]);
    }

    public function markRead($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->update(['is_read' => 1]);

        return response()->json(['success' => true, 'data' => $notif]);
    }

    public function destroy($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->delete();

        return response()->json(['success' => true]);
    }
}
