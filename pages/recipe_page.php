  <?php
  require_once __DIR__ . '/../includes/auth.php';
  cookbook_require_login();
  $pdo = cookbook_db();

  $searchTerm = trim($_GET['search'] ?? '');
  $selectedCategory = trim($_GET['category'] ?? '');
  $selectedIngredient = trim($_GET['ingredient'] ?? '');

  $categories = $pdo->query('SELECT category_name FROM categories ORDER BY category_name')->fetchAll();
  $conditions = ['r.moderation_status = :moderation_status'];
  $parameters = ['moderation_status' => 'approved'];
  $hasBrowseFilter = $searchTerm !== '' || $selectedCategory !== '' || $selectedIngredient !== '';

  if ($searchTerm !== '') {
    $conditions[] = '(r.title LIKE :title_search OR r.ingredients LIKE :ingredient_search OR EXISTS (
      SELECT 1 FROM recipe_categories search_rc
      JOIN categories search_c ON search_c.category_id = search_rc.category_id
      WHERE search_rc.recipe_id = r.recipe_id AND search_c.category_name LIKE :category_search
    ))';
    $search = '%' . $searchTerm . '%';
    $parameters['title_search'] = $search;
    $parameters['ingredient_search'] = $search;
    $parameters['category_search'] = $search;
  }

  if ($selectedCategory !== '') {
    $conditions[] = 'EXISTS (
      SELECT 1 FROM recipe_categories category_rc
      JOIN categories category_c ON category_c.category_id = category_rc.category_id
      WHERE category_rc.recipe_id = r.recipe_id AND category_c.category_name = :category
    )';
    $parameters['category'] = $selectedCategory;
  }

  if ($selectedIngredient !== '') {
    $conditions[] = 'r.ingredients LIKE :ingredient';
    $parameters['ingredient'] = '%' . $selectedIngredient . '%';
  }

  $recipeQuery = '
    SELECT r.recipe_id, r.title, r.prep_time_minutes, r.cook_time_minutes,
        r.calories_per_serving,
        GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ", ") AS categories
    FROM recipes r
    LEFT JOIN recipe_categories rc ON rc.recipe_id = r.recipe_id
    LEFT JOIN categories c ON c.category_id = rc.category_id
  ';

  if ($hasBrowseFilter) {
    $recipeQuery .= ' WHERE ' . implode(' AND ', $conditions);
  }

  $recipeQuery .= ' GROUP BY r.recipe_id ORDER BY r.title';
  $recipes = [];
  if ($hasBrowseFilter) {
    $recipeStatement = $pdo->prepare($recipeQuery);
    $recipeStatement->execute($parameters);
    $recipes = $recipeStatement->fetchAll();
  }

  $recipeImages = [];
  foreach (glob(__DIR__ . '/../images/Recipe images/*') ?: [] as $imagePath) {
    $imageName = strtolower(pathinfo($imagePath, PATHINFO_FILENAME));
    $recipeImages[$imageName] = '../images/Recipe%20images/' . rawurlencode(basename($imagePath));
  }

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
        <link rel="stylesheet" href="../styles/recipe_page.css">
      <!-- end of local csss -->

    <title>CookBook</title>
    <link rel="icon" type="image/x-icon" href="../images/cblogo2.png">

  </head>

<body>

<!-- This is the start of the nav bar -->
 
<!-- THIS IS THE START OF THE NAV BAR (MENU SECTION) -->
<nav class="navbar navbar-expand-lg navbar-light">
  <!-- Logo -->
  <a class="gochihand nav-name" href="../pages/index.php">
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
            <a class="nav-link nav-text4" href="../pages/recipe_page.php">RECIPES</a>
          </li>
        </ul>

      <!-- Right Side of Navbar -->
       <div class="ml-auto d-flex align-items-center">
        
        <!-- For Search -->
        <form class="form-inline" action="recipe_page.php" method="get">
          <input class="form-control navbar-search" type="search" name="search" placeholder="SEARCH" aria-label="Search recipes">
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

  <!-- This is the start of the first-block -->
  <section class = "first-block">

  <div class="container">
      <div class="row justify-content-center">
          <h1 id="recipeTitle" class="text-center">RECIPES</h1>
              <div class="col-12">
                      <nav class="navbar bg-transparent">
                          <form class="form-inline w-100">
                              <input id="searchRecipe" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" class="form-control w-100" type="search" placeholder="Search recipes, ingredients, or categories" aria-label="Search recipes" onchange="this.form.submit()">
                          </form>
                      </nav>

                      <div class="d-flex justify-content-center">
                          <div class="btn-group mx-3">
                              <button type="button" class="btn recipe-dropdown dropdown-toggle"
                                      data-toggle="dropdown">
                                  Ingredient
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="recipe_page.php">All ingredients</a>
                                <?php foreach (['Chicken', 'Beef', 'Pork'] as $ingredient): ?>
                                  <a class="dropdown-item" href="?ingredient=<?php echo rawurlencode($ingredient); ?>"><?php echo htmlspecialchars($ingredient, ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php endforeach; ?>
                              </div>
                          </div>

                          <div class="btn-group mx-3">
                              <button type="button" class="btn recipe-dropdown dropdown-toggle"
                                      data-toggle="dropdown">
                                Category
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="recipe_page.php">All categories</a>
                                <?php foreach ($categories as $category): ?>
                                  <a class="dropdown-item" href="?category=<?php echo rawurlencode($category['category_name']); ?>"><?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php endforeach; ?>
                              </div>
                          </div>
                      </div>
              </div>
      </div>
  </div>

  </section>
  <!-- This is the end of the first-block -->

  <!-- This is the start of the second-block -->
  <!-- This is the start of the second-block -->
  <?php if ($searchTerm === '' && $selectedCategory === '' && $selectedIngredient === ''): ?>
  <section class="second-block">

      <h2 class="category-title text-center">BROWSE RECIPE CATEGORIES</h2>

      <div class="container">
          <div class="row justify-content-center">

              <div class="col-md-4 text-center category-card">
                  <a href="?category=Breakfast">
                    <img class="category-img" src="../images/breakfast_recipe_page.jpg" alt="Breakfast">
                  </a>
                  <h3 class="meal-name">Breakfast</h3>
              </div>

              <div class="col-md-4 text-center category-card">
                  <a href="?category=Lunch">
                    <img class="category-img" src="../images/lunch_recipe_page.jpg" alt="Lunch">
                  </a>
                  <h3 class="meal-name">Lunch</h3>
              </div>

              <div class="col-md-4 text-center category-card">
                  <a href="?category=Dinner">
                    <img class="category-img" src="../images/dinner_recipe_page.jpg" alt="Dinner">
                  </a>
                  <h3 class="meal-name">Dinner</h3>
              </div>

              <div class="col-md-4 text-center category-card">
                  <a href="?category=Dessert">
                    <img class="category-img" src="../images/dessert_recipe_page.jpg" alt="Dessert">
                  </a>
                  <h3 class="meal-name">Dessert</h3>
              </div>

              <div class="col-md-4 text-center category-card">
                  <a href="?category=Snack">
                    <img class="category-img" src="../images/snack_recipe_page.jpg" alt="Snack">
                  </a>
                  <h3 class="meal-name">Snack</h3>
              </div>

          </div>
      </div>

  </section>
  <?php endif; ?>
  <!-- This is the end of the second-block -->

  <!-- This is the start of the third-block -->
  <?php if ($hasBrowseFilter): ?>
  <section class = "third-block">
    <div class="container py-4" id="searchResultsContainer">
      <h2 class="category-title text-center">
        <?php
        if ($searchTerm !== '') {
          $resultsTitle = 'Search results for "' . $searchTerm . '"';
        } elseif ($selectedCategory !== '') {
          $resultsTitle = $selectedCategory . ' recipes';
        } elseif ($selectedIngredient !== '') {
          $resultsTitle = $selectedIngredient . ' recipes';
        } else {
          $resultsTitle = 'RECIPES';
        }
        echo htmlspecialchars($resultsTitle, ENT_QUOTES, 'UTF-8');
        ?>
      </h2>
      <div class="row">
        <?php if (!$recipes): ?>
          <p class="col-12 text-center">No recipes found. Try another search or filter.</p>
        <?php endif; ?>
        <?php foreach ($recipes as $recipe): ?>
          <div class="col-md-4 mb-4">
            <article class="card h-100">
              <?php if (isset($recipeImages[strtolower($recipe['title'])])): ?>
                <img class="card-img-top recipe-card-image" src="<?php echo htmlspecialchars($recipeImages[strtolower($recipe['title'])], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php endif; ?>
              <div class="card-body">
                <h3 class="h5 card-title"><?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="card-text text-muted"><?php echo htmlspecialchars($recipe['categories'] ?: 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="card-text">Prep: <?php echo (int) ($recipe['prep_time_minutes'] ?? 0); ?> min · Cook: <?php echo (int) ($recipe['cook_time_minutes'] ?? 0); ?> min</p>
                <a class="btn btn-outline-secondary" href="recipe.php?id=<?php echo (int) $recipe['recipe_id']; ?>">View recipe</a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
  <!-- This is the end of the third-block -->

<!-- Optional JavaScript -->
      <!-- jQuery first, then Popper.js, then Bootstrap JS -->
      <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

        <!-- start of footer -->

  <!-- Footer -->
  <footer class="text-center text-lg-start bg-body-tertiary text-muted footer">

  <!-- Section: Links  -->
  <section class="">
    <div class="container text-center mt-5">
      <!-- Grid row -->
      <div class="row mt-3">
        <!-- Grid column -->
        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
          <!-- Content -->
          <h6 class="fw-bold mb-4">
          <a class="fas fa-gem me-3 gochihand footer-text1" href="index.php"><img class="logo" src="../images/cblogo2.png" alt="CookBook Logo" title="CookBook logo">CookBook</a>
          </h6>
          <p class="footer-text2 ralewaybold">
            Improve the cooking experience.
          </p>
          <hr>
          <p>
            <a href="#" class="footer-links ralewaybold footer-text2">Instagram</a>
            <a href="#" class="footer-links ralewaybold footer-text2">Facebook</a>
            <a href="#" class="footer-links ralewaybold footer-text2">Tiktok</a>
            <a href="#" class="footer-links ralewaybold footer-text2">YouTube</a>
          </p>
        </div>
        <!-- Grid column -->


        <!-- Grid column -->
        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
          <!-- Links -->
          <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">
            Quick links
          </h6>
          <p>
            <a href="index.php" class="footer-links ralewaybold">Home</a>
          </p>
          <p>
            <a href="recipe_page.php" class="footer-links ralewaybold">Recipes</a>
          </p>
          <p>
            <a href="privacy.php" class="footer-links ralewaybold">Privacy Policy</a>
          </p>
        </div>
        <!-- Grid column -->

        <!-- Grid column -->
        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
          <!-- Links -->
          <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">GROUP INFORMATION</h6>
          <p>
            <a class="footer-links ralewaybold footer-text2">Joewiey Franzine Ibanez - K231663</a>
          </p>
          <p>
            <a class="footer-links ralewaybold footer-text2">Sheirina Glee Nadera - K240664</a>
          </p>
          <p>
            <a class="footer-links ralewaybold footer-text2">Regil Maharjan - K240722</a>
          </p>
          <p>
            <a class="footer-links ralewaybold footer-text2">Chauncey Ariel Nieto - K240938</a>
          </p>
          <p>
            <a class="footer-links ralewaybold footer-text2">Cassandra Noeribelle Dejucos - K240945</a>
          </p>
        </div>
        <!-- Grid column -->
      </div>
      <!-- Grid row -->
    </div>
  </section>
  <!-- Section: Links  -->

  <!-- Copyright -->
  <div class="text-center p-4">
  <p class ="ralewaybold footer-text2">This website was created for the final assessment for DWIN309 at Kent Institute Australia - Trimester 2, 2026</p>
    <p class ="ralewaybold footer-text2">&copy; CookBook 2026. All rights reserved.</p>
  </div>
  <!-- Copyright -->
  </footer>
  <!-- Footer -->
  <!-- end of footer -->

  </body>
  </html>
