<?php

// Connect to the authentication functions
require_once __DIR__ . '/../includes/auth.php';

// Only users with the "admin" role can access this page
cookbook_require_role('admin');

// Page title
$page_title = "Admin Dashboard";

// Include navbar/header
include __DIR__ . '/../includes/header.php';

?>

<!-- This is the start of the first-block -->
<section class="first-block">

    <h1 class="ralewayextrabold">
        Admin Dashboard
    </h1>

</section>
<!-- This is the end of the first-block -->


<!-- This is the start of the second-block -->
<section class="second-block">

</section>
<!-- This is the end of the second-block -->


<!-- This is the start of the third-block -->
<section class="third-block">

</section>
<!-- This is the end of the third-block -->


<?php

// Include footer
include __DIR__ . '/../includes/footer.php';

?>