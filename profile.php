<?php

session_start();

require "db.php";


/* =====================================
   CHECK LOGIN
===================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit;

}


/* =====================================
   PREVENT ADMIN FROM OPENING USER PROFILE
===================================== */

if (
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "admin"
) {

    header("Location: admin.php");
    exit;

}


$userId = (int) $_SESSION["user_id"];


/* =====================================
   VARIABLES
===================================== */

$message = "";
$messageType = "";

$account = null;


/* =====================================
   GET USER INFORMATION
===================================== */

try {

    $userStmt = $pdo->prepare("
        SELECT
            id,
            username,
            created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([$userId]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {

    $user = false;

}


if (!$user) {

    session_destroy();

    header("Location: index.php");
    exit;

}


/* =====================================
   UPDATE PROFILE
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {

    $username =
        trim($_POST["username"] ?? "");


    if ($username === "") {

        $message =
            "Username cannot be empty.";

        $messageType =
            "error";

    } elseif (strlen($username) < 3) {

        $message =
            "Username must contain at least 3 characters.";

        $messageType =
            "error";

    } elseif (strlen($username) > 50) {

        $message =
            "Username is too long.";

        $messageType =
            "error";

    } else {

        try {

            /* Check username */

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1
            ");

            $checkStmt->execute([
                $username,
                $userId
            ]);

            $existingUser =
                $checkStmt->fetch(PDO::FETCH_ASSOC);


            if ($existingUser) {

                $message =
                    "That username is already in use.";

                $messageType =
                    "error";

            } else {

                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET username = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $username,
                    $userId
                ]);


                $_SESSION["username"] =
                    $username;


                $user["username"] =
                    $username;


                $message =
                    "Profile updated successfully.";

                $messageType =
                    "success";

            }

        } catch (Exception $e) {

            $message =
                "Unable to update your profile.";

            $messageType =
                "error";

        }

    }

}


/* =====================================
   GET ACCOUNT INFORMATION
===================================== */

try {

    $accountStmt = $pdo->prepare("
        SELECT
            account_number,
            account_type,
            account_status,
            balance
        FROM accounts
        WHERE user_id = ?
        LIMIT 1
    ");

    $accountStmt->execute([$userId]);

    $account =
        $accountStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {

    $account = null;

}


/* =====================================
   ACCOUNT VALUES
===================================== */

$accountNumber =
    $account["account_number"]
    ?? "Not Available";


$accountType =
    $account["account_type"]
    ?? "Trading Account";


$accountStatus =
    $account["account_status"]
    ?? "Inactive";


$balance =
    (float) (
        $account["balance"]
        ?? 0
    );


$createdAt =
    $user["created_at"]
    ?? "";


/* =====================================
   PROFILE INITIAL
===================================== */

$profileInitial = "U";


if (!empty($user["username"])) {

    $profileInitial =
        strtoupper(
            substr(
                $user["username"],
                0,
                1
            )
        );

}


/* =====================================
   MEMBER DATE
===================================== */

$memberSince =
    "Not Available";


if (!empty($createdAt)) {

    $timestamp =
        strtotime($createdAt);


    if ($timestamp !== false) {

        $memberSince =
            date(
                "F j, Y",
                $timestamp
            );

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>
    My Profile | Crypto Circle Trading
</title>

<style>

/* =====================================
   RESET
===================================== */

* {
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:

        radial-gradient(
            circle at 90% 10%,
            rgba(240, 185, 11, .12),
            transparent 30%
        ),

        #0b0e11;

    color: #ffffff;

    overflow-x: hidden;

}


/* =====================================
   HEADER
===================================== */

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    padding: 18px 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

}


.brand {

    display: flex;

    align-items: center;

    gap: 13px;

    min-width: 0;

}


.logo {

    width: 52px;

    height: 52px;

    min-width: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f0b90b;

    color: #111111;

    border-radius: 12px;

    font-size: 26px;

    font-weight: bold;

}


.brand h1 {

    margin: 0 0 5px;

    font-size: 21px;

    line-height: 1.2;

}


.brand p {

    margin: 0;

    color: #848e9c;

    font-size: 13px;

}


/* =====================================
   USER AREA
===================================== */

.user-area {

    display: flex;

    align-items: center;

    gap: 18px;

    flex-shrink: 0;

}


.user-name {

    text-align: right;

}


.user-name strong {

    display: block;

    font-size: 14px;

}


.user-name span {

    display: block;

    margin-top: 3px;

    color: #848e9c;

    font-size: 12px;

}


.logout {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 42px;

    padding: 11px 18px;

    background: #f0b90b;

    color: #111111;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

    transition: .2s ease;

}


.logout:hover {

    background: #ffd84d;

}


/* =====================================
   NAVIGATION
===================================== */

.navigation {

    display: flex;

    align-items: center;

    overflow-x: auto;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

    scrollbar-width: thin;

    -webkit-overflow-scrolling: touch;

}


.navigation::-webkit-scrollbar {

    height: 4px;

}


.navigation::-webkit-scrollbar-thumb {

    background: #2b3139;

    border-radius: 10px;

}


.navigation a {

    display: block;

    padding: 18px 20px;

    color: #848e9c;

    text-decoration: none;

    white-space: nowrap;

    font-weight: bold;

    font-size: 14px;

    border-bottom: 2px solid transparent;

    transition: .2s ease;

}


.navigation a:hover {

    color: #f0b90b;

}


.navigation .active {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, .06);

    border-bottom-color: #f0b90b;

}


/* =====================================
   MAIN
===================================== */

.container {

    width: 100%;

    max-width: 1050px;

    margin: auto;

    padding: 45px 25px 55px;

}


.page-title {

    margin-bottom: 30px;

}


.page-title h2 {

    margin: 0 0 10px;

    font-size: 32px;

}


.page-title p {

    margin: 0;

    color: #848e9c;

    font-size: 15px;

    line-height: 1.5;

}


/* =====================================
   PROFILE GRID
===================================== */

.profile-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 25px;

}


/* =====================================
   CARDS
===================================== */

.card {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

    padding: 28px;

}


.card h3 {

    margin: 0 0 22px;

    color: #f0b90b;

    font-size: 18px;

}


/* =====================================
   AVATAR
===================================== */

.avatar {

    width: 82px;

    height: 82px;

    margin-bottom: 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f0b90b;

    color: #111111;

    border-radius: 50%;

    font-size: 34px;

    font-weight: bold;

}


/* =====================================
   INFORMATION ROWS
===================================== */

.info-row {

    padding: 16px 0;

    border-bottom:
        1px solid #2b3139;

}


.info-row:last-child {

    border-bottom: none;

}


.info-label {

    display: block;

    margin-bottom: 7px;

    color: #848e9c;

    font-size: 13px;

}


.info-row strong {

    display: block;

    word-break: break-word;

    font-size: 14px;

}


/* =====================================
   BALANCE
===================================== */

.balance-box {

    margin-top: 24px;

    padding: 22px;

    background:

        linear-gradient(
            135deg,
            #20242c,
            #181a20
        );

    border:
        1px solid #343a43;

    border-radius: 10px;

}


.balance-box span {

    color: #848e9c;

    font-size: 13px;

}


.balance-box h2 {

    margin: 10px 0 0;

    color: #f0b90b;

    font-size: 30px;

    word-break: break-word;

}


/* =====================================
   FORM
===================================== */

.form-group {

    margin-bottom: 20px;

}


.form-group label {

    display: block;

    margin-bottom: 9px;

    color: #d1d4dc;

    font-size: 14px;

    font-weight: bold;

}


.form-group input {

    width: 100%;

    min-height: 48px;

    padding: 14px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 7px;

    color: #ffffff;

    font-size: 15px;

    outline: none;

}


.form-group input:focus {

    border-color: #f0b90b;

}


.save-button {

    width: 100%;

    min-height: 48px;

    padding: 14px;

    border: none;

    border-radius: 8px;

    background: #f0b90b;

    color: #111111;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    transition: .2s ease;

}


.save-button:hover {

    background: #ffd84d;

}


/* =====================================
   ALERTS
===================================== */

.message {

    padding: 16px;

    margin-bottom: 25px;

    border-radius: 8px;

    font-size: 14px;

    line-height: 1.5;

}


.success {

    background:
        rgba(14, 203, 129, .12);

    border:
        1px solid #0ecb81;

    color: #0ecb81;

}


.error {

    background:
        rgba(246, 70, 93, .12);

    border:
        1px solid #f6465d;

    color: #f6465d;

}


/* =====================================
   PROFILE NOTE
===================================== */

.profile-note {

    margin-top: 20px;

    padding: 15px;

    background:
        rgba(240, 185, 11, .07);

    border-left:
        4px solid #f0b90b;

    border-radius: 6px;

    color: #a9b0bc;

    line-height: 1.6;

    font-size: 13px;

}


/* =====================================
   FOOTER
===================================== */

footer {

    padding: 30px 15px;

    text-align: center;

    color: #848e9c;

    font-size: 13px;

}


/* =====================================
   TABLET
===================================== */

@media (max-width: 1000px) {

    .header {

        padding-left: 4%;

        padding-right: 4%;

    }


    .navigation {

        padding-left: 4%;

        padding-right: 4%;

    }


    .container {

        padding-top: 35px;

    }

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 750px) {

    .header {

        flex-direction: column;

        align-items: stretch;

        padding: 18px 15px;

        gap: 18px;

    }


    .brand h1 {

        font-size: 18px;

    }


    .brand p {

        font-size: 12px;

    }


    .logo {

        width: 46px;

        height: 46px;

        min-width: 46px;

        font-size: 23px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

        gap: 12px;

    }


    .user-name {

        text-align: left;

        min-width: 0;

    }


    .user-name strong {

        max-width: 180px;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

    }


    .logout {

        min-width: 90px;

    }


    .navigation {

        padding-left: 8px;

        padding-right: 8px;

    }


    .navigation a {

        padding: 15px 14px;

        font-size: 13px;

    }


    .container {

        padding:
            30px 15px 45px;

    }


    .page-title h2 {

        font-size: 27px;

    }


    .profile-grid {

        grid-template-columns:
            1fr;

        gap: 18px;

    }


    .card {

        padding: 23px;

    }

}


/* =====================================
   SMALL PHONES
===================================== */

@media (max-width: 480px) {

    .header {

        padding: 15px 12px;

    }


    .brand {

        gap: 10px;

    }


    .brand h1 {

        font-size: 16px;

    }


    .brand p {

        font-size: 11px;

    }


    .logo {

        width: 42px;

        height: 42px;

        min-width: 42px;

        border-radius: 10px;

        font-size: 20px;

    }


    .user-area {

        gap: 8px;

    }


    .user-name strong {

        max-width: 145px;

        font-size: 13px;

    }


    .user-name span {

        font-size: 11px;

    }


    .logout {

        min-width: 82px;

        min-height: 38px;

        padding: 9px 10px;

        font-size: 12px;

    }


    .navigation a {

        padding: 14px 12px;

        font-size: 12px;

    }


    .container {

        padding:
            25px 10px 40px;

    }


    .page-title {

        margin-bottom: 22px;

    }


    .page-title h2 {

        font-size: 23px;

    }


    .page-title p {

        font-size: 12px;

    }


    .card {

        padding: 20px;

        border-radius: 11px;

    }


    .avatar {

        width: 70px;

        height: 70px;

        font-size: 29px;

    }


    .card h3 {

        font-size: 17px;

    }


    .info-row {

        padding: 14px 0;

    }


    .info-label {

        font-size: 12px;

    }


    .info-row strong {

        font-size: 13px;

    }


    .balance-box {

        padding: 18px;

    }


    .balance-box h2 {

        font-size: 25px;

    }


    .form-group input {

        min-height: 46px;

        font-size: 14px;

    }


    .save-button {

        min-height: 46px;

        font-size: 14px;

    }

}


/* =====================================
   VERY SMALL PHONES
===================================== */

@media (max-width: 360px) {

    .brand h1 {

        font-size: 15px;

    }


    .brand p {

        font-size: 10px;

    }


    .user-name strong {

        max-width: 120px;

    }


    .logout {

        min-width: 75px;

        font-size: 11px;

    }


    .navigation a {

        padding: 13px 10px;

        font-size: 11px;

    }


    .page-title h2 {

        font-size: 21px;

    }


    .card {

        padding: 17px;

    }


    .balance-box h2 {

        font-size: 22px;

    }

}

</style>

</head>

<body>

<!-- =====================================
     HEADER
===================================== -->

<header class="header">

```
<div class="brand">

    <div class="logo">
        ₿
    </div>


    <div>

        <h1>
            Crypto Circle Trading
        </h1>

        <p>
            Account Profile
        </p>

    </div>

</div>



<div class="user-area">


    <div class="user-name">

        <strong>

            <?php
            echo htmlspecialchars(
                $user["username"]
            );
            ?>

        </strong>


        <span>
            User Account
        </span>

    </div>


    <a
        href="logout.php"
        class="logout"
    >
        Sign Out
    </a>


</div>
```

</header>

<!-- =====================================
     NAVIGATION
===================================== -->

<nav class="navigation">

```
<a href="dashboard.php">
    Dashboard
</a>

<a href="transactions.php">
    Transactions
</a>

<a href="transfer.php">
    Transfer
</a>

<a href="withdraw.php">
    Withdraw
</a>

<a href="wallet.php">
    Wallet
</a>

<a href="messages.php">
    Messages
</a>

<a
    href="profile.php"
    class="active"
>
    Profile
</a>
```

</nav>

<!-- =====================================
     MAIN
===================================== -->

<main class="container">

```
<section class="page-title">

    <h2>
        My Profile
    </h2>

    <p>
        View your account information and manage your profile.
    </p>

</section>



<!-- =====================================
     MESSAGE
===================================== -->

<?php if (!empty($message)): ?>

    <div
        class="
            message
            <?php echo htmlspecialchars($messageType); ?>
        "
    >

        <?php
        echo htmlspecialchars(
            $message
        );
        ?>

    </div>

<?php endif; ?>



<div class="profile-grid">


    <!-- =====================================
         ACCOUNT INFORMATION
    ===================================== -->

    <section class="card">


        <div class="avatar">

            <?php
            echo htmlspecialchars(
                $profileInitial
            );
            ?>

        </div>


        <h3>
            Account Information
        </h3>


        <div class="info-row">

            <span class="info-label">
                Username
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $user["username"]
                );
                ?>

            </strong>

        </div>



        <div class="info-row">

            <span class="info-label">
                Member Since
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $memberSince
                );
                ?>

            </strong>

        </div>



        <div class="info-row">

            <span class="info-label">
                Account Number
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $accountNumber
                );
                ?>

            </strong>

        </div>



        <div class="info-row">

            <span class="info-label">
                Account Type
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $accountType
                );
                ?>

            </strong>

        </div>



        <div class="info-row">

            <span class="info-label">
                Account Status
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    ucfirst(
                        $accountStatus
                    )
                );
                ?>

            </strong>

        </div>



        <div class="balance-box">

            <span>
                Available Balance
            </span>

            <h2>

                $

                <?php
                echo number_format(
                    $balance,
                    2
                );
                ?>

            </h2>

        </div>


    </section>



    <!-- =====================================
         EDIT PROFILE
    ===================================== -->

    <section class="card">


        <h3>
            Edit Profile
        </h3>


        <form
            method="POST"
            autocomplete="off"
        >


            <div class="form-group">

                <label for="username">
                    Username
                </label>


                <input
                    type="text"
                    id="username"
                    name="username"
                    minlength="3"
                    maxlength="50"
                    value="<?php echo htmlspecialchars($user["username"]); ?>"
                    required
                >

            </div>



            <button
                type="submit"
                name="update_profile"
                class="save-button"
            >
                Save Changes
            </button>


        </form>



        <div class="profile-note">

            Your username is used to identify your account
            within the platform.

        </div>


    </section>


</div>
```

</main>

<footer>

```
© <?php echo date("Y"); ?>

Crypto Circle Trading
```

</footer>

</body>

</html>
