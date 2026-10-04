<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_login();
$currentUser = cookbook_current_user();
if (!cookbook_is_moderator($currentUser)) {
    http_response_code(403);
    exit('Moderator access is required.');
}

$pdo = cookbook_db();
$message = '';
$isAdmin = $currentUser['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cookbook_csrf_is_valid()) {
        http_response_code(400);
        exit('Invalid security token.');
    }

    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);
    if (in_array($action, ['approve_recipe', 'reject_recipe'], true)) {
        $statement = $pdo->prepare(
            'SELECT r.recipe_id, r.owner_id, r.title, r.pending_event, u.username
             FROM recipes r LEFT JOIN users u ON u.user_id = r.owner_id
             WHERE r.recipe_id = ? AND r.moderation_status = "pending"'
        );
        $statement->execute([$itemId]);
        $recipe = $statement->fetch();
        if (!$recipe || (!$isAdmin && (int) $recipe['owner_id'] === (int) $currentUser['id'])) {
            http_response_code(404);
            exit('Pending recipe not found.');
        }

        if ($action === 'approve_recipe') {
            $pdo->prepare('UPDATE recipes SET moderation_status = "approved" WHERE recipe_id = ?')->execute([$itemId]);
            cookbook_record_recipe_activity(
                $pdo,
                ['id' => (int) $recipe['owner_id'], 'username' => $recipe['username'] ?: 'Former user'],
                $recipe['pending_event'] === 'updated' ? 'recipe_updated' : 'recipe_created',
                (int) $recipe['recipe_id'],
                $recipe['title']
            );
            $message = 'Recipe approved and published.';
        } else {
            $pdo->prepare('UPDATE recipes SET moderation_status = "rejected" WHERE recipe_id = ?')->execute([$itemId]);
            $message = 'Recipe rejected and kept private from public search.';
        }
    } elseif (in_array($action, ['approve_comment', 'reject_comment'], true)) {
        $statement = $pdo->prepare(
            'SELECT comment_id, user_id FROM recipe_comments
             WHERE comment_id = ? AND moderation_status = "pending"'
        );
        $statement->execute([$itemId]);
        $comment = $statement->fetch();
        if (!$comment || (!$isAdmin && (int) $comment['user_id'] === (int) $currentUser['id'])) {
            http_response_code(404);
            exit('Pending comment not found.');
        }

        $status = $action === 'approve_comment' ? 'approved' : 'rejected';
        $pdo->prepare('UPDATE recipe_comments SET moderation_status = ? WHERE comment_id = ?')->execute([$status, $itemId]);
        $message = $status === 'approved' ? 'Comment approved.' : 'Comment rejected.';
    } else {
        http_response_code(400);
        exit('Unknown moderation action.');
    }
}

$recipeQuery = 'SELECT r.recipe_id, r.title, r.pending_event, u.username
                FROM recipes r LEFT JOIN users u ON u.user_id = r.owner_id
                WHERE r.moderation_status = "pending"';
$recipeParameters = [];
if (!$isAdmin) {
    $recipeQuery .= ' AND r.owner_id <> ?';
    $recipeParameters[] = $currentUser['id'];
}
$recipeQuery .= ' ORDER BY r.recipe_id ASC';
$recipeStatement = $pdo->prepare($recipeQuery);
$recipeStatement->execute($recipeParameters);
$pendingRecipes = $recipeStatement->fetchAll();

$commentQuery = 'SELECT c.comment_id, c.comment_text, c.created_at, r.recipe_id, r.title, u.username
                 FROM recipe_comments c
                 JOIN recipes r ON r.recipe_id = c.recipe_id
                 JOIN users u ON u.user_id = c.user_id
                 WHERE c.moderation_status = "pending"';
$commentParameters = [];
if (!$isAdmin) {
    $commentQuery .= ' AND c.user_id <> ?';
    $commentParameters[] = $currentUser['id'];
}
$commentQuery .= ' ORDER BY c.created_at ASC';
$commentStatement = $pdo->prepare($commentQuery);
$commentStatement->execute($commentParameters);
$pendingComments = $commentStatement->fetchAll();
?>
<?php
$page_title = 'Content Moderation';
include __DIR__ . '/../includes/header.php';
?>
<main class="container my-5">
  <h1>Content Moderation</h1>
  <p class="text-muted">Review submissions before they become visible to other users.</p>
  <?php if ($message !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

  <h2 class="mt-4">Pending recipes</h2>
  <?php if (!$pendingRecipes): ?><p class="text-muted">No recipes need review.</p><?php endif; ?>
  <?php foreach ($pendingRecipes as $recipe): ?>
    <article class="card mb-3">
      <div class="card-body">
        <h3 class="h5"><?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="text-muted">Submitted by <?php echo htmlspecialchars($recipe['username'] ?? 'Unknown user', ENT_QUOTES, 'UTF-8'); ?> · <?php echo $recipe['pending_event'] === 'updated' ? 'Edit' : 'New recipe'; ?></p>
        <a class="btn btn-outline-secondary btn-sm" href="recipe.php?id=<?php echo (int) $recipe['recipe_id']; ?>">Review recipe</a>
        <form method="post" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="item_id" value="<?php echo (int) $recipe['recipe_id']; ?>">
          <button class="btn btn-primary btn-sm" name="action" value="approve_recipe" type="submit">Approve</button>
          <button class="btn btn-outline-danger btn-sm" name="action" value="reject_recipe" type="submit">Reject</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>

  <h2 class="mt-5">Pending comments</h2>
  <?php if (!$pendingComments): ?><p class="text-muted">No comments need review.</p><?php endif; ?>
  <?php foreach ($pendingComments as $comment): ?>
    <article class="card mb-3">
      <div class="card-body">
        <p><?php echo nl2br(htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8')); ?></p>
        <p class="text-muted">By <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?> on <a href="recipe.php?id=<?php echo (int) $comment['recipe_id']; ?>"><?php echo htmlspecialchars($comment['title'], ENT_QUOTES, 'UTF-8'); ?></a></p>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="item_id" value="<?php echo (int) $comment['comment_id']; ?>">
          <button class="btn btn-primary btn-sm" name="action" value="approve_comment" type="submit">Approve</button>
          <button class="btn btn-outline-danger btn-sm" name="action" value="reject_comment" type="submit">Reject</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>