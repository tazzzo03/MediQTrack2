@extends('layouts.guest')
<title>@yield('title', 'Staff/Doctor Register')</title>

@section('content')
<section class="vh-100" style="background-color:#f8f9fa;">
  <div class="container py-5">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-lg-7">
        <div class="card shadow border-0" style="border-radius: 1rem;">
          <div class="card-body p-5">

            <h3 class="fw-bold text-center mb-4 text-primary">Register as Clinic Staff / Doctor</h3>

            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('clinic.register') }}">
              @csrf

              <!-- Full Name -->
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
              </div>

              <!-- Email -->
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
              </div>

              <!-- Password -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <input type="password" name="password_confirmation" class="form-control" required>
                </div>
              </div>

              <!-- Phone -->
              <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
              </div>

              <!-- Role -->
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                  <option value="" disabled selected>Select your role</option>
                  <option value="doctor">Doctor</option>
                  <option value="staff">Staff</option>
                </select>
              </div>

              <!-- Hidden: Auto Link to Clinic -->
              <input type="hidden" name="clinic_id" value="1">

              <div class="mb-4">
                <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
              </div>

              <p class="text-center text-muted mb-0">
                Already have an account? 
                <a href="{{ route('clinic.login') }}" class="text-primary fw-semibold">Login here</a>
              </p>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
