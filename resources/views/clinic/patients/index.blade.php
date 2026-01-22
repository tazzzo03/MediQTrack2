@extends('layouts.clinic')

@section('title', 'Patients Management')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Patients Management</h3>

    <div class="d-flex align-items-center gap-2">
      <form method="GET" action="{{ route('clinic.patients.index') }}" class="d-flex">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control me-2" placeholder="Search name or email">
        <button class="btn btn-outline-primary">Search</button>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 5%">#</th>
            <th style="width: 30%">Name</th>
            <th style="width: 30%">Email</th>
            <th style="width: 20%">Phone</th>
            <th class="text-center" style="width: 15%">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($patients as $index => $patient)
            <tr>
              <td class="fw-semibold">{{ $index + 1 }}</td>
              <td class="fw-semibold">{{ $patient->name }}</td>
              <td>{{ $patient->email ?? '-' }}</td>
              <td>{{ $patient->phone_number ?? '-' }}</td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                    <!-- View Button -->
                    <button class="btn btn-sm btn-secondary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#viewPatientModal{{ $patient->id }}">
                    <i class="fa-solid fa-eye me-1"></i> View
                    </button>

                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-primary"
                            data-bs-toggle="modal" 
                            data-bs-target="#editPatientModal{{ $patient->id }}">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </button>

                    <!-- Delete Button -->
                    <form action="{{ route('clinic.patients.destroy', $patient->id) }}" 
                        method="POST" 
                        onsubmit="return confirm('Delete this patient?')" 
                        class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-trash me-1"></i> Delete
                    </button>
                    </form>
                </div>
                </td>
            </tr>

            {{-- View Modal --}}
            <div class="modal fade" id="viewPatientModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header">
                    <h5 class="modal-title fw-semibold text-primary">Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p><strong>Name:</strong> {{ $patient->name }}</p>
                    <p><strong>Email:</strong> {{ $patient->email ?? '-' }}</p>
                    <p><strong>Phone:</strong> {{ $patient->phone_number ?? '-' }}</p>
                    <p><strong>IC Number:</strong> {{ $patient->ic_number ?? '-' }}</p>
                    <p><strong>Date of Birth:</strong> {{ $patient->dob ?? '-' }}</p>
                    <p><strong>Gender:</strong> {{ ucfirst($patient->gender ?? '-') }}</p>
                    <p><strong>Registered:</strong> {{ $patient->created_at->format('d M Y, h:i A') }}</p>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>

            {{-- Edit Modal --}}
            <div class="modal fade" id="editPatientModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold text-primary">Edit Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('clinic.patients.update', $patient->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $patient->name }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">IC Number</label>
                        <input type="text" name="ic_number" class="form-control" value="{{ $patient->ic_number }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="{{ $patient->dob }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $patient->email }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="{{ $patient->phone_number }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gender</label>
                        <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" {{ $patient->gender == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $patient->gender == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ $patient->gender == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    </div>

                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                </div>
            </div>
            </div>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-3">No patients found. Add one to get started.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  table td, table th {
    vertical-align: middle;
  }
  .btn-sm i {
    font-size: 13px;
  }
  .btn-sm {
    padding: 4px 10px;
  }
</style>
@endsection
