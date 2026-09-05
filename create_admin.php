<?php

require "db.php";

$username = "admin";
$password = "MyNewAdminPassword123";

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {

    $check = $pdo->prepare(
        "SELECT id FROM users WHERE username = ?"
    );

    $check->execute([$username]);

    $existingUser = $check->fetch();

    if ($existingUser) {

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?, role = 'admin'
            WHERE username = ?
        ");

        $stmt->execute([
            $hashedPassword,
            $username
        ]);

        echo "Admin password reset successfully.";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO users
            (username, password, role)
            VALUES (?, ?, 'admin')
        ");

        $stmt->execute([
            $username,
            $hashedPassword
        ]);

        echo "Admin account created successfully.";

    }

} catch (PDOException $e) {

    echo "Database error: " .
        $e->getMessage();

}