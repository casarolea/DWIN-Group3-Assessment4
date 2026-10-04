<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Privacy Policy';
include __DIR__ . '/../includes/header.php';
?>
<section class="first-block">
  <main class="container my-5 privacy-policy">
    <h1>Privacy Policy</h1>
    <p class="text-muted">Last updated: October 4, 2026</p>
    <p>This notice describes how CookBook handles information in this educational project. The site is designed for recipe browsing and account-based recipe management.</p>

    <h2>Information we store</h2>
    <ul>
      <li>Account details: username, email address, optional name and location, profile photo, and a securely hashed password.</li>
      <li>Password reset requests: a hashed, single-use token with a 30-minute expiry. For local development only, a reset link may be shown to a request from the same computer because email delivery is not configured.</li>
      <li>Content you submit: recipes, ingredients, instructions, recipe photos and videos, collection names, and collection membership.</li>
      <li>Interactions: favorites, ratings, and comments. Comments display the commenter’s username and comment date on the recipe page.</li>
      <li>Session data: a necessary PHP session cookie used to keep you signed in and protect account actions.</li>
    </ul>

    <h2>How information is used</h2>
    <p>Information is used to operate accounts, show and search recipes, manage personal collections, and protect the site. Passwords are stored as hashes; the site does not store the original password.</p>

    <h2>What is public</h2>
    <p>Recipes and their uploaded media are available to site visitors. Comments and the commenter’s username are also visible on recipe pages. Profile details, profile photos, favorites, and personal collections are account data and are not shown as public profile content. Site administrators with database or server access may be able to access stored data.</p>

    <h2>Sharing and external services</h2>
    <p>CookBook does not sell account information. The pages load libraries such as Bootstrap, jQuery, and Popper from third-party content delivery networks. Those providers may receive technical information such as your IP address and browser request when those assets load. Social-media links take you to external services; their own privacy policies apply there.</p>

    <h2>Storage and retention</h2>
    <p>For the local XAMPP setup, account and recipe data are stored in the local MySQL database and uploaded media is stored in the project’s images directory. If the project is hosted, the hosting provider may also process or store this data. Recipes can be deleted from My Recipes, but the site currently has no self-service account or collection deletion feature. Contact the project maintainer through the channel where you received the project to request removal of account data.</p>

    <h2>Your choices</h2>
    <p>While signed in, you can update your account details, change your password, manage your own recipes, and manage your collections. Avoid including sensitive personal information in public recipes or comments.</p>

    <h2>Security</h2>
    <p>The site uses password hashing, session protections, access checks, and request tokens for account-changing forms. No website or local development environment can guarantee absolute security. Do not use a password here that you use for another service.</p>

    <h2>Changes</h2>
    <p>This notice may be updated as the project changes. Before deploying CookBook publicly, the project owner should provide a direct privacy contact and review this notice for the applicable hosting location and privacy laws.</p>
  </main>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
