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
   VARIABLES
===================================== */

$username = "User";
$balance = 0;
$accountId = 0;
$accountStatus = "inactive";

$message = "";
$messageType = "";

$withdrawals = [];


/* =====================================
   GET USER AND ACCOUNT INFORMATION
===================================== */

try {

    $userStmt = $pdo->prepare("
        SELECT
            users.username,
            accounts.id AS account_id,
            accounts.balance,
            accounts.account_status

        FROM users

        LEFT JOIN accounts
            ON accounts.user_id = users.id

        WHERE users.id = ?

        LIMIT 1
    ");

    $userStmt->execute([
        $userId
    ]);

    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);


    if ($userData) {

        $username =
            $userData["username"] ?? "User";

        $accountId =
            (int) ($userData["account_id"] ?? 0);

        $balance =
            (float) ($userData["balance"] ?? 0);

        $accountStatus =
            strtolower(
                $userData["account_status"] ?? "inactive"
            );

    }

} catch (Exception $e) {

    $message =
        "Unable to load account information.";

    $messageType =
        "error";

}


/* =====================================
   CREATE ACCOUNT WITHDRAWAL REQUEST
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["request_withdrawal"])
) {

    $amount =
        (float) ($_POST["amount"] ?? 0);


    if ($accountId <= 0) {

        $message =
            "No account was found for this user.";

        $messageType =
            "error";

    } elseif ($accountStatus !== "active") {

        $message =
            "Your account must be active before submitting a withdrawal request.";

        $messageType =
            "error";

    } elseif ($amount <= 0) {

        $message =
            "Please enter a valid withdrawal amount.";

        $messageType =
            "error";

    } elseif ($amount > $balance) {

        $message =
            "The requested amount is greater than your available balance.";

        $messageType =
            "error";

    } else {

        try {

            $description =
    "Withdrawal request: €"
    . number_format($amount, 2)
    . " - Pending review";

            $withdrawStmt =
                $pdo->prepare("
                    INSERT INTO transactions
                    (
                        user_id,
                        transaction_type,
                        description,
                        status
                    )
                    VALUES
                    (
                        ?,
                        'withdrawal',
                        ?,
                        'pending'
                    )
                ");


            $withdrawStmt->execute([
                $userId,
                $description
            ]);


            $message =
                "Your withdrawal request has been submitted successfully and is now pending.";

            $messageType =
                "success";

        } catch (Exception $e) {

            $message =
                "Unable to create your withdrawal request.";

            $messageType =
                "error";

        }

    }

}


/* =====================================
   LOAD WITHDRAWAL HISTORY
===================================== */

try {

    $withdrawalsStmt =
        $pdo->prepare("
            SELECT
                id,
                transaction_type,
                description,
                status

            FROM transactions

            WHERE
                user_id = ?

                AND transaction_type = 'withdrawal'

            ORDER BY id DESC
        ");


    $withdrawalsStmt->execute([
        $userId
    ]);


    $withdrawals =
        $withdrawalsStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Exception $e) {

    $withdrawals = [];

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

<title>Withdraw | Crypto Circle Trading</title>


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
            rgba(240, 185, 11, .12),
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

    gap: 13px;

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

    font-size: 24px;

    font-weight: bold;

}


.brand h1 {

    margin: 0 0 5px;

    font-size: 21px;

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


.user-name {

    text-align: right;

}


.user-name strong {

    display: block;

}


.user-name span {

    display: block;

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

    overflow-x: auto;

    padding-left: 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

}


.navigation a {

    padding: 18px 20px;

    color: #a9b0bc;

    text-decoration: none;

    font-weight: bold;

    white-space: nowrap;

}


.navigation a:hover {

    color: #f0b90b;

}


.navigation .active {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, .06);

}


/* =====================================
   MAIN
===================================== */

main {

    max-width: 1000px;

    margin: auto;

    padding: 45px 25px;

}


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
   BALANCE
===================================== */

.balance-card {

    margin-bottom: 30px;

    padding: 25px;

    background:
        linear-gradient(
            135deg,
            #f0b90b,
            #c99400
        );

    color: #111;

    border-radius: 14px;

}


.balance-card span {

    font-size: 14px;

}


.balance-card h2 {

    margin: 10px 0 0;

    font-size: 32px;

}


/* =====================================
   CARD
===================================== */

.card {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

    padding: 30px;

    margin-bottom: 30px;

}


.card h3 {

    margin-top: 0;

    color: #f0b90b;

}


/* =====================================
   FORM
===================================== */

.card label {

    display: block;

    margin-bottom: 10px;

    color: #d1d4dc;

}


.card input {

    width: 100%;

    padding: 15px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 8px;

    color: white;

    font-size: 16px;

    outline: none;

}


.card input:focus {

    border-color: #f0b90b;

}


/* =====================================
   REQUIREMENT
===================================== */

.requirement {

    margin: 25px 0;

    padding: 20px;

    background:
        rgba(240, 185, 11, .08);

    border:
        1px solid #f0b90b;

    border-radius: 10px;

}


.requirement h4 {

    margin-top: 0;

    color: #f0b90b;

}


.requirement p {

    margin-bottom: 0;

    line-height: 1.6;

    color: #d1d4dc;

}


.fee-amount {

    display: block;

    margin-top: 12px;

    color: #f0b90b;

    font-size: 28px;

    font-weight: bold;

}


/* =====================================
   BUTTON
===================================== */

.button {

    padding: 14px 28px;

    border: none;

    border-radius: 8px;

    background: #f0b90b;

    color: #111;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

}


.button:hover {

    opacity: .9;

}


/* =====================================
   ALERTS
===================================== */

.alert {

    padding: 16px;

    margin-bottom: 25px;

    border-radius: 8px;

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
   WITHDRAWAL SLIP
===================================== */

.slip {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

    padding: 25px;

    margin-bottom: 20px;

}


.slip-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


.slip-header h3 {

    margin: 0;

    color: #f0b90b;

}


.pending {

    display: inline-block;

    padding: 8px 14px;

    border-radius: 20px;

    background:
        rgba(240, 185, 11, .12);

    color: #f0b90b;

    font-size: 12px;

    font-weight: bold;

}


.slip-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 13px 0;

    border-bottom:
        1px solid #2b3139;

}


.slip-row:last-of-type {

    border-bottom: none;

}


.slip-row span {

    color: #848e9c;

}


.slip-row strong {

    text-align: right;

}


/* =====================================
   WITHDRAWAL FEE
===================================== */

.fee-box {

    margin-top: 25px;

    padding: 22px;

    background:
        rgba(240, 185, 11, .08);

    border:
        1px solid #f0b90b;

    border-radius: 10px;

}


.fee-box h4 {

    margin: 0 0 15px;

    color: #f0b90b;

    font-size: 18px;

}


.fee-box p {

    margin: 0;

    color: #d1d4dc;

    line-height: 1.6;

}


.fee-value {

    margin-top: 15px !important;

    color: #f0b90b !important;

    font-size: 28px;

    font-weight: bold;

}


/* =====================================
   ACCOUNT NOTICE
===================================== */

.account-notice {

    margin-top: 20px;

    padding: 15px;

    background:
        rgba(246, 70, 93, .08);

    border:
        1px solid #f6465d;

    border-radius: 8px;

    color: #f1b0b7;

}


.account-notice strong {

    display: block;

}


.Account-notice p {

    margin: 8px 0 0;

    line-height: 1.6;

}


/* =====================================
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
===================================== */

.empty {

    padding: 35px;

    text-align: center;

    color: #848e9c;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 700px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

    }


    .user-name {

        text-align: left;

    }


    main {

        padding: 30px 15px;

    }


    .slip-row {

        flex-direction: column;

        gap: 5px;

    }


    .slip-row strong {

        text-align: left;

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
                User Account
            </p>

        </div>

    </div>


    <div class="user-area">

        <div class="user-name">

            <strong>
                <?php echo htmlspecialchars($username); ?>
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

    <a href="transactions.php">
        Transactions
    </a>

    <a href="transfer.php">
        Transfer
    </a>

    <a
        href="withdraw.php"
        class="active"
    >
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
        Withdraw Funds
    </h2>

    <p>
        Submit a withdrawal request for review.
    </p>

</section>


<div class="balance-card">

    <span>
        Available Balance
    </span>

    <h2>

        €

        <?php
        echo number_format(
            $balance,
            2
        );
        ?>

    </h2>

</div>


<?php if (!empty($message)): ?>

<div
    class="
        alert
        <?php echo htmlspecialchars($messageType); ?>
    "
>

    <?php
    echo htmlspecialchars($message);
    ?>

</div>

<?php endif; ?>


<!-- WITHDRAWAL FORM -->

<section class="card">

    <h3>
        Withdrawal Request
    </h3>


    <form method="POST">

        <label for="amount">
            Withdrawal Amount (€)
        </label>


        <input
            type="number"
            id="amount"
            name="amount"
            min="1"
            step="0.01"
            max="<?php echo htmlspecialchars((string) $balance); ?>"
            placeholder="Enter withdrawal amount"
            required
        >


        <div class="requirement">

            <h4>
                Request Status
            </h4>

            <p>

                After submitting your request, it will appear
                below with a pending status.

            </p>

        </div>


        <button
            type="submit"
            name="request_withdrawal"
            class="button"
        >
            Submit Withdrawal Request
        </button>

    </form>

</section>


<!-- WITHDRAWAL HISTORY -->

<section>

<h2>
    Withdrawal Requests
</h2>


<?php if (count($withdrawals) > 0): ?>


<?php foreach ($withdrawals as $withdrawal): ?>


<?php

$currentStatus =
    strtolower(
        $withdrawal["status"]
        ?? "pending"
    );

?>


<div class="slip">


    <div class="slip-header">

        <h3>
            Withdrawal Request
        </h3>


        <span class="pending">

            <?php
            echo htmlspecialchars(
                strtoupper(
                    $currentStatus
                )
            );
            ?>

        </span>

    </div>


    <div class="slip-row">

        <span>
            Request ID
        </span>

        <strong>

            #
            <?php
            echo (int)
                $withdrawal["id"];
            ?>

        </strong>

    </div>


    <div class="slip-row">

        <span>
            Status
        </span>

        <strong>

            <?php
            echo htmlspecialchars(
                strtoupper(
                    $currentStatus
                )
            );
            ?>

        </strong>

    </div>


    <div class="slip-row">

        <span>
            Request Details
        </span>

        <strong>

            <?php
            echo htmlspecialchars(
                $withdrawal["description"]
                ?? ""
            );
            ?>

        </strong>

    </div>


    <!-- PROCESSING FEE AFTER PENDING -->

    <?php if ($currentStatus === "pending"): ?>


    <div class="fee-box">

        <h4>
            Withdrawal Processing Fee
        </h4>

        <p>

            This pending withdrawal request displays the
            processing-fee requirement.

        </p>

        <p class="fee-value">

           
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

            


            €1,000.00

        </p>

    </div>


    <?php endif; ?>


    <!-- ACCOUNT NOTICE -->

    <div class="Account-notice">

        <strong>
            ACCOUNT ONLY
        </strong>

        <p>

            A transaction fee of €1,000.00 applies to this transaction. Please review all payment details before proceeding.
        </p>

    </div>


</div>


<?php endforeach; ?>


<?php else: ?>


<div class="empty">

    <h3>
     Withdrawal Requests
    </h3>

    <p>
        Your withdrawal requests will appear here.
    </p>

</div>


<?php endif; ?>


</section>


</main>


</body>

</html>