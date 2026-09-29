<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = cookbook_db();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (!cookbook_csrf_is_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || strlen($username) > 80) {
        $error = 'Enter a username of up to 80 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Use a password with at least 8 characters.';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
            );
            $statement->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $pdo->lastInsertId(),
                'username' => $username,
                'email' => $email,
                'role' => 'user',
            ];
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'An account with that email already exists.';
            } else {
                throw $exception;
            }
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
  <title>Create account | CookBook</title>
</head>
<body>
<?php include __DIR__ . '/../includes/site_navbar.php'; ?>
<section class="first-block">
<main class="container my-5 auth-panel" style="max-width: 520px;">
  <h1 class="auth-page-title mb-4">Create account</h1>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <form method="post" action="register.php" class="auth-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-group">
      <label for="username">Username</label>
      <input id="username" name="username" class="form-control" autocomplete="username" required maxlength="80" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" class="form-control" autocomplete="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" class="form-control" autocomplete="new-password" required minlength="8">
    </div>
    <div class="form-group">
      <label for="password_confirmation">Confirm password</label>
      <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required minlength="8">
    </div>
    <button class="btn cookbook-button" type="submit">Create account</button>
  </form>
  <p class="mt-3">Already registered? <a class="cookbook-link" href="login.php">Log in</a></p>
  <a class="cookbook-link" href="index.php">Return to CookBook</a>
</main>
 </section>
<?php include __DIR__ . '/../includes/site_footer.php'; ?>
</body>
</html>