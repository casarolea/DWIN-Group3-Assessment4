<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = "Home";
$extra_css = ["../styles/recipe_page.css"];
include "../includes/header.php";
?>

<!-- This is the start of the first-block -->
<section class="first-block">
  <!-- Added standard Bootstrap 4 margin-bottom (mb-4) and a custom modifier class -->
  <div class="jumbotron custom-jumbotron mb-4">
    <div class="container-fluid">
      <div class="row align-items-center">
        
        <!-- Left Side: Image Column (Renders first) -->
        <div class="col-md-6 text-center">
          <img src="../images/homepagefood.jpg" class="img-fluid rounded" alt="Cooking experience">
        </div>

        <!-- Right Side: Your Content Column -->
        <div class="col-md-6 text-side">
          <h1 class="homepage-title">Improve Your Cooking Experience.</h1>
          <hr class="title-divider">
          <p class="subtitle-text">Different recipes available from different individuals around the world!</p>
          <p class="lead mb-0">
            <!-- Swapped btn-primary for a custom outline class -->
            <a class="btn custom-btn btn-lg" href="recipe_page.php" role="button">View Recipes</a>
          </p>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- This is the end of the first-block -->

<!-- This is the start of the second-block -->
<section class="second-block">

    <h2 class="category-title text-center">
        BROWSE RECIPE CATEGORIES
    </h2>

    <div class="container">

        <div class="row justify-content-center">

            <!-- Breakfast -->
            <div class="col-md-4 text-center category-card">

                <a href="recipe_page.php?category=Breakfast">
                    <img
                        class="category-img"
                        src="../images/breakfast_recipe_page.jpg"
                        alt="Breakfast">
                </a>

                <h3 class="meal-name">Breakfast</h3>

            </div>


            <!-- Lunch -->
            <div class="col-md-4 text-center category-card">

                <a href="recipe_page.php?category=Lunch">
                    <img
                        class="category-img"
                        src="../images/lunch_recipe_page.jpg"
                        alt="Lunch">
                </a>

                <h3 class="meal-name">Lunch</h3>

            </div>


            <!-- Dinner -->
            <div class="col-md-4 text-center category-card">

                <a href="recipe_page.php?category=Dinner">
                    <img
                        class="category-img"
                        src="../images/dinner_recipe_page.jpg"
                        alt="Dinner">
                </a>

                <h3 class="meal-name">Dinner</h3>

            </div>


            <!-- Dessert -->
            <div class="col-md-4 text-center category-card">

                <a href="recipe_page.php?category=Dessert">
                    <img
                        class="category-img"
                        src="../images/dessert_recipe_page.jpg"
                        alt="Dessert">
                </a>

                <h3 class="meal-name">Dessert</h3>

            </div>


            <!-- Snack -->
            <div class="col-md-4 text-center category-card">

                <a href="recipe_page.php?category=Snack">
                    <img
                        class="category-img"
                        src="../images/snack_recipe_page.jpg"
                        alt="Snack">
                </a>

                <h3 class="meal-name">Snack</h3>

            </div>

        </div>

    </div>

</section>
<!-- This is the end of the second-block -->

<!-- This is the start of the third-block -->
<section class = "third-block">
  <div class="container-fluid">

    <h2 class="developers-title">MEET THE DEVELOPERS</h2>

    <div class="developers-container">

      <!-- Joewiey -->
      <div class="developer-card">
        <button class="developer-button"
                type="button"
                data-toggle="collapse"
                data-target="#joewieyContributions"
                aria-expanded="false"
                aria-controls="joewieyContributions">
          Joewiey Franzine Ibanez
        </button>

        <div class="collapse contribution-box" id="joewieyContributions">
          <div class="contribution-content">
            <h4>Contributions</h4>
            <ul>
              <li>My Account Page</li>
              <li>Login/Logout Functions</li>
              <li>Update Profile</li>
            </ul>
          </div>
        </div>
      </div>


      <!-- Sheirina -->
      <div class="developer-card">
        <button class="developer-button"
                type="button"
                data-toggle="collapse"
                data-target="#sheirinaContributions"
                aria-expanded="false"
                aria-controls="sheirinaContributions">
          Sheirina Glee Nadera
        </button>

        <div class="collapse contribution-box" id="sheirinaContributions">
          <div class="contribution-content">
            <h4>Contributions</h4>
            <ul>
              <li>Recipe Management</li>
              <li>Create, Edit, Add Recipes</li>
            </ul>
          </div>
        </div>
      </div>


      <!-- Regil -->
      <div class="developer-card">
        <button class="developer-button"
                type="button"
                data-toggle="collapse"
                data-target="#regilContributions"
                aria-expanded="false"
                aria-controls="regilContributions">
          Regil Maharjan
        </button>

        <div class="collapse contribution-box" id="regilContributions">
          <div class="contribution-content">
            <h4>Contributions</h4>
            <ul>
              <li>Search and Filter Functions</li>
              <li>Recipes Page</li>
            </ul>
          </div>
        </div>
      </div>


      <!-- Chauncey -->
      <div class="developer-card">
        <button class="developer-button"
                type="button"
                data-toggle="collapse"
                data-target="#chaunceyContributions"
                aria-expanded="false"
                aria-controls="chaunceyContributions">
          Chauncey Ariel Nieto
        </button>

        <div class="collapse contribution-box" id="chaunceyContributions">
          <div class="contribution-content">
            <h4>Contributions</h4>
            <ul>
              <li>Admin Dashboard</li>
              <li>Recipes Page</li>
            </ul>
          </div>
        </div>
      </div>


      <!-- Cassandra -->
      <div class="developer-card">
        <button class="developer-button"
                type="button"
                data-toggle="collapse"
                data-target="#cassandraContributions"
                aria-expanded="false"
                aria-controls="cassandraContributions">
          Cassandra Noeribelle Dejucos
        </button>

        <div class="collapse contribution-box" id="cassandraContributions">
          <div class="contribution-content">
            <h4>Contributions</h4>
            <ul>
              <li>Website Design: Logo and Color Palette</li>
              <li>Navbar and Footer</li>
              <li>Home Page</li>
            </ul>
          </div>
        </div>
      </div>

  </div>

  </div>
</section>
<!-- This is the end of the third-block -->

<!-- footer -->
<?php include "../includes/footer.php"; ?>