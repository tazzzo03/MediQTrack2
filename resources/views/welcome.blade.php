<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediQTrack | Smart Clinic Queue Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.bunny.net/css?family=montserrat:400,600,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --brand: #1d4ed8;
            --brand-2: #2563eb;
            --accent: #14b8a6;
            --bg: #f8fafc;
            --panel: #ffffff;
            --shadow: 0 30px 80px rgba(15, 23, 42, 0.15);
        }

        * {
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--ink);
            padding-top: 78px;
        }

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            background: #ffffff;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }

        .site-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
        }

        .site-brand img {
            height: 48px;
            width: 48px;
        }

        .hero {
            position: relative;
            padding: 90px 0 70px;
            overflow: hidden;
            background:
                radial-gradient(600px circle at 10% 20%, rgba(20, 184, 166, 0.15), transparent 60%),
                radial-gradient(700px circle at 90% 0%, rgba(37, 99, 235, 0.2), transparent 60%),
                linear-gradient(135deg, #0f172a, #1e3a8a);
            color: #f8fafc;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 18px 18px;
            opacity: 0.25;
            pointer-events: none;
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: clamp(2rem, 3.6vw, 3rem);
            line-height: 1.05;
            margin-top: 18px;
        }

        .hero-subtitle {
            max-width: 640px;
            color: rgba(248, 250, 252, 0.85);
        }

        .hero-actions .btn {
            padding: 12px 28px;
            font-weight: 600;
        }


        .section-title {
            font-weight: 700;
            font-size: 2rem;
        }

        .feature-card {
            background: var(--panel);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            padding: 24px;
            height: 100%;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.12);
            color: var(--brand);
            margin-bottom: 12px;
            font-size: 1.2rem;
        }

        .steps {
            background: var(--panel);
            border-radius: 22px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .step-badge {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: var(--brand);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .cta {
            background: linear-gradient(135deg, #0ea5e9, #1d4ed8);
            color: #fff;
            border-radius: 24px;
            padding: 36px;
        }

        .fade-up {
            animation: fadeUp 0.8s ease forwards;
            opacity: 0;
            transform: translateY(16px);
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        footer {
            color: var(--muted);
        }
    </style>
</head>

<body>
<header class="site-header">
    <div class="container">
        <a class="site-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/app_icon.png') }}" alt="MediQTrack Logo">
            <span>MediQTrack</span>
        </a>
        <div class="d-none d-md-flex gap-3">
            <a href="https://drive.google.com/file/d/1nkE1jOzN60AwMsP14lwxxQnsh8dAY5wM/view?usp=sharing" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Download Apps</a>
            <a href="{{ route('clinic.login') }}" class="btn btn-sm btn-primary">Clinic Login</a>
        </div>
    </div>
</header>
<section class="hero">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
            <div class="fade-up">
                <h1 class="hero-title mt-3 fw-bold">Smarter Clinic Queue Management</h1>
                <p class="hero-subtitle mt-3 fs-5">
                    Real-time queue visibility, location-aware automation, and proactive alerts
                    so clinics stay efficient and patients stay informed.
                </p>
                <div class="hero-actions mt-4 d-flex flex-wrap gap-3">
                    <a href="https://drive.google.com/file/d/1nkE1jOzN60AwMsP14lwxxQnsh8dAY5wM/view?usp=sharing" class="btn btn-light" target="_blank" rel="noopener noreferrer">Download Apps</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5 fade-up delay-1">
            <h2 class="section-title">Why MediQTrack</h2>
            <p class="text-muted">
                Built for busy clinics that need clarity, speed, and better patient flow.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card fade-up delay-1">
                    <div class="feature-icon"><i class="bi bi-clock"></i></div>
                    <h6 class="fw-semibold">Real-Time Queue</h6>
                    <p class="text-muted small mb-0">
                        Patients monitor live queue progress without waiting physically.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card fade-up delay-2">
                    <div class="feature-icon"><i class="bi bi-geo-alt"></i></div>
                    <h6 class="fw-semibold">Geofence Control</h6>
                    <p class="text-muted small mb-0">
                        Automatic cancellation when patients leave the clinic area.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card fade-up delay-2">
                    <div class="feature-icon"><i class="bi bi-bell"></i></div>
                    <h6 class="fw-semibold">Notification Alerts</h6>
                    <p class="text-muted small mb-0">
                        Instant notifications so patients arrive at the right time.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card fade-up delay-3">
                    <div class="feature-icon"><i class="bi bi-layout-text-window-reverse"></i></div>
                    <h6 class="fw-semibold">Clinic Dashboard</h6>
                    <p class="text-muted small mb-0">
                        Manage rooms, queues, and reports from one workspace.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="steps fade-up">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <h3 class="fw-bold mb-3">How it works</h3>
                    <p class="text-muted mb-0">
                        A simple flow that keeps staff focused and patients informed.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-start gap-2">
                                <span class="step-badge">1</span>
                                <div>
                                    <div class="fw-semibold">Join queue</div>
                                    <div class="text-muted small">Register and login to the application</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start gap-2">
                                <span class="step-badge">2</span>
                                <div>
                                    <div class="fw-semibold">Track status</div>
                                    <div class="text-muted small">Watch your queue progress live</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start gap-2">
                                <span class="step-badge">3</span>
                                <div>
                                    <div class="fw-semibold">Get alerts</div>
                                    <div class="text-muted small">Rea time notification when turn is coming</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 fade-up">
            <div>
                <h3 class="fw-bold mb-2">Access clinic queues through MediQTrack</h3>
                <p class="mb-0 text-white-50">
                    Download the app to join queues and track consultation progress in real time.
                </p>
            </div>
            <div class="d-flex gap-3">
                <a href="https://drive.google.com/file/d/1nkE1jOzN60AwMsP14lwxxQnsh8dAY5wM/view?usp=sharing" class="btn btn-light fw-semibold" target="_blank" rel="noopener noreferrer">Download Apps</a>
            </div>
        </div>
    </div>
</section>

<footer class="py-4 text-center bg-white">
    <p class="mb-1">MediQTrack - Smart clinic queue management system</p>
    <p class="small mb-0">Support: support@mediqtrack.com</p>
</footer>
</body>
</html>
