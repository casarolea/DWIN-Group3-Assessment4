<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = cookbook_db();

$message = '';
$resetLink = '';
$requestHost = strtolower(preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
  && in_array($requestHost, ['localhost', '127.0.0.1'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cookbook_csrf_is_valid()) {
        $message = 'Your session expired. Please reload the page and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';
        } else {
            $statement = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $statement->execute([$email]);
            $user = $statement->fetch();

            if ($user && $isLocalRequest) {
                $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$user['user_id']]);

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);

                $insert = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $insert->execute([$user['user_id'], $tokenHash, $expiresAt]);

                $resetLink = 'reset_password.php?token=' . urlencode($token);
            }

            $message = 'If an account exists for that email, a password reset request has been created.';
        }
    }
}
?>

<!-- header -->
<?php
$page_title = "Forgot Password";
include "../includes/header.php";
?>

<section class="first-block">
<main class="container my-5 auth-panel" style="max-width: 520px;">
  <h1 class="auth-page-title mb-4">Forgot password</h1>

  <?php if ($message !== ''): ?>
    <div class="alert alert-info" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <form method="post" action="forgot_password.php" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <button class="btn cookbook-button" type="submit">Request password reset</button>
  </form>

  <?php if ($resetLink !== '' && $isLocalRequest): ?>
    <div class="alert alert-warning mt-4" role="alert">
      <strong>Local testing only:</strong> email is not configured in XAMPP, so use this reset link:<br>
      <a href="<?php echo htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'); ?>">Reset my password</a>
    </div>
  <?php endif; ?>

  <p class="mt-3"><a class="cookbook-link" href="login.php">Back to login</a></p>
</main>
</section>

<!-- footer -->
<?php include "../includes/footer.php"; ?>
