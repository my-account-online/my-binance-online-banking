<?php
require "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {

        $message = "Please complete all fields.";

    } else {

        $check = $pdo->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $check->execute([$username]);

        if ($check->fetch()) {

            $message = "Username already exists.";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, 'user')"
            );

            $stmt->execute([
                $username,
                $hashedPassword
            ]);

            $message = "Registration successful. You can now log in.";
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

    <title>Create Account | Crypto Circle Trading</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="background-symbol">₿</div>

<div class="login-container">

    <div class="login-box">

        <h2>Create Account</h2>

        <p class="subtitle">
            Crypto Circle Trading
        </p>

        <?php if ($message !== ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label>Username</label>

            <input
                type="text"
                name="username"
                required
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                required
            >

            <button type="submit">
                Create Account
            </button>

        </form>

        <br>

        <a href="index.php"
           style="color:#d6a741;">

            Back to Login

        </a>

    </div>

</div>

</body>
</html>