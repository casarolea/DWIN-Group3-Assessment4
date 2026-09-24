<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CookBook | Home</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<header>
    <a href="index.php" class="logo-link">
        <img src="images/cookbooklogo2.png"
             alt="CookBook Logo"
             title="CookBook Logo"
             class="logo">
        <h1>CookBook</h1>
    </a>

            <li><a href="recipes.php">Recipes</a></li>
            <li><a href="collection.php">Collection</a></li>

    <form action="search.php" method="get" class="search-bar">
        <input type="text"
               name="query"
               placeholder="Search recipes..."
               aria-label="Search">
        <button type="submit">Search</button>
    </form>

            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
</header>


<div class="container">
    <main>
        <img src="images/Test1.jpg"
        alt="Test1"
        title="Test1">

        <h2>Improve Your Cooking Experience.</h2>
        <h3>Different recipes available from different individuals around the world!</h3>

        <li><a href="recipes.php"> View Recipes</a></li>

        <hr>
        <h2>POPULAR RECIPES</h2>
            <div class="popular-grid">
            <div class="recipe-card">
                <img src="images/recipe1.jpg" alt="Creamy Garlic Pasta" title="Creamy Garlic Pasta">
                <h3>Creamy Garlic Pasta</h3>
            </div>
            <div class="recipe-card">
                <img src="images/recipe2.jpg" alt="Spicy Chicken Tacos" title="Spicy Chicken Tacos">
                <h3>Spicy Chicken Tacos</h3>
            </div>
            <div class="recipe-card">
                <img src="images/recipe3.jpg" alt="Berry Almond Smoothie" title="Berry Almond Smoothie">
                <h3>Berry Almond Smoothie</h3>
            </div>
            <div class="recipe-card">
                <img src="images/recipe4.jpg" alt="Chocolate Lava Cake" title="Chocolate Lava Cake">
                <h3>Chocolate Lava Cake</h3>
            </div>
        </div>

        <hr>
        <h2>Browser Recipes Categories</h2>
                <div class="category-grid">
            <a href="breakfast.php" class="category-card">
                <img src="images/category-breakfast.jpg" alt="Breakfast" title="Breakfast Recipes">
                <h3>Breakfast</h3>
                <p>Start your day right</p>
            </a>
            <a href="lunch.php" class="category-card">
                <img src="images/category-lunch.jpg" alt="Lunch" title="Lunch Recipes">
                <h3>Lunch</h3>
                <p>Midday fuel</p>
            </a>
            <a href="dinner.php" class="category-card">
                <img src="images/category-dinner.jpg" alt="Dinner" title="Dinner Recipes">
                <h3>Dinner</h3>
                <p>Evening favourites</p>
            </a>
        </div>

        <hr>
        <h2>Meet The Developers</h2>
        <p>Joewiey Franzine Ibanez</p>
        <p>Sheirina Glee Nadera</p>
        <p>Regil Maharjan</p>
        <p>Chauncey Ariel Nieto</p>
        <p>Cassandra Noeribelle Dejucos</p>
    </main>

</div>

<footer>
    <img src="images/cookbooklogo2.png"
             alt="CookBook Logo"
             title="CookBook Logo"
             class="logo">
        <h3>CookBook</h3>
        <p>Improve The Cooking Experience</p>
            <p>
                <a href="https://www.instagram.com"
                target="_blank">
                Instagram Profile
            </a>
        </p>

         <p>
            <a href="https://www.facebook.com"
            target="_blank">
            Facebook Profile
            </a>
        </p>     

            <p>
                <a href="https://www.tiktok.com"
                target="_blank">
                TikTok Profile
            </a>
        </p>

         <p>
            <a href="https://www.youtube.com"
            target="_blank">
            YouTube Profile
            </a>
        </p>

    <h3>Useful Links</h3>
    <ul>
    <li><a href="favourite.php">Favourite Recipes</a></li>
    <li><a href="about.html">About Us</a></li>
    <li><a href="contact.php">Contact Us</a></li>
    </ul>

    <h3>Group Information</h3>
        <p>Joewiey Franzine Ibanez - K231663</p>
        <p>Sheirina Glee Nadera - K240664</p>
        <p>Regil Maharjan - K240722</p>
        <p>Chauncey Ariel Nieto - K240938</p>
        <p>Cassandra Noeribelle Dejucos - K240945</p>    

    <p>This website was created for the final assessment for DWIN309 at Kent Institute Australia - Trimester 2, 2026</p>
    <p>© CookBook 2026</p>

    
</footer>

</body>
</html>
