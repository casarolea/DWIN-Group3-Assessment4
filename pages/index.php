<?php require_once __DIR__ . '/../includes/auth.php';
  $page_title = "Home";
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
<section class = "second-block">
  
</section>
<!-- This is the end of the second-block -->

<!-- This is the start of the third-block -->
<section class = "third-block">
  
</section>
<!-- This is the end of the third-block -->

<?php include "../includes/footer.php"; ?>