<?php

session_start();

require "db.php";


/* =====================================
   REDIRECT LOGGED-IN USERS
===================================== */

if (isset($_SESSION["user_id"])) {

    if (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "admin"
    ) {

        header("Location: admin.php");
        exit;

    }

    header("Location: dashboard.php");
    exit;

}


/* =====================================
   VARIABLES
===================================== */

$message = "";
$messageType = "";


/* =====================================
   LOGIN
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["login"])
) {

    $username = trim(
        $_POST["username"] ?? ""
    );

    $password =
        $_POST["password"] ?? "";


    if (
        $username === "" ||
        $password === ""
    ) {

        $message =
            "Please enter your username and password.";

        $messageType =
            "error";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    username,
                    password,
                    role
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username
            ]);

            $user =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                $user &&
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                session_regenerate_id(true);

                $_SESSION["user_id"] =
                    $user["id"];

                $_SESSION["username"] =
                    $user["username"];

                $_SESSION["role"] =
                    $user["role"] ?? "user";


                if (
                    $_SESSION["role"] === "admin"
                ) {

                    header(
                        "Location: admin.php"
                    );

                    exit;

                }


                header(
                    "Location: dashboard.php"
                );

                exit;

            } else {

                $message =
                    "Invalid username or password.";

                $messageType =
                    "error";

            }

        } catch (Exception $e) {

            $message =
                "Unable to sign in. Please try again.";

            $messageType =
                "error";

        }

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
    Sign In | Crypto Circle Trading
</title>


<style>

/* =====================================
   RESET
===================================== */

* {

    box-sizing: border-box;

}


html {

    min-height: 100%;

}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #080c10;

    color: #ffffff;

}


/* =====================================
   BACKGROUND
===================================== */

body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    background:

        radial-gradient(
            circle at 15% 30%,
            rgba(240, 185, 11, 0.10),
            transparent 28%
        ),

        radial-gradient(
            circle at 85% 80%,
            rgba(240, 185, 11, 0.06),
            transparent 30%
        );

    z-index: -1;

}


/* =====================================
   HEADER
===================================== */

.header {

    min-height: 90px;

    padding: 15px 5%;

    display: flex;

    justify-content: space-between;

    align-items: center;

    background:
        rgba(8, 12, 16, 0.96);

    border-bottom:
        1px solid #222a33;

}


.brand {

    display: flex;

    align-items: center;

    gap: 13px;

    text-decoration: none;

    color: white;

}


.logo {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f0b90b;

    color: #111111;

    border-radius: 12px;

    font-size: 25px;

    font-weight: bold;

}


.brand-text h1 {

    margin: 0;

    color: #f0b90b;

    font-size: 21px;

    letter-spacing: 0.5px;

}


.brand-text p {

    margin: 4px 0 0;

    color: #a7b0bd;

    font-size: 12px;

    letter-spacing: 3px;

}


/* =====================================
   HEADER NAVIGATION
===================================== */

.header-nav {

    display: flex;

    align-items: center;

    gap: 30px;

}


.header-nav a {

    color: #b7bec8;

    text-decoration: none;

    font-size: 14px;

}


.header-nav a:hover {

    color: #f0b90b;

}


.create-header-button {

    padding: 11px 18px;

    background: transparent;

    border:
        1px solid #f0b90b;

    border-radius: 7px;

    color: #f0b90b !important;

    font-weight: bold;

}


/* =====================================
   MAIN
===================================== */

.main-container {

    width: 100%;

    min-height:
        calc(100vh - 150px);

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 60px;

    align-items: center;

    max-width: 1400px;

    margin: auto;

    padding:
        70px 7%;

}


/* =====================================
   LEFT SIDE
===================================== */

.welcome-section {

    position: relative;

}


.small-title {

    margin: 0 0 12px;

    color: #d1d5db;

    font-size: 20px;

}


.welcome-section h2 {

    margin: 0;

    max-width: 650px;

    font-size: 56px;

    line-height: 1.1;

}


.gold-text {

    color: #f0b90b;

}


.welcome-description {

    max-width: 570px;

    margin-top: 25px;

    color: #aeb6c2;

    font-size: 19px;

    line-height: 1.7;

}


/* =====================================
   FEATURES
===================================== */

.features {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-top: 50px;

}


.feature {

    padding: 10px;

}


.feature-icon {

    width: 48px;

    height: 48px;

    margin-bottom: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        rgba(240, 185, 11, 0.08);

    border:
        1px solid
        rgba(240, 185, 11, 0.18);

    color: #f0b90b;

    font-size: 23px;

}


.feature h3 {

    margin: 0 0 10px;

    color: #f0b90b;

    font-size: 15px;

}


.feature p {

    margin: 0;

    color: #8d96a3;

    font-size: 13px;

    line-height: 1.6;

}


/* =====================================
   CRYPTO DISPLAY
===================================== */

.crypto-display {

    position: relative;

    height: 260px;

    margin-top: 40px;

    overflow: hidden;

}


.coin {

    position: absolute;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    border:
        2px solid
        rgba(240, 185, 11, 0.55);

    background:

        radial-gradient(
            circle at 30% 30%,
            #ffd45b,
            #b67d00
        );

    box-shadow:

        0 20px 50px
        rgba(240, 185, 11, 0.15),

        inset
        0 0 20px
        rgba(255, 255, 255, 0.15);

    color: #171717;

    font-weight: bold;

}


.coin-main {

    width: 180px;

    height: 180px;

    left: 40px;

    bottom: 10px;

    font-size: 90px;

}


.coin-small {

    width: 90px;

    height: 90px;

    left: 190px;

    bottom: 0;

    font-size: 42px;

    opacity: 0.85;

}


/* =====================================
   LOGIN CARD
===================================== */

.login-section {

    display: flex;

    justify-content: center;

}


.login-card {

    width: 100%;

    max-width: 510px;

    padding: 45px;

    background:

        linear-gradient(
            145deg,
            rgba(28, 34, 42, 0.98),
            rgba(18, 23, 29, 0.98)
        );

    border:
        1px solid #29313b;

    border-radius: 20px;

    box-shadow:
        0 25px 70px
        rgba(0, 0, 0, 0.45);

}


.login-card h2 {

    margin: 0;

    text-align: center;

    font-size: 30px;

}


.login-card .subtitle {

    margin:
        14px 0 35px;

    text-align: center;

    color: #9ca5b1;

}


/* =====================================
   TABS
===================================== */

.tabs {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    margin-bottom: 30px;

    border-bottom:
        1px solid #303844;

}


.tab {

    padding: 15px;

    text-align: center;

    color: #aab2bd;

    text-decoration: none;

    font-weight: bold;

}


.tab.active {

    color: #ffffff;

    border-bottom:
        2px solid #f0b90b;

}


/* =====================================
   ALERT
===================================== */

.message {

    margin-bottom: 20px;

    padding: 14px;

    border-radius: 8px;

    font-size: 14px;

}


.message.error {

    color: #ff707f;

    background:
        rgba(246, 70, 93, 0.10);

    border:
        1px solid
        rgba(246, 70, 93, 0.35);

}


/* =====================================
   FORM
===================================== */

.form-group {

    margin-bottom: 22px;

}


.form-group label {

    display: block;

    margin-bottom: 10px;

    color: #d6dbe2;

    font-size: 14px;

}


.form-group input {

    width: 100%;

    height: 54px;

    padding: 0 17px;

    background: #10151b;

    border:
        1px solid #303946;

    border-radius: 8px;

    outline: none;

    color: #ffffff;

    font-size: 15px;

    transition: 0.2s;

}


.form-group input:focus {

    border-color: #f0b90b;

    box-shadow:
        0 0 0 3px
        rgba(240, 185, 11, 0.08);

}


.form-group input::placeholder {

    color: #707985;

}


/* =====================================
   FORGOT PASSWORD
===================================== */

.form-links {

    display: flex;

    justify-content: flex-end;

    margin:
        -5px 0 25px;

}


.form-links a {

    color: #f0b90b;

    text-decoration: none;

    font-size: 14px;

}


/* =====================================
   LOGIN BUTTON
===================================== */

.login-button {

    width: 100%;

    height: 55px;

    border: none;

    border-radius: 8px;

    background:

        linear-gradient(
            90deg,
            #f0b90b,
            #ffc629
        );

    color: #151515;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;

}


.login-button:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 10px 25px
        rgba(240, 185, 11, 0.20);

}


/* =====================================
   DIVIDER
===================================== */

.divider {

    display: flex;

    align-items: center;

    gap: 15px;

    margin: 28px 0;

    color: #737c88;

    font-size: 13px;

}


.divider::before,

.divider::after {

    content: "";

    height: 1px;

    flex: 1;

    background: #303844;

}


/* =====================================
   CREATE ACCOUNT
===================================== */

.create-account {

    margin: 0;

    text-align: center;

    color: #9ca5b1;

}


.create-account a {

    margin-left: 5px;

    color: #f0b90b;

    text-decoration: none;

    font-weight: bold;

}


/* =====================================
   FOOTER
===================================== */

footer {

    padding: 25px;

    text-align: center;

    color: #78818d;

    border-top:
        1px solid #1e252d;

    font-size: 13px;

}


/* =====================================
   TABLET
===================================== */

@media (max-width: 1000px) {

    .main-container {

        grid-template-columns: 1fr;

        gap: 40px;

    }


    .welcome-section {

        text-align: center;

    }


    .welcome-section h2 {

        margin: auto;

        font-size: 48px;

    }


    .welcome-description {

        margin-left: auto;

        margin-right: auto;

    }


    .features {

        max-width: 700px;

        margin-left: auto;

        margin-right: auto;

    }


    .crypto-display {

        display: none;

    }


    .login-card {

        max-width: 560px;

    }

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 700px) {

    .header {

        padding:
            15px 20px;

    }


    .header-nav {

        display: none;

    }


    .main-container {

        padding:
            45px 20px;

    }


    .welcome-section h2 {

        font-size: 38px;

    }


    .welcome-description {

        font-size: 16px;

    }


    .features {

        grid-template-columns:
            1fr;

        text-align: left;

        margin-top: 35px;

    }


    .feature {

        display: grid;

        grid-template-columns:
            55px 1fr;

        column-gap: 15px;

    }


    .feature-icon {

        grid-row:
            span 2;

    }


    .feature h3 {

        margin-top: 5px;

    }


    .login-card {

        padding: 30px 22px;

    }

}

</style>

</head>


<body>


<!-- =====================================
     HEADER
===================================== -->

<header class="header">


    <a
        href="index.php"
        class="brand"
    >


        <div class="logo">
            ₿
        </div>


        <div class="brand-text">

            <h1>
                CRYPTO CIRCLE
            </h1>

            <p>
                TRADING
            </p>

        </div>


    </a>



    <nav class="header-nav">

        <a href="#about">
            About Us
        </a>

        <a href="#security">
            Security
        </a>

        <a href="#support">
            Support
        </a>

        <a
            href="create_account.php"
            class="create-header-button"
        >
            Create Account
        </a>

    </nav>


</header>



<!-- =====================================
     MAIN
===================================== -->

<main class="main-container">


    <!-- =====================================
         WELCOME SECTION
    ===================================== -->

    <section
        class="welcome-section"
        id="about"
    >


        <p class="small-title">

            Welcome to

        </p>


        <h2>

            <span class="gold-text">
                Crypto Circle Trading
            </span>

        </h2>


        <p class="welcome-description">

            Access your account through a modern digital
            trading platform designed for a clear and
            straightforward account experience.

        </p>



        <!-- FEATURES -->

        <div class="features">


            <div class="feature">

                <div class="feature-icon">
                    ◈
                </div>

                <div>

                    <h3>
                        Secure Access
                    </h3>

                    <p>
                        Account protection and secure login
                        for your platform access.
                    </p>

                </div>

            </div>



            <div class="feature">

                <div class="feature-icon">
                    ϟ
                </div>

                <div>

                    <h3>
                        Easy Navigation
                    </h3>

                    <p>
                        Quickly access your account and
                        available platform features.
                    </p>

                </div>

            </div>



            <div class="feature">

                <div class="feature-icon">
                    ▥
                </div>

                <div>

                    <h3>
                        Trading Platform
                    </h3>

                    <p>
                        Manage and view your account
                        information in one place.
                    </p>

                </div>

            </div>


        </div>



        <!-- CRYPTO DISPLAY -->

        <div class="crypto-display">

            <div class="coin coin-main">
                ₿
            </div>

            <div class="coin coin-small">
                ◇
            </div>

        </div>


    </section>



    <!-- =====================================
         LOGIN SECTION
    ===================================== -->

    <section class="login-section">


        <div class="login-card">


            <h2>
                Welcome Back
            </h2>


            <p class="subtitle">

                Sign in to access your account

            </p>



            <!-- TABS -->

            <div class="tabs">

                <a
                    href="index.php"
                    class="tab active"
                >
                    Login
                </a>


                <a
                    href="create_account.php"
                    class="tab"
                >
                    Create Account
                </a>

            </div>



            <!-- ERROR MESSAGE -->

            <?php if ($message !== ""): ?>

                <div
                    class="
                        message
                        <?php
                        echo htmlspecialchars(
                            $messageType
                        );
                        ?>
                    "
                >

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </div>

            <?php endif; ?>



            <!-- LOGIN FORM -->

            <form method="POST">


                <div class="form-group">

                    <label for="username">

                        Username

                    </label>


                    <input

                        type="text"

                        id="username"

                        name="username"

                        placeholder="Enter your username"

                        autocomplete="username"

                        required

                    >

                </div>



                <div class="form-group">

                    <label for="password">

                        Password

                    </label>


                    <input

                        type="password"

                        id="password"

                        name="password"

                        placeholder="Enter your password"

                        autocomplete="current-password"

                        required

                    >

                </div>



                <div class="form-links">

                    <a href="#">

                        Forgot Password?

                    </a>

                </div>



                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    Sign In

                </button>


            </form>



            <div class="divider">

                OR

            </div>



            <p class="create-account">

                Don't have an account?

                <a href="create_account.php">

                    Create Account

                </a>

            </p>


        </div>


    </section>


</main>



<!-- =====================================
     FOOTER
===================================== -->

<footer>

    © <?php echo date("Y"); ?>

    Crypto Circle Trading.
    All rights reserved.

</footer>


</body>

</html>