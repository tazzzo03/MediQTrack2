@extends('layouts.clinic')

@section('title', 'Rooms Management')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Rooms Management</h3>
    <button class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
      <i class="fa-solid fa-plus me-1"></i> Add Room
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 10%">#</th>
            <th>Room Name</th>
            <th>Doctor Assigned</th>
            <th class="text-center">Status</th>
            <th class="text-center" style="width: 20%">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rooms as $index => $room)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="fw-semibold">{{ $room->name }}</td>
              <td>
                @if(isset($room->doctor_name))
                  {{ $room->doctor_name }}
                @else
                  <span class="text-muted fst-italic">Not assigned</span>
                @endif
              </td>

              <td class="text-center">
                <form action="{{ route('clinic.rooms.toggle', $room->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('PATCH')
                  <label class="switch">
                    <input type="checkbox" onchange="this.form.submit()" {{ $room->status === 'on' ? 'checked' : '' }}>
                    <span class="slider"></span>
                  </label>
                </form>
              </td>

              <td class="text-center">
                <!-- Edit Button -->
                <button class="btn btn-sm btn-primary me-1" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editRoomModal{{ $room->id }}">
                  <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                </button>

                <!-- Delete Button -->
                <form action="{{ route('clinic.rooms.destroy', $room->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this room?')">
                    <i class="fa-solid fa-trash me-1"></i>Delete
                  </button>
                </form>
              </td>
            </tr>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalLabel{{ $room->id }}" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header">
                    <h5 class="modal-title fw-semibold text-primary" id="editRoomModalLabel{{ $room->id }}">Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form action="{{ route('clinic.rooms.update', $room->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Room Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $room->name }}" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Doctor Assigned</label>
                        <input type="text" name="doctor_name" class="form-control" value="{{ $room->doctor_name }}" required>
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
              <td colspan="5" class="text-center text-muted">No rooms found. Add one to get started.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold text-primary" id="addRoomModalLabel">Add New Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('clinic.rooms.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Room Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter room name" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Doctor Assigned</label>
            <input type="text" name="doctor_name" class="form-control" placeholder="Enter doctor name" required>
          </div>
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
  .switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }

  .switch input { opacity: 0; width: 0; height: 0; }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 20px; width: 20px;
    left: 4px; bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
  }

  input:checked + .slider {
    background-color: #00BFA5;
  }

  input:checked + .slider:before {
    transform: translateX(24px);
  }

  .slider::after {
    content: "Off";
    color: white;
    position: absolute;
    left: 8px;
    top: 4px;
    font-size: 11px;
    font-weight: 600;
  }

  input:checked + .slider::after {
    content: "On";
    left: 28px;
  }

  table td, table th {
    vertical-align: middle;
  }
</style>
@endsection
