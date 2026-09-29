<?php
require_once __DIR__ . '/../includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cookbook_csrf_is_valid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $statement = cookbook_db()->prepare(
            'SELECT user_id, username, email, password_hash, role FROM users WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            header('Location: ' . ($user['role'] === 'admin' ? 'create-recipe.php' : 'index.php'));
            exit;
        }

        $error = 'Email or password is incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../styles/styles.css">
  <link rel="icon" type="image/x-icon" href="../images/cblogo2.png">
  <title>Log in | CookBook</title>
</head>
<body>
<?php include __DIR__ . '/../includes/site_navbar.php'; ?>
<section class="first-block">
<main class="container my-5 auth-panel" style="max-width: 520px;">
  <h1 class="auth-page-title mb-4">Log in</h1>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <form method="post" action="login.php" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" class="form-control" autocomplete="username" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
    </div>
    <button class="btn cookbook-button" type="submit">Log in</button>
  </form>
  <p class="mt-3"><a class="cookbook-link" href="forgot_password.php">Forgot your password?</a></p>
  <p>New here? <a class="cookbook-link" href="register.php">Create an account</a></p>
  <a class="cookbook-link" href="index.php">Return to CookBook</a>
</main>
 </section>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
</body>
</html>
