<?php
session_start();
require_once __DIR__ . '/db.php';

$err = '';
$username_prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic CSRF protection
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $err = 'Invalid request. Please reload the page and try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        $username_prefill = $username;

        if ($username === '' || $password === '') {
            $err = "Missing username or password.";
        } else {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();

            if ($row && password_verify($password, $row['password_hash'])) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $row['id'];
                // remove CSRF token after successful auth
                unset($_SESSION['csrf_token']);
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $err = "Invalid credentials.";
            }
        }
    }
}

// ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login - CarServ</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Favicon -->
  <link href="img/favicon.ico" rel="icon">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700&family=Ubuntu:wght@400;500&display=swap" rel="stylesheet">

  <!-- Icon fonts -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Bootstrap (matching the site) -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">

  <style>
    /* -- page baseline -- */
    html,body { height:100%; }
    body {
      margin: 0;
      font-family: 'Ubuntu', sans-serif;
      background: #f2f4f7;
    }

    /* full-screen background image (soft visible behind dim) */
    .bg-wrap {
      position: fixed;
      inset: 0;
      z-index: 0;
      background-image: url('img/carousel-bg-1.jpg');
      background-size: cover;
      background-position: center;
      filter: saturate(.9) blur(1px);
      transform: scale(1.02);
    }

    /* dim layer to emphasize popup */
    .page-dim {
      position: fixed;
      inset: 0;
      background: rgba(8,10,12,0.46);
      z-index: 1;
    }

    /* centered popup card */
    .login-modal {
      --card-w: 860px;
      position: fixed;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 2;
      width: min(calc(var(--card-w)), 94%);
      max-width: var(--card-w);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(6, 12, 30, 0.35);
      background: linear-gradient(180deg,#ffffff,#fbfbfb);
      display: flex;
      align-items: stretch;
      min-height: 420px;
      max-height: calc(100vh - 80px);
    }

    /* left image panel inside the card */
    .login-left {
      width: 40%;
      min-width: 240px;
      background-image: url('img/login-side.jpg'), url('img/carousel-bg-1.jpg');
      /* prefer login-side.jpg; fallback to carousel-bg-1.jpg if not present */
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      display: none; /* hidden on small screens */
    }

    /* right form area */
    .login-right {
      width: 60%;
      padding: 34px 36px;
      overflow: auto;
    }

    /* responsive behaviour */
    @media (min-width: 768px) {
      .login-left { display: block; }
    }
    @media (max-width: 767.98px) {
      .login-modal { flex-direction: column; max-height: none; position: static; transform: none; margin: 6vh auto; width: 92%; }
      .login-left { display: none; width: auto; min-height: 0; }
      .login-right { width: 100%; padding: 22px; }
      .page-dim { position: fixed; } /* keep dim on mobile too */
    }

    /* small style touches to match site */
    .brand {
      font-family: 'Barlow', sans-serif;
      font-weight: 700;
      color: #0b2a4a;
    }

    .form-control:focus { box-shadow: none; border-color: #d21c23; }
    .input-icon { width: 46px; justify-content:center; border-right: none; }
    .btn-primary { background-color: #d21c23; border-color: #d21c23; }
    .btn-primary:hover { background-color: #b01619; border-color: #b01619; }
    .small-muted { color: #6c757d; font-size: .95rem; }

    /* ensure inner scrolling for very small screens */
    .login-right { -webkit-overflow-scrolling: touch; }

    /* maintain visible rounded corners on left image and right content */
    .login-left, .login-right { display: block; }
  </style>
</head>
<body>

  <!-- Background -->
  <div class="bg-wrap" aria-hidden="true"></div>

  <!-- dim overlay -->
  <div class="page-dim" aria-hidden="true"></div>

  <!-- Centered login modal -->
  <div class="login-modal" role="dialog" aria-labelledby="loginTitle" aria-modal="true">
    <div class="login-left" aria-hidden="true"></div>

    <div class="login-right">
      <div class="mb-3 text-center">
        <a href="../index.html" class="text-decoration-none brand">
          <h3 class="m-0"><i class="fa fa-car text-danger me-2"></i>CarServ Admin</h3>
        </a>
        <p class="small-muted mb-0">Sign in to manage bookings</p>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo htmlspecialchars($err); ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="mb-3">
          <label class="form-label small">Username</label>
          <div class="input-group">
            <span class="input-group-text bg-white input-icon"><i class="fa fa-user text-danger"></i></span>
            <input
              name="username"
              type="text"
              class="form-control"
              required
              maxlength="100"
              placeholder="admin"
              value="<?php echo htmlspecialchars($username_prefill); ?>"
              autocomplete="username"
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-white input-icon"><i class="fa fa-lock text-danger"></i></span>
            <input
              name="password"
              type="password"
              class="form-control"
              required
              maxlength="255"
              placeholder="••••••••"
              autocomplete="current-password"
            >
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
            <label class="form-check-label small" for="rememberMe">Remember me</label>
          </div>
          <a href="#" class="small text-decoration-none">Forgot password?</a>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary py-2">
            <i class="fa fa-sign-in-alt me-2"></i> SIGN IN
          </button>
        </div>

        <div class="text-center mt-2 small-muted">
          You will be redirected to the admin dashboard after successful login.
        </div>

        <div class="mt-4 text-center small">
          <a href="../index.html" class="text-decoration-none"><i class="fa fa-arrow-left me-1"></i> Back to site</a>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript Libraries (matching main site) -->
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- small enhancement to prevent double submits -->
  <script>
    (function () {
      const form = document.querySelector('form');
      if (!form) return;
      form.addEventListener('submit', function () {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Signing in...';
        }
      }, { once: true });
    })();
  </script>
</body>
</html>