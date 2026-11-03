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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Cars</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>

    <div class="logo">CarRental</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="cars.php">Cars</a>
    </nav>

</header>


<section class="cars-section">

    <h1>Our Cars</h1>
    <form method="GET" class="filter-form">

    <input
        type="text"
        name="search"
        placeholder="Search brand or model"
        value="<?= htmlspecialchars($search) ?>"
    >

    <select name="category">
        <option value="">All categories</option>

        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat["id"] ?>"
                <?= (string)$category === (string)$cat["id"] ? "selected" : "" ?>>
                <?= htmlspecialchars($cat["name"]) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input
        type="number"
        name="max_price"
        min="0"
        step="0.01"
        placeholder="Max price (DH/day)"
        value="<?= htmlspecialchars((string)$maxPrice) ?>"
    >

    <button type="submit" class="btn">Search</button>

    <a href="cars.php">Reset</a>

</form>

    <div class="cars-container">

        <?php foreach ($cars as $car): ?>

            <div class="car-card">

                <h2>
                    <?= htmlspecialchars($car["brand"]) ?>
                    <?= htmlspecialchars($car["model"]) ?>
                </h2>

                <p>
                    Category:
                    <?= htmlspecialchars($car["category_name"]) ?>
                </p>

                <p>
                    Year:
                    <?= $car["year"] ?>
                </p>

                <p>
                    <?= htmlspecialchars($car["description"]) ?>
                </p>

                <p>
                    <strong>
                        <?= $car["price_per_day"] ?> DH / day
                    </strong>
                </p>

                <p>
                    Status:
                    <?= htmlspecialchars($car["status"]) ?>
                </p>

            </div>

        <?php endforeach; ?>

    </div>

</section>

</body>
</html>