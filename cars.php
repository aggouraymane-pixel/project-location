
<?php

session_start();

require_once "config/database.php";

// Get search values from URL
$search = trim($_GET["search"] ?? "");
$category = $_GET["category"] ?? "";
$maxPrice = $_GET["max_price"] ?? "";

// Get all categories
$categories = $pdo->query(
    "SELECT * FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Base SQL query
$sql = "SELECT cars.*, categories.name AS category_name
        FROM cars
        INNER JOIN categories
        ON cars.category_id = categories.id
        WHERE 1=1";

$params = [];

// Search by brand or model
if ($search !== "") {
    $sql .= " AND (cars.brand LIKE :search_brand
               OR cars.model LIKE :search_model)";

    $params["search_brand"] = "%$search%";
    $params["search_model"] = "%$search%";
}

// Filter by category
if (ctype_digit((string)$category) && (int)$category > 0) {
    $sql .= " AND cars.category_id = :category";
    $params["category"] = (int)$category;
}

// Filter by price
if ($maxPrice !== "" && is_numeric($maxPrice) && $maxPrice >= 0) {
    $sql .= " AND cars.price_per_day <= :max_price";
    $params["max_price"] = $maxPrice;
}

$sql .= " ORDER BY cars.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Our Cars - CarRental</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>

    <div class="logo">CarRental</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="cars.php">Cars</a>

        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>

</header>

<section class="cars-section">

    <h1>Our Cars</h1>

    <!-- Search and filter form -->
    <form method="GET" class="filter-form">

        <input
            type="text"
            name="search"
            placeholder="Search brand or model"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >

        <select name="category">

            <option value="">All categories</option>

            <?php foreach ($categories as $cat): ?>

                <option
                    value="<?= (int)$cat["id"] ?>"
                    <?= (string)$category === (string)$cat["id"] ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($cat["name"], ENT_QUOTES, 'UTF-8') ?>
                </option>

            <?php endforeach; ?>

        </select>

        <input
            type="number"
            name="max_price"
            min="0"
            step="0.01"
            placeholder="Max price (DH/day)"
            value="<?= htmlspecialchars((string)$maxPrice, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit" class="btn">
            Search
        </button>

        <a href="cars.php">Reset</a>

    </form>

    <!-- Cars catalogue -->
    <div class="cars-container">

        <?php if (empty($cars)): ?>

            <p>No cars found.</p>

        <?php else: ?>

            <?php foreach ($cars as $car): ?>

                <div class="car-card">

                    <?php
                    // Use the image stored in MySQL
                    $imagePath = !empty($car["image"])
                        ? $car["image"]
                        : "assets/images/cars/default-car.jpg";
                    ?>

                    <!-- Car picture -->
                    <img
                        src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($car["brand"] . " " . $car["model"], ENT_QUOTES, 'UTF-8') ?>"
                        class="car-image"
                        loading="lazy"
                    >

                    <h2>
                        <?= htmlspecialchars($car["brand"], ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($car["model"], ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <p>
                        Category:
                        <?= htmlspecialchars($car["category_name"], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <p>
                        Year:
                        <?= (int)$car["year"] ?>
                    </p>

                    <p>
                        <?= htmlspecialchars($car["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <p>
                        <strong>
                            <?= htmlspecialchars($car["price_per_day"], ENT_QUOTES, 'UTF-8') ?>
                            DH / day
                        </strong>
                    </p>

                    <p>
                        Status:
                        <?= htmlspecialchars($car["status"], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <a
                        href="car-details.php?id=<?= (int)$car["id"] ?>"
                        class="btn"
                    >
                        View Details
                    </a>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

</body>
</html>
