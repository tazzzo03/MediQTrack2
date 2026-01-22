@extends('layouts.clinic')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4 text-primary fw-bold">Clinic Operational Report</h2>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('clinic.reports.export') }}">
                @php
                    $currentYear = now()->year;
                    $years = range($currentYear, $currentYear - 5);
                    $selectedMonth = (int) request('month', now()->month);
                    $selectedYear = (int) request('year', $currentYear);
                    $months = [
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December',
                    ];
                @endphp
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Report Period</label>
                        <select name="type" id="report-type" class="form-select" required>
                            <option value="day">Daily</option>
                            <option value="week">Weekly</option>
                            <option value="month" selected>Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month" id="report-month" class="form-select">
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" {{ $selectedMonth === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <select name="year" id="report-year" class="form-select">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            Export PDF
                        </button>
                    </div>
                </div>
            </form>
            <script>
                (function () {
                    var typeEl = document.getElementById('report-type');
                    var monthEl = document.getElementById('report-month');
                    var yearEl = document.getElementById('report-year');

                    if (!typeEl || !monthEl || !yearEl) {
                        return;
                    }

                    function syncMonthYearState() {
                        var isMonthly = typeEl.value === 'month';
                        monthEl.disabled = !isMonthly;
                        yearEl.disabled = !isMonthly;
                    }

                    typeEl.addEventListener('change', syncMonthYearState);
                    syncMonthYearState();
                })();
            </script>

            <hr>

            <p class="text-muted mb-0">
                This report summarizes clinic performance, consultation room activity,
                and queue cancellation analysis based on the selected time period.
            </p>
        </div>
    </div>
</div>
@endsection
