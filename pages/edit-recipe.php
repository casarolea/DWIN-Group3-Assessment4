<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/recipe_media.php';
cookbook_require_login();
$pdo = cookbook_db();
$currentUser = cookbook_current_user();
$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int) ($_POST['recipe_id'] ?? 0);

$recipeStatement = $pdo->prepare('SELECT * FROM recipes WHERE recipe_id = ?');
$recipeStatement->execute([$recipeId]);
$recipe = $recipeStatement->fetch();
if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}
if ($currentUser['role'] !== 'admin' && (int) $recipe['owner_id'] !== (int) $currentUser['id']) {
    http_response_code(403);
    exit('You can only edit your own recipes.');
}

$categoryStatement = $pdo->prepare(
    'SELECT category_id FROM recipe_categories WHERE recipe_id = ? LIMIT 1'
);
$categoryStatement->execute([$recipeId]);
$selectedCategory = (int) $categoryStatement->fetchColumn();
$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$mediaStatement = $pdo->prepare('SELECT media_path, media_type FROM recipe_media WHERE recipe_id = ? ORDER BY media_id');
$mediaStatement->execute([$recipeId]);
$mediaItems = $mediaStatement->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $prepTime = max(0, (int) ($_POST['prep_time_minutes'] ?? 0));
    $cookTime = max(0, (int) ($_POST['cook_time_minutes'] ?? 0));
    $servings = max(0, (int) ($_POST['servings'] ?? 0));
    $calories = max(0, (int) ($_POST['calories_per_serving'] ?? 0));

    if (!cookbook_csrf_is_valid()) {
        $error = 'Your session expired. Please reload and try again.';
    } elseif ($title === '' || $ingredients === '' || $instructions === '' || $categoryId <= 0) {
        $error = 'Title, category, ingredients, and instructions are required.';
    } elseif (cookbook_reject_external_links($title . "\n" . $ingredients . "\n" . $instructions)) {
      $error = 'Recipe titles, ingredients, and instructions cannot contain external links.';
    } else {
        try {
            $uploadedMedia = cookbook_validate_recipe_media($_FILES['media'] ?? null);
            $pdo->beginTransaction();
        $canPublish = $currentUser['role'] === 'admin';
        $nextStatus = $canPublish ? 'approved' : 'pending';
        $pendingEvent = $recipe['moderation_status'] === 'pending'
          ? $recipe['pending_event']
          : 'updated';
            $update = $pdo->prepare(
                'UPDATE recipes SET title = ?, prep_time_minutes = ?, cook_time_minutes = ?, servings = ?,
           calories_per_serving = ?, ingredients = ?, instructions = ?, moderation_status = ?, pending_event = ?
           WHERE recipe_id = ?'
            );
        $update->execute([$title, $prepTime, $cookTime, $servings, $calories, $ingredients, $instructions, $nextStatus, $pendingEvent, $recipeId]);
            $pdo->prepare('DELETE FROM recipe_categories WHERE recipe_id = ?')->execute([$recipeId]);
            $pdo->prepare('INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)')->execute([$recipeId, $categoryId]);
            cookbook_store_recipe_media($pdo, $recipeId, $uploadedMedia);
            if ($canPublish) {
              $activityEvent = $pendingEvent === 'created' ? 'recipe_created' : 'recipe_updated';
              cookbook_record_recipe_activity($pdo, $currentUser, $activityEvent, $recipeId, $title);
            }
            $pdo->commit();
            header('Location: myaccount.php?tab=recipes&status=' . ($canPublish ? 'recipe-updated' : 'review-pending'));
            exit;
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
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
  <title>Edit Recipe | CookBook</title>
</head>
<body>
<main class="container my-5" style="max-width: 850px;">
  <h1>Edit Recipe</h1>
  <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <h2 class="h5">Current media</h2>
  <div class="row mb-4">
    <?php foreach ($mediaItems as $item): ?>
      <div class="col-md-4 mb-3">
        <?php if ($item['media_type'] === 'video'): ?>
          <video controls class="w-100"><source src="../images/<?php echo rawurlencode(dirname($item['media_path'])) . '/' . rawurlencode(basename($item['media_path'])); ?>"></video>
        <?php else: ?>
          <img class="img-fluid" src="../images/<?php echo rawurlencode(dirname($item['media_path'])) . '/' . rawurlencode(basename($item['media_path'])); ?>" alt="Recipe media">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="recipe_id" value="<?php echo (int) $recipeId; ?>">
    <div class="form-group"><label for="title">Title</label><input id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? $recipe['title'], ENT_QUOTES, 'UTF-8'); ?>"></div>
    <div class="form-group"><label for="category_id">Category</label><select id="category_id" name="category_id" class="form-control" required><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['category_id']; ?>" <?php echo (int) ($_POST['category_id'] ?? $selectedCategory) === (int) $category['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
    <div class="form-row">
      <div class="form-group col-md-3"><label for="prep_time_minutes">Prep minutes</label><input id="prep_time_minutes" name="prep_time_minutes" type="number" min="0" class="form-control" value="<?php echo (int) ($_POST['prep_time_minutes'] ?? $recipe['prep_time_minutes']); ?>"></div>
      <div class="form-group col-md-3"><label for="cook_time_minutes">Cook minutes</label><input id="cook_time_minutes" name="cook_time_minutes" type="number" min="0" class="form-control" value="<?php echo (int) ($_POST['cook_time_minutes'] ?? $recipe['cook_time_minutes']); ?>"></div>
      <div class="form-group col-md-3"><label for="servings">Servings</label><input id="servings" name="servings" type="number" min="0" class="form-control" value="<?php echo (int) ($_POST['servings'] ?? $recipe['servings']); ?>"></div>
      <div class="form-group col-md-3"><label for="calories_per_serving">Calories</label><input id="calories_per_serving" name="calories_per_serving" type="number" min="0" class="form-control" value="<?php echo (int) ($_POST['calories_per_serving'] ?? $recipe['calories_per_serving']); ?>"></div>
    </div>
    <div class="form-group"><label for="ingredients">Ingredients</label><textarea id="ingredients" name="ingredients" rows="7" class="form-control" required><?php echo htmlspecialchars($_POST['ingredients'] ?? $recipe['ingredients'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
    <div class="form-group"><label for="instructions">Instructions</label><textarea id="instructions" name="instructions" rows="9" class="form-control" required><?php echo htmlspecialchars($_POST['instructions'] ?? $recipe['instructions'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
    <div class="form-group"><label for="media">Add photos or videos</label><input id="media" name="media[]" type="file" class="form-control-file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"><small class="form-text text-muted">JPG, PNG, WebP, MP4, WebM, or MOV; up to 10 files, 20MB each.</small></div>
    <button class="btn btn-primary" type="submit">Save changes</button>
    <a class="btn btn-outline-secondary" href="myaccount.php?tab=recipes">Cancel</a>
  </form>
</main>
</body>
</html>