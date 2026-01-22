@extends('layouts.clinic')

@section('title', 'Rooms Management')

@section('content')
<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Rooms Management</h3>
    <button class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
      <i class="fa-solid fa-plus me-1"></i> Add Room
    </button>
  </div>

  <!-- Success Alert -->
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <!-- Room Cards -->
  <div class="row g-4">
    @forelse($rooms as $room)
      <div class="col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm h-100 text-center">
          <div class="card-body">
            <i class="fa-solid fa-door-open fa-2x mb-2 text-primary"></i>
            <h5 class="fw-semibold mb-1">{{ $room->name }}</h5>

            <span class="badge {{ $room->status === 'on' ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
              {{ ucfirst($room->status) }}
            </span>
          </div>

          <div class="card-footer bg-white border-0 pb-3">
            <form action="{{ route('clinic.rooms.toggle', $room->id) }}" method="POST">
              @csrf
              @method('PATCH')
              <button class="btn btn-outline-primary btn-sm">
                Toggle
              </button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <p class="text-muted">No rooms available yet. Click “Add Room” to create one.</p>
    @endforelse
  </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold text-primary" id="addRoomModalLabel">Add New Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('clinic.rooms.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <label class="form-label">Room Name</label>
          <input type="text" name="name" class="form-control" placeholder="Enter room name" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Room</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .card {
    transition: 0.2s ease;
    border-radius: 12px;
  }

  .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
  }

  .badge {
    border-radius: 8px;
  }

  .btn-outline-primary {
    border-radius: 8px;
  }

  .modal-content {
    border-radius: 10px;
  }
</style>
@endsection
