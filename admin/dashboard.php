<?php
require_once __DIR__ . '/../includes/auth.php';

cookbook_require_role('admin');

$pdo = cookbook_db();
$currentUser = cookbook_current_user();

$totalRecipes = (int)$pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalComments = (int)$pdo->query("SELECT COUNT(*) FROM recipe_comments")->fetchColumn();
$totalRatings = (int)$pdo->query("SELECT COUNT(*) FROM recipe_ratings")->fetchColumn();

$recentRecipes = $pdo->query("
    SELECT r.recipe_id, r.title, r.owner_id, u.username
    FROM recipes r
    LEFT JOIN users u ON r.owner_id = u.user_id
    ORDER BY r.recipe_id DESC
    LIMIT 5
")->fetchAll();

$page_title = "Admin Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<main class="admin-dashboard">
    <div class="container">
        <div class="mb-5">
            <h1 class="admin-title">Admin Dashboard</h1>
            <p class="admin-subtitle">
                Welcome, <?= htmlspecialchars($currentUser['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>.
            </p>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card admin-stat-card h-100">
                    <div class="card-body text-center">
                        <h2 class="admin-stat-number"><?= $totalRecipes ?></h2>
                        <p class="admin-stat-text mb-0">Total Recipes</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card admin-stat-card h-100">
                    <div class="card-body text-center">
                        <h2 class="admin-stat-number"><?= $totalUsers ?></h2>
                        <p class="admin-stat-text mb-0">Total Users</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card admin-stat-card h-100">
                    <div class="card-body text-center">
                        <h2 class="admin-stat-number"><?= $totalComments ?></h2>
                        <p class="admin-stat-text mb-0">Total Comments</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card admin-stat-card h-100">
                    <div class="card-body text-center">
                        <h2 class="admin-stat-number"><?= $totalRatings ?></h2>
                        <p class="admin-stat-text mb-0">Total Ratings</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-section-card mb-5">
            <div class="card-body">
                <h2 class="admin-section-title">Quick Management</h2>

                <div class="d-flex flex-wrap">
                    <a href="recipes.php" class="btn admin-main-btn mr-3 mb-2">
                        Manage Recipes
                    </a>

                    <a href="users.php" class="btn admin-main-btn mr-3 mb-2">
                        Manage Users
                    </a>

                    <a href="comments.php" class="btn admin-main-btn mb-2">
                        Manage Comments
                    </a>
                </div>
            </div>
        </div>

        <div class="card admin-section-card">
            <div class="card-body">
                <h2 class="admin-section-title">Recent Recipes</h2>

                <?php if (empty($recentRecipes)): ?>

                    <p class="admin-empty-message mb-0">
                        No recipes found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover admin-table">
                            <thead>
                                <tr>
                                    <th>Recipe ID</th>
                                    <th>Recipe</th>
                                    <th>Owner</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($recentRecipes as $recipe): ?>
                                    <tr>
                                        <td><?= (int)$recipe['recipe_id'] ?></td>

                                        <td>
                                            <?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($recipe['username'] ?? 'No owner', ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <a
                                                href="../pages/recipe.php?id=<?= (int)$recipe['recipe_id'] ?>"
                                                class="btn btn-sm admin-view-btn"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>