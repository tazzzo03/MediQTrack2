<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Clinic;

class ClinicAuthController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        return view('clinic.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clinics,email',
            'password' => 'required|confirmed|min:6',
            'role' => 'required|in:doctor,staff',
            'phone' => 'nullable|string|max:20',
            'clinic_id' => 'required|integer',
        ]);

        // Create new clinic user (doctor/staff)
        $clinicUser = Clinic::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'room_id' => null,
        ]);

        return redirect()->route('clinic.login')->with('success', 'Registration successful! Please log in.');
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('clinic.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('clinic')->attempt($credentials)) {
            $request->session()->regenerate();

            $clinicUser = Auth::guard('clinic')->user();
            return redirect()->route('clinic.dashboard')->with('success', 'Welcome, ' . $clinicUser->name . '!');
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::guard('clinic')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('clinic.login')->with('success', 'Logged out successfully.');
    }
}
