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


$adminId = (int) $_SESSION["user_id"];

$admin = [
    "username" => "Administrator"
];

$success = "";
$error = "";

$messages = [];


/* =====================================
   GET ADMIN INFORMATION
===================================== */

try {

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

    /* Keep default administrator name */

}


/* =====================================
   SEND ADMIN REPLY
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["send_reply"])
) {

    $messageId = (int) ($_POST["message_id"] ?? 0);

    $reply = trim(
        $_POST["admin_reply"] ?? ""
    );


    if ($messageId <= 0) {

        $error =
            "Invalid message selected.";

    } elseif ($reply === "") {

        $error =
            "Please enter a reply.";

    } else {

        try {

            $replyStmt = $pdo->prepare("
                UPDATE messages
                SET
                    admin_reply = ?,
                    status = 'closed'
                WHERE id = ?
            ");

            $replyStmt->execute([
                $reply,
                $messageId
            ]);


            $success =
                "Reply sent successfully.";

        } catch (Exception $e) {

            $error =
                "Unable to send the reply. Please check your messages table.";

        }

    }

}


/* =====================================
   REOPEN MESSAGE
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["reopen_message"])
) {

    $messageId = (int) ($_POST["message_id"] ?? 0);


    if ($messageId > 0) {

        try {

            $reopenStmt = $pdo->prepare("
                UPDATE messages
                SET status = 'open'
                WHERE id = ?
            ");

            $reopenStmt->execute([
                $messageId
            ]);


            $success =
                "Message reopened successfully.";

        } catch (Exception $e) {

            $error =
                "Unable to reopen the message.";

        }

    }

}


/* =====================================
   LOAD MESSAGE STATISTICS
===================================== */

$totalMessages = 0;

$openMessages = 0;

$closedMessages = 0;


try {

    $totalStmt = $pdo->query("
        SELECT COUNT(*)
        FROM messages
    ");

    $totalMessages =
        (int) $totalStmt->fetchColumn();


    $openStmt = $pdo->query("
        SELECT COUNT(*)
        FROM messages
        WHERE status = 'open'
    ");

    $openMessages =
        (int) $openStmt->fetchColumn();


    $closedStmt = $pdo->query("
        SELECT COUNT(*)
        FROM messages
        WHERE status = 'closed'
    ");

    $closedMessages =
        (int) $closedStmt->fetchColumn();


} catch (Exception $e) {

    /*
       Statistics remain zero if there
       is a database problem.
    */

}


/* =====================================
   LOAD USER MESSAGES
===================================== */

try {

    $messagesStmt = $pdo->query("
        SELECT
            messages.id,
            messages.user_id,
            messages.message,
            messages.admin_reply,
            messages.status,

            users.username

        FROM messages

        LEFT JOIN users
        ON users.id = messages.user_id

        ORDER BY
            CASE
                WHEN messages.status = 'open'
                THEN 0
                ELSE 1
            END,
            messages.id DESC
    ");


    $messages =
        $messagesStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Exception $e) {

    $error =
        "Unable to load user messages.";

    $messages = [];

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
    User Messages | Crypto Circle Trading
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

    font-size: 24px;

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
   ADMIN AREA
===================================== */

.admin-area {

    display: flex;

    align-items: center;

    gap: 12px;

}


.admin-name {

    text-align: right;

    margin-right: 8px;

}


.admin-name strong {

    display: block;

}


.admin-name span {

    display: block;

    color: #848e9c;

    font-size: 12px;

}


.admin-link {

    padding: 11px 16px;

    background: #252931;

    color: white;

    text-decoration: none;

    border:
        1px solid #343a43;

    border-radius: 7px;

    font-weight: bold;

}


.admin-link:hover {

    color: #f0b90b;

    border-color: #f0b90b;

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

nav {

    display: flex;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

}


nav a {

    padding: 18px 20px;

    color: #848e9c;

    text-decoration: none;

}


nav a:hover {

    color: #f0b90b;

}


nav a.active {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, .06);

}


/* =====================================
   MAIN
===================================== */

main {

    max-width: 1280px;

    margin: auto;

    padding: 45px 25px;

}


.page-heading {

    margin-bottom: 30px;

}


.page-heading h2 {

    margin: 0 0 10px;

    font-size: 30px;

}


.page-heading p {

    margin: 0;

    color: #848e9c;

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

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

}


.stat-card span {

    display: block;

    color: #848e9c;

    font-size: 14px;

}


.stat-card h3 {

    margin: 12px 0 0;

    font-size: 32px;

    color: #f0b90b;

}


/* =====================================
   ALERTS
===================================== */

.alert {

    padding: 16px;

    margin-bottom: 25px;

    border-radius: 8px;

}


.alert-success {

    background:
        rgba(14, 203, 129, .12);

    border:
        1px solid #0ecb81;

    color: #0ecb81;

}


.alert-error {

    background:
        rgba(246, 70, 93, .12);

    border:
        1px solid #f6465d;

    color: #f6465d;

}


/* =====================================
   MESSAGE LIST
===================================== */

.messages-container {

    display: grid;

    gap: 20px;

}


.message-card {

    padding: 25px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


.message-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 20px;

}


.user-information h3 {

    margin: 0 0 7px;

    font-size: 18px;

}


.user-information span {

    color: #848e9c;

    font-size: 13px;

}


/* =====================================
   STATUS
===================================== */

.status {

    display: inline-block;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.status-open {

    color: #f0b90b;

    background:
        rgba(240, 185, 11, .12);

}


.status-closed {

    color: #0ecb81;

    background:
        rgba(14, 203, 129, .12);

}


/* =====================================
   MESSAGE CONTENT
===================================== */

.message-label {

    display: block;

    margin-bottom: 10px;

    color: #848e9c;

    font-size: 13px;

}


.user-message {

    padding: 18px;

    margin-bottom: 22px;

    background: #0b0e11;

    border:
        1px solid #2b3139;

    border-radius: 8px;

    color: #d1d4dc;

    line-height: 1.7;

    white-space: pre-wrap;

}


.reply-box {

    padding: 18px;

    background:
        rgba(240, 185, 11, .04);

    border:
        1px solid #2b3139;

    border-radius: 8px;

}


.reply-box textarea {

    width: 100%;

    min-height: 120px;

    resize: vertical;

    padding: 14px;

    margin-bottom: 12px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 8px;

    color: white;

    font-family: inherit;

    outline: none;

}


.reply-box textarea:focus {

    border-color: #f0b90b;

}


.reply-button {

    padding: 12px 22px;

    border: none;

    border-radius: 7px;

    background: #f0b90b;

    color: #111;

    font-weight: bold;

    cursor: pointer;

}


.reply-button:hover {

    opacity: .9;

}


/* =====================================
   ADMIN REPLY
===================================== */

.admin-reply {

    padding: 18px;

    margin-top: 20px;

    background:
        rgba(14, 203, 129, .06);

    border-left:
        4px solid #0ecb81;

    border-radius: 6px;

}


.admin-reply p {

    margin: 0;

    line-height: 1.7;

    white-space: pre-wrap;

    color: #d1d4dc;

}


/* =====================================
   REOPEN
===================================== */

.reopen-form {

    margin-top: 15px;

}


.reopen-button {

    padding: 10px 18px;

    border:
        1px solid #343a43;

    border-radius: 7px;

    background: #252931;

    color: white;

    font-weight: bold;

    cursor: pointer;

}


.reopen-button:hover {

    color: #f0b90b;

    border-color: #f0b90b;

}


/* =====================================
   EMPTY
===================================== */

.empty {

    padding: 60px 25px;

    text-align: center;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


.empty h3 {

    margin-top: 0;

}


.empty p {

    color: #848e9c;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 850px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }


    .admin-area {

        width: 100%;

        flex-wrap: wrap;

    }


    .admin-name {

        text-align: left;

        margin-right: auto;

    }


    .stats {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 600px) {

    main {

        padding: 30px 15px;

    }


    .message-header {

        flex-direction: column;

        gap: 10px;

    }


    nav {

        overflow-x: auto;

    }


    nav a {

        white-space: nowrap;

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
            C
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


        <div class="admin-name">

            <strong>
                <?php echo htmlspecialchars($admin["username"]); ?>
            </strong>

            <span>
                Administrator
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

<nav>

    <a href="admin.php">
        Admin Dashboard
    </a>


    <a
        href="admin_messages.php"
        class="active"
    >
        User Messages
    </a>

</nav>



<!-- =====================================
     MAIN CONTENT
===================================== -->

<main>


<section class="page-heading">

    <h2>
        User Messages
    </h2>

    <p>
        View and respond to support messages submitted by users.
    </p>

</section>



<!-- =====================================
     STATISTICS
===================================== -->

<section class="stats">


    <div class="stat-card">

        <span>
            Total Messages
        </span>

        <h3>
            <?php echo $totalMessages; ?>
        </h3>

    </div>



    <div class="stat-card">

        <span>
            Open Messages
        </span>

        <h3>
            <?php echo $openMessages; ?>
        </h3>

    </div>



    <div class="stat-card">

        <span>
            Closed Messages
        </span>

        <h3>
            <?php echo $closedMessages; ?>
        </h3>

    </div>


</section>



<!-- =====================================
     SUCCESS MESSAGE
===================================== -->

<?php if ($success !== ""): ?>

    <div class="alert alert-success">

        <?php
        echo htmlspecialchars($success);
        ?>

    </div>

<?php endif; ?>



<!-- =====================================
     ERROR MESSAGE
===================================== -->

<?php if ($error !== ""): ?>

    <div class="alert alert-error">

        <?php
        echo htmlspecialchars($error);
        ?>

    </div>

<?php endif; ?>



<!-- =====================================
     MESSAGES
===================================== -->

<section class="messages-container">


<?php if (count($messages) > 0): ?>


<?php foreach ($messages as $item): ?>


<div class="message-card">


    <!-- MESSAGE HEADER -->

    <div class="message-header">


        <div class="user-information">

            <h3>

                <?php

                echo htmlspecialchars(
                    $item["username"]
                    ?? "Unknown User"
                );

                ?>

            </h3>


            <span>

                User ID:

                <?php
                echo (int) $item["user_id"];
                ?>

            </span>

        </div>



        <?php

        $status =
            strtolower(
                $item["status"]
                ?? "open"
            );

        ?>


        <span
            class="
                status
                <?php
                echo
                    $status === "closed"
                    ? "status-closed"
                    : "status-open";
                ?>
            "
        >

            <?php
            echo htmlspecialchars(
                ucfirst($status)
            );
            ?>

        </span>


    </div>



    <!-- USER MESSAGE -->

    <span class="message-label">
        USER MESSAGE
    </span>


    <div class="user-message">

        <?php

        echo htmlspecialchars(
            $item["message"]
        );

        ?>

    </div>



    <!-- ADMIN REPLY -->

    <?php if (
        !empty($item["admin_reply"])
    ): ?>


        <div class="admin-reply">

            <span class="message-label">
                ADMIN REPLY
            </span>


            <p>

                <?php

                echo htmlspecialchars(
                    $item["admin_reply"]
                );

                ?>

            </p>

        </div>


    <?php endif; ?>



    <!-- OPEN MESSAGE REPLY FORM -->

    <?php if ($status === "open"): ?>


        <form
            method="POST"
            class="reply-box"
        >


            <span class="message-label">

                Write Reply

            </span>


            <textarea
                name="admin_reply"
                placeholder="Write your response to the user..."
                required
            ></textarea>


            <input
                type="hidden"
                name="message_id"
                value="<?php echo (int) $item["id"]; ?>"
            >


            <button
                type="submit"
                name="send_reply"
                class="reply-button"
            >
                Send Reply
            </button>


        </form>


    <?php else: ?>


        <form
            method="POST"
            class="reopen-form"
        >


            <input
                type="hidden"
                name="message_id"
                value="<?php echo (int) $item["id"]; ?>"
            >


            <button
                type="submit"
                name="reopen_message"
                class="reopen-button"
            >
                Reopen Message
            </button>


        </form>


    <?php endif; ?>


</div>


<?php endforeach; ?>


<?php else: ?>


<div class="empty">

    <h3>
        No User Messages
    </h3>

    <p>
        There are currently no support messages from users.
    </p>

</div>


<?php endif; ?>


</section>


</main>


</body>

</html>