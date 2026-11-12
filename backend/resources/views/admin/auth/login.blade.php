<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Boarding House Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bh-ink: #1b2430; --bh-muted: #6b7688; --bh-line: #e6eaf2;
            --bh-brand: #2c516e; --bh-brand-2: #40739e;
            --bh-shadow: 0 1px 2px rgba(20,30,45,.05), 0 22px 48px -22px rgba(20,30,45,.35);
        }
        body {
            color: var(--bh-ink);
            -webkit-font-smoothing: antialiased;
            background:
                radial-gradient(900px 420px at 50% -12%, #dfe9f3 0%, rgba(223,233,243,0) 70%),
                #f4f6fb;
        }
        .login-card {
            width: 100%; max-width: 396px;
            background: #fff; border: 1px solid var(--bh-line);
            border-radius: 18px; box-shadow: var(--bh-shadow);
            padding: 30px 28px 26px;
        }
        .brand-mark {
            width: 46px; height: 46px; border-radius: 14px; margin: 0 auto 14px;
            display: grid; place-items: center; font-size: 1.35rem;
            background: #eaf0f6; color: var(--bh-brand);
        }
        .login-card h1 { font-size: 1.28rem; font-weight: 700; letter-spacing: -.02em; }
        .login-card .lede { font-size: .84rem; color: var(--bh-muted); margin-bottom: 22px; }
        .form-label { font-size: .82rem; font-weight: 600; color: var(--bh-muted); }
        .form-control { border-radius: 10px; border-color: #d8dfea; padding: .6rem .8rem; }
        .form-control:focus { border-color: var(--bh-brand-2); box-shadow: 0 0 0 3px rgba(64,115,158,.14); }
        .btn-login {
            --bs-btn-bg: var(--bh-brand); --bs-btn-border-color: var(--bh-brand);
            --bs-btn-hover-bg: #23445e; --bs-btn-hover-border-color: #23445e;
            --bs-btn-active-bg: #1d3a51; --bs-btn-active-border-color: #1d3a51;
            --bs-btn-color: #fff; --bs-btn-hover-color: #fff; --bs-btn-active-color: #fff;
            border-radius: 10px; font-weight: 600; padding: .6rem 1rem;
        }
        .alert { border: 0; border-radius: 12px; font-size: .85rem; }
        .foot-note { font-size: .74rem; color: var(--bh-muted); text-align: center; margin-top: 18px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 p-3">
    {{-- Demo account: admin@gmail.com (see the users table for the current password) --}}
    <div class="login-card">
        <h1 class="text-center mb-1">Boarding House Admin</h1>
        

        @if($errors->any())
            <div class="alert alert-danger d-flex align-items-start gap-2 py-2">
                <i class="bi bi-exclamation-circle mt-1"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
        </form>

        <p class="foot-note mb-0">Tenant accounts sign in from the mobile app.</p>
    </div>
</body>
</html>
