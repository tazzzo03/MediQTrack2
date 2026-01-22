<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>MediQTrack Clinic - @yield('title')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  

  <style>
    :root {
      --primary: #1565C0;
      --secondary: #00BFA5;
      --bg-light: #E3F2FD;
      --text: #1E2A3A;
      --hover: #F5F8FF;
    }

    * {
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: var(--bg-light);
      color: var(--text);
      margin: 0;
      overflow-x: hidden;
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 240px;
      height: 100vh;
      background: #fff;
      border-right: 1px solid #e0e0e0;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 2px 0 12px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    .sidebar .logo {
      padding: 25px 20px 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      font-size: 1.1rem;
      color: var(--primary);
      border-bottom: 1px solid #f0f0f0;
    }

    .nav-links {
      padding: 25px 0;
      flex-grow: 1;
      overflow-y: auto;
      min-height: 0;
    }

    .nav-links a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 22px;
      text-decoration: none;
      color: var(--text);
      font-weight: 500;
      transition: 0.2s;
      position: relative;
    }

    .nav-links a:hover {
      background-color: var(--hover);
    }

    .nav-links a.active {
      background-color: var(--hover);
      color: var(--primary);
      font-weight: 600;
    }

    .nav-links a.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 4px;
      height: 100%;
      background: var(--primary);
      border-radius: 0 3px 3px 0;
    }

    .nav-links i {
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
      color: inherit;
    }

    .nav-links .menu-toggle .chevron {
      margin-left: auto;
      font-size: 0.8rem;
    }

    .nav-links .submenu {
      padding: 6px 0 8px;
    }

    .nav-links .sub-link {
      padding: 10px 22px 10px 52px;
      font-size: 0.95rem;
    }

    .sidebar-footer {
      border-top: 1px solid #f0f0f0;
      padding: 15px 20px;
      font-size: 0.9rem;
      color: #666;
      flex-shrink: 0;
    }

    .sidebar-footer button {
      border: 1px solid var(--primary);
      color: var(--primary);
      border-radius: 6px;
      padding: 5px 12px;
      font-weight: 500;
      transition: 0.2s;
    }

    .sidebar-footer button:hover {
      background: var(--primary);
      color: #fff;
    }

    /* ===== CONTENT ===== */
    .main-content {
      margin-left: 240px;
      padding: 35px 40px;
      min-height: 100vh;
    }

    .navbar-top {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 12px 25px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 25px;
    }

  </style>
</head>
<body>

  <div class="sidebar">
    <div>
      <div class="logo">
        <img src="{{ asset('images/app_icon.png') }}" alt="logo" width="36">
        MediQTrack
      </div>
      <div class="nav-links">
        <a href="{{ route('clinic.dashboard') }}" class="{{ request()->is('clinic/dashboard') ? 'active' : '' }}">
          <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="{{ route('clinic.rooms.index') }}" class="{{ request()->is('clinic/rooms*') ? 'active' : '' }}">
          <i class="fa-solid fa-door-open"></i> Rooms Management
        </a>
        @php
          $queueMenuOpen = request()->routeIs('clinic.queue*') || request()->is('clinic/queue*');
        @endphp
        <a class="menu-toggle {{ $queueMenuOpen ? 'active' : '' }}" data-bs-toggle="collapse" href="#queueSubmenu" role="button" aria-expanded="{{ $queueMenuOpen ? 'true' : 'false' }}" aria-controls="queueSubmenu">
          <i class="fa-solid fa-list"></i> Queue Management
          <i class="fa-solid fa-chevron-down chevron"></i>
        </a>
        <div class="collapse submenu {{ $queueMenuOpen ? 'show' : '' }}" id="queueSubmenu">
          <a href="{{ route('clinic.queue.index') }}" class="sub-link {{ request()->routeIs('clinic.queue.index') ? 'active' : '' }}">
            Active Queue
          </a>
          <a href="{{ route('clinic.queue.history') }}" class="sub-link {{ request()->routeIs('clinic.queue.history') ? 'active' : '' }}">
            Queue History
          </a>
        </div>
        <a href="{{ route('clinic.patients.index') }}" class="{{ request()->is('clinic/patients*') ? 'active' : '' }}">
          <i class="fa-solid fa-door-open"></i> Patient Management
        </a>
        <a href="{{ route('clinic.reports.index') }}" class="{{ request()->is('clinic/reports*') ? 'active' : '' }}">
          <i class="fa-solid fa-chart-column"></i> Reports
        </a>
      </div>
    </div>

    <div class="sidebar-footer text-center">
      <p class="mb-2 text-muted">Support 24/7</p>
      <form action="{{ route('clinic.logout') }}" method="POST">
        @csrf
        <button type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div class="main-content">
    <div class="navbar-top">
      <span class="me-3 fw-semibold">Welcome, Clinic Staff</span>
    </div>

    @yield('content')
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
