<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = new PDO(
  'mysql:host=localhost;dbname=cookbook;charset=utf8mb4',
  'root',
  '',
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]
);

$searchTerm = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');
$selectedIngredient = trim($_GET['ingredient'] ?? '');

$categories = $pdo->query('SELECT category_name FROM categories ORDER BY category_name')->fetchAll();
$conditions = [];
$parameters = [];

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

if ($conditions) {
  $recipeQuery .= ' WHERE ' . implode(' AND ', $conditions);
}

$recipeQuery .= ' GROUP BY r.recipe_id ORDER BY r.title';
$recipes = [];
if ($conditions) {
  $recipeStatement = $pdo->prepare($recipeQuery);
  $recipeStatement->execute($parameters);
  $recipes = $recipeStatement->fetchAll();
}

$recipeImages = [];
foreach (glob(__DIR__ . '/../images/Recipe images/*') ?: [] as $imagePath) {
  $imageName = strtolower(pathinfo($imagePath, PATHINFO_FILENAME));
  $recipeImages[$imageName] = '../images/Recipe%20images/' . rawurlencode(basename($imagePath));
}
?>

<!-- header -->
<?php
$page_title = "Recipes";
$extra_css = ["../styles/recipe_page.css"];
include "../includes/header.php";
?>

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
<?php if ($conditions): ?>
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

<!-- footer -->
<?php include "../includes/footer.php"; ?>
