<?php

session_start();

require_once "config/database.php";

// Get car ID from URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

// Check if ID is valid
if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid car ID.");
}

// Get car details from database
$sql = "SELECT cars.*, categories.name AS category_name
        FROM cars
        INNER JOIN categories
        ON cars.category_id = categories.id
        WHERE cars.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $id]);

$car = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the car exists
if (!$car) {
    http_response_code(404);
    exit("Car not found.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">CarRental</div>
    <nav>
        <?php if (isset($_SESSION["user_id"])): ?>
    <a href="my-reservations.php">My Reservations</a>
<?php endif; ?>
        <a href="index.php">Home</a>
        <a href="cars.php">Cars</a>
    </nav>
</header>

<section class="car-details">
<?php
// Get the car image from MySQL
$imagePath = !empty($car["image"])
    ? $car["image"]
    : "assets/images/cars/default-car.jpg";
?>

<img
    src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
    alt="<?= htmlspecialchars($car["brand"] . " " . $car["model"], ENT_QUOTES, 'UTF-8') ?>"
    class="detail-car-image"
>
    <h1>
        <?= htmlspecialchars($car["brand"]) ?>
        <?= htmlspecialchars($car["model"]) ?>
    </h1>

    <p>Category:
        <?= htmlspecialchars($car["category_name"]) ?>
    </p>

    <p>Year: <?= (int)$car["year"] ?></p>

    <p>
        Description:
        <?= htmlspecialchars($car["description"] ?? "") ?>
    </p>

    <p>
        Price:
        <strong>
            <?= htmlspecialchars($car["price_per_day"]) ?> DH/day
        </strong>
    </p>

    <p>
        Status:
        <?= htmlspecialchars($car["status"]) ?>
    </p>

    <?php if ($car["status"] === "available"): ?>

        <a href="reservation.php?car_id=<?= (int)$car["id"] ?>"
           class="btn">
            Book this car
        </a>

    <?php else: ?>

        <p>This car is currently unavailable.</p>

    <?php endif; ?>

</section>

</body>
</html>