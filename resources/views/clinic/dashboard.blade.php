@extends('layouts.clinic')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

  <h3 class="fw-bold text-primary mb-4">Clinic Dashboard</h3>

  <!-- Stats Section -->
  <div class="row g-4 mb-4">

      <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 stat-card">
          <div class="d-flex align-items-center">
            <div class="icon-box me-3" style="background-color:#1565C0;">
              <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Total Queues</h6>
              <h3 class="fw-bold mb-0" style="color:#1565C0;">{{ $totalQueues }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 stat-card">
          <div class="d-flex align-items-center">
            <div class="icon-box me-3" style="background-color:#00BFA5;">
              <i class="fa-solid fa-users"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Active Queues</h6>
              <h3 class="fw-bold mb-0" style="color:#00BFA5;">{{ $activeQueues }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 stat-card">
          <div class="d-flex align-items-center">
            <div class="icon-box me-3" style="background-color:#F9A825;">
              <i class="fa-solid fa-check-circle"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Completed Today</h6>
              <h3 class="fw-bold mb-0" style="color:#F9A825;">{{ $completedToday }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 stat-card">
          <div class="d-flex align-items-center">
            <div class="icon-box me-3" style="background-color:#8E24AA;">
              <i class="fa-solid fa-door-open"></i>
            </div>
            <div>
              <h6 class="text-muted mb-1">Active Rooms</h6>
              <h3 class="fw-bold mb-0" style="color:#8E24AA;">{{ $activeRooms }}</h3>
            </div>
          </div>
        </div>
      </div>

  </div>

  <!-- Info Section -->
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-3 h-100">
        <h6 class="fw-semibold text-primary mb-3">
          <i class="fa-solid fa-user-md me-2"></i> Doctor on Duty
        </h6>

        <table class="table align-middle">
          <tbody>
          @foreach($doctors as $d)
              <tr>
                  <td>{{ $d->doctor_name }} ({{ $d->name }})</td>

                  <td class="text-end">
                      @if($d->status === 'on')
                          <span class="badge bg-success px-3 py-2">Active</span>
                      @else
                          <span class="badge bg-secondary px-3 py-2">Off</span>
                      @endif
                  </td>
              </tr>
          @endforeach
          </tbody>
        </table>

      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-3 h-100">
        <h6 class="fw-semibold text-danger mb-3">
          <i class="fa-solid fa-ban me-2"></i> Cancellation Summary
        </h6>

        <p class="mb-1"><strong>Total Cancelled Today:</strong> {{ $totalCancelledToday }}</p>

        <p class="mb-1">
          User Cancelled:
          <strong class="text-primary">{{ $userCancelledToday }}</strong>
        </p>

        <p class="mb-1">
          Auto Cancel (Timeout / Left Geofence):
          <strong class="text-warning">{{ $autoCancelledToday }}</strong>
        </p>

        <hr>

        <p class="text-muted mb-0" style="font-size: 14px;">Status tracked from queue updates</p>
      </div>
    </div>

    <div class="row g-4 mt-4">

    {{-- Queue Activity Chart --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-semibold text-primary mb-3">
                <i class="fa-solid fa-chart-line me-2"></i> Queue Activity Today (Hourly)
            </h6>

            <canvas id="queueChart"
                data-hours='@json($hours)'
                data-totals='@json($totals)'
                height="120">
            </canvas>
        </div>
    </div>
 
    <div class="row g-4 mt-4">

    <!-- RIGHT SIDE (Pie Chart BIG CARD) -->
    <div class="col-12">
        <div class="card border-0 shadow-sm p-3">
            <h6 class="fw-semibold text-primary mb-3">
                <i class="fa-solid fa-user-nurse me-2"></i> Doctor Performance
            </h6>

            <canvas id="doctorChart" height="140" style="max-width: 420px; margin: 0 auto; display: block;"></canvas>
        </div>
    </div>

</div>

    


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const chartEl = document.getElementById('queueChart');

    const hours = JSON.parse(chartEl.dataset.hours);
    const totals = JSON.parse(chartEl.dataset.totals);

    const ctx = chartEl.getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'Number of Patients',
                data: totals,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>

<script>
    // Doctor Performance Chart Data
    const doctorChart = document.getElementById('doctorChart').getContext('2d');

    new Chart(doctorChart, {
        type: 'pie',
        data: {
            labels: @json($doctorNames),
            datasets: [{
                data: @json($doctorTotals),
                backgroundColor: [
                    '#1565C0',
                    '#00BFA5',
                    '#F9A825',
                    '#8E24AA',
                    '#EF5350',
                    '#5C6BC0',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

@endsection
