<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_login();
$pdo = cookbook_db();
$currentUser = cookbook_current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cookbook_csrf_is_valid()) {
    http_response_code(400);
    exit('Invalid delete request.');
}

$recipeId = (int) ($_POST['recipe_id'] ?? 0);
$statement = $pdo->prepare('SELECT owner_id, photo, title, moderation_status FROM recipes WHERE recipe_id = ?');
$statement->execute([$recipeId]);
$recipe = $statement->fetch();
if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}
if ($currentUser['role'] !== 'admin' && (int) $recipe['owner_id'] !== (int) $currentUser['id']) {
    http_response_code(403);
    exit('You can only delete your own recipes.');
}

$mediaStatement = $pdo->prepare('SELECT media_path FROM recipe_media WHERE recipe_id = ?');
$mediaStatement->execute([$recipeId]);
$mediaPaths = $mediaStatement->fetchAll(PDO::FETCH_COLUMN);
if ($recipe['moderation_status'] === 'approved') {
    cookbook_record_recipe_activity($pdo, $currentUser, 'recipe_deleted', $recipeId, $recipe['title']);
}
$pdo->prepare('DELETE FROM recipes WHERE recipe_id = ?')->execute([$recipeId]);

$recipeDirectory = realpath(__DIR__ . '/../images/recipes');
$pathsToDelete = $mediaPaths;
if ($recipe['photo']) {
    $pathsToDelete[] = 'recipes/' . basename($recipe['photo']);
}
foreach (array_unique($pathsToDelete) as $mediaPath) {
    if (!str_starts_with($mediaPath, 'recipes/')) {
        continue;
    }
    $filePath = realpath(__DIR__ . '/../images/' . $mediaPath);
    if ($recipeDirectory && $filePath && str_starts_with($filePath, $recipeDirectory . DIRECTORY_SEPARATOR)) {
        unlink($filePath);
    }
}

header('Location: myaccount.php?tab=recipes&status=recipe-deleted');
exit;