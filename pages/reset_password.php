<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = cookbook_db();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;
$resetRecord = false;

if ($token !== '') {
    $tokenHash = hash('sha256', $token);
    $statement = $pdo->prepare(
        'SELECT reset_id, user_id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW()'
    );
    $statement->execute([$tokenHash]);
    $resetRecord = $statement->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cookbook_csrf_is_valid()) {
        $error = 'Your session expired. Please reload the page and try again.';
    } elseif (!$resetRecord) {
        $error = 'This password reset link is invalid or has expired.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Use a password with at least 8 characters.';
        } elseif ($password !== $passwordConfirmation) {
            $error = 'The passwords do not match.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $resetRecord['user_id']]);

            $delete = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
            $delete->execute([$resetRecord['user_id']]);
            $success = true;
        }
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
  <title>Reset password | CookBook</title>
</head>
<body>
<?php include __DIR__ . '/../includes/site_navbar.php'; ?>
<section class="first-block">
<main class="container my-5 auth-panel" style="max-width: 520px;">
  <h1 class="auth-page-title mb-4">Reset password</h1>

  <?php if ($success): ?>
    <div class="alert alert-success" role="status">Your password has been reset successfully.</div>
    <a class="btn cookbook-button" href="login.php">Log in</a>
  <?php elseif (!$resetRecord): ?>
    <div class="alert alert-danger" role="alert">This password reset link is invalid or has expired.</div>
    <a class="cookbook-link" href="forgot_password.php">Request a new reset link</a>
  <?php else: ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="reset_password.php" class="auth-form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="form-group">
        <label for="password">New password</label>
        <input id="password" name="password" type="password" class="form-control" minlength="8" required>
      </div>
      <div class="form-group">
        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" required>
      </div>
      <button class="btn cookbook-button" type="submit">Reset password</button>
    </form>
  <?php endif; ?>
</main>
</section>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
</body>
</html>
