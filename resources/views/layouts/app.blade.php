<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'The Tooth Lounge') · The Tooth Lounge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --teal: #117f91;
            --teal-deep: #075b70;
            --teal-dark: #06495b;
            --teal-soft: #e5f6f8;
            --gold: #aa8500;
            --ink: #263b42;
            --paper: #f4f8f8;
        }

        body { background: var(--paper); color: var(--ink); min-height: 100vh; }
        .navbar { min-height: 66px; padding-top: .1rem; padding-bottom: .1rem; background: linear-gradient(110deg, #075b70 0%, #087d8e 56%, #127fa0 100%); border-bottom: 1px solid rgba(255,255,255,.22); box-shadow: 0 3px 14px rgba(2,45,63,.16); }
        .navbar-brand { display: inline-flex; align-items: center; color: var(--teal)!important; }
        .navbar .navbar-brand { color: #fff!important; }
        .navbar-logo { display: block; width: 112px; height: 58px; padding: 2px 5px; object-fit: contain; background: #fff; border-radius: .4rem; }
        .navbar .nav-link { display: inline-flex; align-items: center; gap: .4rem; padding: .85rem .62rem; color: #344c54; font-size: .9rem; font-weight: 500; white-space: nowrap; }
        .navbar .nav-link .bi { font-size: .88rem; color: #536d74; }
        .navbar .nav-link.active, .navbar .nav-link:hover, .navbar .nav-link.active .bi { color: var(--teal); }
        .navbar .nav-link.active { box-shadow: inset 0 -3px var(--teal); }
        .navbar .nav-link { color: rgba(255,255,255,.92); }
        .navbar .nav-link .bi { color: rgba(255,255,255,.8); }
        .navbar .nav-link.active, .navbar .nav-link:hover, .navbar .nav-link.active .bi, .navbar .nav-link:hover .bi { color: #fff; }
        .navbar .nav-link.active { box-shadow: inset 0 -3px rgba(255,255,255,.95); }
        .navbar-toggler { border: 0; box-shadow: none!important; }
        .navbar .navbar-toggler-icon { filter: brightness(0) invert(1); }
        .navbar .account-actions { gap: .5rem; }
        .navbar .account-name { max-width: 11rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .navbar .account-actions .btn-outline-secondary { color: #fff; border-color: rgba(255,255,255,.72); }
        .navbar .account-actions .btn-outline-secondary:hover { color: var(--teal-deep); background: #fff; border-color: #fff; }
        .navbar .account-actions .btn-primary { color: var(--teal-deep); background: #fff; border-color: #fff; }
        .navbar .account-actions .btn-primary:hover { color: var(--teal-dark); background: #e8f8fa; border-color: #e8f8fa; }
        .btn-primary { --bs-btn-bg: var(--teal); --bs-btn-border-color: var(--teal); --bs-btn-hover-bg: #096d7d; --bs-btn-hover-border-color: #096d7d; --bs-btn-active-bg: var(--teal-dark); }
        .btn-outline-primary { --bs-btn-color: var(--teal); --bs-btn-border-color: #89bac2; --bs-btn-hover-bg: var(--teal); --bs-btn-hover-border-color: var(--teal); }
        .text-brand { color: var(--teal)!important; }
        .page-wrap { max-width: 1240px; }
        .card { border: 1px solid #e5edef; box-shadow: 0 8px 24px rgba(22,61,70,.07); border-radius: 1.25rem; }
        .card-header { background: #fff; border-bottom-color: #e7eff0; }
        .stat-card { border-left: 4px solid var(--teal); height: 100%; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--teal); }
        .form-label { font-weight: 600; }
        .badge-pending { background: #fff1c2; color: #765700; border: 1px solid #f0d77d; }
        .badge-confirmed { background: #dff3ea; color: #176644; }
        .badge-completed { background: #dceff3; color: #155a69; }
        .badge-cancelled { background: #fbe5e5; color: #9a3030; }
        .badge-no_show { background: #ece8f6; color: #59488b; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7,minmax(0,1fr)); gap: 3px; }
        .calendar-day { min-height: 74px; border: 1px solid #dce7e9; background: white; padding: .35rem; border-radius: .35rem; text-decoration: none; color: var(--ink); }
        .calendar-day:hover { background: #eff8f9; }
        .calendar-day.selected { border: 2px solid #008fa3; background: #effafb; }
        .calendar-day.outside { background: #f4f7f8; color: #aab8bc; }
        .calendar-badges { display: flex; flex-wrap: wrap; gap: .2rem; margin-top: .3rem; }
        .calendar-badges .badge { font-size: .59rem; white-space: normal; }
        .staff-workspace-page, .patient-workspace-page {
            background-color: #08768c;
            background-image:
                radial-gradient(ellipse at 17% 28%, rgba(5,73,100,.38) 0 15%, transparent 43%),
                radial-gradient(ellipse at 84% 15%, rgba(25,164,174,.48) 0 18%, transparent 48%),
                radial-gradient(ellipse at 59% 84%, rgba(15,91,142,.64) 0 21%, transparent 55%),
                linear-gradient(128deg, #087d8e 0%, #075873 48%, #127fa0 100%);
            background-attachment: fixed;
        }
        .staff-workspace-page main > .d-flex h1,
        .staff-workspace-page main > h1,
        .patient-workspace-page main > .d-flex h1,
        .patient-workspace-page main > h1 { color: #fff!important; }
        .staff-workspace-page main > .d-flex p,
        .staff-workspace-page main > p,
        .patient-workspace-page main > .d-flex p,
        .patient-workspace-page main > p { color: rgba(255,255,255,.9)!important; }
        .patient-workspace-page main > .row h1 { color: #fff!important; }
        .patient-workspace-page main > .row > div > p { color: rgba(255,255,255,.94)!important; }
        .staff-dashboard-page main { max-width: 1160px; padding-top: 2.35rem!important; }
        .staff-dashboard-page main > .d-flex:first-child .text-brand { color: #d8fbff!important; }
        .staff-dashboard-page main > .d-flex:first-child .pending-review-btn { color: #075b70!important; background: #fff; border: 2px solid #fff; box-shadow: 0 5px 16px rgba(2,45,63,.2); }
        .staff-dashboard-page main > .d-flex:first-child .pending-review-btn:hover { color: #06495b!important; background: #e9fbfd; border-color: #e9fbfd; }
        .staff-dashboard-page .dashboard-stat { min-height: 172px; padding: 1.55rem; border: 1px solid rgba(255,255,255,.75); border-radius: 1.25rem; background: rgba(255,255,255,.96); box-shadow: 0 8px 24px rgba(3,48,71,.1); }
        .staff-dashboard-page .dashboard-stat-icon { display: grid; width: 3.15rem; height: 3.15rem; margin-bottom: .85rem; place-items: center; border-radius: .85rem; color: var(--teal); background: var(--teal-soft); font-size: 1.35rem; }
        .staff-dashboard-page .dashboard-stat-label { color: #445e66; font-size: .9rem; }
        .staff-dashboard-page .dashboard-stat-value { margin-top: .15rem; color: var(--teal); font-size: 1.9rem; font-weight: 700; line-height: 1.15; }
        .staff-dashboard-page .quick-actions { padding: 1.5rem; border: 0; background: rgba(255,255,255,.97); }
        .staff-dashboard-page .quick-actions h2 { color: var(--teal); font-size: 1.3rem; font-weight: 700; }
        .staff-workspace-page .header-action-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: .6rem 1rem; border: 2px solid rgba(255,255,255,.95); border-radius: .65rem; color: var(--teal-deep); background: #fff; box-shadow: 0 4px 14px rgba(2,45,63,.18); font-weight: 700; text-decoration: none; }
        .staff-workspace-page .header-action-btn:hover { color: var(--teal-dark); background: #eaf9fb; border-color: #eaf9fb; }
        .patient-workspace-page .request-appointment-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: .6rem 1rem; border: 2px solid rgba(255,255,255,.95); border-radius: .65rem; color: var(--teal-deep)!important; background: #fff; box-shadow: 0 4px 14px rgba(2,45,63,.18); font-weight: 700; text-decoration: none; }
        .patient-workspace-page .request-appointment-btn:hover { color: var(--teal-dark)!important; background: #eaf9fb; border-color: #eaf9fb; }
        .staff-dashboard-page .quick-action { display: flex; align-items: center; gap: .65rem; min-height: 58px; padding: .8rem 1rem; border: 2px solid #9ecbd2; border-radius: 1rem; color: var(--teal-deep); background: #fff; box-shadow: 0 3px 8px rgba(5,73,91,.08); font-weight: 700; text-decoration: none; transition: background-color .16s ease, border-color .16s ease, transform .16s ease; }
        .staff-dashboard-page .quick-action:hover { color: var(--teal-dark); background: #eaf9fb; border-color: var(--teal); transform: translateY(-1px); }
        .staff-dashboard-page .dashboard-lists .card { overflow: hidden; }
        .patient-dashboard-page main { max-width: 1160px; padding-top: 2.35rem!important; }
        .patient-dashboard-page .patient-stat { min-height: 172px; padding: 1.55rem; border: 1px solid rgba(255,255,255,.75); border-radius: 1.25rem; background: rgba(255,255,255,.96); box-shadow: 0 8px 24px rgba(3,48,71,.1); }
        .patient-dashboard-page .patient-stat-icon { display: grid; width: 3.15rem; height: 3.15rem; margin-bottom: .85rem; place-items: center; border-radius: .85rem; color: var(--teal); background: var(--teal-soft); font-size: 1.35rem; }
        .patient-dashboard-page .patient-stat-label { color: #445e66; font-size: .9rem; }
        .patient-dashboard-page .patient-stat-value { margin-top: .15rem; color: var(--teal); font-size: 1.9rem; font-weight: 700; line-height: 1.15; }
        .patient-dashboard-page .quick-actions { padding: 1.5rem; border: 0; background: rgba(255,255,255,.97); }
        .patient-dashboard-page .quick-actions h2 { color: var(--teal); font-size: 1.3rem; font-weight: 700; }
        .patient-dashboard-page .quick-action { display: flex; align-items: center; gap: .65rem; min-height: 58px; padding: .8rem 1rem; border: 1px solid #d8e5e7; border-radius: 1rem; color: var(--teal-deep); font-weight: 600; text-decoration: none; transition: background-color .16s ease, transform .16s ease; }
        .patient-dashboard-page .quick-action:hover { background: #eefafb; transform: translateY(-1px); }
        .patient-dashboard-page .request-appointment-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: .6rem 1rem; border: 2px solid rgba(255,255,255,.95); border-radius: .65rem; color: var(--teal-deep)!important; background: #fff; box-shadow: 0 4px 14px rgba(2,45,63,.18); font-weight: 700; text-decoration: none; }
        .patient-dashboard-page .request-appointment-btn:hover { color: var(--teal-dark)!important; background: #eaf9fb; border-color: #eaf9fb; }
        .footer { color: #71858b; }
        @media(max-width:1199.98px) {
            .navbar .nav-link { padding: .65rem .45rem; }
            .navbar .nav-link.active { box-shadow: none; }
            .navbar .account-actions { padding: .5rem 0 1rem; }
        }
        @media(max-width:767px) {
            .navbar-logo { width: 104px; height: 54px; }
            .calendar-day { min-height: 58px; padding: .2rem; }
            .calendar-badges .badge { font-size: .52rem; }
            .staff-dashboard-page main { padding-top: 1.5rem!important; }
            .staff-dashboard-page .dashboard-stat { min-height: 148px; padding: 1rem; }
            .staff-dashboard-page .dashboard-stat-icon { width: 2.75rem; height: 2.75rem; margin-bottom: .65rem; }
            .staff-dashboard-page .quick-actions { padding: 1rem; }
            .patient-dashboard-page main { padding-top: 1.5rem!important; }
            .patient-dashboard-page .patient-stat { min-height: 148px; padding: 1rem; }
            .patient-dashboard-page .patient-stat-icon { width: 2.75rem; height: 2.75rem; margin-bottom: .65rem; }
            .patient-dashboard-page .quick-actions { padding: 1rem; }
        }
        @media print {
            .navbar, .screen-only, .footer { display: none!important; }
            body { background: #fff; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body @class(['staff-workspace-page' => request()->routeIs('staff.*'), 'patient-workspace-page' => request()->routeIs('patient.*'), 'staff-dashboard-page' => request()->routeIs('staff.dashboard'), 'patient-dashboard-page' => request()->routeIs('patient.dashboard')])>
<nav class="navbar navbar-expand-xxl sticky-top">
    <div class="container-fluid px-3 px-xl-4">
        <a class="navbar-brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="The Tooth Lounge home"><img class="navbar-logo" src="{{ asset('images/tooth-lounge-logo.png') }}" alt="The Tooth Lounge"></a>
        @auth
            @php($staff = auth()->user()->role === 'staff')
            @php($pendingAttention = $staff ? \App\Models\Appointment::where('status', 'pending')->count() : 0)
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="main-navigation">
                <ul class="navbar-nav ms-auto align-items-xl-center">
                    @if($staff)
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.patients*') ? 'active' : '' }}" href="{{ route('staff.patients') }}"><i class="bi bi-people-fill" aria-hidden="true"></i>Patients</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.appointments*') ? 'active' : '' }}" href="{{ route('staff.appointments') }}"><i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>Appointments @if($pendingAttention)<span class="badge rounded-pill badge-pending">{{ $pendingAttention }}</span>@endif</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.visits*') ? 'active' : '' }}" href="{{ route('staff.visits') }}"><i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i>Records</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.reports') ? 'active' : '' }}" href="{{ route('staff.reports') }}"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i>Reports</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.activity-log') ? 'active' : '' }}" href="{{ route('staff.activity-log') }}"><i class="bi bi-list-check" aria-hidden="true"></i>Activity log</a></li>
                        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle {{ request()->routeIs('staff.accounts') ? 'active' : '' }}" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">More</a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('staff.accounts') }}">Staff accounts</a></li></ul></li>
                    @else
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.dashboard') ? 'active' : '' }}" href="{{ route('patient.dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.appointments.request') ? 'active' : '' }}" href="{{ route('patient.appointments.request') }}"><i class="bi bi-calendar-plus" aria-hidden="true"></i>Request appointment</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.appointments') ? 'active' : '' }}" href="{{ route('patient.appointments') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i>Appointments</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.visits') ? 'active' : '' }}" href="{{ route('patient.visits') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i>Visit history</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.profile*') ? 'active' : '' }}" href="{{ route('patient.profile') }}"><i class="bi bi-person" aria-hidden="true"></i>Profile</a></li>
                    @endif
                </ul>
                <div class="account-actions d-flex align-items-center ms-xl-2">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('password.change') }}"><i class="bi bi-key-fill" aria-hidden="true"></i> Password</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('logout.confirm') }}"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout</a>
                </div>
            </div>
        @endauth
    </div>
</nav>
<main class="container-fluid page-wrap py-4 py-lg-5">
    @if(session('status'))<div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
    @if(session('created_patient_credentials'))<div class="alert alert-warning"><strong>Temporary patient credentials (shown once)</strong><br>Email: {{ session('created_patient_credentials.email') }} · Temporary password: {{ session('created_patient_credentials.temporary_password') }} · Patient number: {{ session('created_patient_credentials.patient_number') }}</div>@endif
    @if(session('created_staff_credentials'))<div class="alert alert-warning"><strong>Temporary staff credentials (shown once)</strong><br>{{ session('created_staff_credentials.email') }} · {{ session('created_staff_credentials.temporary_password') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please review:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="footer text-center py-4 small">The Tooth Lounge · Clinic appointments and patient records</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/clinic.js') }}" defer></script>
@stack('scripts')
</body>
</html>
