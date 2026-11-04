
<?php

session_start();

require_once "config/database.php";

// Only logged-in users can access this page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Get reservations belonging to the logged-in user
$sql = "SELECT
            reservations.id,
            reservations.start_date,
            reservations.end_date,
            reservations.total_price,
            reservations.status,
            cars.brand,
            cars.model,
            cars.image
        FROM reservations
        INNER JOIN cars
            ON reservations.car_id = cars.id
        WHERE reservations.user_id = :user_id
        ORDER BY reservations.created_at DESC,
                 reservations.id DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "user_id" => $_SESSION["user_id"]
]);

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Reservations - CarRental</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">CarRental</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="cars.php">Cars</a>
        <a href="my-reservations.php">My Reservations</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="reservations-section">

    <h1>My Reservations</h1>

    <?php if (empty($reservations)): ?>

        <p>You have no reservations yet.</p>

        <a href="cars.php" class="btn">Explore Cars</a>

    <?php else: ?>

        <div class="reservations-container">

            <?php foreach ($reservations as $reservation): ?>

                <?php
                // Choose the reservation's car image
                $imagePath = !empty($reservation["image"])
                    ? $reservation["image"]
                    : "assets/images/cars/default-car.jpg";
                ?>

                <div class="reservation-card">

                    <img
                        src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars(
                            $reservation["brand"] . " " .
                            $reservation["model"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="reservation-image"
                    >

                    <div class="reservation-info">

                        <h2>
                            <?= htmlspecialchars(
                                $reservation["brand"] . " " .
                                $reservation["model"],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <p>
                            Booking #<?= (int)$reservation["id"] ?>
                        </p>

                        <p>
                            From:
                            <?= htmlspecialchars($reservation["start_date"]) ?>
                        </p>

                        <p>
                            To:
                            <?= htmlspecialchars($reservation["end_date"]) ?>
                        </p>

                        <p>
                            Total:
                            <strong>
                                <?= htmlspecialchars(
                                    $reservation["total_price"]
                                ) ?> DH
                            </strong>
                        </p>

                        <p>
                            Status:
                            <strong>
                                <?= htmlspecialchars(
                                    $reservation["status"]
                                ) ?>
                            </strong>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</body>
</html>
