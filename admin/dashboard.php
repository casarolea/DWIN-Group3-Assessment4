<?php require_once __DIR__ . '/../includes/auth.php'; ?>

<?php $page_title = "Admin Dashboard";
include "../includes/header.php"; ?>

<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/index.php");
    exit();
}

?>

<!-- This is the start of the first-block -->
<section class = "first-block">
  
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