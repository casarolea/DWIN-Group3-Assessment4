<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = cookbook_db();
$currentUser = cookbook_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_collection'])) {
    if (!$currentUser) {
        header('Location: login.php');
        exit;
    }
    if (!cookbook_csrf_is_valid()) {
        http_response_code(400);
        exit('Invalid security token.');
    }

    $collectionId = (int) ($_POST['collection_id'] ?? 0);
    $collectionOwner = $pdo->prepare(
        'SELECT collection_id FROM user_collections WHERE collection_id = ? AND owner_id = ?'
    );
    $collectionOwner->execute([$collectionId, $currentUser['id']]);
    if (!$collectionOwner->fetchColumn()) {
        http_response_code(403);
        exit('That collection is not available to your account.');
    }

    $recipeToSave = (int) ($_POST['recipe_id'] ?? 0);
    $saveRecipe = $pdo->prepare(
        'INSERT IGNORE INTO user_collection_recipes (collection_id, recipe_id) VALUES (?, ?)'
    );
    $saveRecipe->execute([$collectionId, $recipeToSave]);
    header('Location: recipe.php?id=' . $recipeToSave . '&collection_status=added');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_action'])) {
    if (!$currentUser) {
        header('Location: login.php');
        exit;
    }

    if (!cookbook_csrf_is_valid()) {
        die('Invalid security token.');
    }

    $favoriteRecipeId = (int)($_POST['recipe_id'] ?? 0);

    if ($_POST['favorite_action'] === 'add') {
        $favoriteStmt = $pdo->prepare(
            'INSERT IGNORE INTO favorites (user_id, recipe_id)
             VALUES (:user_id, :recipe_id)'
        );
        $favoriteStmt->execute([
            'user_id' => $currentUser['id'],
            'recipe_id' => $favoriteRecipeId
        ]);
    } elseif ($_POST['favorite_action'] === 'remove') {
        $favoriteStmt = $pdo->prepare(
            'DELETE FROM favorites
             WHERE user_id = :user_id
             AND recipe_id = :recipe_id'
        );
        $favoriteStmt->execute([
            'user_id' => $currentUser['id'],
            'recipe_id' => $favoriteRecipeId
        ]);
    }

    header('Location: recipe.php?id=' . $favoriteRecipeId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (!$currentUser) {
        header('Location: login.php');
        exit;
    }

    if (!cookbook_csrf_is_valid()) {
        die('Invalid security token.');
    }

    $ratingRecipeId = (int)($_POST['recipe_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);

    if ($rating >= 1 && $rating <= 5) {
        $ratingStmt = $pdo->prepare(
            'INSERT INTO recipe_ratings (user_id, recipe_id, rating)
             VALUES (:user_id, :recipe_id, :rating)
             ON DUPLICATE KEY UPDATE rating = :updated_rating'
        );
        $ratingStmt->execute([
            'user_id' => $currentUser['id'],
            'recipe_id' => $ratingRecipeId,
            'rating' => $rating,
            'updated_rating' => $rating
        ]);
    }

    header('Location: recipe.php?id=' . $ratingRecipeId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    if (!$currentUser) {
        header('Location: login.php');
        exit;
    }

    if (!cookbook_csrf_is_valid()) {
        die('Invalid security token.');
    }

    $commentRecipeId = (int)($_POST['recipe_id'] ?? 0);
    $commentText = trim($_POST['comment_text'] ?? '');

    if ($commentText !== '') {
        $commentStmt = $pdo->prepare(
            'INSERT INTO recipe_comments (user_id, recipe_id, comment_text)
             VALUES (:user_id, :recipe_id, :comment_text)'
        );
        $commentStmt->execute([
            'user_id' => $currentUser['id'],
            'recipe_id' => $commentRecipeId,
            'comment_text' => $commentText
        ]);
    }

    header('Location: recipe.php?id=' . $commentRecipeId);
    exit;
}

$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recipeId <= 0) {
    die('Invalid Recipe ID.');
}

$stmt = $pdo->prepare(
    'SELECT
        r.recipe_id AS id,
        r.owner_id,
        r.photo,
        r.title,
        r.prep_time_minutes,
        r.cook_time_minutes,
        r.calories_per_serving,
        r.instructions,
        r.ingredients
     FROM recipes r
     WHERE r.recipe_id = :id'
);

$stmt->execute(['id' => $recipeId]);
$recipe = $stmt->fetch();

if (!$recipe) {
    die('Recipe not found.');
}

$userCollections = [];
if ($currentUser) {
    $userCollectionsStatement = $pdo->prepare(
        'SELECT collection_id, name FROM user_collections WHERE owner_id = ? ORDER BY name'
    );
    $userCollectionsStatement->execute([$currentUser['id']]);
    $userCollections = $userCollectionsStatement->fetchAll();
}

$isFavorite = false;

if ($currentUser) {
    $favoriteCheck = $pdo->prepare(
        'SELECT favorite_id
         FROM favorites
         WHERE user_id = :user_id
         AND recipe_id = :recipe_id'
    );
    $favoriteCheck->execute([
        'user_id' => $currentUser['id'],
        'recipe_id' => $recipeId
    ]);
    $isFavorite = (bool)$favoriteCheck->fetch();
}

$ratingSummary = $pdo->prepare(
    'SELECT
        AVG(rating) AS average_rating,
        COUNT(*) AS rating_count
     FROM recipe_ratings
     WHERE recipe_id = :recipe_id'
);

$ratingSummary->execute([
    'recipe_id' => $recipeId
]);

$ratingData = $ratingSummary->fetch();

$averageRating = $ratingData['average_rating']
    ? round((float)$ratingData['average_rating'], 1)
    : 0;

$ratingCount = (int)$ratingData['rating_count'];
$userRating = 0;

if ($currentUser) {
    $userRatingStmt = $pdo->prepare(
        'SELECT rating
         FROM recipe_ratings
         WHERE user_id = :user_id
         AND recipe_id = :recipe_id'
    );
    $userRatingStmt->execute([
        'user_id' => $currentUser['id'],
        'recipe_id' => $recipeId
    ]);

    $existingRating = $userRatingStmt->fetch();

    if ($existingRating) {
        $userRating = (int)$existingRating['rating'];
    }
}

$commentsStmt = $pdo->prepare(
    'SELECT
        rc.comment_id,
        rc.comment_text,
        rc.created_at,
        u.username
     FROM recipe_comments rc
     JOIN users u ON u.user_id = rc.user_id
     WHERE rc.recipe_id = :recipe_id
     ORDER BY rc.created_at DESC'
);

$commentsStmt->execute([
    'recipe_id' => $recipeId
]);

$comments = $commentsStmt->fetchAll();

$mediaStatement = $pdo->prepare(
    'SELECT media_path, media_type, mime_type
     FROM recipe_media
     WHERE recipe_id = ?
     ORDER BY media_id'
);

$mediaStatement->execute([$recipeId]);
$mediaItems = $mediaStatement->fetchAll();

$imageSrc = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 300 150"><rect width="100%" height="100%" fill="%23e9ecef"/><text x="50%" y="50%" fill="%236c757d" dominant-baseline="middle" text-anchor="middle">No Image Available</text></svg>';

$canManageRecipe = $currentUser && (
    $currentUser['role'] === 'admin' ||
    (int)$recipe['owner_id'] === (int)$currentUser['id']
);

if (!$mediaItems && $recipe['photo'] && is_file(__DIR__ . '/../images/recipes/' . basename($recipe['photo']))) {
    $mediaItems[] = [
        'media_path' => 'recipes/' . basename($recipe['photo']),
        'media_type' => 'image',
        'mime_type' => 'image/jpeg'
    ];
}

if (!$mediaItems) {
    foreach (glob(__DIR__ . '/../images/Recipe images/*') ?: [] as $imageFile) {
        if (strtolower(pathinfo($imageFile, PATHINFO_FILENAME)) === strtolower($recipe['title'])) {
            $mediaItems[] = [
                'media_path' => 'Recipe images/' . basename($imageFile),
                'media_type' => 'image',
                'mime_type' => mime_content_type($imageFile) ?: 'image/jpeg'
            ];
            break;
        }
    }
}

function recipe_media_url(string $path): string
{
    return '../images/' . implode(
        '/',
        array_map('rawurlencode', explode('/', ltrim($path, '/\\')))
    );
}
?>

<!-- header -->
<?php
$page_title = "Recipes";
include "../includes/header.php";
?>

<main class="container my-5 recipe-detail-container">
    <a href="recipe_page.php" class="btn btn-outline-secondary mb-4">
        &larr; Back to Recipes
    </a>

    <div class="card recipe-detail-card">
        <?php if ($mediaItems): ?>
            <div class="row no-gutters">
                <?php foreach ($mediaItems as $media): ?>
                    <div class="col-md-6 p-2">
                        <?php if ($media['media_type'] === 'video'): ?>
                            <video controls class="w-100" style="max-height: 400px;">
                                <source src="<?php echo htmlspecialchars(recipe_media_url($media['media_path']), ENT_QUOTES, 'UTF-8'); ?>" type="<?php echo htmlspecialchars($media['mime_type'], ENT_QUOTES, 'UTF-8'); ?>">
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars(recipe_media_url($media['media_path']), ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?>" style="max-height: 400px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?>" style="max-height: 400px; object-fit: cover;">
        <?php endif; ?>

        <div class="card-body recipe-detail-body">
            <h1 class="card-title">
                <?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>

            <?php if ($canManageRecipe): ?>
                <a href="edit-recipe.php?id=<?php echo (int)$recipeId; ?>" class="btn btn-outline-primary mb-3">
                    Edit Recipe
                </a>
                <form method="post" action="delete-recipe.php" class="d-inline" onsubmit="return confirm('Delete this recipe and its uploaded media?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeId; ?>">
                    <button type="submit" class="btn btn-outline-danger mb-3">
                        Delete Recipe
                    </button>
                </form>
            <?php endif; ?>

            <p class="text-muted">
                Prep Time: <?php echo (int)($recipe['prep_time_minutes'] ?? 0); ?> mins |
                Cook Time: <?php echo (int)($recipe['cook_time_minutes'] ?? 0); ?> mins |
                Calories: <?php echo (int)($recipe['calories_per_serving'] ?? 0); ?> kcal
            </p>

            <?php if (($_GET['collection_status'] ?? '') === 'added'): ?>
                <div class="alert alert-success" role="status">Recipe added to your collection.</div>
            <?php endif; ?>

            <div class="mb-3">
                <?php if (!$currentUser): ?>
                    <a href="login.php">Log in to save this recipe to a collection.</a>
                <?php elseif ($userCollections): ?>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="add_to_collection" value="1">
                        <input type="hidden" name="recipe_id" value="<?php echo (int) $recipeId; ?>">
                        <label class="mr-2" for="collection_id">Save to collection</label>
                        <select class="form-control mr-2" id="collection_id" name="collection_id" required>
                            <?php foreach ($userCollections as $collection): ?>
                                <option value="<?php echo (int) $collection['collection_id']; ?>"><?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary" type="submit">Add recipe</button>
                    </form>
                <?php else: ?>
                    <a href="create_collection.php">Create a collection to save this recipe.</a>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <?php if ($currentUser): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeId; ?>">

                        <?php if ($isFavorite): ?>
                            <input type="hidden" name="favorite_action" value="remove">
                            <button type="submit" class="btn btn-danger">
                                ♥ Favorited
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="favorite_action" value="add">
                            <button type="submit" class="btn btn-outline-danger">
                                ♡ Add to Favorites
                            </button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-danger">
                        ♡ Login to Favorite
                    </a>
                <?php endif; ?>

                <button type="button" class="btn btn-outline-secondary" onclick="copyRecipeLink()">
                    🔗 Copy Link
                </button>
                <span id="copyMessage" class="ml-2 text-success"></span>
            </div>

            <hr>

            <h3>Ingredients</h3>
            <p>
                <?php
                echo nl2br(
                    htmlspecialchars(
                        $recipe['ingredients'] ?? 'No ingredients provided.',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );
                ?>
            </p>

            <hr>

            <h3>Instructions</h3>
            <p>
                <?php
                echo nl2br(
                    htmlspecialchars(
                        $recipe['instructions'] ?? 'No instructions provided.',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );
                ?>
            </p>

            <hr>

            <div class="mt-4">
                <h3>Ratings &amp; Comments</h3>

                <div class="mb-4">
                    <p class="mb-2">
                        <strong>
                            ⭐ <?php echo number_format($averageRating, 1); ?> / 5
                        </strong>
                        <span class="text-muted">
                            (<?php echo $ratingCount; ?>
                            <?php echo $ratingCount === 1 ? 'rating' : 'ratings'; ?>)
                        </span>
                    </p>

                    <?php if ($currentUser): ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeId; ?>">

                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <button type="submit" name="rating" value="<?php echo $star; ?>" class="btn btn-link p-1" style="font-size: 32px; text-decoration: none; color: #ffc107;" title="Rate <?php echo $star; ?> out of 5">
                                    <?php echo $star <= $userRating ? '★' : '☆'; ?>
                                </button>
                            <?php endfor; ?>
                        </form>

                        <?php if ($userRating > 0): ?>
                            <p class="text-muted">
                                Your rating: <?php echo $userRating; ?>/5
                            </p>
                        <?php else: ?>
                            <p class="text-muted">
                                Click a star to rate this recipe.
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>
                            <a href="login.php">Login</a> to rate this recipe.
                        </p>
                    <?php endif; ?>
                </div>

                <h4>Write a Comment</h4>

                <?php if ($currentUser): ?>
                    <form method="post" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeId; ?>">

                        <div class="form-group">
                            <textarea name="comment_text" class="form-control" rows="4" maxlength="1000" placeholder="What did you think of this recipe?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Post Comment
                        </button>
                    </form>
                <?php else: ?>
                    <p>
                        <a href="login.php">Login</a> to write a comment.
                    </p>
                <?php endif; ?>

                <h4 class="mt-4">Comments</h4>

                <?php if (!$comments): ?>
                    <p class="text-muted">
                        No comments yet. Be the first to comment!
                    </p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>
                                    <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($comment['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                            </div>
                            <p class="mb-0 mt-2">
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $comment['comment_text'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                );
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<footer class="text-center text-lg-start bg-body-tertiary text-muted footer">
    <section>
        <div class="container text-center mt-5">
            <div class="row mt-3">
                <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
                    <h6 class="fw-bold mb-4">
                        <a class="gochihand footer-text1" href="index.php">
                            <img class="logo" src="../images/cblogo2.png" alt="CookBook Logo" title="CookBook logo">
                            CookBook
                        </a>
                    </h6>
                    <p class="footer-text2 ralewaybold">
                        Improve the cooking experience.
                    </p>
                    <hr>
                    <p>
                        <a href="https://www.instagram.com/" class="footer-links ralewaybold footer-text2">Instagram</a>
                        <a href="https://www.facebook.com/" class="footer-links ralewaybold footer-text2">Facebook</a>
                        <a href="https://www.tiktok.com/" class="footer-links ralewaybold footer-text2">Tiktok</a>
                        <a href="https://www.youtube.com/" class="footer-links ralewaybold footer-text2">YouTube</a>
                    </p>
                </div>

                <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
                    <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">
                        Quick Links
                    </h6>
                    <p>
                        <a href="index.php" class="footer-links ralewaybold">Home</a>
                    </p>
                    <p>
                        <a href="recipe_page.php" class="footer-links ralewaybold">Recipes</a>
                    </p>
                    <p>
                        <a href="collections.php" class="footer-links ralewaybold">Collections</a>
                    </p>
                </div>

                <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
                    <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">
                        GROUP INFORMATION
                    </h6>
                    <p>
                        <span class="footer-text2 ralewaybold">Joewiey Franzine Ibanez - K231663</span>
                    </p>
                    <p>
                        <span class="footer-text2 ralewaybold">Sheirina Glee Nadera - K240664</span>
                    </p>
                    <p>
                        <span class="footer-text2 ralewaybold">Regil Maharjan - K240722</span>
                    </p>
                    <p>
                        <span class="footer-text2 ralewaybold">Chauncey Ariel Nieto - K240938</span>
                    </p>
                    <p>
                        <span class="footer-text2 ralewaybold">Cassandra Noeribelle Dejucos - K240945</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="text-center p-4">
        <p class="ralewaybold footer-text2">
            This website was created for the final assessment for DWIN309 at Kent Institute Australia - Trimester 2, 2026
        </p>
        <p class="ralewaybold footer-text2">
            &copy; CookBook 2026. All rights reserved.
        </p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<script>
function copyRecipeLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        document.getElementById("copyMessage").textContent = "Link copied!";

        setTimeout(function() {
            document.getElementById("copyMessage").textContent = "";
        }, 2000);
    });
}
</script>
</body>
</html>
