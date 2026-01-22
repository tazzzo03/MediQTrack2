@extends('layouts.guest')
@section('title', 'Clinic Login')

@section('content')
<section class="vh-70" style="background: linear-gradient(135deg, #eef2ff, #f8fafc); padding-bottom: 24px;">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col col-xl-10">
        <div class="card shadow-lg border-0" style="border-radius: 1rem;">
          <div class="row g-0">

            <!-- Image Section -->
            <div class="col-md-6 col-lg-5 d-none d-md-block p-0">
              <img src="{{ asset('images/image1.jpg') }}"
                   class="img-fluid h-100 w-100"
                   style="object-fit: cover; border-radius: 1rem 0 0 1rem;" />
            </div>

            <!-- Login Form -->
            <div class="col-md-6 col-lg-7 d-flex align-items-center">
              <div class="card-body p-4 p-lg-5 text-black">

                <!-- Branding -->
                <div class="mb-4 text-center">
                  <img src="{{ asset('images/app_icon.png') }}" width="90" class="mb-2">
                  <h4 class="fw-bold mb-1">MediQTrack</h4>
                  <p class="text-muted small">Clinic Administration Portal</p>
                </div>

                @if (session('success'))
                  <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                  <div class="alert alert-danger">
                    <ul class="mb-0">
                      @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <form method="POST" action="{{ route('clinic.login') }}">
                  @csrf

                  <h6 class="fw-normal mb-4 text-center" style="letter-spacing: 0.5px;">
                    Sign in to manage clinic operations
                  </h6>

                  <!-- Email -->
                  <div class="form-outline mb-3">
                    <label class="form-label small" for="email">Email Address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control form-control-lg"
                           placeholder="clinic@example.com"
                           value="{{ old('email') }}"
                           required autofocus />
                  </div>

                  <!-- Password -->
                  <div class="form-outline mb-4">
                    <label class="form-label small" for="password">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control form-control-lg"
                           placeholder="••••••••"
                           required />
                  </div>

                  <!-- Submit -->
                  <div class="d-flex justify-content-center mb-3">
                    <button class="btn btn-lg text-white px-5"
                            type="submit"
                            style="background-color:#2563eb;">
                      Login
                    </button>
                  </div>

                  <p class="text-center text-muted small mb-4">
                    Secure clinic access • Authorized personnel only
                  </p>

                </form>

              </div>
            </div>
            <!-- End Form Section -->

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
