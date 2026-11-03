
<?php

session_start();
require_once "config/database.php";

// Only logged-in customers can reserve
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";

// CSRF token protects form submission
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Get the selected car ID
$carId = filter_input(INPUT_GET, "car_id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $carId = filter_input(INPUT_POST, "car_id", FILTER_VALIDATE_INT);
}

if (!$carId || $carId < 1) {
    exit("Invalid car.");
}

// Get car information
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = :id");
$stmt->execute(["id" => $carId]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    http_response_code(404);
    exit("Car not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $startDate = $_POST["start_date"] ?? "";
    $endDate = $_POST["end_date"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    // Validate the security token
    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {

        $message = "Invalid request.";

    } else {

        // Validate date format
        $start = DateTimeImmutable::createFromFormat(
            "!Y-m-d", $startDate
        );

        $end = DateTimeImmutable::createFromFormat(
            "!Y-m-d", $endDate
        );

        $today = new DateTimeImmutable("today");

        if (!$start || !$end ||
            $start->format("Y-m-d") !== $startDate ||
            $end->format("Y-m-d") !== $endDate ||
            $start < $today ||
            $start >= $end) {

            $message = "Please select valid rental dates.";

        } else {

            try {
                // Start transaction to avoid conflicting bookings
                $pdo->beginTransaction();

                // Lock this car while checking availability
                $lock = $pdo->prepare(
                    "SELECT * FROM cars WHERE id = :id FOR UPDATE"
                );
                $lock->execute(["id" => $carId]);
                $lockedCar = $lock->fetch(PDO::FETCH_ASSOC);

                if (!$lockedCar ||
                    $lockedCar["status"] !== "available") {

                    $message = "This car is unavailable.";

                } else {

                    // Check overlapping reservations
                    $check = $pdo->prepare(
                        "SELECT COUNT(*) FROM reservations
                         WHERE car_id = :car_id
                         AND status IN
                             ('pending', 'confirmed', 'ongoing')
                         AND start_date < :end_date
                         AND end_date > :start_date"
                    );

                    $check->execute([
                        "car_id" => $carId,
                        "start_date" => $startDate,
                        "end_date" => $endDate
                    ]);

                    if ($check->fetchColumn() > 0) {

                        $message = "Car already booked for these dates.";

                    } else {

                        // Calculate rental duration and price
                        $days = $start->diff($end)->days;
                        $total = $days * $lockedCar["price_per_day"];

                        // Save reservation
                        $insert = $pdo->prepare(
                            "INSERT INTO reservations
                            (user_id, car_id, start_date,
                             end_date, total_price, status)
                            VALUES
                            (:user_id, :car_id, :start_date,
                             :end_date, :total_price, 'pending')"
                        );

                        $insert->execute([
                            "user_id" => $_SESSION["user_id"],
                            "car_id" => $carId,
                            "start_date" => $startDate,
                            "end_date" => $endDate,
                            "total_price" => $total
                        ]);

                        $message = "Reservation created successfully! "
                                 . "Total: " . $total . " DH.";

                        // Prevent reusing the submitted token
                        $_SESSION["csrf_token"] = bin2hex(
                            random_bytes(32)
                        );
                    }
                }

                $pdo->commit();

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());
                $message = "Reservation failed. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Book a Car</title>
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

<section class="car-details">

    <h1>Book Your Car</h1>

    <h2>
        <?= htmlspecialchars($car["brand"]) ?>
        <?= htmlspecialchars($car["model"]) ?>
    </h2>

    <p>
        Price:
        <?= htmlspecialchars($car["price_per_day"]) ?> DH/day
    </p>

    <p><?= htmlspecialchars($message) ?></p>

    <?php if ($car["status"] === "available"): ?>

        <form method="POST">

            <input type="hidden"
                   name="car_id"
                   value="<?= (int)$car["id"] ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <label>Pick-up date</label>
            <input type="date" name="start_date" required>

            <br><br>

            <label>Return date</label>
            <input type="date" name="end_date" required>

            <br><br>

            <button type="submit" class="btn">
                Confirm Reservation
            </button>

        </form>

    <?php else: ?>

        <p>This car is currently unavailable.</p>

    <?php endif; ?>

</section>

</body>
</html>
