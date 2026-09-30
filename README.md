# CookBook

CookBook is a PHP and MySQL recipe website. Visitors can browse and search recipes by title, ingredient, or category. Registered users can manage their own recipes and profile, create personal collections, and save recipes to those collections. Administrators can manage all recipes.

## Requirements

- XAMPP with Apache, MySQL, and PHP 8 or newer
- A web browser

The app's database connection currently uses `localhost`, database `cookbook`, username `root`, and an empty password. Update the connection settings in the PHP files if your local MySQL credentials differ.

## Setup with XAMPP

1. Place the project folder in `C:\xampp\htdocs\DWIN\DWIN-Group3-Assessment4`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin` and create a database named `cookbook` using `utf8mb4`.
4. Select the `cookbook` database, choose **Import**, and import `databases/recipe.sql` to create the recipe/category tables and sample recipes.
5. Open the site at `http://localhost/DWIN/DWIN-Group3-Assessment4/pages/index.php`.
6. Register an account at `http://localhost/DWIN/DWIN-Group3-Assessment4/pages/register.php`.

The account, recipe ownership, profile-photo, recipe-media, and personal collection tables/columns are created or migrated automatically by `includes/auth.php` the first time an account-related page connects to the database. Keep the MySQL server running while using the site.

## Accounts and Access

All public registrations receive the `user` role. Users can browse the site, create and manage their own recipes, create personal collections and save recipes to them, edit their profile, upload a profile photo, and change their password. Recipe creation, editing, deletion, collection management, and account changes require a signed-in session.

To make a registered account an administrator:

1. Register the account normally.
2. In phpMyAdmin, open the `cookbook` database and run this query with that account's email:

   ```sql
   UPDATE users
   SET role = 'admin'
   WHERE email = 'admin@example.com';
   ```

3. Sign out and sign back in to load the updated role into the session.

Administrators can manage all recipes. Personal collections remain private to their owners. Never add a public role selector to registration; assign administrator access only through a trusted database account.

## Recipe Media

Recipe creation supports a cover image and up to 10 additional image or video files. Additional files must be JPG, PNG, WebP, MP4, WebM, or MOV and no larger than 20 MB each. Profile photos accept JPG, PNG, or WebP up to 4 MB. Uploaded files are stored under `images/recipes/` and `images/profiles/`.

If uploads are rejected due to request size, raise PHP's `upload_max_filesize` and `post_max_size` settings in `C:\xampp\php\php.ini`, then restart Apache.

## Main Pages

- `pages/index.php`: homepage
- `pages/recipe_page.php`: recipe search, filters, categories, and results
- `pages/recipe.php?id=<recipe_id>`: recipe details and media
- `pages/login.php` and `pages/register.php`: authentication
- `pages/myaccount.php`: profile, password, and recipe management
- `pages/create-recipe.php`: add a recipe while signed in
- `pages/collections.php`: browse collections

## Project Structure

- `databases/`: SQL setup and migration scripts
- `includes/`: shared authentication and recipe media helpers
- `pages/`: PHP pages and account workflows
- `scripts/`: client-side form behavior
- `styles/`: page stylesheets
- `images/`: category art, recipe photos, and uploaded media
- `fonts/`: local fonts
