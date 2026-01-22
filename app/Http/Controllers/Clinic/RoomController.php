<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    // Papar semua bilik
    public function index()
    {
        $rooms = Room::all();
        return view('clinic.rooms.index', compact('rooms'));
    }

    // Tambah bilik baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'doctor_name' => 'required|string|max:100',
        ]);

        Room::create([
            'name' => $request->name,
            'doctor_name' => $request->doctor_name,
            'status' => 'off',
        ]);

        return redirect()->back()->with('success', 'Room added successfully!');
    }

    // Tukar status bilik (on/off)
    public function toggleStatus($id)
    {
        $room = Room::findOrFail($id);
        $room->status = $room->status === 'on' ? 'off' : 'on';
        $room->save();

        return redirect()->back()->with('success', 'Room status updated.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'doctor_name' => 'required|string|max:100',
        ]);

        $room = Room::findOrFail($id);
        $room->update([
            'name' => $request->name,
            'doctor_name' => $request->doctor_name,
        ]);

        return redirect()->back()->with('success', 'Room updated successfully!');
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return redirect()->back()->with('success', 'Room deleted successfully!');
    }

    // API: kira jumlah bilik aktif
    public function activeCount()
    {
        $count = Room::where('status', 'on')->count();

        return response()->json([
            'success' => true,
            'active_rooms' => $count,
        ]);
    }

}
