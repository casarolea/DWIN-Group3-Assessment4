<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_role('admin');

// 1. Database Connection Configuration
$host    = 'localhost';
$user    = 'root';     // Replace with your DB username
$pass    = '';         // Replace with your DB password
$db      = 'cookbook'; // Replace with your DB name
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';

// 2. Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collection_name = trim($_POST['name'] ?? '');

    if ($collection_name !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (:name)");
            $stmt->execute(['name' => $collection_name]);

            // Redirect back to main page on success
            header("Location: collections.php?status=success");
            exit();
        } catch (\PDOException $e) {
            $message = "Error saving collection: " . $e->getMessage();
        }
    } else {
        $message = "Collection name is required.";
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