<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_login();
$pdo = cookbook_db();
$currentUser = cookbook_current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collectionName = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!cookbook_csrf_is_valid()) {
        $message = 'Your session expired. Please reload and try again.';
    } elseif ($collectionName === '' || strlen($collectionName) > 100) {
        $message = 'Enter a collection name of up to 100 characters.';
    } elseif (strlen($description) > 500) {
        $message = 'The description must be 500 characters or fewer.';
    } else {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO user_collections (owner_id, name, description) VALUES (?, ?, ?)'
            );
            $statement->execute([$currentUser['id'], $collectionName, $description ?: null]);
            header('Location: collections.php?status=success');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $message = 'You already have a collection with that name.';
            } else {
                throw $exception;
            }
        }
    }
}
?>

<!-- header -->
<?php
$page_title = "Create Collections";
include "../includes/header.php";
?>

<!-- MAIN CONTENT FORM -->
<main class="container my-5" style="max-width: 600px;">
    <h2 class="mb-4">Create New Collection</h2>

    <?php if ($message !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form action="create_collection.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group mb-3">
            <label for="name" class="font-weight-bold">Collection Name:</label>
            <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Italian Dishes">
        </div>

        <div class="form-group mb-4">
            <label for="description" class="font-weight-bold">Description:</label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Brief description of this recipe collection..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Collection</button>
        <a href="collections.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</main>

<!-- footer -->
<?php include "../includes/footer.php"; ?>