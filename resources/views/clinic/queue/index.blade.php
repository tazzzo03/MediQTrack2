@extends('layouts.clinic')

@section('title', 'Queue Management')

@section('content')

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Queue Management</h3>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs" id="queueTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-semibold" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staffView" type="button" role="tab">Staff View</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="doctor-tab" data-bs-toggle="tab" data-bs-target="#doctorView" type="button" role="tab">Doctor View</button>
    </li>
  </ul>

  <div class="tab-content mt-4" id="queueTabsContent">

    <!-- ================= Staff View ================= -->
    <div class="tab-pane fade show active" id="staffView" role="tabpanel" aria-labelledby="staff-tab">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold text-secondary">Staff Queue Overview</h5>
      </div>

      <ul class="nav nav-tabs" id="staffQueueTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-semibold" id="active-queue-tab" data-bs-toggle="tab" data-bs-target="#activeQueue" type="button" role="tab">Active Queue</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-semibold" id="completed-queue-tab" data-bs-toggle="tab" data-bs-target="#completedQueue" type="button" role="tab">Completed Today</button>
        </li>
      </ul>

      <div class="tab-content mt-3" id="staffQueueTabsContent">
        <div class="tab-pane fade show active" id="activeQueue" role="tabpanel" aria-labelledby="active-queue-tab">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Queue Number</th>
                    <th>Patient Name</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($activeQueues as $index => $queue)
                    <tr>
                      <td>{{ $index + 1 }}</td>
                      <td>{{ $queue->queue_number }}</td>
                      @php
                        $fullName = $queue->patient->name ?? 'Unknown';
                        $nameParts = preg_split('/\s+/', trim($fullName));
                        $shortName = count($nameParts) > 1
                          ? ($nameParts[0] . ' ' . $nameParts[1])
                          : $fullName;
                      @endphp
                      <td>{{ $shortName }}</td>
                      <td>{{ $queue->room->name ?? '-' }}</td>
                      <td>
                        @if($queue->status == 'waiting')
                          <span class="badge bg-warning text-dark">Waiting</span>
                        @elseif($queue->status == 'serving')
                          <span class="badge bg-success">Serving</span>
                        @elseif($queue->status == 'completed')
                          <span class="badge bg-primary">Completed</span>
                        @elseif($queue->status == 'skipped')
                          <span class="badge bg-secondary">Skipped</span>
                        @elseif($queue->status == 'cancelled')
                          <span class="badge bg-danger">Cancelled</span>
                        @else
                          <span class="badge bg-light text-dark">{{ ucfirst($queue->status) }}</span>
                        @endif
                      </td>
                      <td>
                        @if($queue->status == 'waiting')
                          {{-- Delete --}}
                          <form action="{{ route('clinic.queue.destroy', $queue->queue_id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete this queue?')">
                              <i class="fa-solid fa-trash me-1"></i> Delete
                            </button>
                          </form>

                        @elseif($queue->status == 'in_consultation')
                          {{-- No action --}}
                          <span class="text-muted small">In consultation</span>

                        @elseif($queue->status == 'cancelled')
                          {{-- No action --}}
                      
                        @elseif($queue->status == 'serving')
                          {{-- Call Patient --}}
                          <form action="{{ route('clinic.queue.callPatient', $queue->queue_id) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-primary">
                              <i class="fa-solid fa-bullhorn me-1"></i> Call Patient
                            </button>
                          </form>

                        @elseif($queue->status == 'called')
                          {{-- Mark Done --}}
                          <form action="{{ route('clinic.queue.markDone', $queue->queue_id) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">
                              <i class="fa-solid fa-check me-1"></i> Mark Done
                            </button>
                          </form>
                        @endif
                      </td>

                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted">No active queues found.</td>
                    </tr>
                  @endforelse

                  {{-- ================= Edit Queue Modal ================= --}}
                  @foreach($activeQueues as $queue)
                  <div class="modal fade" id="editQueueModal{{ $queue->queue_id }}" tabindex="-1" aria-labelledby="editQueueLabel{{ $queue->queue_id }}" aria-hidden="true">
                    <div class="modal-dialog">
                      <form action="{{ route('clinic.queue.update', $queue->queue_id) }}" method="POST" class="modal-content">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                          <h5 class="modal-title" id="editQueueLabel{{ $queue->queue_id }}">Edit Queue</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Patient</label>
                            <select name="patient_id" class="form-select" required>
                              @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ $queue->patient_id == $patient->id ? 'selected' : '' }}>
                                  {{ $patient->name }}
                                </option>
                              @endforeach
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                              <option value="waiting" {{ $queue->status == 'waiting' ? 'selected' : '' }}>Waiting</option>
                              <option value="in_consultation" {{ $queue->status == 'in_consultation' ? 'selected' : '' }}>In Consultation</option>
                              <option value="pharmacy" {{ $queue->status == 'pharmacy' ? 'selected' : '' }}>Pharmacy</option>
                              <option value="completed" {{ $queue->status == 'completed' ? 'selected' : '' }}>Completed</option>
                              <option value="cancelled" {{ $queue->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                  @endforeach
                </tbody>

              </table>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="completedQueue" role="tabpanel" aria-labelledby="completed-queue-tab">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Queue Number</th>
                    <th>Patient Name</th>
                    <th>Room</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($completedQueues as $index => $queue)
                    <tr>
                      <td>{{ $index + 1 }}</td>
                      <td>{{ $queue->queue_number }}</td>
                      @php
                        $fullName = $queue->patient->name ?? 'Unknown';
                        $nameParts = preg_split('/\s+/', trim($fullName));
                        $shortName = count($nameParts) > 1
                          ? ($nameParts[0] . ' ' . $nameParts[1])
                          : $fullName;
                      @endphp
                      <td>{{ $shortName }}</td>
                      <td>{{ $queue->room->name ?? '-' }}</td>
                      <td><span class="badge bg-primary">Completed</span></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted">No completed queues today.</td>
                    </tr>
                  @endforelse
                </tbody>

              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= Doctor View ================= -->
    <div class="tab-pane fade" id="doctorView" role="tabpanel" aria-labelledby="doctor-tab">
      <h5 class="fw-semibold text-secondary mb-3">Doctor Room Panel</h5>

      <div class="card border-0 shadow-sm p-4">
        <div class="row g-3 align-items-center mb-4">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Select Room</label>
            <select id="doctorRoom" class="form-select" name="room_id" required>
              <option selected disabled>-- Choose Room --</option>
              @foreach($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->doctor_name }})</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="now-serving text-center my-5" id="nowServingPanel">
          <h6 class="text-muted">Please select a room to view current patient</h6>
        </div>
      </div>
    </div>

    <script>
      document.getElementById('doctorRoom').addEventListener('change', function() {
        const roomId = this.value;

        fetch(`/clinic/queue/now-serving/${roomId}`)
          .then(res => res.json())
          .then(data => {
            window.currentQueueId = data.id;
            const panel = document.getElementById('nowServingPanel');

            // ✅ 1. Tiada patient in consultation
            if (!data.patient) {
              // kalau ada waiting queue, tunjuk button
              if (data.hasWaiting) {
                panel.innerHTML = `
                  <h6 class="text-muted">No patient currently in consultation</h6>
                  <button id="nextBtn" class="btn btn-primary px-4 mt-3">
                    <i class="fa-solid fa-forward me-1"></i> Call Next Patient
                  </button>
                `;
              } else {
                // kalau betul-betul takde queue
                panel.innerHTML = `
                  <h6 class="text-muted">No patient currently in queue</h6>
                `;
              }
            }

            // ✅ 2. Kalau ada patient sedang consultation
            else {
              panel.innerHTML = `
                <h6 class="text-muted">Now Serving</h6>
                <h2 class="fw-bold text-primary">${data.queue_number} - ${data.patient}</h2>
                <p class="text-muted mb-4">${data.room} (${data.doctor})</p>

                <form id="actionForm">
                  <button id="completeBtn" class="btn btn-success px-4 me-2" type="button">
                    <i class="fa-solid fa-check me-1"></i> Complete
                  </button>
                </form>
              `;
            }
          })
          .catch(err => console.error(err));
      });
      </script>


  </div>
</div>

<script>
document.addEventListener('click', async function(e) {
  const setButtonLoading = (btn, loading, label) => {
    if (!btn) {
      return;
    }
    if (loading) {
      if (!btn.dataset.originalHtml) {
        btn.dataset.originalHtml = btn.innerHTML;
      }
      btn.disabled = true;
      btn.innerHTML = label || 'Processing...';
    } else {
      btn.disabled = false;
      if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.originalHtml;
      }
    }
  };

  // 🧩 COMPLETE BUTTON (patient finished consultation)
  if (e.target.closest('#completeBtn')) {
    e.preventDefault();

    if (!window.currentQueueId) {
      alert('No active patient selected.');
      return;
    }

    try {
      const completeBtn = e.target.closest('#completeBtn');
      setButtonLoading(completeBtn, true, 'Completing...');

      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      const res = await fetch(`/clinic/queue/${window.currentQueueId}/complete`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
      });

      const text = await res.text();
      console.log('✅ Complete response:', text);

      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        setButtonLoading(completeBtn, false);
        alert('⚠️ Invalid JSON from server:\n' + text);
        return;
      }

      const panel = document.getElementById('nowServingPanel');
      if (data.hasNext) {
        panel.innerHTML = `
          <h6 class="text-muted">Room available</h6>
          <p class="text-muted mb-4">Staff is preparing medication for previous patient.</p>
          <button id="nextBtn" class="btn btn-primary px-4">
            <i class="fa-solid fa-forward me-1"></i> Call Next Patient
          </button>
        `;
      } else {
        panel.innerHTML = `
          <h6 class="text-muted">Room available</h6>
          <p class="text-muted mb-4">No more waiting patients at the moment.</p>
        `;
      }

      alert('✅ ' + (data.message || 'Consultation completed.'));
      window.currentQueueId = null;
      const staffTab = document.getElementById('staff-tab');
      if (staffTab && window.bootstrap?.Tab) {
        new bootstrap.Tab(staffTab).show();
        setTimeout(() => window.location.reload(), 300);
      } else {
        window.location.reload();
      }

    } catch (err) {
      console.error(err);
      setButtonLoading(completeBtn, false);
      alert('❌ Error completing consultation: ' + err.message);
    }
  }

  // 🧩 NEXT BUTTON (call next patient)
  if (e.target.closest('#nextBtn')) {
    e.preventDefault();

    const roomId = document.getElementById('doctorRoom').value;
    if (!roomId) {
      alert('Please select a room first.');
      return;
    }

    let nextBtn;
    try {
      nextBtn = e.target.closest('#nextBtn');
      setButtonLoading(nextBtn, true, 'Calling...');
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch(`/clinic/queue/next/${roomId}`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
      });

      const raw = await res.text();
      console.log('🔥 Raw response:', raw);

      let data;
      try {
        data = JSON.parse(raw);
      } catch (err) {
        setButtonLoading(nextBtn, false);
        alert('⚠️ Could not parse server response:\n' + raw);
        return;
      }

      console.log('✅ Parsed JSON:', data);

      const panel = document.getElementById('nowServingPanel');
      if (data.success && data.next) {
        window.currentQueueId = data.next.id;
        panel.innerHTML = `
          <h6 class="text-muted">Now Serving</h6>
          <h2 class="fw-bold text-primary">${data.next.queue_number} - ${data.next.patient}</h2>
          <p class="text-muted mb-4">${data.next.room}</p>
          <form id="actionForm">
            <button id="completeBtn" class="btn btn-success px-4 me-2" type="button">
              <i class="fa-solid fa-check me-1"></i> Complete
            </button>
          </form>
        `;
        alert('✅ ' + (data.message || 'Next patient called.'));
      } else {
        setButtonLoading(nextBtn, false);
        panel.innerHTML = `<h6 class="text-muted">No waiting patients.</h6>`;
        alert('ℹ️ ' + (data.message || 'No waiting patients.'));
      }

    } catch (err) {
      console.error(err);
      setButtonLoading(nextBtn, false);
      alert('❌ JS Error: ' + err.message);
    }
  }

});
</script>

<script>
(() => {
  const staffTabBtn = document.getElementById('staff-tab');
  const doctorTabBtn = document.getElementById('doctor-tab');
  let refreshTimer = null;

  const startAutoRefresh = () => {
    if (refreshTimer) {
      return;
    }
    refreshTimer = setInterval(() => {
      const staffView = document.getElementById('staffView');
      if (staffView && staffView.classList.contains('show')) {
        window.location.reload();
      }
    }, 10000);
  };

  const stopAutoRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer);
      refreshTimer = null;
    }
  };

  const staffView = document.getElementById('staffView');
  if (staffView && staffView.classList.contains('show')) {
    startAutoRefresh();
  }

  staffTabBtn?.addEventListener('shown.bs.tab', startAutoRefresh);
  doctorTabBtn?.addEventListener('shown.bs.tab', stopAutoRefresh);
})();
</script>

<style>
  .nav-tabs .nav-link {
    color: #1565C0;
    border: none;
    border-bottom: 3px solid transparent;
    transition: 0.2s;
  }
  .nav-tabs .nav-link.active {
    border-bottom: 3px solid #1565C0;
    font-weight: 600;
  }
  .table td, .table th {
    vertical-align: middle;
  }
  .modal-content {
    border-radius: 10px;
  }
</style>
@endsection
