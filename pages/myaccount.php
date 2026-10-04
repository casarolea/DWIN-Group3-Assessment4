<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_login();
$pdo = cookbook_db();
$accountUser = cookbook_current_user();
$accountStatement = $pdo->prepare('SELECT username, email, full_name, location, profile_photo, password_hash FROM users WHERE user_id = ?');
$accountStatement->execute([$accountUser['id']]);
$accountDetails = $accountStatement->fetch();
$profilePhotoUrl = null;
if (
  !empty($accountDetails['profile_photo'])
  && str_starts_with($accountDetails['profile_photo'], 'profiles/')
  && is_file(__DIR__ . '/../images/' . $accountDetails['profile_photo'])
) {
  $profilePhotoUrl = 'profile_photo.php';
}
$accountError = '';
$activeTab = $_GET['tab'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $activeTab = 'profile';
  if (!cookbook_csrf_is_valid()) {
    $accountError = 'Your session expired. Please reload the page and try again.';
  } elseif (($_POST['action'] ?? '') === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $fullName = trim($_POST['full_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $profilePhoto = $accountDetails['profile_photo'];
    $newPhotoPath = null;

    if ($username === '' || strlen($username) > 80) {
      $accountError = 'Enter a username of up to 80 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $accountError = 'Enter a valid email address.';
    } elseif (strlen($fullName) > 120) {
      $accountError = 'Enter a name of up to 120 characters.';
    } elseif (strlen($location) > 120) {
      $accountError = 'Enter a location of up to 120 characters.';
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
      $upload = $_FILES['profile_photo'];
      if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 4 * 1024 * 1024) {
        $accountError = 'Profile photos must be smaller than 4MB.';
      } else {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mimeType])) {
          $accountError = 'Use a JPG, PNG, or WebP profile photo.';
        } else {
          $directory = __DIR__ . '/../images/profiles/';
          if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $accountError = 'Could not create the profile photo directory.';
          } else {
            $fileName = (int) $accountUser['id'] . '_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mimeType];
            if (!move_uploaded_file($upload['tmp_name'], $directory . $fileName)) {
              $accountError = 'Could not save the profile photo.';
            } else {
              $newPhotoPath = 'profiles/' . $fileName;
              $profilePhoto = $newPhotoPath;
            }
          }
        }
      }
    }

    if ($accountError === '') {
      try {
        $update = $pdo->prepare('UPDATE users SET username = ?, email = ?, full_name = ?, location = ?, profile_photo = ? WHERE user_id = ?');
        $update->execute([$username, $email, $fullName, $location, $profilePhoto, $accountUser['id']]);
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['email'] = $email;
        if ($newPhotoPath && $accountDetails['profile_photo'] && str_starts_with($accountDetails['profile_photo'], 'profiles/')) {
          @unlink(__DIR__ . '/../images/' . $accountDetails['profile_photo']);
        }
        header('Location: myaccount.php?tab=profile&status=profile-updated');
        exit;
      } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
          $accountError = 'That email address is already used by another account.';
        } else {
          throw $exception;
        }
      }
    }
     } elseif (($_POST['action'] ?? '') === 'delete_profile_photo') {
    if (!empty($accountDetails['profile_photo']) && str_starts_with($accountDetails['profile_photo'], 'profiles/')) {
      $photoFile = __DIR__ . '/../images/' . $accountDetails['profile_photo'];
      if (is_file($photoFile)) {
        @unlink($photoFile);
      }
    }
    $update = $pdo->prepare('UPDATE users SET profile_photo = NULL WHERE user_id = ?');
    $update->execute([$accountUser['id']]);
    header('Location: myaccount.php?tab=profile&status=photo-deleted');
    exit;
  } elseif (($_POST['action'] ?? '') === 'change_password') {
    $newPassword = $_POST['new_password'] ?? '';
    if (!password_verify($_POST['current_password'] ?? '', $accountDetails['password_hash'])) {
      $accountError = 'Your current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
      $accountError = 'Use a new password with at least 8 characters.';
    } elseif ($newPassword !== ($_POST['confirm_password'] ?? '')) {
      $accountError = 'The new passwords do not match.';
    } else {
      $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
      $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $accountUser['id']]);
      header('Location: myaccount.php?tab=profile&status=password-updated');
      exit;
    }
  }
}

$accountQuery = 'SELECT r.recipe_id, r.title, r.prep_time_minutes, r.cook_time_minutes, r.photo,
                        GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ", ") AS categories
                 FROM recipes r
                 LEFT JOIN recipe_categories rc ON rc.recipe_id = r.recipe_id
                 LEFT JOIN categories c ON c.category_id = rc.category_id';
$recipeParameters = [];
if ($accountUser['role'] !== 'admin') {
  $accountQuery .= ' WHERE r.owner_id = ?';
  $recipeParameters[] = $accountUser['id'];
}
$accountQuery .= ' GROUP BY r.recipe_id ORDER BY r.recipe_id DESC';
$recipeStatement = $pdo->prepare($accountQuery);
$recipeStatement->execute($recipeParameters);
$accountRecipes = $recipeStatement->fetchAll();
$collectionStatement = $pdo->prepare(
  'SELECT uc.collection_id, uc.name, uc.description, COUNT(ucr.recipe_id) AS recipe_count
   FROM user_collections uc
   LEFT JOIN user_collection_recipes ucr ON ucr.collection_id = uc.collection_id
   WHERE uc.owner_id = ?
   GROUP BY uc.collection_id, uc.name, uc.description
   ORDER BY uc.name'
);
$collectionStatement->execute([$accountUser['id']]);
$accountCollections = $collectionStatement->fetchAll();
?>

<!-- header -->
<?php
$page_title = "My Account";
include "../includes/header.php";
?>

<!-- This is the start of the first-block -->
<section class = "first-block">

<main class="container my-5 account-page">
<div class="row">
<div class="col-lg-3 mb-4">
<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
  <a class="nav-link <?php echo $activeTab === '' ? 'active' : ''; ?>" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="<?php echo $activeTab === '' ? 'true' : 'false'; ?>">My Account</a>
  <a class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" id="v-pills-profile-tab" data-toggle="pill" href="#v-pills-profile" role="tab" aria-controls="v-pills-profile" aria-selected="<?php echo $activeTab === 'profile' ? 'true' : 'false'; ?>">My Profile</a>
  <a class="nav-link <?php echo $activeTab === 'recipes' ? 'active' : ''; ?>" id="v-pills-recipes-tab" data-toggle="pill" href="#v-pills-recipes" role="tab" aria-controls="v-pills-recipes" aria-selected="<?php echo $activeTab === 'recipes' ? 'true' : 'false'; ?>">My Recipes</a>
  <a class="nav-link <?php echo $activeTab === 'collections' ? 'active' : ''; ?>" id="v-pills-messages-tab" data-toggle="pill" href="#v-pills-messages" role="tab" aria-controls="v-pills-messages" aria-selected="<?php echo $activeTab === 'collections' ? 'true' : 'false'; ?>">Collections</a>
</div>
</div>
<div class="col-lg-9">
<div class="tab-content p-4 h-100" id="v-pills-tabContent">
  <div class="tab-pane fade <?php echo $activeTab === '' ? 'show active' : ''; ?>" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
    <div class="account-profile-summary">
      <?php if ($profilePhotoUrl): ?>
        <img class="account-avatar" src="<?php echo htmlspecialchars($profilePhotoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($accountUser['username'], ENT_QUOTES, 'UTF-8'); ?> profile picture">
      <?php else: ?>
        <div class="account-avatar account-avatar-placeholder" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($accountUser['username'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <div>
        <h1>Welcome, <?php echo htmlspecialchars($accountUser['username'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Access level: <?php echo htmlspecialchars(ucfirst($accountUser['role']), ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="cookbook-link" href="myaccount.php?tab=profile">Edit profile</a>
      </div>
    </div>
  </div>
  <div class="tab-pane fade <?php echo $activeTab === 'profile' ? 'show active' : ''; ?>" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
    <?php if ($accountError !== ''): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($accountError, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php elseif (($_GET['status'] ?? '') === 'profile-updated'): ?>
      <div class="alert alert-success" role="status">Profile updated.</div>
    <?php elseif (($_GET['status'] ?? '') === 'password-updated'): ?>
      <div class="alert alert-success" role="status">Password changed.</div>
    <?php elseif (($_GET['status'] ?? '') === 'photo-deleted'): ?>
      <div class="alert alert-success" role="status">Profile picture deleted.</div>
    <?php endif; ?>
    <?php if ($profilePhotoUrl): ?>
      <img class="profile-photo" src="<?php echo htmlspecialchars($profilePhotoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile photo" width="120" height="120" style="object-fit: cover; border-radius: 50%;">
    <?php endif; ?>
    
    <h2>Edit profile</h2>
    <form method="post" action="myaccount.php" enctype="multipart/form-data" class="mb-4">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="action" value="update_profile">
      <div class="form-group"><label for="username">Username</label><input id="username" name="username" class="form-control" maxlength="80" required value="<?php echo htmlspecialchars($_POST['username'] ?? $accountDetails['username'], ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? $accountDetails['email'], ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="form-group"><label for="full_name">Name</label><input id="full_name" name="full_name" class="form-control" maxlength="120" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ($accountDetails['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="form-group"><label for="location">Location</label><input id="location" name="location" class="form-control" maxlength="120" placeholder="e.g. Sydney, Australia" value="<?php echo htmlspecialchars($_POST['location'] ?? ($accountDetails['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="form-group"><label for="profile_photo">Profile photo</label><input id="profile_photo" name="profile_photo" type="file" class="form-control-file" accept="image/jpeg,image/png,image/webp"><small class="form-text text-muted">JPG, PNG, or WebP; maximum 4MB.</small></div>
      <button type="submit" class="btn btn-primary">Save profile</button>
    </form>
    <?php if ($profilePhotoUrl): ?>
      <form method="post" action="myaccount.php" class="mb-4" onsubmit="return confirm('Delete your profile picture?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="delete_profile_photo">
        <button type="submit" class="btn btn-outline-danger">Delete profile picture</button>
      </form>
    <?php endif; ?>
    <h2>Change password</h2>
    <form method="post" action="myaccount.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required></div>
      <div class="form-group"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
      <div class="form-group"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
      <button type="submit" class="btn btn-primary">Change password</button>
    </form>
    <p class="mt-3"><a class="cookbook-link" href="forgot_password.php">Forgot your password?</a></p>
  </div>
  <div class="tab-pane fade <?php echo $activeTab === 'recipes' ? 'show active' : ''; ?>" id="v-pills-recipes" role="tabpanel" aria-labelledby="v-pills-recipes-tab">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><?php echo $accountUser['role'] === 'admin' ? 'All Recipes' : 'My Recipes'; ?></h2>
      <a class="btn btn-primary" href="create-recipe.php">Add recipe</a>
    </div>
    <?php if (!$accountRecipes): ?>
      <p>You have not added any recipes yet.</p>
    <?php else: ?>
      <?php foreach ($accountRecipes as $recipe): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
          <div><strong><?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></strong><br><span class="text-muted"><?php echo htmlspecialchars($recipe['categories'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></div>
          <div class="d-flex align-items-center">
            <a class="btn btn-sm btn-outline-secondary mr-2" href="recipe.php?id=<?php echo (int) $recipe['recipe_id']; ?>">View</a>
            <a class="btn btn-sm btn-outline-primary mr-2" href="edit-recipe.php?id=<?php echo (int) $recipe['recipe_id']; ?>">Edit</a>
            <form method="post" action="delete-recipe.php" onsubmit="return confirm('Delete this recipe and its uploaded media?');">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cookbook_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="recipe_id" value="<?php echo (int) $recipe['recipe_id']; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="tab-pane fade <?php echo $activeTab === 'collections' ? 'show active' : ''; ?>" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>My Collections</h2>
      <a class="btn btn-primary" href="create_collection.php">Create collection</a>
    </div>
    <?php if (!$accountCollections): ?>
      <p class="text-muted">You haven't added any collections yet.</p>
    <?php else: ?>
      <?php foreach ($accountCollections as $collection): ?>
        <div class="border-bottom py-3">
          <h3 class="h5"><?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php if ($collection['description']): ?>
            <p class="text-muted mb-2"><?php echo htmlspecialchars($collection['description'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <p class="mb-0"><?php echo (int) $collection['recipe_count']; ?> <?php echo (int) $collection['recipe_count'] === 1 ? 'recipe' : 'recipes'; ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <a class="btn btn-outline-primary mt-3" href="collections.php">Manage collections</a>
  </div>
</div>
</div>
</div>
</main>

</section>

<!-- footer -->
<?php include "../includes/footer.php"; ?>
