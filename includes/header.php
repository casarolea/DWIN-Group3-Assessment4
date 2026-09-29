<?php 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// get the current logged-in user
$current_user = cookbook_current_user();

// check if user is logged in
$logged_in = ($current_user !== false && $current_user !== null);

// check the user role - ONLY FOR ADMIN
$is_admin = $logged_in && ($current_user['role'] ?? '') === 'admin';

// get current page for active page check
$current_page = basename($_SERVER['PHP_SELF']);

// title page
$page_title = isset($page_title) ? $page_title: 'CookBook';

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <!-- local css -->
    <link rel="stylesheet" href="../styles/styles.css">
    <!-- end of local csss -->

    <!-- function for extra css in pages -->
    <?php if (isset($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php endforeach; ?>
    <?php endif; ?>

    <title>CookBook</title>
    <link rel="icon" type="image/x-icon" href="../images/cblogo2.png">

  </head>


<body>

<!-- This is the start of the nav bar -->
 
<!-- THIS IS THE START OF THE NAV BAR (MENU SECTION) -->
<nav class="navbar navbar-expand-lg navbar-light">
  <!-- Logo -->
  <a class="gochihand nav-name" href="index.php">
    <img class="logo" src="../images/cblogo1.png" alt="CookBook Logo" title="CookBook logo">
    CookBook
  </a>

  <!-- Mobile Button -->
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">

      <!-- Left Side of Navbar -->
        <ul class="navbar-nav ralewayextrabold nav-text">
          <li class="nav-item">
            <a class="nav-link nav-text4" href="recipe_page.php">RECIPES</a>
          </li>
        </ul>

      <!-- Right Side of Navbar -->
       <div class="ml-auto d-flex align-items-center">
        
        <!-- For Search -->
        <form class="form-inline">
          <input class="form-control navbar-search" type="search" placeholder="SEARCH">
        </form>
        <!-- For Login -->

        <!-- admin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link nav-text4 login-link" href="../admin/dashboard.php">ADMIN</a>
        <?php endif; ?>

         <!-- logged in user -->
        <?php if (cookbook_current_user()): ?>
            <a class="nav-link nav-text4 login-link" href="../pages/myaccount.php">ACCOUNT</a>

            <!-- logout -->
          <form class="form-inline" method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <button class="register-button" type="submit">LOG OUT</button>
          </form>

          <!-- if user is not logged in -->
        <?php else: ?>
          <a class="nav-link nav-text4 login-link" href="login.php">LOGIN</a>
          <a class="register-button" href="register.php">REGISTER</a>
        <?php endif; ?>
      </div>
      </div>
</nav>
<!-- THIS IS THE END OF THE NAV BAR (MENU SECTION) -->