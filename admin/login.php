<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (verifyAdminCredentials($username, $password)) {
        $_SESSION['ajftfp_admin_logged_in'] = true;
        $_SESSION['ajftfp_admin_user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid administrative username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal Login | AJFTFP</title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --aj-navy-primary: #0a2540;
      --aj-gold-primary: #c59b27;
      --aj-bg-light: #f8fafc;
    }
    body {
      background: linear-gradient(135deg, #0a2540 0%, #153e6b 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Plus Jakarta Sans', sans-serif;
      padding: 20px;
    }
    .login-card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.3);
      max-width: 420px;
      width: 100%;
      overflow: hidden;
    }
    .login-header {
      background: #ffffff;
      padding: 30px 25px 15px;
      text-align: center;
      border-bottom: 1px solid #eef2f6;
    }
    .login-header img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid var(--aj-gold-primary);
      margin-bottom: 12px;
    }
    .btn-login {
      background: #0a2540;
      color: #ffffff;
      font-weight: 600;
      border: none;
    }
    .btn-login:hover {
      background: #123762;
      color: #ffffff;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <img src="../assets/img/logo.jpg" alt="AJFTFP Emblem">
      <h4 class="fw-bold mb-0 text-navy" style="color: #0a2540;">AJFTFP Editorial Admin</h4>
      <p class="small text-muted mb-0">Federal University Dutse</p>
    </div>

    <div class="p-4">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="mb-3">
          <label class="form-label small fw-bold text-secondary">Admin Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold text-secondary">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" class="btn btn-login w-100 py-2 mb-3">
          <i class="bi bi-box-arrow-in-right me-1"></i> Access Editorial Desk
        </button>

        <div class="text-center">
          <a href="../index.html" class="small text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Return to Main Public Website
          </a>
        </div>
      </form>
    </div>
  </div>

</body>
</html>
