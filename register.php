<?php

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($name) || empty($email) || empty($password)) {

        $message = "All fields are required.";

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password)
                VALUES (:name, :email, :password)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            "name" => $name,
            "email" => $email,
            "password" => $hashedPassword
        ]);

        $message = "Account created successfully.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>

<body>

<h1>Create an account</h1>

<p><?= htmlspecialchars($message) ?></p>

<form method="POST">

    <label>Name</label><br>
    <input type="text" name="name" required>

    <br><br>

    <label>Email</label><br>
    <input type="email" name="email" required>

    <br><br>

    <label>Password</label><br>
    <input type="password" name="password" required>

    <br><br>

    <button type="submit">Register</button>

</form>

</body>
</html>