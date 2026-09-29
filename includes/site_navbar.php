<?php
require_once __DIR__ . '/auth.php';
$siteCurrentUser = cookbook_current_user();
?>
<nav class="navbar navbar-expand-lg navbar-light">
  <a class="gochihand nav-name" href="index.php">
    <img class="logo" src="../images/cblogo1.png" alt="CookBook Logo" title="CookBook logo">
    CookBook
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ralewayextrabold nav-text">
      <li class="nav-item"><a class="nav-link nav-text4" href="recipe_page.php">RECIPES</a></li>
    </ul>
    <div class="ml-auto d-flex align-items-center">
      <form class="form-inline" action="recipe_page.php" method="get">
        <input class="form-control navbar-search" type="search" name="search" placeholder="SEARCH" aria-label="Search recipes">
      </form>
      <?php if ($siteCurrentUser): ?>
        <a class="nav-link nav-text4 login-link" href="myaccount.php">ACCOUNT</a>
        <form class="form-inline" method="post" action="logout.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <button class="register-button" type="submit">LOG OUT</button>
        </form>
      <?php else: ?>
        <a class="nav-link nav-text4 login-link" href="login.php">LOGIN</a>
        <a class="register-button" href="register.php">REGISTER</a>
      <?php endif; ?>
    </div>
  </div>
</nav>