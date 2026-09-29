<?php
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

// 2. Load and Execute SQL File
$sqlFile = __DIR__ . '/DWIN-Group3-Assessment4/databases/recipe.sql';

if (file_exists($sqlFile)) {
    $sqlCommands = file_get_contents($sqlFile);
    try {
        $pdo->exec($sqlCommands);
    } catch (\PDOException $e) {
        // SQL execution error handling
    }
}

// 3. Search Logic
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// 4. Fetch Collections and Recipe Counts
$query = '
    SELECT 
        c.category_id AS id,
        c.category_name AS name,
        CONCAT("Collection of ", LOWER(c.category_name), " recipes.") AS description,
        COUNT(rc.recipe_id) AS recipe_count
    FROM categories c
    LEFT JOIN recipe_categories rc ON c.category_id = rc.category_id
';

$params = [];
if ($searchTerm !== '') {
    $query .= ' WHERE c.category_name LIKE :search';
    $params['search'] = '%' . $searchTerm . '%';
}

$query .= ' GROUP BY c.category_id, c.category_name ORDER BY c.category_name ASC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$collections = $stmt->fetchAll();

// 5. Pagination Logic
$perPage     = 6;
$totalItems  = count($collections);
$totalPages  = max(1, (int) ceil($totalItems / $perPage));
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$currentPage = min($currentPage, $totalPages);

$offset           = ($currentPage - 1) * $perPage;
$pagedCollections = array_slice($collections, $offset, $perPage);
?>

<!-- header -->
<?php
$page_title = "Collections";
include "../includes/header.php";
?>

<!-- MAIN CONTENT -->
<main class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Collections</h1>
            <p class="text-muted mb-0">Browse recipe categories and saved sets.</p>
        </div>
        <a href="create_collection.php" class="register-button">+ Create New Collection</a>
    </div>

    <!-- Success Message Alert -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Collection created successfully!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($searchTerm !== ''): ?>
        <p class="alert alert-info">
            Showing results for "<strong><?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?></strong>". 
            <a href="collections.php" class="alert-link">Clear filter</a>
        </p>
    <?php endif; ?>

    <?php if (empty($collections) && $searchTerm === ''): ?>
        <div class="text-center py-5">
            <p class="lead text-muted">No collections found.</p>
            <a href="create_collection.php" class="register-button">Create the first collection</a>
        </div>

    <?php elseif (empty($collections) && $searchTerm !== ''): ?>
        <p>No collections match "<strong><?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?></strong>". <a href="collections.php">Back to all collections</a>.</p>

    <?php else: ?>
        <!-- Collections Grid -->
        <div class="row">
            <?php foreach ($pagedCollections as $collection): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 160px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="bi bi-folder2-open" viewBox="0 0 16 16">
                                <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.111 1.184l.288.516h5.837A1.5 1.5 0 0 1 15 5.2V6h-1v-.8a.5.5 0 0 0-.5-.5H7.5a.5.5 0 0 1-.447-.276L6.5 3.324A.5.5 0 0 0 6.064 3H2.5a.5.5 0 0 0-.5.5V4h-1v-.5z"/>
                                <path d="M2.5 5a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-11zM1 5.5A1.5 1.5 0 0 1 2.5 4h11A1.5 1.5 0 0 1 15 5.5v8a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 13.5v-8z"/>
                            </svg>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-capitalize mb-2">
                                <?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h5>
                            <p class="card-text text-muted small mb-4">
                                <?php echo htmlspecialchars($collection['description'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="badge badge-pill badge-light border text-secondary px-3 py-2">
                                    <?php echo (int)$collection['recipe_count']; ?> 
                                    <?php echo (int)$collection['recipe_count'] === 1 ? 'recipe' : 'recipes'; ?>
                                </span>
                                <a href="recipe_page.php?category=<?php echo rawurlencode($collection['name']); ?>" class="btn btn-sm btn-outline-primary">
                                  Browse Recipes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $p; ?><?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<!-- footer -->
<?php include "../includes/footer.php"; ?>