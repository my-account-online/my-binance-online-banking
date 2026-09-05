<?php

session_start();

require "db.php";


/* =====================================
   CHECK USER LOGIN
===================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit;

}


/* =====================================
   PREVENT ADMIN ACCESS
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
   GET USER INFORMATION
===================================== */

$username = "User";


try {

    $userStmt = $pdo->prepare("
        SELECT username
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([$userId]);

    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);


    if ($userData) {

        $username = $userData["username"];

    }

} catch (Exception $e) {

    $username = "User";

}


/* =====================================
   GET ACCOUNT BALANCE
===================================== */

$balance = 0;


try {

    $accountStmt = $pdo->prepare("
        SELECT balance
        FROM accounts
        WHERE user_id = ?
        LIMIT 1
    ");

    $accountStmt->execute([$userId]);

    $accountData =
        $accountStmt->fetch(PDO::FETCH_ASSOC);


    if ($accountData) {

        $balance =
            (float) $accountData["balance"];

    }

} catch (Exception $e) {

    $balance = 0;

}


/* =====================================
   WALLET ADDRESSES
===================================== */

$wallets = [

    [
        "name" => "Bitcoin",
        "symbol" => "BTC",
        "icon" => "₿",
        "address" =>
            "bc1quehtlkycrau4kvc67nx0neme6r7dzvgcgswhxh",
        "network" => "Bitcoin Network"
    ],

    [
        "name" => "Ethereum",
        "symbol" => "ETH",
        "icon" => "◆",
        "address" =>
            "0x6e5adDb92e20742f88029569aF3Cd4eEEe38f37b",
        "network" => "Ethereum Network"
    ],

    [
        "name" => "Tether",
        "symbol" => "USDT",
        "icon" => "₮",
        "address" =>
            "0x6e5adDb92e20742f88029569aF3Cd4eEEe38f37b",
        "network" => "TRON Network"
    ],

    [
        "name" => "BNB",
        "symbol" => "BNB",
        "icon" => "⬡",
        "address" =>
            "0x6e5adDb92e20742f88029569aF3Cd4eEEe38f37b",
        "network" => "BNB Smart Chain"
    ]

];

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
    Wallet | Crypto Circle Trading
</title>


<style>

/* =====================================
   GENERAL
===================================== */

* {

    box-sizing: border-box;

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
            rgba(240, 185, 11, 0.12),
            transparent 30%
        ),

        #0b0e11;

    color: white;

}


/* =====================================
   HEADER
===================================== */

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 6%;

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

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f0b90b;

    color: #111;

    border-radius: 12px;

    font-size: 26px;

    font-weight: bold;

}


.brand h1 {

    margin: 0 0 5px;

    font-size: 22px;

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

    gap: 20px;

}


.username {

    text-align: right;

}


.username strong {

    display: block;

}


.username span {

    display: block;

    margin-top: 3px;

    color: #848e9c;

    font-size: 12px;

}


.logout {

    padding: 12px 18px;

    background: #f0b90b;

    color: #111;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

}


/* =====================================
   NAVIGATION
===================================== */

.navigation {

    display: flex;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

    overflow-x: auto;

}


.navigation a {

    padding: 18px;

    color: #848e9c;

    text-decoration: none;

    white-space: nowrap;

    transition: 0.2s;

}


.navigation a:hover {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, 0.05);

}


.navigation .active {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, 0.06);

}


/* =====================================
   MAIN
===================================== */

main {

    max-width: 1200px;

    margin: auto;

    padding: 45px 25px;

}


/* =====================================
   PAGE HEADING
===================================== */

.page-heading {

    margin-bottom: 30px;

}


.page-heading h2 {

    margin: 0 0 10px;

    font-size: 32px;

}


.page-heading p {

    margin: 0;

    color: #848e9c;

    line-height: 1.6;

}


/* =====================================
   ACCOUNT SUMMARY
===================================== */

.summary-card {

    position: relative;

    overflow: hidden;

    margin-bottom: 35px;

    padding: 32px;

    background:

        linear-gradient(
            135deg,
            #181a20,
            #252931
        );

    border:
        1px solid #343a43;

    border-radius: 16px;

}


.summary-card::after {

    content: "₿";

    position: absolute;

    right: 30px;

    bottom: -65px;

    color:
        rgba(240, 185, 11, 0.08);

    font-size: 200px;

    font-weight: bold;

}


.summary-label {

    position: relative;

    z-index: 1;

    color: #848e9c;

}


.summary-balance {

    position: relative;

    z-index: 1;

    margin: 12px 0 0;

    color: #f0b90b;

    font-size: 42px;

}


/* =====================================
   WALLET GRID
===================================== */

.wallet-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 22px;

}


/* =====================================
   WALLET CARD
===================================== */

.wallet-card {

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

    transition: 0.2s;

}


.wallet-card:hover {

    border-color: #f0b90b;

    transform:
        translateY(-3px);

}


.wallet-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.wallet-coin {

    display: flex;

    align-items: center;

    gap: 14px;

}


.coin-icon {

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(240, 185, 11, 0.12);

    color: #f0b90b;

    font-size: 25px;

    font-weight: bold;

}


.wallet-name h3 {

    margin: 0 0 5px;

    font-size: 18px;

}


.wallet-name span {

    color: #848e9c;

    font-size: 13px;

}


.wallet-network {

    padding: 7px 10px;

    background:
        rgba(14, 203, 129, 0.10);

    color: #0ecb81;

    border-radius: 20px;

    font-size: 11px;

}


/* =====================================
   ADDRESS
===================================== */

.address-label {

    display: block;

    margin-bottom: 10px;

    color: #848e9c;

    font-size: 13px;

}


.address-box {

    display: flex;

    align-items: center;

    gap: 10px;

}


.wallet-address {

    flex: 1;

    padding: 14px;

    overflow: hidden;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 8px;

    color: #d1d4dc;

    font-size: 13px;

    word-break: break-all;

}


.copy-button {

    padding: 14px 17px;

    border: none;

    border-radius: 8px;

    background: #f0b90b;

    color: #111;

    font-weight: bold;

    cursor: pointer;

}


.copy-button:hover {

    opacity: 0.9;

}


/* =====================================
   WALLET FOOTER
===================================== */

.wallet-footer {

    margin-top: 22px;

    padding-top: 18px;

    border-top:
        1px solid #2b3139;

}


.wallet-footer span {

    color: #848e9c;

    font-size: 12px;

}


/* =====================================
   NOTICE
===================================== */

.notice {

    margin-top: 40px;

    padding: 20px;

    background:
        rgba(240, 185, 11, 0.07);

    border-left:
        4px solid #f0b90b;

    border-radius: 7px;

    color: #d1d4dc;

    line-height: 1.7;

}


/* =====================================
   FOOTER
===================================== */

footer {

    padding: 30px;

    text-align: center;

    color: #848e9c;

    font-size: 13px;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 800px) {

    .wallet-grid {

        grid-template-columns: 1fr;

    }


    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 18px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

    }


    .username {

        text-align: left;

    }

}


@media (max-width: 550px) {

    main {

        padding: 30px 15px;

    }


    .summary-balance {

        font-size: 34px;

    }


    .address-box {

        flex-direction: column;

        align-items: stretch;

    }


    .copy-button {

        width: 100%;

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
                Wallet Information
            </p>

        </div>

    </div>



    <div class="user-area">


        <div class="username">

            <strong>
                <?php
                echo htmlspecialchars($username);
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


</header>



<!-- =====================================
     NAVIGATION
===================================== -->

<nav class="navigation">

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

    <a
        href="wallet.php"
        class="active"
    >
        Wallet
    </a>

    <a href="messages.php">
        Messages
    </a>

    <a href="profile.php">
        Profile
    </a>

</nav>



<!-- =====================================
     MAIN CONTENT
===================================== -->

<main>


<!-- PAGE HEADING -->

<section class="page-heading">

    <h2>
        My Wallets
    </h2>

    <p>
        View the cryptocurrency wallet information associated
        with your account.
    </p>

</section>



<!-- ACCOUNT SUMMARY -->

<section class="summary-card">

    <span class="summary-label">
        Account Balance
    </span>

    <h2 class="summary-balance">

        $

        <?php
        echo number_format(
            $balance,
            2
        );
        ?>

    </h2>

</section>



<!-- WALLET LIST -->

<section class="wallet-grid">


<?php foreach ($wallets as $wallet): ?>


    <div class="wallet-card">


        <!-- WALLET HEADER -->

        <div class="wallet-top">


            <div class="wallet-coin">


                <div class="coin-icon">

                    <?php
                    echo htmlspecialchars(
                        $wallet["icon"]
                    );
                    ?>

                </div>


                <div class="wallet-name">

                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $wallet["name"]
                        );
                        ?>

                    </h3>


                    <span>

                        <?php
                        echo htmlspecialchars(
                            $wallet["symbol"]
                        );
                        ?>

                    </span>

                </div>


            </div>



            <span class="wallet-network">

                <?php
                echo htmlspecialchars(
                    $wallet["network"]
                );
                ?>

            </span>


        </div>



        <!-- ADDRESS -->

        <span class="address-label">
            Wallet Address
        </span>


        <div class="address-box">


            <div
                class="wallet-address"
                id="address-<?php
                    echo htmlspecialchars(
                        $wallet["symbol"]
                    );
                ?>"
            >

                <?php
                echo htmlspecialchars(
                    $wallet["address"]
                );
                ?>

            </div>


            <button
                type="button"
                class="copy-button"
                onclick="
                    copyAddress(
                        'address-<?php
                        echo htmlspecialchars(
                            $wallet["symbol"]
                        );
                        ?>',
                        this
                    )
                "
            >

                Copy

            </button>


        </div>



        <!-- FOOTER -->

        <div class="wallet-footer">

            <span>
                Network:
                <?php
                echo htmlspecialchars(
                    $wallet["network"]
                );
                ?>
            </span>

        </div>


    </div>


<?php endforeach; ?>


</section>



<!-- NOTICE -->

<div class="notice">

    <strong>
        Wallet Information:
    </strong>

    Wallet addresses and cryptocurrency information are displayed for account reference and information purposes. Cryptocurrency services and transaction availability are subject to the platform’s operational capabilities.

</div>


</main>



<!-- =====================================
     FOOTER
===================================== -->

<footer>

    © <?php echo date("Y"); ?>

    Crypto Circle Trading

</footer>



<!-- =====================================
     JAVASCRIPT
===================================== -->

<script>

/* =====================================
   COPY WALLET ADDRESS bc1quehtlkycrau4kvc67nx0neme6r7dzvgcgswhxh
===================================== */

function copyAddress(
    addressId,
    button
) {

    const addressElement =
        document.getElementById(
            addressId
        );


    const address =
        addressElement.innerText.trim();


    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {

        navigator.clipboard.writeText(
            address
        ).then(
            function() {

                showCopied(button);

            }
        );

    } else {

        const textArea =
            document.createElement(
                "textarea"
            );


        textArea.value =
            address;


        document.body.appendChild(
            textArea
        );


        textArea.select();


        document.execCommand(
            "copy"
        );


        textArea.remove();


        showCopied(button);

    }

}


/* =====================================
   COPY SUCCESS MESSAGE
===================================== */

function showCopied(button) {

    const originalText =
        button.innerText;


    button.innerText =
        "Copied!";


    setTimeout(
        function() {

            button.innerText =
                originalText;

        },
        2000
    );

}

</script>


</body>

</html>