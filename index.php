<?php

session_start();

require_once "config/database.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Car Rental</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>

    <div class="logo">
        CarRental
    </div>

    <nav>

        <a href="index.php">Home</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <span>
                Welcome <?= htmlspecialchars($_SESSION["name"]) ?>
            </span>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>
            <a href="register.php">Register</a>

        <?php endif; ?>

    </nav>

</header>


<section class="hero">

    <h1>Find your perfect car</h1>

    <p>
        Simple, fast and secure car rental.
    </p>

  <a href="cars.php" class="btn">
        Explore Cars
    </a>

</section>

</body>
</html>