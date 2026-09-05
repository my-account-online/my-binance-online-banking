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
   GET USER INFORMATION
===================================== */

$userId = (int) $_SESSION["user_id"];

$userStmt = $pdo->prepare("
    SELECT id, username
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->execute([$userId]);

$user = $userStmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    session_destroy();

    header("Location: index.php");
    exit;

}


/* =====================================
   INVESTMENT PLANS

   Account Performance Summary.
===================================== */

$plans = [

    100 => [
        "name" => "Starter Plan",
        "description" => "Basic account plan information.",
        "duration" => "Account period",
        "profit" => 500
    ],

    250 => [
        "name" => "Growth Plan",
        "description" => "Intermediate account plan information.",
        "duration" => "Account period",
        "profit" => 1100
    ],

    400 => [
        "name" => "Advanced Plan",
        "description" => "Advanced account plan information.",
        "duration" => "Account period",
        "profit" => 2700
    ],

    500 => [
        "name" => "Premium Plan",
        "description" => "Premium account plan information.",
        "duration" => "Account period",
        "profit" => 3740
    ],

    650 => [
        "name" => "Professional Plan",
        "description" => "Professional account plan information.",
        "duration" => "Account period",
        "profit" => 5500
    ],

    1000 => [
        "name" => "Platinum Plan",
        "description" => "Platinum account plan information.",
        "duration" => "Account period",
        "profit" => 8000
    ],

    2000 => [
        "name" => "Elite Plan",
        "description" => "Elite investment plan information.",
        "duration" => "Account period",
        "profit" => 10500
    ]

];


/* =====================================
   GET SELECTED PLAN
===================================== */

$selectedPlan = isset($_GET["plan"])
    ? (int) $_GET["plan"]
    : 0;


/* =====================================
   VALIDATE PLAN
===================================== */

if (!isset($plans[$selectedPlan])) {

    header("Location: dashboard.php#investment");
    exit;

}


$plan = $plans[$selectedPlan];


/* =====================================
   INVESTMENT ACTION
===================================== */

$success = false;


if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["invest"])
) {

    $success = true;

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
    Investment Details | Crypto Circle Trading
</title>


<style>

/* =====================================
   GENERAL
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
            circle at 85% 10%,
            rgba(240, 185, 11, 0.12),
            transparent 30%
        ),

        radial-gradient(
            circle at 10% 90%,
            rgba(240, 185, 11, 0.05),
            transparent 30%
        ),

        #0b0e11;

    color: white;

}


/* =====================================
   HEADER
===================================== */

.header {

    padding: 18px 6%;

    display: flex;

    justify-content: space-between;

    align-items: center;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

}


.brand {

    display: flex;

    align-items: center;

    gap: 14px;

}


.logo {

    width: 50px;

    height: 50px;

    display: flex;

    justify-content: center;

    align-items: center;

    background: #f0b90b;

    color: #111111;

    border-radius: 12px;

    font-size: 24px;

    font-weight: bold;

}


.brand h1 {

    margin: 0;

    font-size: 21px;

}


.brand p {

    margin: 5px 0 0;

    color: #848e9c;

    font-size: 13px;

}


.back-btn {

    padding: 11px 17px;

    color: #f0b90b;

    text-decoration: none;

    border:
        1px solid #f0b90b;

    border-radius: 7px;

    font-weight: bold;

}


/* =====================================
   MAIN CONTAINER
===================================== */

.container {

    width: 100%;

    max-width: 800px;

    margin: auto;

    padding: 50px 20px;

}


/* =====================================
   PAGE TITLE
===================================== */

.page-title {

    text-align: center;

    margin-bottom: 30px;

}


.page-title h2 {

    font-size: 32px;

    margin: 0 0 12px;

}


.page-title p {

    margin: 0;

    color: #848e9c;

    line-height: 1.6;

}


/* =====================================
   ACCOUNT NOTICE
===================================== */

.account-notice {

    padding: 20px;

    margin-bottom: 25px;

    background:
        rgba(240, 185, 11, 0.10);

    border-left:
        4px solid #f0b90b;

    border-radius: 8px;

    color: #d1d4dc;

    line-height: 1.7;

}


.account-notice strong {

    color: #f0b90b;

}


/* =====================================
   PLAN CARD
===================================== */

.plan-card {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 15px;

    padding: 30px;

    margin-bottom: 25px;

}


.plan-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 25px;

}


.plan-header h3 {

    margin: 0 0 8px;

    color: #f0b90b;

    font-size: 24px;

}


.plan-header p {

    margin: 0;

    color: #848e9c;

    line-height: 1.5;

}


.plan-badge {

    padding: 8px 12px;

    background:
        rgba(240, 185, 11, 0.12);

    color: #f0b90b;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    white-space: nowrap;

}


/* =====================================
   PLAN ROWS
===================================== */

.plan-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 17px 0;

    border-bottom:
        1px solid #2b3139;

}


.plan-row:last-child {

    border-bottom: none;

}


.plan-label {

    color: #848e9c;

}


.plan-value {

    text-align: right;

    font-weight: bold;

}


/* =====================================
   AMOUNT DISPLAY
===================================== */

.amount-box {

    padding: 25px;

    margin: 25px 0;

    text-align: center;

    background:

        linear-gradient(
            135deg,
            #20242c,
            #181a20
        );

    border:
        1px solid #343a43;

    border-radius: 12px;

}


.amount-box span {

    display: block;

    color: #848e9c;

    font-size: 13px;

    margin-bottom: 10px;

}


.amount-box h2 {

    margin: 0;

    color: #f0b90b;

    font-size: 36px;

}


/* =====================================
   INVESTMENT BOX
===================================== */

.investment-box {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 15px;

    padding: 30px;

    margin-bottom: 25px;

}


.investment-box h3 {

    margin-top: 0;

    color: #f0b90b;

}


.investment-box p {

    color: #848e9c;

    line-height: 1.7;

}


/* =====================================
   ACCOUNT DETAILS
===================================== */

.account-details {

    margin: 20px 0;

    padding: 18px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 10px;

}


.account-details div {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 10px 0;

    border-bottom:
        1px solid #2b3139;

}


.account-details div:last-child {

    border-bottom: none;

}


.account-details span {

    color: #848e9c;

}


.account-details strong {

    text-align: right;

}


/* =====================================
   BUTTON
===================================== */

.submit-btn {

    width: 100%;

    border: none;

    padding: 16px;

    background: #f0b90b;

    color: #111111;

    border-radius: 8px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.2s;

}


.submit-btn:hover {

    opacity: 0.88;

    transform:
        translateY(-1px);

}


/* =====================================
   SUCCESS BOX
===================================== */

.success-box {

    padding: 35px 25px;

    text-align: center;

    background:
        rgba(14, 203, 129, 0.10);

    border:
        1px solid #0ecb81;

    border-radius: 15px;

}


.success-icon {

    width: 70px;

    height: 70px;

    margin:
        0 auto 20px;

    display: flex;

    justify-content: center;

    align-items: center;

    border-radius: 50%;

    background:
        rgba(14, 203, 129, 0.15);

    color: #0ecb81;

    font-size: 32px;

}


.success-box h2 {

    margin: 0 0 15px;

    color: #0ecb81;

}


.success-box p {

    color: #d1d4dc;

    line-height: 1.7;

}


/* =====================================
   PROFIT DISPLAY
===================================== */

.profit-box {

    margin-top: 20px;

    padding: 20px;

    text-align: left;

    background: #0b0e11;

    border: 1px solid #343a43;

    border-radius: 10px;

}


.profit-box span {

    display: block;

    color: #848e9c;

    font-size: 13px;

    margin-bottom: 8px;

}


.profit-box h2,
.profit-box h3 {

    margin: 0;

}


.profit-box h3 {

    color: #f0b90b;

}


.profit-highlight {

    text-align: center;

    border-color: #0ecb81;

    background: rgba(14, 203, 129, 0.08);

}


.profit-highlight h2 {

    color: #0ecb81;

    font-size: 34px;

}


.profit-note {

    margin-top: 20px;

    font-size: 13px;

    color: #848e9c !important;

}


/* =====================================
   WALLET BUTTON
===================================== */

.wallet-btn {

    display: block;

    width: 100%;

    margin-top: 20px;

    padding: 16px;

    border: none;

    background: #f0b90b;

    color: #111111;

    border-radius: 8px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

}


/* =====================================
   DEPOSIT SLIDE PANEL
===================================== */

.deposit-panel {

    display: none;

    margin-top: 20px;

    padding: 25px;

    text-align: left;

    background: #0b0e11;

    border: 1px solid #f0b90b;

    border-radius: 12px;

    animation: slideDown 0.35s ease;

}


.deposit-panel.show {

    display: block;

}


@keyframes slideDown {

    from {

        opacity: 0;

        transform: translateY(-15px);

    }

    to {

        opacity: 1;

        transform: translateY(0);

    }

}


.deposit-panel h3 {

    margin-top: 0;

    text-align: center;

    color: #f0b90b;

}


.deposit-address {

    margin: 20px 0;

    padding: 18px;

    overflow-wrap: anywhere;

    text-align: center;

    background: #181a20;

    border: 1px solid #343a43;

    border-radius: 8px;

    color: #f0b90b;

    font-family: monospace;

    font-size: 14px;

    line-height: 1.6;

}


.deposit-info {

    padding: 15px;

    margin-top: 15px;

    background: rgba(240, 185, 11, 0.08);

    border-left: 4px solid #f0b90b;

    border-radius: 6px;

    color: #d1d4dc;

    font-size: 13px;

    line-height: 1.6;

}


.close-wallet-btn {

    width: 100%;

    margin-top: 15px;

    padding: 13px;

    border: 1px solid #343a43;

    background: #20242c;

    color: white;

    border-radius: 8px;

    font-weight: bold;

    cursor: pointer;

}


/* =====================================
   DASHBOARD BUTTON
===================================== */

.dashboard-btn {

    display: block;

    width: 100%;

    margin-top: 12px;

    padding: 16px;

    background: #20242c;

    color: white;

    text-decoration: none;

    border:
        1px solid #343a43;

    border-radius: 8px;

    font-weight: bold;

}


/* =====================================
   FOOTER
===================================== */

footer {

    padding: 30px 20px;

    text-align: center;

    color: #848e9c;

    font-size: 13px;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 650px) {

    .header {

        padding: 15px;

    }


    .brand h1 {

        font-size: 16px;

    }


    .brand p {

        font-size: 11px;

    }


    .back-btn {

        padding: 9px 12px;

        font-size: 12px;

    }


    .container {

        padding: 35px 15px;

    }


    .plan-card,
    .investment-box {

        padding: 20px;

    }


    .plan-header {

        flex-direction: column;

    }


    .plan-row {

        flex-direction: column;

        gap: 7px;

    }


    .plan-value {

        text-align: left;

    }


    .account-details div {

        flex-direction: column;

        gap: 5px;

    }


    .amount-box h2 {

        font-size: 30px;

    }

}

</style>

</head>


<body>


<!-- =====================================
     HEADER
===================================== -->

<header class="header">

    <div class="brand">

        <div class="logo">
            ₿
        </div>


        <div>

            <h1>
                Crypto Circle Trading
            </h1>

            <p>
                Investment Information
            </p>

        </div>

    </div>


    <a
        href="dashboard.php#investment"
        class="back-btn"
    >
        ← Back
    </a>

</header>



<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="container">


<?php if ($success): ?>


    <section class="success-box">


        <div class="success-icon">
            ✓
        </div>


        <h2>
            INVESTMENT SELECTED
        </h2>


        <p>
            Your selected plan information is shown below.
        </p>



        <!-- SELECTED PLAN -->

        <div class="profit-box">

            <span>
                Selected Plan
            </span>

            <h3>

                <?php
                echo htmlspecialchars(
                    $plan["name"]
                );
                ?>

            </h3>

        </div>



        <!-- INVESTMENT AMOUNT -->

        <div class="profit-box">

            <span>
                Investment Amount
            </span>

            <h2>

                $

                <?php
                echo number_format(
                    $selectedPlan,
                    2
                );
                ?>

            </h2>

        </div>



        <!-- DISPLAYED PROFIT -->

        <div class="profit-box profit-highlight">

            <span>
                Plan Return
            </span>

            <h2>

                $

                <?php
                echo number_format(
                    $plan["profit"],
                    2
                );
                ?>

            </h2>

        </div>


        <p class="profit-note">

            Investment returns are subject to market conditions and are not guaranteed.

        </p>



        <!-- SEE DEPOSIT WALLET -->

        <button
            type="button"
            class="wallet-btn"
            onclick="showDepositWallet()"
        >
            See Deposit Wallet
        </button>



        <!-- =====================================
             DEPOSIT SLIDE PANEL
        ===================================== -->

        <div
            id="depositPanel"
            class="deposit-panel"
        >

            <h3>
                Deposit Account
            </h3>



            <div class="account-details">


                <div>

                    <span>
                        Selected Plan
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $plan["name"]
                        );
                        ?>

                    </strong>

                </div>



                <div>

                    <span>
                        Selected Deposit Amount
                    </span>

                    <strong>

                        $

                        <?php
                        echo number_format(
                            $selectedPlan,
                            2
                        );
                        ?>

                    </strong>

                </div>


            </div>



            <!-- ACCOUNT ADDRESS -->

            <div class="deposit-address">

                DEPOSIT ADDRESS

            </div>



            <div class="deposit-info">

                <strong>
                    Deposit Address
                </strong>

                <br><br>

<p id="depositAddress">
    bc1quehtlkycrau4kvc67nx0neme6r7dzvgcgswhxh
</p>

<button type="button" onclick="copyAddress()">Copy Address</button>

<script>
function copyAddress() {
    const address = document.getElementById("depositAddress").innerText;

    navigator.clipboard.writeText(address).then(() => {
        alert("Address copied successfully!");
    });
}
</script>
            </div>



            <button
                type="button"
                class="close-wallet-btn"
                onclick="hideDepositWallet()"
            >
                Close Deposit Account
            </button>


        </div>



        <!-- RETURN TO DASHBOARD -->

        <a
            href="dashboard.php"
            class="dashboard-btn"
        >
            Return to Dashboard
        </a>


    </section>


<?php else: ?>


    <!-- =====================================
         PAGE TITLE
    ===================================== -->

    <section class="page-title">

        <h2>
            Investment Details
        </h2>


        <p>

            Welcome,

            <?php
            echo htmlspecialchars(
                $user["username"]
            );
            ?>.

            Review your selected investment plan below.

        </p>

    </section>



    <!-- =====================================
         ACCOUNT NOTICE
    ===================================== -->

    <section class="account-notice">

        <strong>
            Investment Information
        </strong>

        <br><br>

        Review the selected plan and the illustrative return before continuing.

    </section>



    <!-- =====================================
         SELECTED PLAN
    ===================================== -->

    <section class="plan-card">


        <div class="plan-header">


            <div>

                <h3>

                    <?php
                    echo htmlspecialchars(
                        $plan["name"]
                    );
                    ?>

                </h3>


                <p>

                    <?php
                    echo htmlspecialchars(
                        $plan["description"]
                    );
                    ?>

                </p>

            </div>


            <span class="plan-badge">
                SELECTED PLAN
            </span>


        </div>



        <div class="amount-box">

            <span>
                Investment Amount
            </span>


            <h2>

                $

                <?php
                echo number_format(
                    $selectedPlan,
                    2
                );
                ?>

            </h2>

        </div>



        <div class="plan-row">

            <span class="plan-label">
                Plan Name
            </span>


            <span class="plan-value">

                <?php
                echo htmlspecialchars(
                    $plan["name"]
                );
                ?>

            </span>

        </div>



        <div class="plan-row">

            <span class="plan-label">
                Investment Amount
            </span>


            <span class="plan-value">

                $

                <?php
                echo number_format(
                    $selectedPlan,
                    2
                );
                ?>

            </span>

        </div>



        <div class="plan-row">

            <span class="plan-label">
                Illustrative Return
            </span>


            <span class="plan-value">

                $

                <?php
                echo number_format(
                    $plan["profit"],
                    2
                );
                ?>

            </span>

        </div>



        <div class="plan-row">

            <span class="plan-label">
                Duration
            </span>


            <span class="plan-value">

                <?php
                echo htmlspecialchars(
                    $plan["duration"]
                );
                ?>

            </span>

        </div>


    </section>



    <!-- =====================================
         INVESTMENT CONFIRMATION
    ===================================== -->

    <section class="investment-box">


        <h3>
            Confirm Investment Plan
        </h3>


        <p>
            Review your selected plan information below before continuing.
        </p>



        <div class="account-details">


            <div>

                <span>
                    Account
                </span>


                <strong>

                    <?php
                    echo htmlspecialchars(
                        $user["username"]
                    );
                    ?>

                </strong>

            </div>



            <div>

                <span>
                    Selected Plan
                </span>


                <strong>

                    <?php
                    echo htmlspecialchars(
                        $plan["name"]
                    );
                    ?>

                </strong>

            </div>



            <div>

                <span>
                    Investment Amount
                </span>


                <strong>

                    $

                    <?php
                    echo number_format(
                        $selectedPlan,
                        2
                    );
                    ?>

                </strong>

            </div>



            <div>

                <span>
                    Illustrative Return
                </span>


                <strong>

                    $

                    <?php
                    echo number_format(
                        $plan["profit"],
                        2
                    );
                    ?>

                </strong>

            </div>


        </div>



        <!-- INVEST BUTTON -->

        <form method="POST">

            <button
                type="submit"
                name="invest"
                class="submit-btn"
            >
                Invest
            </button>

        </form>


    </section>


<?php endif; ?>


</main>



<footer>

    © <?php echo date("Y"); ?>

    Crypto Circle Trading

</footer>



<!-- =====================================
     JAVASCRIPT
===================================== -->

<script>

function showDepositWallet() {

    document
        .getElementById("depositPanel")
        .classList
        .add("show");

}


function hideDepositWallet() {

    document
        .getElementById("depositPanel")
        .classList
        .remove("show");

}

</script>


</body>

</html>
```
