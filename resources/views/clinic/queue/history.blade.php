@extends('layouts.clinic')

@section('title', 'Queue History')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Queue History</h3>
  </div>

  <form method="GET" class="card border-0 shadow-sm p-3 mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">Date</label>
        <select id="dateFilter" name="date_filter" class="form-select">
          <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
          <option value="last7" {{ $dateFilter === 'last7' ? 'selected' : '' }}>Last 7 Days</option>
          <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>Custom Date</option>
        </select>
      </div>
      <div class="col-md-3" id="customDateFrom">
        <label class="form-label fw-semibold">From</label>
        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
      </div>
      <div class="col-md-3" id="customDateTo">
        <label class="form-label fw-semibold">To</label>
        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          <option value="auto_cancelled" {{ $statusFilter === 'auto_cancelled' ? 'selected' : '' }}>Auto Cancelled</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Patient Name</label>
        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search patient name">
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Apply</button>
      </div>
    </div>
  </form>

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
            <th>Date</th>
            <th>Time Completed</th>
          </tr>
        </thead>
        <tbody>
          @forelse($queues as $index => $queue)
            @php
              if ($queue->status === 'completed') {
                $statusLabel = 'Completed';
                $statusClass = 'bg-primary';
              } elseif ($queue->status === 'auto_cancelled') {
                $statusLabel = 'Auto Cancelled';
                $statusClass = 'bg-warning text-dark';
              } else {
                $statusLabel = 'Cancelled';
                $statusClass = 'bg-danger';
              }
              $dateValue = $queue->created_at ? $queue->created_at->format('Y-m-d') : '-';
              $timeValue = null;
              if ($queue->status === 'completed') {
                $timeValue = $queue->end_time ?? $queue->updated_at;
              } else {
                $timeValue = $queue->cancelled_at ?? $queue->updated_at;
              }
              $timeLabel = $timeValue ? $timeValue->format('H:i') : '-';
            @endphp
            <tr>
              <td>{{ $index + 1 }}</td>
              <td>{{ $queue->queue_number }}</td>
              <td>{{ $queue->patient->name ?? 'Unknown' }}</td>
              <td>{{ $queue->room->name ?? '-' }}</td>
              <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td>{{ $dateValue }}</td>
              <td>{{ $timeLabel }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted">No queue history found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const dateFilter = document.getElementById('dateFilter');
  const customDateFrom = document.getElementById('customDateFrom');
  const customDateTo = document.getElementById('customDateTo');

  function toggleCustomDates() {
    const showCustom = dateFilter.value === 'custom';
    customDateFrom.classList.toggle('d-none', !showCustom);
    customDateTo.classList.toggle('d-none', !showCustom);
  }

  dateFilter.addEventListener('change', toggleCustomDates);
  toggleCustomDates();
</script>
@endsection
