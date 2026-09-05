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
   PREVENT ADMIN FROM OPENING USER DASHBOARD
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

try {

    $userStmt = $pdo->prepare("
        SELECT id, username
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
   GET ACCOUNT INFORMATION
===================================== */

$account = null;


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

    $account = $accountStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {

    $account = null;

}


/* =====================================
   DEFAULT ACCOUNT VALUES
===================================== */

$accountNumber =
    $account["account_number"] ?? "Not Available";

$accountType =
    $account["account_type"] ?? "Trading Account";

$accountStatus =
    $account["account_status"] ?? "Inactive";

$balance =
    (float) ($account["balance"] ?? 0);


/* =====================================
   USERNAME
===================================== */

$username =
    $user["username"] ?? "User";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Dashboard | Crypto Circle Trading</title>


<style>

/* =========================================================
   GENERAL
========================================================= */

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
            circle at 90% 5%,
            rgba(240, 185, 11, 0.14),
            transparent 25%
        ),

        radial-gradient(
            circle at 10% 80%,
            rgba(240, 185, 11, 0.06),
            transparent 30%
        ),

        #0b0e11;

    color: #ffffff;

    overflow-x: hidden;
}

img {
    max-width: 100%;
    height: auto;
}

a,
button,
input,
select,
textarea {
    font-family: inherit;
}


/* =========================================================
   HEADER
========================================================= */

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

    min-width: 0;

}


.logo {

    width: 52px;

    height: 52px;

    flex: 0 0 52px;

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

    font-size: 22px;

}


.brand p {

    margin: 0;

    color: #848e9c;

    font-size: 13px;

}


/* =========================================================
   USER AREA
========================================================= */

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

    overflow-wrap: anywhere;

}


.username span {

    color: #848e9c;

    font-size: 13px;

}


.logout {

    display: inline-block;

    padding: 12px 18px;

    background: #f0b90b;

    color: #111111;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

    white-space: nowrap;

}


/* =========================================================
   NAVIGATION
========================================================= */

nav {

    display: flex;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

    overflow-x: auto;

    -webkit-overflow-scrolling: touch;

    scrollbar-width: thin;

}


nav a {

    display: block;

    padding: 18px;

    color: #848e9c;

    text-decoration: none;

    white-space: nowrap;

    transition: 0.2s;

}


nav a:hover {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, 0.05);

}


nav a:first-child {

    color: #f0b90b;

}


/* =========================================================
   MAIN
========================================================= */

main {

    width: 100%;

    max-width: 1250px;

    margin: auto;

    padding: 45px 25px;

}


/* =========================================================
   WELCOME
========================================================= */

.welcome {

    margin-bottom: 30px;

}


.welcome h2 {

    margin: 0 0 10px;

    font-size: 28px;

    overflow-wrap: anywhere;

}


.welcome p {

    margin: 0;

    color: #848e9c;

    line-height: 1.6;

}


/* =========================================================
   SETTINGS
========================================================= */

.settings {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 30px;

}


.setting {

    min-width: 0;

    padding: 18px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 10px;

}


.setting label {

    display: block;

    color: #848e9c;

    margin-bottom: 9px;

    font-size: 13px;

}


.setting select {

    width: 100%;

    min-width: 0;

    padding: 12px;

    background: #0b0e11;

    color: white;

    border:
        1px solid #343a43;

    border-radius: 7px;

    outline: none;

}


/* =========================================================
   BALANCE CARD
========================================================= */

.balance-card {

    position: relative;

    overflow: hidden;

    padding: 35px;

    margin-bottom: 45px;

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


.balance-card::after {

    content: "₿";

    position: absolute;

    right: 30px;

    bottom: -55px;

    color:
        rgba(240, 185, 11, 0.08);

    font-size: 180px;

    font-weight: bold;

    pointer-events: none;

}


.balance-label {

    color: #848e9c;

    position: relative;

    z-index: 1;

}


.balance {

    position: relative;

    z-index: 1;

    margin: 12px 0 30px;

    font-size: 45px;

    overflow-wrap: anywhere;

}


.account-info {

    position: relative;

    z-index: 1;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

}


.account-info > div {

    min-width: 0;

}


.account-info span {

    display: block;

    color: #848e9c;

    font-size: 13px;

    margin-bottom: 8px;

}


.account-info strong {

    overflow-wrap: anywhere;

}


/* =========================================================
   TRADING SECTION
========================================================= */

.trading-section {

    margin-top: 40px;

}


.section-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 22px;

}


.section-header h2 {

    margin: 0 0 8px;

    font-size: 28px;

}


.section-header p {

    margin: 0;

    color: #848e9c;

    line-height: 1.5;

}


.market-status {

    padding: 9px 14px;

    background:
        rgba(14, 203, 129, .10);

    color: #0ecb81;

    border-radius: 20px;

    font-size: 13px;

    white-space: nowrap;

}


/* =========================================================
   TRADING GRID
========================================================= */

.trading-grid {

    display: grid;

    grid-template-columns:
        2fr 1fr;

    gap: 22px;

}


/* =========================================================
   CHART
========================================================= */

.chart-card {

    min-width: 0;

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


.chart-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 15px;

}


.chart-label {

    color: #848e9c;

    font-size: 13px;

}


.chart-top h2 {

    margin: 8px 0 0;

    font-size: 30px;

    overflow-wrap: anywhere;

}


.price-change {

    padding: 8px 12px;

    background:
        rgba(14, 203, 129, .12);

    color: #0ecb81;

    border-radius: 7px;

    font-weight: bold;

    white-space: nowrap;

}


.chart-tabs {

    display: flex;

    gap: 8px;

    margin: 25px 0 15px;

    overflow-x: auto;

}


.chart-tab {

    flex: 0 0 auto;

    padding: 8px 13px;

    border: none;

    background: #252931;

    color: #848e9c;

    border-radius: 5px;

    cursor: pointer;

}


.chart-tab.active {

    background: #f0b90b;

    color: #111;

}


/* =========================================================
   CHART AREA
========================================================= */

.chart-area {

    width: 100%;

    height: 280px;

    padding: 10px;

    background: #0b0e11;

    border-radius: 10px;

    overflow: hidden;

}


.chart-area svg {

    display: block;

    width: 100%;

    height: 100%;

}


.chart-area polyline {

    fill: none;

    stroke: #f0b90b;

    stroke-width: 4;

    vector-effect:
        non-scaling-stroke;

}


/* =========================================================
   CHART FOOTER
========================================================= */

.chart-footer {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-top: 20px;

}


.chart-footer span {

    color: #848e9c;

    font-size: 12px;

}


.chart-footer strong {

    display: block;

    margin-top: 6px;

    color: white;

    font-size: 14px;

    overflow-wrap: anywhere;

}


/* =========================================================
   PERFORMANCE
========================================================= */

.performance-card {

    min-width: 0;

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


.performance-card h3 {

    margin-top: 0;

    color: #f0b90b;

}


.performance-item {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 17px 0;

    border-bottom:
        1px solid #2b3139;

}


.performance-item span {

    color: #848e9c;

}


.performance-item strong {

    color: white;

    text-align: right;

    overflow-wrap: anywhere;

}


.positive {

    color: #0ecb81 !important;

}


.negative {

    color: #f6465d !important;

}


.performance-note {

    margin-top: 20px;

    padding: 13px;

    background:
        rgba(240, 185, 11, .07);

    border-radius: 7px;

    color: #848e9c;

    font-size: 12px;

    line-height: 1.5;

}


/* =========================================================
   ACTIVITY
========================================================= */

.activity-section {

    margin-top: 50px;

}


.activity-table {

    width: 100%;

    overflow-x: auto;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

    -webkit-overflow-scrolling: touch;

}


.activity-row {

    min-width: 650px;

    display: grid;

    grid-template-columns:
        1.3fr
        1fr
        1fr
        1fr;

    gap: 15px;

    padding: 18px 22px;

    border-bottom:
        1px solid #2b3139;

    color: #d1d4dc;

}


.activity-row:last-child {

    border-bottom: none;

}


.activity-head {

    background: #20242c;

    color: #f0b90b;

    font-size: 13px;

    font-weight: bold;

}


/* =========================================================
   INVESTMENT SECTION
========================================================= */

.section-title {

    margin: 50px 0 10px;

    font-size: 28px;

}


.section-description {

    color: #848e9c;

    line-height: 1.6;

    margin-bottom: 25px;

}


.notice {

    padding: 18px;

    margin-bottom: 25px;

    background:
        rgba(240, 185, 11, 0.08);

    border-left:
        4px solid #f0b90b;

    border-radius: 6px;

    color: #d1d4dc;

    line-height: 1.6;

}


.plans {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

}


.plan {

    min-width: 0;

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

}


.plan:hover {

    border-color: #f0b90b;

}


.plan-name {

    color: #f0b90b;

    font-size: 13px;

    font-weight: bold;

    text-transform: uppercase;

}


.plan h3 {

    font-size: 30px;

    margin: 18px 0 10px;

    overflow-wrap: anywhere;

}


.plan p {

    color: #848e9c;

    line-height: 1.6;

}


.invest-button {

    display: block;

    margin-top: 22px;

    padding: 14px;

    text-align: center;

    background: #f0b90b;

    color: #111111;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

}


/* =========================================================
   QUICK LINKS
========================================================= */

.quick-links {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-top: 50px;

}


.quick-card {

    display: block;

    min-width: 0;

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

    text-decoration: none;

    color: white;

}


.quick-card:hover {

    border-color: #f0b90b;

}


.quick-card h3 {

    margin-top: 0;

    color: #f0b90b;

}


.quick-card p {

    margin-bottom: 0;

    color: #848e9c;

    line-height: 1.6;

}


/* =========================================================
   FOOTER
========================================================= */

footer {

    padding: 30px;

    text-align: center;

    color: #848e9c;

    font-size: 13px;

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1000px) {

    .settings,
    .plans,
    .quick-links {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .trading-grid {

        grid-template-columns: 1fr;

    }

}


/* =========================================================
   MOBILE — 700px AND BELOW
========================================================= */

@media (max-width: 700px) {

    .header {

        flex-direction: column;

        align-items: stretch;

        gap: 18px;

        padding: 15px;

    }


    .brand {

        width: 100%;

    }


    .brand h1 {

        font-size: 18px;

    }


    .brand p {

        font-size: 12px;

    }


    .logo {

        width: 45px;

        height: 45px;

        flex-basis: 45px;

        font-size: 22px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

        gap: 12px;

    }


    .username {

        min-width: 0;

        text-align: left;

    }


    .username strong {

        font-size: 14px;

    }


    .logout {

        padding: 11px 14px;

    }


    /* Navigation becomes horizontally scrollable */

    nav {

        padding: 0 10px;

        overflow-x: auto;

    }


    nav a {

        padding: 15px 13px;

        font-size: 13px;

    }


    main {

        padding: 30px 15px;

    }


    .welcome h2 {

        font-size: 24px;

    }


    .settings {

        grid-template-columns: 1fr;

    }


    .balance-card {

        padding: 25px 20px;

        margin-bottom: 35px;

    }


    .balance {

        font-size: 35px;

        margin-bottom: 25px;

    }


    .balance-card::after {

        right: -5px;

        bottom: -35px;

        font-size: 130px;

    }


    .account-info {

        grid-template-columns: 1fr;

        gap: 18px;

    }


    .section-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 14px;

    }


    .section-header h2 {

        font-size: 24px;

    }


    .market-status {

        align-self: flex-start;

    }


    .trading-grid {

        grid-template-columns: 1fr;

    }


    .chart-card,
    .performance-card {

        padding: 18px;

    }


    .chart-top {

        flex-direction: column;

        gap: 12px;

    }


    .chart-top h2 {

        font-size: 26px;

    }


    .price-change {

        align-self: flex-start;

    }


    .chart-area {

        height: 220px;

    }


    .chart-footer {

        grid-template-columns: 1fr;

        gap: 12px;

    }


    .performance-item {

        padding: 14px 0;

    }


    .activity-section {

        margin-top: 40px;

    }


    .activity-table {

        border-radius: 10px;

    }


    .activity-row {

        min-width: 620px;

        padding: 15px;

    }


    .section-title {

        font-size: 24px;

    }


    .plans {

        grid-template-columns: 1fr;

    }


    .plan {

        padding: 20px;

    }


    .plan h3 {

        font-size: 27px;

    }


    .quick-links {

        grid-template-columns: 1fr;

        gap: 15px;

        margin-top: 35px;

    }


    .quick-card {

        padding: 20px;

    }


    footer {

        padding: 25px 15px;

    }

}


/* =========================================================
   SMALL PHONE — 480px AND BELOW
========================================================= */

@media (max-width: 480px) {

    body {

        font-size: 14px;

    }


    main {

        padding: 25px 10px;

    }


    .header {

        padding: 13px 10px;

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

        flex-basis: 42px;

        border-radius: 9px;

        font-size: 20px;

    }


    .user-area {

        align-items: center;

    }


    .username strong {

        font-size: 13px;

    }


    .username span {

        font-size: 11px;

    }


    .logout {

        padding: 10px 12px;

        font-size: 13px;

    }


    nav {

        padding: 0 5px;

    }


    nav a {

        padding: 13px 11px;

        font-size: 12px;

    }


    .welcome {

        margin-bottom: 22px;

    }


    .welcome h2 {

        font-size: 21px;

    }


    .welcome p {

        font-size: 13px;

    }


    .setting {

        padding: 15px;

    }


    .balance-card {

        padding: 22px 16px;

        border-radius: 12px;

    }


    .balance {

        font-size: 29px;

    }


    .balance-card::after {

        font-size: 100px;

        bottom: -25px;

    }


    .account-info {

        gap: 15px;

    }


    .section-header h2 {

        font-size: 21px;

    }


    .section-header p {

        font-size: 13px;

    }


    .chart-card,
    .performance-card {

        padding: 15px;

        border-radius: 11px;

    }


    .chart-top h2 {

        font-size: 23px;

    }


    .chart-area {

        height: 190px;

        padding: 5px;

    }


    .chart-tabs {

        margin: 18px 0 12px;

    }


    .chart-tab {

        padding: 8px 11px;

        font-size: 12px;

    }


    .activity-row {

        min-width: 580px;

        font-size: 13px;

    }


    .section-title {

        font-size: 21px;

    }


    .section-description {

        font-size: 13px;

    }


    .notice {

        padding: 14px;

        font-size: 13px;

    }


    .plan {

        padding: 18px;

    }


    .plan h3 {

        font-size: 25px;

    }


    .plan p {

        font-size: 13px;

    }


    .quick-card {

        padding: 18px;

    }


    .quick-card h3 {

        font-size: 17px;

    }


    .quick-card p {

        font-size: 13px;

    }

}


/* =========================================================
   VERY SMALL PHONE — 360px AND BELOW
========================================================= */

@media (max-width: 360px) {

    main {

        padding: 20px 8px;

    }


    .header {

        padding: 12px 8px;

    }


    .brand h1 {

        font-size: 15px;

    }


    .user-area {

        gap: 8px;

    }


    .logout {

        padding: 9px 10px;

        font-size: 12px;

    }


    nav a {

        padding: 12px 9px;

        font-size: 11px;

    }


    .welcome h2 {

        font-size: 19px;

    }


    .balance {

        font-size: 26px;

    }


    .chart-area {

        height: 170px;

    }


    .activity-row {

        min-width: 540px;

    }

}

</style>

</head>


<body>


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
                Account Dashboard
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


<nav>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="#balance">
        Balance
    </a>

    <a href="#trading">
        Trading
    </a>

    <a href="#investment">
        Investment
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

    <a href="profile.php">
        Profile
    </a>

    <a href="settings.php">
        Settings
    </a>

</nav>


<main>


<section class="welcome">

    <h2 id="welcomeTitle">

        Welcome back,
        <?php echo htmlspecialchars($username); ?>

    </h2>

    <p id="welcomeText">
        View your account information and platform activity.
    </p>

</section>


<section class="settings">


    <div class="setting">

        <label id="countryLabel">
            Country
        </label>

        <select id="country">

            <option value="US">United States</option>
            <option value="NG">Nigeria</option>
            <option value="GB">United Kingdom</option>
            <option value="CA">Canada</option>
            <option value="DE">Germany</option>
            <option value="FR">France</option>
            <option value="IT">Italy</option>
            <option value="ES">Spain</option>
            <option value="NL">Netherlands</option>
            <option value="ZA">South Africa</option>
            <option value="GH">Ghana</option>
            <option value="KE">Kenya</option>
            <option value="AE">United Arab Emirates</option>
            <option value="SA">Saudi Arabia</option>
            <option value="IN">India</option>
            <option value="JP">Japan</option>
            <option value="CN">China</option>
            <option value="AU">Australia</option>
            <option value="BR">Brazil</option>
            <option value="MX">Mexico</option>

        </select>

    </div>


    <div class="setting">

        <label id="currencyLabel">
            Currency
        </label>

        <select id="currency">

            <option value="USD">USD - US Dollar</option>
            <option value="EUR">EUR - Euro</option>
            <option value="GBP">GBP - British Pound</option>
            <option value="NGN">NGN - Nigerian Naira</option>
            <option value="CAD">CAD - Canadian Dollar</option>
            <option value="AUD">AUD - Australian Dollar</option>
            <option value="CHF">CHF - Swiss Franc</option>
            <option value="JPY">JPY - Japanese Yen</option>
            <option value="CNY">CNY - Chinese Yuan</option>
            <option value="INR">INR - Indian Rupee</option>
            <option value="ZAR">ZAR - South African Rand</option>
            <option value="GHS">GHS - Ghanaian Cedi</option>
            <option value="KES">KES - Kenyan Shilling</option>
            <option value="AED">AED - UAE Dirham</option>
            <option value="SAR">SAR - Saudi Riyal</option>
            <option value="BRL">BRL - Brazilian Real</option>
            <option value="MXN">MXN - Mexican Peso</option>
            <option value="SEK">SEK - Swedish Krona</option>
            <option value="NOK">NOK - Norwegian Krone</option>
            <option value="DKK">DKK - Danish Krone</option>
            <option value="PLN">PLN - Polish Zloty</option>
            <option value="TRY">TRY - Turkish Lira</option>

        </select>

    </div>


    <div class="setting">

        <label id="languageLabel">
            Language
        </label>

        <select id="language">

            <option value="en">English</option>
            <option value="fr">Français</option>
            <option value="es">Español</option>
            <option value="de">Deutsch</option>
            <option value="pt">Português</option>

        </select>

    </div>


</section>


<section id="balance">

    <div class="balance-card">

        <p
            class="balance-label"
            id="balanceLabel"
        >
            Available Balance
        </p>


        <h2
            class="balance"
            id="balanceAmount"
            data-balance="<?php echo htmlspecialchars((string) $balance); ?>"
        >
            $<?php echo number_format($balance, 2); ?>
        </h2>


        <div class="account-info">

            <div>

                <span id="accountNumberLabel">
                    Account Number
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars($accountNumber);
                    ?>
                </strong>

            </div>


            <div>

                <span id="accountTypeLabel">
                    Account Type
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars($accountType);
                    ?>
                </strong>

            </div>


            <div>

                <span id="accountStatusLabel">
                    Account Status
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        ucfirst($accountStatus)
                    );
                    ?>
                </strong>

            </div>

        </div>

    </div>

</section>


<section
    class="trading-section"
    id="trading"
>


    <div class="section-header">

        <div>

            <h2>
                Trading Overview
            </h2>

            <p>
                Market activity and account performance overview.
            </p>

        </div>


        <span class="market-status">
            ● Market Active
        </span>

    </div>


    <div class="trading-grid">


        <div class="chart-card">

            <div class="chart-top">

                <div>

                    <span class="chart-label">
                        BTC / USD
                    </span>

                    <h2
                        id="btcPrice"
                        data-usd="67420"
                    >
                        $67,420.00
                    </h2>

                </div>


                <div class="price-change">
                    +2.84%
                </div>

            </div>


            <div class="chart-tabs">

                <button
                    type="button"
                    class="chart-tab active"
                >
                    1H
                </button>

                <button
                    type="button"
                    class="chart-tab"
                >
                    1D
                </button>

                <button
                    type="button"
                    class="chart-tab"
                >
                    1W
                </button>

                <button
                    type="button"
                    class="chart-tab"
                >
                    1M
                </button>

            </div>


            <div class="chart-area">

                <svg
                    viewBox="0 0 800 300"
                    preserveAspectRatio="none"
                >

                    <polyline
                        points="
                        0,230
                        40,210
                        80,225
                        120,180
                        160,195
                        200,150
                        240,175
                        280,130
                        320,145
                        360,100
                        400,125
                        440,90
                        480,115
                        520,75
                        560,100
                        600,60
                        640,85
                        680,50
                        720,70
                        760,35
                        800,55
                        "
                    />

                </svg>

            </div>


            <div class="chart-footer">

                <span>

                    24h Low

                    <strong
                        class="convert-amount"
                        data-usd="65820"
                    >
                        $65,820
                    </strong>

                </span>


                <span>

                    24h High

                    <strong
                        class="convert-amount"
                        data-usd="68120"
                    >
                        $68,120
                    </strong>

                </span>


                <span>

                    Volume

                    <strong
                        class="convert-amount"
                        data-usd="1240000000"
                    >
                        $1.24B
                    </strong>

                </span>

            </div>

        </div>


        <div class="performance-card">

            <h3>
                Customer Performance
            </h3>


            <div class="performance-item">

                <span>
                    Today's P/L
                </span>

                <strong
                    class="positive convert-amount"
                    data-usd="1240.50"
                >
                    +$1,240.50
                </strong>

            </div>


            <div class="performance-item">

                <span>
                    Weekly P/L
                </span>

                <strong
                    class="positive convert-amount"
                    data-usd="4820"
                >
                    +$4,820.00
                </strong>

            </div>


            <div class="performance-item">

                <span>
                    Open Positions
                </span>

                <strong>
                    4
                </strong>

            </div>


            <div class="performance-item">

                <span>
                    Total Trades
                </span>

                <strong>
                    27
                </strong>

            </div>


            <div class="performance-note">

                Customer dashboard statistics and account activity overview.

            </div>

        </div>


    </div>

</section>


<section class="activity-section">


    <div class="section-header">

        <div>

            <h2>
                Recent Trading Activity
            </h2>

            <p>
                Customer platform activity.
            </p>

        </div>

    </div>


    <div class="activity-table">


        <div class="activity-row activity-head">

            <span>User</span>
            <span>Market</span>
            <span>Trade</span>
            <span>Result</span>

        </div>


        <div class="activity-row">

            <span>Trader #1042</span>
            <span>BTC/USDT</span>
            <span>Long</span>

            <strong
                class="positive convert-amount"
                data-usd="842.20"
            >
                +$842.20
            </strong>

        </div>


        <div class="activity-row">

            <span>Trader #2087</span>
            <span>ETH/USDT</span>
            <span>Long</span>

            <strong
                class="positive convert-amount"
                data-usd="412.50"
            >
                +$412.50
            </strong>

        </div>


        <div class="activity-row">

            <span>Trader #3561</span>
            <span>SOL/USDT</span>
            <span>Short</span>

            <strong
                class="negative convert-amount"
                data-usd="-125"
            >
                -$125.00
            </strong>

        </div>


        <div class="activity-row">

            <span>Trader #4910</span>
            <span>BTC/USDT</span>
            <span>Long</span>

            <strong
                class="positive convert-amount"
                data-usd="1280.75"
            >
                +$1,280.75
            </strong>

        </div>


    </div>

</section>


<section id="investment">


    <h2 class="section-title">
        Investment Tiers
    </h2>


    <p class="section-description">

        Review the available tiers and their details before making
        any financial decisions.

    </p>


    <div class="notice">

        <strong>
            Important:
        </strong>

        Investments involve risk, and returns cannot be guaranteed.

    </div>


    <div class="plans">


        <div class="plan">

            <span class="plan-name">
                Starter Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="100"
            >
                $100.00
            </h3>

            <p>
                Entry-level tier information.
            </p>

            <a
                href="invest.php?plan=100"
                class="invest-button"
            >
                View Details
            </a>

        </div>


        <div class="plan">

            <span class="plan-name">
                Growth Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="250"
            >
                $250.00
            </h3>

            <p>
                Growth tier information.
            </p>

            <a
                href="invest.php?plan=250"
                class="invest-button"
            >
                View Details
            </a>

        </div>


        <div class="plan">

            <span class="plan-name">
                Advanced Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="400"
            >
                $400.00
            </h3>

            <p>
                Advanced tier information.
            </p>

            <a
                href="invest.php?plan=400"
                class="invest-button"
            >
                View Details
            </a>

        </div>


        <div class="plan">

            <span class="plan-name">
                Premium Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="500"
            >
                $500.00
            </h3>

            <p>
                Premium tier information.
            </p>

            <a
                href="invest.php?plan=500"
                class="invest-button"
            >
                View Details
            </a>

        </div>


        <div class="plan">

            <span class="plan-name">
                Professional Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="650"
            >
                $650.00
            </h3>

            <p>
                Professional tier information.
            </p>

            <a
                href="invest.php?plan=650"
                class="invest-button"
            >
                View Details
            </a>

        </div>


        <div class="plan">

            <span class="plan-name">
                Platinum Tier
            </span>

            <h3
                class="convert-amount"
                data-usd="1000"
            >
                $1,000.00
            </h3>

            <p>
                Platinum tier information.
            </p>

            <a
                href="invest.php?plan=1000"
                class="invest-button"
            >
                View Details
            </a>

        </div>


    </div>


</section>


<section class="quick-links">


    <a
        href="transactions.php"
        class="quick-card"
    >

        <h3>
            Transactions
        </h3>

        <p>
            View your account transaction records.
        </p>

    </a>


    <a
        href="transfer.php"
        class="quick-card"
    >

        <h3>
            Transfer
        </h3>

        <p>
            Enter and review transfer information.
        </p>

    </a>


    <a
        href="withdraw.php"
        class="quick-card"
    >

        <h3>
            Withdraw
        </h3>

        <p>
            View withdrawal requests and their status.
        </p>

    </a>


    <a
        href="wallet.php"
        class="quick-card"
    >

        <h3>
            Wallet Information
        </h3>

        <p>
            View cryptocurrency wallet information.
        </p>

    </a>


    <a
        href="messages.php"
        class="quick-card"
    >

        <h3>
            Help & Support
        </h3>

        <p>
            Send a message to the administrator and view replies.
        </p>

    </a>


    <a
        href="profile.php"
        class="quick-card"
    >

        <h3>
            My Profile
        </h3>

        <p>
            View and manage your basic profile information.
        </p>

    </a>


</section>


</main>


<footer>

    © <?php echo date("Y"); ?>

    Crypto Circle Trading

</footer>


<script>

/* =====================================
   USER DATA
===================================== */

const username =
    <?php echo json_encode($username); ?>;


/* =====================================
   BALANCE
===================================== */

const balanceElement =
    document.getElementById(
        "balanceAmount"
    );


const originalBalance =
    parseFloat(
        balanceElement.dataset.balance
    );


/* =====================================
   CURRENCY DATA
===================================== */

const currencyData = {

    USD: {
        symbol: "$",
        rate: 1
    },

    EUR: {
        symbol: "€",
        rate: 0.92
    },

    GBP: {
        symbol: "£",
        rate: 0.78
    },

    NGN: {
        symbol: "₦",
        rate: 1600
    },

    CAD: {
        symbol: "C$",
        rate: 1.36
    },

    AUD: {
        symbol: "A$",
        rate: 1.52
    },

    CHF: {
        symbol: "CHF ",
        rate: 0.90
    },

    JPY: {
        symbol: "¥",
        rate: 150
    },

    CNY: {
        symbol: "¥",
        rate: 7.20
    },

    INR: {
        symbol: "₹",
        rate: 83
    },

    ZAR: {
        symbol: "R",
        rate: 18.50
    },

    GHS: {
        symbol: "GH₵",
        rate: 15.50
    },

    KES: {
        symbol: "KSh ",
        rate: 130
    },

    AED: {
        symbol: "AED ",
        rate: 3.67
    },

    SAR: {
        symbol: "SAR ",
        rate: 3.75
    },

    BRL: {
        symbol: "R$",
        rate: 5
    },

    MXN: {
        symbol: "MX$",
        rate: 17
    },

    SEK: {
        symbol: "kr ",
        rate: 10.40
    },

    NOK: {
        symbol: "kr ",
        rate: 10.70
    },

    DKK: {
        symbol: "kr ",
        rate: 6.87
    },

    PLN: {
        symbol: "zł ",
        rate: 4
    },

    TRY: {
        symbol: "₺",
        rate: 32
    }

};


/* =====================================
   FORMAT MONEY
===================================== */

function formatMoney(
    usdAmount,
    currency
) {

    const data =
        currencyData[currency];


    if (!data) {

        return "$0.00";

    }


    const convertedAmount =
        usdAmount * data.rate;


    const sign =
        convertedAmount < 0
            ? "-"
            : "";


    const amount =
        Math.abs(
            convertedAmount
        );


    return (
        sign +
        data.symbol +
        amount.toLocaleString(
            undefined,
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        )
    );

}


/* =====================================
   UPDATE CURRENCY
===================================== */

function updateCurrency() {

    const currency =
        document.getElementById(
            "currency"
        ).value;


    balanceElement.innerText =
        formatMoney(
            originalBalance,
            currency
        );


    const btcPrice =
        document.getElementById(
            "btcPrice"
        );


    btcPrice.innerText =
        formatMoney(
            parseFloat(
                btcPrice.dataset.usd
            ),
            currency
        );


    document
        .querySelectorAll(
            ".convert-amount"
        )
        .forEach(
            function(element) {

                const usdAmount =
                    parseFloat(
                        element.dataset.usd
                    );


                element.innerText =
                    formatMoney(
                        usdAmount,
                        currency
                    );

            }
        );


    localStorage.setItem(
        "dashboardCurrency",
        currency
    );

}


/* =====================================
   LOAD SAVED CURRENCY
===================================== */

const currencySelect =
    document.getElementById(
        "currency"
    );


const savedCurrency =
    localStorage.getItem(
        "dashboardCurrency"
    );


if (
    savedCurrency &&
    currencyData[savedCurrency]
) {

    currencySelect.value =
        savedCurrency;

}


updateCurrency();


currencySelect.addEventListener(
    "change",
    updateCurrency
);


/* =====================================
   COUNTRY
===================================== */

const countrySelect =
    document.getElementById(
        "country"
    );


const savedCountry =
    localStorage.getItem(
        "dashboardCountry"
    );


if (savedCountry) {

    countrySelect.value =
        savedCountry;

}


countrySelect.addEventListener(
    "change",
    function() {

        localStorage.setItem(
            "dashboardCountry",
            this.value
        );

    }
);


/* =====================================
   TRANSLATIONS
===================================== */

const translations = {

    en: {

        welcome:
        "Welcome back",

        welcomeText:
        "View your account information and platform activity.",

        country:
        "Country",

        currency:
        "Currency",

        language:
        "Language",

        balance:
        "Available Balance"

    },


    fr: {

        welcome:
        "Bon retour",

        welcomeText:
        "Consultez les informations de votre compte et votre activité.",

        country:
        "Pays",

        currency:
        "Devise",

        language:
        "Langue",

        balance:
        "Solde disponible"

    },


    es: {

        welcome:
        "Bienvenido de nuevo",

        welcomeText:
        "Consulta la información de tu cuenta y la actividad.",

        country:
        "País",

        currency:
        "Moneda",

        language:
        "Idioma",

        balance:
        "Saldo disponible"

    },


    de: {

        welcome:
        "Willkommen zurück",

        welcomeText:
        "Sehen Sie Ihre Kontoinformationen und Aktivitäten.",

        country:
        "Land",

        currency:
        "Währung",

        language:
        "Sprache",

        balance:
        "Verfügbares Guthaben"

    },


    pt: {

        welcome:
        "Bem-vindo de volta",

        welcomeText:
        "Veja as informações da sua conta e atividade da plataforma.",

        country:
        "País",

        currency:
        "Moeda",

        language:
        "Idioma",

        balance:
        "Saldo disponível"

    }

};


/* =====================================
   UPDATE LANGUAGE
===================================== */

function updateLanguage() {

    const language =
        document.getElementById(
            "language"
        ).value;


    const text =
        translations[language];


    document.getElementById(
        "welcomeTitle"
    ).innerText =
        text.welcome +
        ", " +
        username;


    document.getElementById(
        "welcomeText"
    ).innerText =
        text.welcomeText;


    document.getElementById(
        "countryLabel"
    ).innerText =
        text.country;


    document.getElementById(
        "currencyLabel"
    ).innerText =
        text.currency;


    document.getElementById(
        "languageLabel"
    ).innerText =
        text.language;


    document.getElementById(
        "balanceLabel"
    ).innerText =
        text.balance;


    localStorage.setItem(
        "dashboardLanguage",
        language
    );

}


/* =====================================
   LOAD SAVED LANGUAGE
===================================== */

const languageSelect =
    document.getElementById(
        "language"
    );


const savedLanguage =
    localStorage.getItem(
        "dashboardLanguage"
    );


if (
    savedLanguage &&
    translations[savedLanguage]
) {

    languageSelect.value =
        savedLanguage;

}


updateLanguage();


languageSelect.addEventListener(
    "change",
    updateLanguage
);


/* =====================================
   CHART TABS
===================================== */

const chartButtons =
    document.querySelectorAll(
        ".chart-tab"
    );


chartButtons.forEach(
    function(button) {

        button.addEventListener(
            "click",
            function() {

                chartButtons.forEach(
                    function(item) {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                this.classList.add(
                    "active"
                );

            }
        );

    }
);

</script>


</body>

</html>
```
