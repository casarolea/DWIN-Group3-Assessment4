<!-- start of footer -->

 <!-- Footer -->
 <footer class="text-center text-lg-start bg-body-tertiary text-muted footer">

<!-- Section: Links  -->
<section class="">
  <div class="container text-center mt-5">
    <!-- Grid row -->
    <div class="row mt-3">
      <!-- Grid column -->
      <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
        <!-- Content -->
        <h6 class="fw-bold mb-4">
        <a class="fas fa-gem me-3 gochihand footer-text1" href="index.php"><img class="logo" src="../images/cblogo2.png" alt="CookBook Logo" title="CookBook logo">CookBook</a>
        </h6>
        <p class="footer-text2 ralewaybold">
          Improve the cooking experience.
        </p>
        <hr>
        <p>
          <a href="#" class="footer-links ralewaybold footer-text2">Instagram</a>
          <a href="#" class="footer-links ralewaybold footer-text2">Facebook</a>
          <a href="#" class="footer-links ralewaybold footer-text2">Tiktok</a>
          <a href="#" class="footer-links ralewaybold footer-text2">YouTube</a>
        </p>
      </div>
      <!-- Grid column -->


      <!-- Grid column -->
      <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
        <!-- Links -->
        <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">
          Quick links
        </h6>
        <p>
          <a href="index.php" class="footer-links ralewaybold">Home</a>
        </p>
        <p>
          <a href="recipes.php" class="footer-links ralewaybold">Recipes</a>
        </p>
        <p>
          <a href="mycollections.php" class="footer-links ralewaybold">Collections</a>
        </p>
      </div>
      <!-- Grid column -->

      <!-- Grid column -->
      <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
        <!-- Links -->
        <h6 class="text-uppercase ralewayextrabold footer-text1 mb-4">GROUP INFORMATION</h6>
        <p>
          <a class="footer-links ralewaybold footer-text2">Joewiey Franzine Ibanez - K231663</a>
        </p>
        <p>
          <a class="footer-links ralewaybold footer-text2">Sheirina Glee Nadera - K240664</a>
        </p>
        <p>
          <a class="footer-links ralewaybold footer-text2">Regil Maharjan - K240722</a>
        </p>
        <p>
          <a class="footer-links ralewaybold footer-text2">Chauncey Ariel Nieto - K240938</a>
        </p>
        <p>
          <a class="footer-links ralewaybold footer-text2">Cassandra Noeribelle Dejucos - K240945</a>
        </p>
      </div>
      <!-- Grid column -->
    </div>
    <!-- Grid row -->
  </div>
</section>
<!-- Section: Links  -->

<!-- Copyright -->
<div class="text-center p-4">
<p class ="ralewaybold footer-text2">This website was created for the final assessment for DWIN309 at Kent Institute Australia - Trimester 2, 2026</p>
  <p class ="ralewaybold footer-text2">&copy; CookBook 2026. All rights reserved.</p>
</div>
<!-- Copyright -->
</footer>
<!-- Footer -->
<!-- end of footer -->

<!-- Bootstrap Javascript -->

<!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<!-- Page-specific JavaScript -->
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
