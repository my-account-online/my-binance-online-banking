<?php

session_start();

require "db.php";


/* =====================================
   USER ACCESS CHECK
===================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit;

}


/* =====================================
   PREVENT ADMIN FROM OPENING USER PAGE
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

$username = "User";

$transactions = [];

$errorMessage = "";


/* =====================================
   GET USER INFORMATION
===================================== */

try {

    $userStmt = $pdo->prepare("
        SELECT username
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([
        $userId
    ]);

    $userData = $userStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if ($userData) {

        $username =
            $userData["username"];

    }

} catch (Exception $e) {

    $username = "User";

}


/* =====================================
   LOAD TRANSACTIONS
===================================== */

try {

    $transactionStmt = $pdo->prepare("
        SELECT
            id,
            transaction_type,
            amount,
            description,
            status,
            created_at

        FROM transactions

        WHERE user_id = ?

        ORDER BY id DESC
    ");


    $transactionStmt->execute([
        $userId
    ]);


    $transactions =
        $transactionStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Exception $e) {

    $errorMessage =
        "Unable to load transactions.";

    $transactions = [];

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

<title>Transactions | Crypto Circle Trading</title>

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

    font-size: 25px;

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

    overflow-y: hidden;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

}


.navigation a {

    display: block;

    padding: 18px 20px;

    color: #a9b0bc;

    text-decoration: none;

    font-weight: bold;

    font-size: 14px;

    white-space: nowrap;

    border-bottom: 2px solid transparent;

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

main {

    width: 100%;

    max-width: 1300px;

    margin: 0 auto;

    padding: 45px 25px 60px;

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

    color: #a9b0bc;

}


/* =====================================
   ERROR
===================================== */

.error {

    padding: 16px;

    margin-bottom: 25px;

    background:
        rgba(246, 70, 93, .12);

    border:
        1px solid #f6465d;

    border-radius: 8px;

    color: #f6465d;

}


/* =====================================
   TABLE
===================================== */

.table-container {

    width: 100%;

    overflow-x: auto;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


table {

    width: 100%;

    min-width: 850px;

    border-collapse: collapse;

}


th {

    padding: 18px;

    text-align: left;

    background: #20242c;

    color: #f0b90b;

    font-size: 13px;

}


td {

    padding: 18px;

    border-top:
        1px solid #2b3139;

    color: #d1d4dc;

    font-size: 14px;

    vertical-align: middle;

}


tbody tr:hover {

    background:
        rgba(255,255,255,.025);

}


/* =====================================
   TRANSACTION TYPE
===================================== */

.type {

    display: inline-flex;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

    background:
        rgba(240, 185, 11, .12);

    color: #f0b90b;

}


/* =====================================
   STATUS
===================================== */

.status {

    display: inline-flex;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.status-completed {

    background:
        rgba(14, 203, 129, .12);

    color: #0ecb81;

}


.status-pending {

    background:
        rgba(240, 185, 11, .12);

    color: #f0b90b;

}


.status-failed {

    background:
        rgba(246, 70, 93, .12);

    color: #f6465d;

}


.status-processing {

    background:
        rgba(240, 185, 11, .12);

    color: #f0b90b;

}


/* =====================================
   AMOUNT
===================================== */

.amount {

    font-weight: bold;

    white-space: nowrap;

}


.amount-positive {

    color: #0ecb81;

}


.amount-negative {

    color: #f6465d;

}


/* =====================================
   EMPTY STATE
===================================== */

.empty {

    padding: 70px 25px;

    text-align: center;

    color: #848e9c;

}


.empty-icon {

    width: 60px;

    height: 60px;

    margin: 0 auto 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(240, 185, 11, .10);

    color: #f0b90b;

    font-size: 27px;

}


.empty h3 {

    margin: 0 0 8px;

    color: #ffffff;

    font-size: 20px;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 700px) {

    .header {

        flex-direction: column;

        align-items: stretch;

        padding: 18px 15px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

    }


    .user-name {

        text-align: left;

    }


    .navigation {

        padding: 0 8px;

    }


    .navigation a {

        padding: 15px 14px;

        font-size: 13px;

    }


    main {

        padding: 30px 12px 45px;

    }


    .page-heading h2 {

        font-size: 25px;

    }


    table {

        min-width: 760px;

    }


    th,
    td {

        padding: 14px;

    }

}

</style>

</head>

<body>


<!-- HEADER -->

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
                Transaction History
            </p>

        </div>

    </div>


    <div class="user-area">

        <div class="user-name">

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


<!-- NAVIGATION -->

<nav class="navigation">

    <a href="dashboard.php">
        Dashboard
    </a>

    <a
        href="transactions.php"
        class="active"
    >
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

</nav>


<!-- MAIN -->

<main>


<section class="page-heading">

    <h2>
        Transaction History
    </h2>

    <p>
        View your account transaction activity.
    </p>

</section>


<?php if ($errorMessage !== ""): ?>

<div class="error">

    <?php
    echo htmlspecialchars($errorMessage);
    ?>

</div>

<?php endif; ?>


<div class="table-container">


<?php if (count($transactions) > 0): ?>


<table>

<thead>

<tr>

    <th>ID</th>

    <th>Type</th>

    <th>Description</th>

    <th>Amount</th>

    <th>Status</th>

    <th>Date</th>

</tr>

</thead>


<tbody>


<?php foreach ($transactions as $transaction): ?>


<?php

$type =
    strtolower(
        trim(
            $transaction["transaction_type"]
            ?? "transaction"
        )
    );


$status =
    strtolower(
        trim(
            $transaction["status"]
            ?? "completed"
        )
    );


$isPositive =
    in_array(
        $type,
        [
            "deposit",
            "credit",
            "received",
            "profit",
            "bonus"
        ],
        true
    );


$allowedStatuses = [
    "completed",
    "pending",
    "failed",
    "processing"
];


$statusClass =
    in_array(
        $status,
        $allowedStatuses,
        true
    )
        ? $status
        : "pending";


/* CLEAN OLD WITHDRAWAL DESCRIPTIONS */

$description =
    $transaction["description"]
    ?? "-";


$description = str_replace(
    "Demo withdrawal request:",
    "Withdrawal request:",
    $description
);

?>


<tr>


<td>

    #
    <?php
    echo (int) $transaction["id"];
    ?>

</td>


<td>

    <span class="type">

        <?php
        echo htmlspecialchars(
            ucfirst($type)
        );
        ?>

    </span>

</td>


<td>

    <?php
    echo htmlspecialchars($description);
    ?>

</td>


<td
    class="
        amount
        <?php
        echo $isPositive
            ? "amount-positive"
            : "amount-negative";
        ?>
    "
>

    <?php
    echo $isPositive
        ? "+"
        : "-";
    ?>

    €

    <?php

    echo number_format(
        (float) ($transaction["amount"] ?? 0),
        2
    );

    ?>

</td>


<td>

    <span
        class="
            status
            status-<?php
                echo htmlspecialchars(
                    $statusClass
                );
            ?>
        "
    >

        <?php
        echo htmlspecialchars(
            ucfirst($status)
        );
        ?>

    </span>

</td>


<td>

    <?php

    echo htmlspecialchars(
        $transaction["created_at"]
        ?? "-"
    );

    ?>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty">

    <div class="empty-icon">
        ₿
    </div>

    <h3>
        No Transactions Yet
    </h3>

    <p>
        Your transactions will appear here when available.
    </p>

</div>


<?php endif; ?>


</div>


</main>


</body>

</html>