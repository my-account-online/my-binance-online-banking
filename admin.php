<?php

session_start();

require "db.php";


/* =====================================
   ADMIN ACCESS CHECK
===================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit;

}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: dashboard.php");
    exit;

}


/* =====================================
   VARIABLES
===================================== */

$message = "";
$messageType = "";

$admin = [
    "username" => "Administrator"
];

$users = [];

$totalUsers = 0;
$totalAccounts = 0;
$activeAccounts = 0;

$search = trim($_GET["search"] ?? "");


/* =====================================
   GET ADMIN INFORMATION
===================================== */

try {

    $adminId = (int) $_SESSION["user_id"];

    $adminStmt = $pdo->prepare("
        SELECT username
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $adminStmt->execute([$adminId]);

    $adminData = $adminStmt->fetch(PDO::FETCH_ASSOC);

    if ($adminData) {

        $admin = $adminData;

    }

} catch (Exception $e) {

    $admin = [
        "username" => "Administrator"
    ];

}


/* =====================================
   UPDATE ACCOUNT STATUS
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_status"])
) {

    $accountId = (int) ($_POST["account_id"] ?? 0);

    $newStatus =
        $_POST["account_status"] ?? "";

    $allowedStatuses = [
        "active",
        "inactive"
    ];


    if (
        $accountId <= 0 ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $message =
            "Invalid account update request.";

        $messageType =
            "error";

    } else {

        try {

            $checkStmt =
                $pdo->prepare("
                    SELECT users.role
                    FROM accounts
                    INNER JOIN users
                    ON users.id = accounts.user_id
                    WHERE accounts.id = ?
                    LIMIT 1
                ");

            $checkStmt->execute([
                $accountId
            ]);

            $accountOwner =
                $checkStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                !$accountOwner ||
                ($accountOwner["role"] ?? "") === "admin"
            ) {

                $message =
                    "Administrator accounts cannot be modified.";

                $messageType =
                    "error";

            } else {

                $updateStmt =
                    $pdo->prepare("
                        UPDATE accounts
                        SET account_status = ?
                        WHERE id = ?
                    ");

                $updateStmt->execute([
                    $newStatus,
                    $accountId
                ]);


                $message =
                    "Account status updated successfully.";

                $messageType =
                    "success";

            }

        } catch (Exception $e) {

            $message =
                "Unable to update the account.";

            $messageType =
                "error";

        }

    }

}


/* =====================================
   ADJUST ACCOUNT BALANCE
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["adjust_balance"])
) {

    $accountId =
        (int) ($_POST["account_id"] ?? 0);

    $action =
        $_POST["balance_action"] ?? "";

    $amount =
        (float) ($_POST["amount"] ?? 0);


    if ($accountId <= 0) {

        $message =
            "Invalid account selected.";

        $messageType =
            "error";

    } elseif ($amount <= 0) {

        $message =
            "Please enter a valid amount.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $action,
            ["add", "remove"],
            true
        )
    ) {

        $message =
            "Invalid balance action.";

        $messageType =
            "error";

    } else {

        try {

            $balanceStmt =
                $pdo->prepare("
                    SELECT
                        accounts.balance,
                        users.role

                    FROM accounts

                    INNER JOIN users
                    ON users.id = accounts.user_id

                    WHERE accounts.id = ?

                    LIMIT 1
                ");


            $balanceStmt->execute([
                $accountId
            ]);


            $accountData =
                $balanceStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$accountData) {

                $message =
                    "Account not found.";

                $messageType =
                    "error";

            } elseif (
                ($accountData["role"] ?? "") === "admin"
            ) {

                $message =
                    "Administrator balance cannot be modified.";

                $messageType =
                    "error";

            } else {

                $currentBalance =
                    (float)
                    $accountData["balance"];


                if ($action === "add") {

                    $newBalance =
                        $currentBalance + $amount;

                } else {

                    $newBalance =
                        $currentBalance - $amount;

                    if ($newBalance < 0) {

                        $newBalance = 0;

                    }

                }


                $updateBalanceStmt =
                    $pdo->prepare("
                        UPDATE accounts
                        SET balance = ?
                        WHERE id = ?
                    ");


                $updateBalanceStmt->execute([
                    $newBalance,
                    $accountId
                ]);


                $message =
                    $action === "add"
                    ? "Account balance added successfully."
                    : "Account balance removed successfully.";


                $messageType =
                    "success";

            }

        } catch (Exception $e) {

            $message =
                "Unable to adjust the demo balance.";

            $messageType =
                "error";

        }

    }

}


/* =====================================
   GET USERS AND ACCOUNTS
===================================== */

try {

    if ($search !== "") {

        $searchTerm =
            "%" . $search . "%";


        $usersStmt =
            $pdo->prepare("
                SELECT

                    users.id AS user_id,
                    users.username,
                    users.role,
                    users.created_at,

                    accounts.id AS account_id,
                    accounts.account_number,
                    accounts.account_type,
                    accounts.account_status,
                    accounts.balance

                FROM users

                LEFT JOIN accounts
                ON accounts.user_id = users.id

                WHERE users.username LIKE ?

                ORDER BY users.id DESC
            ");


        $usersStmt->execute([
            $searchTerm
        ]);

    } else {

        $usersStmt =
            $pdo->prepare("
                SELECT

                    users.id AS user_id,
                    users.username,
                    users.role,
                    users.created_at,

                    accounts.id AS account_id,
                    accounts.account_number,
                    accounts.account_type,
                    accounts.account_status,
                    accounts.balance

                FROM users

                LEFT JOIN accounts
                ON accounts.user_id = users.id

                ORDER BY users.id DESC
            ");


        $usersStmt->execute();

    }


    $users =
        $usersStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Exception $e) {

    $message =
        "Unable to load user records.";

    $messageType =
        "error";

    $users = [];

}


/* =====================================
   CALCULATE STATISTICS
===================================== */

$userIds = [];

$totalUsers = 0;
$totalAccounts = 0;
$activeAccounts = 0;


foreach ($users as $account) {

    $role =
        strtolower(
            $account["role"] ?? "user"
        );


    if (
        $role !== "admin" &&
        !in_array(
            $account["user_id"],
            $userIds,
            true
        )
    ) {

        $userIds[] =
            $account["user_id"];

    }


    if (
        !empty(
            $account["account_id"]
        )
    ) {

        $totalAccounts++;


        if (
            strtolower(
                $account["account_status"]
                ?? ""
            ) === "active"
        ) {

            $activeAccounts++;

        }

    }

}


$totalUsers =
    count($userIds);

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
    Admin Dashboard | Crypto Circle Trading
</title>


<style>

/* =====================================
   ROOT
===================================== */

:root {

    --yellow: #f0b90b;

    --yellow-light: #ffc933;

    --black: #0b0e11;

    --card: #181a20;

    --card-light: #20242c;

    --border: #2b3139;

    --text: #f5f5f5;

    --muted: #848e9c;

    --green: #0ecb81;

    --red: #f6465d;

}


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
            circle at 85% 5%,
            rgba(240,185,11,.12),
            transparent 25%
        ),

        radial-gradient(
            circle at 10% 80%,
            rgba(240,185,11,.05),
            transparent 25%
        ),

        var(--black);

    color: var(--text);

}


/* =====================================
   HEADER
===================================== */

.header {

    position: sticky;

    top: 0;

    z-index: 100;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 15px 5%;

    background:
        rgba(24,26,32,.96);

    backdrop-filter:
        blur(15px);

    border-bottom:
        1px solid var(--border);

}


.brand {

    display: flex;

    align-items: center;

    gap: 14px;

}


.logo {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            var(--yellow-light),
            var(--yellow)
        );

    color: #111;

    border-radius: 12px;

    font-size: 24px;

    font-weight: bold;

    box-shadow:
        0 8px 25px
        rgba(240,185,11,.18);

}


.brand h1 {

    margin: 0 0 4px;

    font-size: 20px;

}


.brand p {

    margin: 0;

    color: var(--muted);

    font-size: 12px;

}


/* =====================================
   ADMIN MENU
===================================== */

.admin-area {

    display: flex;

    align-items: center;

    gap: 12px;

}


.admin-profile {

    text-align: right;

}


.admin-profile strong {

    display: block;

    font-size: 14px;

}


.admin-profile span {

    display: block;

    margin-top: 3px;

    color: var(--muted);

    font-size: 11px;

}


.admin-link {

    padding: 11px 15px;

    background: #252931;

    border:
        1px solid #343a43;

    border-radius: 8px;

    color: white;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;

    transition: .2s;

}


.admin-link:hover {

    border-color: var(--yellow);

    color: var(--yellow);

}


.logout {

    padding: 11px 17px;

    background:
        linear-gradient(
            135deg,
            var(--yellow-light),
            var(--yellow)
        );

    color: #111;

    text-decoration: none;

    border-radius: 8px;

    font-size: 13px;

    font-weight: bold;

    transition: .2s;

}


.logout:hover {

    transform:
        translateY(-1px);

}


/* =====================================
   MAIN
===================================== */

main {

    width: 100%;

    max-width: 1500px;

    margin: auto;

    padding:
        45px 25px 70px;

}


/* =====================================
   PAGE HEADER
===================================== */

.page-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    gap: 20px;

    margin-bottom: 35px;

}


.page-heading h2 {

    margin: 0 0 10px;

    font-size: 32px;

}


.page-heading p {

    margin: 0;

    color: var(--muted);

}


.admin-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 10px 15px;

    background:
        rgba(240,185,11,.08);

    border:
        1px solid
        rgba(240,185,11,.25);

    border-radius: 30px;

    color: var(--yellow);

    font-size: 13px;

    font-weight: bold;

}


/* =====================================
   STATISTICS
===================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 35px;

}


.stat-card {

    position: relative;

    overflow: hidden;

    padding: 25px;

    background:
        linear-gradient(
            135deg,
            #1b1e25,
            #14161b
        );

    border:
        1px solid var(--border);

    border-radius: 16px;

    transition: .2s;

}


.stat-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(240,185,11,.4);

}


.stat-icon {

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 20px;

    background:
        rgba(240,185,11,.1);

    border-radius: 12px;

    color: var(--yellow);

    font-size: 21px;

}


.stat-card span {

    display: block;

    color: var(--muted);

    font-size: 13px;

}


.stat-card h3 {

    margin: 10px 0 0;

    font-size: 32px;

    color: white;

}


.stat-card::after {

    content: "";

    position: absolute;

    width: 100px;

    height: 100px;

    right: -40px;

    bottom: -40px;

    border-radius: 50%;

    background:
        rgba(240,185,11,.04);

}


/* =====================================
   SEARCH PANEL
===================================== */

.control-panel {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 25px;

    padding: 20px;

    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 14px;

}


.search-form {

    display: flex;

    flex: 1;

    gap: 10px;

}


.search-form input {

    flex: 1;

    min-width: 0;

    padding: 13px 16px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 8px;

    color: white;

    font-size: 14px;

    outline: none;

}


.search-form input:focus {

    border-color: var(--yellow);

    box-shadow:
        0 0 0 3px
        rgba(240,185,11,.08);

}


.search-button {

    padding: 13px 24px;

    border: none;

    border-radius: 8px;

    background: var(--yellow);

    color: #111;

    font-weight: bold;

    cursor: pointer;

}


.clear-search {

    padding: 13px 18px;

    border:
        1px solid #343a43;

    border-radius: 8px;

    background: #252931;

    color: white;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;

}


.records-count {

    color: var(--muted);

    font-size: 13px;

    white-space: nowrap;

}


/* =====================================
   ALERTS
===================================== */

.message {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 17px;

    margin-bottom: 25px;

    border-radius: 10px;

    font-size: 14px;

}


.success {

    background:
        rgba(14,203,129,.1);

    border:
        1px solid
        rgba(14,203,129,.4);

    color: var(--green);

}


.error {

    background:
        rgba(246,70,93,.1);

    border:
        1px solid
        rgba(246,70,93,.4);

    color: var(--red);

}


/* =====================================
   TABLE CARD
===================================== */

.table-card {

    overflow: hidden;

    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 16px;

}


.table-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 22px 25px;

    border-bottom:
        1px solid var(--border);

}


.table-header h3 {

    margin: 0;

    font-size: 18px;

}


.table-header span {

    color: var(--muted);

    font-size: 12px;

}


.table-container {

    overflow-x: auto;

}


/* =====================================
   TABLE
===================================== */

table {

    width: 100%;

    min-width: 1250px;

    border-collapse: collapse;

}


thead {

    background: #20242c;

}


th {

    padding: 16px;

    text-align: left;

    color: var(--yellow);

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: .5px;

    white-space: nowrap;

}


td {

    padding: 18px 16px;

    border-top:
        1px solid var(--border);

    color: #d1d4dc;

    font-size: 14px;

    vertical-align: middle;

}


tbody tr {

    transition: .15s;

}


tbody tr:hover {

    background:
        rgba(255,255,255,.015);

}


/* =====================================
   USER CELL
===================================== */

.user-cell {

    display: flex;

    align-items: center;

    gap: 12px;

}


.user-avatar {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(240,185,11,.1);

    color: var(--yellow);

    font-weight: bold;

    text-transform: uppercase;

}


.user-details strong {

    display: block;

    color: white;

}


.user-details small {

    display: block;

    margin-top: 4px;

    color: var(--muted);

}


.account-number {

    font-family:
        monospace;

    color: #d1d4dc;

}


/* =====================================
   BADGES
===================================== */

.role,
.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

}


.role-admin {

    background:
        rgba(240,185,11,.1);

    color: var(--yellow);

}


.role-user {

    background:
        rgba(14,203,129,.1);

    color: var(--green);

}


.status-active {

    background:
        rgba(14,203,129,.1);

    color: var(--green);

}


.status-inactive {

    background:
        rgba(246,70,93,.1);

    color: var(--red);

}


/* =====================================
   BALANCE
===================================== */

.balance {

    color: white;

    font-weight: bold;

}


.currency {

    color: var(--yellow);

    margin-right: 3px;

}


/* =====================================
   FORMS
===================================== */

.status-form,
.balance-form {

    display: flex;

    align-items: center;

    gap: 7px;

}


.status-form select,
.balance-form select,
.balance-form input {

    padding: 9px 10px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 6px;

    color: white;

    outline: none;

    font-size: 12px;

}


.status-form select:focus,
.balance-form select:focus,
.balance-form input:focus {

    border-color: var(--yellow);

}


.balance-form input {

    width: 95px;

}


.status-form button,
.balance-form button {

    padding: 9px 12px;

    border: none;

    border-radius: 6px;

    background: var(--yellow);

    color: #111;

    font-size: 12px;

    font-weight: bold;

    cursor: pointer;

}


.status-form button:hover,
.balance-form button:hover {

    opacity: .9;

}


/* =====================================
   EMPTY
===================================== */

.empty {

    padding: 70px 25px;

    text-align: center;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 15px;

    opacity: .5;

}


.empty h3 {

    margin: 0 0 10px;

}


.empty p {

    margin: 0;

    color: var(--muted);

}


/* =====================================
   FOOTER
===================================== */

.footer {

    margin-top: 30px;

    padding: 20px 0;

    text-align: center;

    color: #5e6673;

    font-size: 12px;

}


/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 1000px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 18px;

    }


    .admin-area {

        width: 100%;

        flex-wrap: wrap;

    }


    .admin-profile {

        text-align: left;

        margin-right: auto;

    }

}


@media (max-width: 800px) {

    .page-top {

        flex-direction: column;

        align-items: flex-start;

    }


    .stats {

        grid-template-columns: 1fr;

    }


    .control-panel {

        flex-direction: column;

        align-items: stretch;

    }


    .search-form {

        width: 100%;

    }


    .records-count {

        white-space: normal;

    }

}


@media (max-width: 550px) {

    main {

        padding:
            30px 15px 50px;

    }


    .brand h1 {

        font-size: 17px;

    }


    .admin-area {

        gap: 8px;

    }


    .admin-link,
    .logout {

        padding: 10px 12px;

        font-size: 12px;

    }


    .search-form {

        flex-direction: column;

    }


    .page-heading h2 {

        font-size: 27px;

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
                Administration Panel
            </p>

        </div>

    </div>



    <div class="admin-area">


        <div class="admin-profile">

            <strong>

                <?php
                echo htmlspecialchars(
                    $admin["username"]
                );
                ?>

            </strong>

            <span>
                Administrator
            </span>

        </div>



        <a
            href="admin_messages.php"
            class="admin-link"
        >
            Messages
        </a>


        <a
            href="admin.php"
            class="admin-link"
        >
            Dashboard
        </a>


        <a
            href="logout.php"
            class="logout"
        >
            Sign Out
        </a>


    </div>


</header>



<!-- =====================================
     MAIN
===================================== -->

<main>


<div class="page-top">


    <section class="page-heading">

        <h2>
            Admin Dashboard
        </h2>

        <p>
            Manage registered users and account settings.
        </p>

    </section>


    <div class="admin-badge">

        ● Admin Access

    </div>


</div>



<!-- =====================================
     STATISTICS
===================================== -->

<section class="stats">


    <div class="stat-card">

        <div class="stat-icon">
            👥
        </div>

        <span>
            Total Users
        </span>

        <h3>

            <?php
            echo $totalUsers;
            ?>

        </h3>

    </div>



    <div class="stat-card">

        <div class="stat-icon">
            💳
        </div>

        <span>
            Total Accounts
        </span>

        <h3>

            <?php
            echo $totalAccounts;
            ?>

        </h3>

    </div>



    <div class="stat-card">

        <div class="stat-icon">
            ✓
        </div>

        <span>
            Active Accounts
        </span>

        <h3>

            <?php
            echo $activeAccounts;
            ?>

        </h3>

    </div>


</section>



<!-- =====================================
     SEARCH
===================================== -->

<div class="control-panel">


    <form
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            placeholder="Search by username..."
            value="<?php
                echo htmlspecialchars(
                    $search
                );
            ?>"
        >


        <button
            type="submit"
            class="search-button"
        >
            Search
        </button>


        <?php if ($search !== ""): ?>

        <a
            href="admin.php"
            class="clear-search"
        >
            Clear
        </a>

        <?php endif; ?>


    </form>


    <div class="records-count">

        <?php
        echo count($users);
        ?>

        record(s) found

    </div>


</div>



<!-- =====================================
     MESSAGE ALERT
===================================== -->

<?php if (!empty($message)): ?>


<div
    class="
        message
        <?php echo htmlspecialchars($messageType); ?>
    "
>

    <span>

        <?php
        echo $messageType === "success"
            ? "✓"
            : "!";
        ?>

    </span>


    <span>

        <?php
        echo htmlspecialchars($message);
        ?>

    </span>


</div>


<?php endif; ?>



<!-- =====================================
     USERS TABLE
===================================== -->

<div class="table-card">


<div class="table-header">

    <div>

        <h3>
            User Accounts
        </h3>

    </div>


    <span>
        Account management
    </span>


</div>



<?php if (count($users) > 0): ?>


<div class="table-container">


<table>


<thead>

<tr>

    <th>User</th>

    <th>Role</th>

    <th>Account Number</th>

    <th>Account Type</th>

    <th>Balance</th>

    <th>Status</th>

    <th>Manage Status</th>

    <th>Demo Balance</th>

</tr>

</thead>



<tbody>


<?php foreach ($users as $account): ?>


<?php

$status =
    strtolower(
        $account["account_status"]
        ?? "inactive"
    );


$role =
    strtolower(
        $account["role"]
        ?? "user"
    );


$username =
    $account["username"]
    ?? "User";


$firstLetter =
    strtoupper(
        substr(
            $username,
            0,
            1
        )
    );

?>


<tr>


<!-- USER -->

<td>

    <div class="user-cell">


        <div class="user-avatar">

            <?php
            echo htmlspecialchars(
                $firstLetter
            );
            ?>

        </div>


        <div class="user-details">

            <strong>

                <?php
                echo htmlspecialchars(
                    $username
                );
                ?>

            </strong>


            <small>

                User ID:

                <?php
                echo
                    (int)
                    $account["user_id"];
                ?>

            </small>


        </div>


    </div>

</td>



<!-- ROLE -->

<td>

    <span
        class="
            role
            <?php
            echo
                $role === "admin"
                ? "role-admin"
                : "role-user";
            ?>
        "
    >

        <?php
        echo htmlspecialchars(
            ucfirst($role)
        );
        ?>

    </span>

</td>



<!-- ACCOUNT NUMBER -->

<td
    class="account-number"
>

    <?php

    echo htmlspecialchars(
        $account["account_number"]
        ?? "No Account"
    );

    ?>

</td>



<!-- ACCOUNT TYPE -->

<td>

    <?php

    echo htmlspecialchars(
        $account["account_type"]
        ?? "-"
    );

    ?>

</td>



<!-- BALANCE -->

<td>

    <span class="balance">

        <span class="currency">
            €
        </span>

        <?php

        echo number_format(
            (float)
            ($account["balance"] ?? 0),
            2
        );

        ?>

    </span>

</td>



<!-- STATUS -->

<td>


<?php if (
    !empty(
        $account["account_id"]
    )
): ?>


<span
    class="
        status
        <?php

        echo
            $status === "active"
            ? "status-active"
            : "status-inactive";

        ?>
    "
>

    <?php

    echo htmlspecialchars(
        ucfirst($status)
    );

    ?>

</span>


<?php else: ?>


<span
    class="
        status
        status-inactive
    "
>

    No Account

</span>


<?php endif; ?>


</td>



<!-- UPDATE STATUS -->

<td>


<?php if (

    !empty(
        $account["account_id"]
    )

    &&

    $role !== "admin"

): ?>


<form
    method="POST"
    class="status-form"
>


    <input
        type="hidden"
        name="account_id"
        value="<?php
            echo
                (int)
                $account["account_id"];
        ?>"
    >


    <select
        name="account_status"
    >


        <option
            value="active"
            <?php

            echo
                $status === "active"
                ? "selected"
                : "";

            ?>
        >

            Active

        </option>



        <option
            value="inactive"
            <?php

            echo
                $status === "inactive"
                ? "selected"
                : "";

            ?>
        >

            Inactive

        </option>


    </select>



    <button
        type="submit"
        name="update_status"
    >

        Update

    </button>


</form>


<?php else: ?>


—

<?php endif; ?>


</td>



<!-- ACCOUNT BALANCE -->

<td>


<?php if (

    !empty(
        $account["account_id"]
    )

    &&

    $role !== "admin"

): ?>


<form
    method="POST"
    class="balance-form"
>


    <input
        type="hidden"
        name="account_id"
        value="<?php
            echo
                (int)
                $account["account_id"];
        ?>"
    >



    <select
        name="balance_action"
    >

        <option value="add">
            Add
        </option>


        <option value="remove">
            Remove
        </option>

    </select>



    <input
        type="number"
        name="amount"
        placeholder="Amount"
        min="0.01"
        step="0.01"
        required
    >



    <button
        type="submit"
        name="adjust_balance"
    >

        Apply

    </button>


</form>


<?php else: ?>


—

<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>



<?php else: ?>


<div class="empty">


    <div class="empty-icon">
        👥
    </div>


    <h3>
        No User Records Found
    </h3>


    <p>
        Registered user accounts will appear here.
    </p>


</div>


<?php endif; ?>


</div>



<div class="footer">

    © 2026 Crypto Circle Trading — Administration Panel

</div>


</main>


</body>

</html>