<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/recipe_media.php';
cookbook_require_login();
$currentUser = cookbook_current_user();
cookbook_db();

// ---------- Database connection (matches cookbook db used by the team) ----------
$pdo = cookbook_db();

// ---------- Load categories for the dropdown ----------
$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_id')->fetchAll();

// ---------- Handle form submission ----------
$errors = [];
$saved = false;
$old = $_POST; // sticky values if validation fails

function minutesFrom($text) {
    if (preg_match('/(\d+)/', $text ?? '', $m)) return (int)$m[1];
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!cookbook_csrf_is_valid()) {
    $errors[] = 'Your session expired. Please reload the page and try again.';
  }

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category'] ?? 0);
    $parts = $_POST['parts'] ?? [];

    // photo validation (server-side, don't trust the browser)
    $ext = null;
    $mime = '';
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Choose a photo and upload it (Step 5).';
    } else {
        $f = $_FILES['photo'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
        if (!$ext) $errors[] = 'Photo must be JPG or PNG.';
        if ($f['size'] > 4 * 1024 * 1024) $errors[] = 'Photo cannot exceed 4MB.';
    }

    if ($title === '') $errors[] = 'Enter a recipe title.';
    if ($categoryId <= 0) $errors[] = 'Choose a category.';

    try {
      $additionalMedia = cookbook_validate_recipe_media($_FILES['media'] ?? null);
    } catch (RuntimeException $exception) {
      $additionalMedia = [];
      $errors[] = $exception->getMessage();
    }

    $ingredientsText = '';
    $instructionsText = '';
    $partsValid = !empty($parts);
    if (!$partsValid) {
        $errors[] = 'Add at least one ingredient and one method step.';
    } else {
        foreach ($parts as $p) {
            $ingredients = array_values(array_filter(array_map('trim', $p['ingredients'] ?? [])));
            $method = array_values(array_filter(array_map('trim', $p['method'] ?? [])));
            if (!$ingredients || !$method) {
                $errors[] = 'Every part needs at least one ingredient and one method step.';
                break;
            }
            $heading = trim($p['name'] ?? '');
            if ($heading !== '') {
                $ingredientsText .= "## $heading\n";
                $instructionsText .= "## $heading\n";
            }
            foreach ($ingredients as $ing) $ingredientsText .= "- $ing\n";
            foreach ($method as $i => $step) $instructionsText .= ($i + 1) . ". $step\n";
        }
    }

    $prepMin  = minutesFrom($_POST['prep'] ?? '');
    $cookMin  = minutesFrom($_POST['cook'] ?? '');
    $servesRaw = $_POST['serves'] ?? '';
    $servings = is_numeric($servesRaw) ? (int)$servesRaw : null;

    $calories = is_numeric($_POST['calories'] ?? null) ? (int)$_POST['calories'] : null;
    $protein  = is_numeric($_POST['protein'] ?? null)  ? (int)$_POST['protein']  : null;
    $carbs    = is_numeric($_POST['carbs'] ?? null)    ? (int)$_POST['carbs']    : null;
    $fat      = is_numeric($_POST['fatTotal'] ?? null) ? (int)$_POST['fatTotal'] : null;

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // save the photo to disk
            $dir = __DIR__ . '/../images/recipes/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $photoName = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $photoName)) {
                throw new RuntimeException('Could not save photo.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO recipes (title, prep_time_minutes, cook_time_minutes, servings,
               calories_per_serving, protein_g, carbs_g, fat_g, ingredients, instructions, photo, owner_id)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $title, $prepMin, $cookMin, $servings,
                $calories, $protein, $carbs, $fat,
              trim($ingredientsText), trim($instructionsText), $photoName, $currentUser['id'],
            ]);
            $recipeId = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)')
                ->execute([$recipeId, $categoryId]);
            $pdo->prepare(
                'INSERT INTO recipe_media (recipe_id, media_path, media_type, mime_type) VALUES (?, ?, ?, ?)'
            )->execute([$recipeId, 'recipes/' . $photoName, 'image', $mime]);
            cookbook_store_recipe_media($pdo, (int) $recipeId, $additionalMedia);
            $pdo->commit();

            // redirect to avoid resubmission on refresh
            header('Location: create-recipe.php?saved=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }
}

if (isset($_GET['saved'])) $saved = true;

// ---------- Recent recipes to show in "My Recipes" ----------
$recentQuery = 'SELECT r.recipe_id, r.title, r.prep_time_minutes, r.cook_time_minutes, r.servings, r.photo,
             c.category_name
        FROM recipes r
        LEFT JOIN recipe_categories rc ON rc.recipe_id = r.recipe_id
        LEFT JOIN categories c ON c.category_id = rc.category_id';
$recentParameters = [];
if ($currentUser['role'] !== 'admin') {
  $recentQuery .= ' WHERE r.owner_id = :owner_id';
  $recentParameters['owner_id'] = $currentUser['id'];
}
$recentQuery .= ' ORDER BY r.recipe_id DESC LIMIT 10';
$recentStatement = $pdo->prepare($recentQuery);
$recentStatement->execute($recentParameters);
$recentRecipes = $recentStatement->fetchAll();

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Create a Recipe";
$extra_css = ["../styles/recipe-form.css"];
$extra_js = ["../scripts/recipe-form.js"];
include "../includes/header.php";
?>

<section class="first-block">
<main class="container recipe-page">
  <h1 class="recipe-title gochihand text-center">Create a Recipe</h1>

  <?php if ($saved): ?>
    <div class="recipe-summary-success" role="status">Recipe saved. You can see it under My Recipes below.</div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="recipe-summary-error" id="summary-error">
      <strong>Please fix the following before saving:</strong>
      <ul id="summary-error-list">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="recipe-form" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h(cookbook_csrf_token()) ?>">
    <div class="row">

      <!-- Step 1 -->
      <div class="col-lg-6 mb-4">
        <section class="recipe-panel h-100">
          <h2 class="recipe-panel-head ralewaybold">Step 1: Recipe Title &amp; Description</h2>
          <div class="recipe-panel-body">
            <label class="sr-only" for="title">Title</label>
            <input id="title" name="title" type="text" class="form-control" placeholder="Title*" required value="<?= h($old['title'] ?? '') ?>">

            <label class="sr-only" for="description">Short description</label>
            <textarea id="description" name="description" rows="12" maxlength="300" class="form-control mt-3" placeholder="Add a short description"><?= h($old['description'] ?? '') ?></textarea>
            <p class="recipe-counter">
              <span>Maximum 300 characters</span>
              <span>Characters remaining: <b id="remaining">300</b></span>
            </p>
          </div>
        </section>
      </div>

      <!-- Step 2 -->
      <div class="col-lg-6 mb-4">
        <section class="recipe-panel h-100">
          <h2 class="recipe-panel-head ralewaybold">Step 2: Recipe Information</h2>
          <div class="recipe-panel-body">
            <div class="form-group">
              <label for="category">Category *</label>
              <select id="category" name="category" class="form-control" required>
                <option value="">- Select a value -</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= h($c['category_id']) ?>" <?= (($old['category'] ?? '') == $c['category_id']) ? 'selected' : '' ?>>
                    <?= h($c['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="cuisine">Cuisine</label>
              <select id="cuisine" name="cuisine" class="form-control">
                <option value="">- None -</option>
                <option>Australian</option><option>Italian</option><option>Asian</option>
                <option>Mexican</option><option>Indian</option><option>Mediterranean</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group col-sm-6">
                <label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty" class="form-control">
                  <option value="">- None -</option>
                  <option>Easy</option><option>Medium</option><option>Hard</option>
                </select>
              </div>
              <div class="form-group col-sm-6">
                <label for="serves">Serves</label>
                <select id="serves" name="serves" class="form-control">
                  <option value="">- None -</option>
                  <option>1</option><option>2</option><option>3</option><option>4</option>
                  <option>5</option><option>6</option><option>8</option><option>10</option>
                </select>
              </div>
              <div class="form-group col-sm-6">
                <label for="prep">Prep Time</label>
                <input id="prep" name="prep" type="text" class="form-control" placeholder="eg. 30 minutes" value="<?= h($old['prep'] ?? '') ?>">
              </div>
              <div class="form-group col-sm-6">
                <label for="marinate">Marinate</label>
                <input id="marinate" name="marinate" type="text" class="form-control" placeholder="eg. 1 hour" value="<?= h($old['marinate'] ?? '') ?>">
              </div>
              <div class="form-group col-sm-6 mb-0">
                <label for="cook">Cook Time</label>
                <input id="cook" name="cook" type="text" class="form-control" placeholder="eg. 1 hour" value="<?= h($old['cook'] ?? '') ?>">
              </div>
              <div class="form-group col-sm-6 mb-0">
                <label for="makes">Makes</label>
                <input id="makes" name="makes" type="text" class="form-control" placeholder="eg. 12 cupcakes" value="<?= h($old['makes'] ?? '') ?>">
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Step 3 -->
    <section class="recipe-panel mb-4">
      <h2 class="recipe-panel-head ralewaybold">Step 3: Recipe Ingredients, Method &amp; Tips</h2>
      <div class="recipe-panel-body">
        <div class="custom-control custom-radio mb-3">
          <input type="radio" class="custom-control-input" id="type-single" name="recipeType" value="single" checked>
          <label class="custom-control-label" for="type-single">
            Single Part Recipe
            <small class="d-block recipe-hint">eg. A recipe for Cake</small>
          </label>
        </div>
        <div class="custom-control custom-radio mb-3">
          <input type="radio" class="custom-control-input" id="type-multi" name="recipeType" value="multi">
          <label class="custom-control-label" for="type-multi">
            Multipart Recipe
            <small class="d-block recipe-hint">eg. One recipe for Cake and one for Icing</small>
          </label>
        </div>

        <div id="parts"></div>
        <button type="button" class="btn btn-outline-recipe" id="add-part" hidden>+ Add another part</button>
        <p class="recipe-error" id="parts-error" hidden>Add at least one ingredient and one method step to every part.</p>

        <div class="form-group mt-4 mb-0">
          <label for="tips">Tips</label>
          <textarea id="tips" name="tips" rows="4" class="form-control" placeholder="Anything that helps the cook"><?= h($old['tips'] ?? '') ?></textarea>
        </div>
      </div>
    </section>

    <!-- Step 4 -->
    <section class="recipe-panel mb-4">
      <h2 class="recipe-panel-head ralewaybold">Step 4: Nutritional Information (per serve)</h2>
      <div class="recipe-panel-body">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="servingSize">Serving size</label>
            <input id="servingSize" name="servingSize" type="text" class="form-control" placeholder="eg. 200g, 2 slices, etc." value="<?= h($old['servingSize'] ?? '') ?>">
          </div>
          <div class="form-group col-md-6">
            <label for="energy">Energy</label>
            <div class="input-group">
              <input id="energy" name="energy" type="number" min="0" step="any" class="form-control" value="<?= h($old['energy'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">kJ</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="calories">Calories</label>
            <div class="input-group">
              <input id="calories" name="calories" type="number" min="0" step="any" class="form-control" value="<?= h($old['calories'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">cal</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="protein">Protein</label>
            <div class="input-group">
              <input id="protein" name="protein" type="number" min="0" step="any" class="form-control" value="<?= h($old['protein'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="fatTotal">Fat, Total</label>
            <div class="input-group">
              <input id="fatTotal" name="fatTotal" type="number" min="0" step="any" class="form-control" value="<?= h($old['fatTotal'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="fatSaturated">Fat, Saturated</label>
            <div class="input-group">
              <input id="fatSaturated" name="fatSaturated" type="number" min="0" step="any" class="form-control" value="<?= h($old['fatSaturated'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="carbs">Carbohydrates</label>
            <div class="input-group">
              <input id="carbs" name="carbs" type="number" min="0" step="any" class="form-control" value="<?= h($old['carbs'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="sugars">Sugars</label>
            <div class="input-group">
              <input id="sugars" name="sugars" type="number" min="0" step="any" class="form-control" value="<?= h($old['sugars'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6 mb-0">
            <label for="fibre">Dietary Fibre</label>
            <div class="input-group">
              <input id="fibre" name="fibre" type="number" min="0" step="any" class="form-control" value="<?= h($old['fibre'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">g</span></div>
            </div>
          </div>
          <div class="form-group col-md-6 mb-0">
            <label for="sodium">Sodium</label>
            <div class="input-group">
              <input id="sodium" name="sodium" type="number" min="0" step="any" class="form-control" value="<?= h($old['sodium'] ?? '') ?>">
              <div class="input-group-append"><span class="input-group-text">mg</span></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Step 5 -->
    <section class="recipe-panel mb-4">
      <h2 class="recipe-panel-head ralewaybold">Step 5: Recipe Photos and Videos</h2>
      <div class="recipe-panel-body">
        <p class="mb-2">Add your own image to your recipe.*</p>
        <p>Images should be JPG/JPEG/PNG format and be between 2MB - 4MB to achieve quality results in your printed PDF cookbook.</p>

        <div class="recipe-photo">
          <div class="recipe-photo-pick">
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="sr-only">
            <label for="photo" class="btn btn-recipe btn-choose">CHOOSE FILE</label>
            <span id="photo-name">No file chosen</span>
          </div>
          <button type="button" class="btn btn-recipe btn-block mt-3" id="upload-btn" disabled>Upload</button>
          <p class="recipe-hint mt-3 mb-0" id="photo-msg" role="status">Images cannot exceed 4MB.</p>
          <img id="photo-preview" class="recipe-photo-preview" alt="Preview of your recipe photo" hidden>
        </div>
        <div class="form-group mt-4">
          <label for="media">Add up to 10 more photos or videos</label>
          <input id="media" name="media[]" type="file" class="form-control-file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime">
          <small class="form-text text-muted">JPG, PNG, WebP, MP4, WebM, or MOV; up to 20MB per file.</small>
        </div>
      </div>
    </section>

    <!-- Step 6 -->
    <div class="recipe-actions">
      <p id="status" role="status"></p>
      <button type="button" class="btn btn-outline-recipe" id="cancel-btn">Cancel</button>
      <button type="submit" class="btn btn-recipe">Save recipe</button>
    </div>
  </form>

  <!-- My Recipes (from the real database now) -->
  <section class="recipe-panel mt-5" id="my-recipes">
    <h2 class="recipe-panel-head ralewaybold">My Recipes</h2>
    <div class="recipe-panel-body">
      <?php if (!$recentRecipes): ?>
        <p class="recipe-hint mb-0" id="recipes-empty">You haven't created any recipes yet. Fill in the form above and click Save recipe to add your first one.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0" id="recipes-list">
          <?php foreach ($recentRecipes as $r): ?>
            <li class="recipe-item">
              <?php if ($r['photo']): ?>
                <img src="../images/recipes/<?= h($r['photo']) ?>" alt="" class="recipe-item-thumb">
              <?php endif; ?>
              <div>
                <h3><?= h($r['title']) ?></h3>
                <p class="recipe-meta">
                  <?= h($r['category_name'] ?? '') ?><?= $r['servings'] ? ', Serves ' . h($r['servings']) : '' ?><?= $r['prep_time_minutes'] ? ', Prep ' . h($r['prep_time_minutes']) . ' min' : '' ?><?= $r['cook_time_minutes'] ? ', Cook ' . h($r['cook_time_minutes']) . ' min' : '' ?>
                </p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</main>
</section>

<!-- footer -->
<?php include "../includes/footer.php"; ?>
