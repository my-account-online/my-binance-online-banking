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


$userId = (int) $_SESSION["user_id"];


/* =====================================
   VARIABLES
===================================== */

$username = "User";

$messageAlert = "";
$messageType = "";

$messages = [];


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

    $userStmt->execute([$userId]);

    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);


    if ($userData) {

        $username = $userData["username"];

    }

} catch (Exception $e) {

    $username = "User";

}


/* =====================================
   SEND MESSAGE
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["send_message"])
) {

    $newMessage = trim(
        $_POST["message"] ?? ""
    );


    if ($newMessage === "") {

        $messageAlert =
            "Please enter a message before sending.";

        $messageType =
            "error";

    } elseif (strlen($newMessage) > 5000) {

        $messageAlert =
            "Your message is too long.";

        $messageType =
            "error";

    } else {

        try {

            $insertStmt = $pdo->prepare("
                INSERT INTO messages
                (
                    user_id,
                    message,
                    admin_reply,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    NULL,
                    'open'
                )
            ");


            $insertStmt->execute([
                $userId,
                $newMessage
            ]);


            $messageAlert =
                "Your message was sent successfully.";

            $messageType =
                "success";


        } catch (Exception $e) {

            $messageAlert =
                "Unable to send your message.";

            $messageType =
                "error";

        }

    }

}


/* =====================================
   LOAD USER MESSAGES
===================================== */

try {

    $messagesStmt = $pdo->prepare("
        SELECT
            id,
            message,
            admin_reply,
            status
        FROM messages
        WHERE user_id = ?
        ORDER BY id DESC
    ");


    $messagesStmt->execute([
        $userId
    ]);


    $messages =
        $messagesStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Exception $e) {

    $messageAlert =
        "Unable to load your messages.";

    $messageType =
        "error";

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
    Messages | Crypto Circle Trading
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

    max-width: 1050px;

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
   MESSAGE BOX
===================================== */

.message-box {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

    padding: 32px;

    margin-bottom: 40px;

}


.message-box h3 {

    margin-top: 0;

    color: #f0b90b;

}


.message-box label {

    display: block;

    margin-bottom: 10px;

    color: #d1d4dc;

}


.message-box textarea {

    width: 100%;

    min-height: 170px;

    resize: vertical;

    padding: 16px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 9px;

    color: white;

    font-family: inherit;

    font-size: 15px;

    outline: none;

}


.message-box textarea:focus {

    border-color: #f0b90b;

}


.message-box button {

    margin-top: 18px;

    padding: 14px 28px;

    border: none;

    border-radius: 8px;

    background: #f0b90b;

    color: #111;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

}


.message-box button:hover {

    opacity: .9;

}


/* =====================================
   ALERTS
===================================== */

.alert {

    padding: 16px;

    margin-bottom: 20px;

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
   MESSAGES LIST
===================================== */

.messages-title {

    margin-bottom: 20px;

}


.message-card {

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

    padding: 25px;

    margin-bottom: 20px;

}


.message-card h4 {

    margin-top: 0;

    color: #f0b90b;

}


.message-text {

    white-space: pre-wrap;

    line-height: 1.6;

    color: #d1d4dc;

}


/* =====================================
   STATUS
===================================== */

.status {

    display: inline-block;

    margin-bottom: 18px;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.status-open {

    background:
        rgba(240, 185, 11, .12);

    color: #f0b90b;

}


.status-closed {

    background:
        rgba(14, 203, 129, .12);

    color: #0ecb81;

}


/* =====================================
   ADMIN REPLY
===================================== */

.reply {

    margin-top: 22px;

    padding: 18px;

    background: #20242c;

    border-left:
        4px solid #f0b90b;

    border-radius: 7px;

}


.reply h4 {

    margin: 0 0 10px;

    color: #f0b90b;

}


.reply p {

    margin: 0;

    color: #d1d4dc;

    white-space: pre-wrap;

    line-height: 1.6;

}


/* =====================================
   EMPTY
===================================== */

.empty {

    padding: 45px 25px;

    text-align: center;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 12px;

    color: #848e9c;

}


/* =====================================
   MOBILE
===================================== */

@media (max-width: 800px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }


    .user-area {

        width: 100%;

        justify-content: space-between;

    }


    .navigation {

        overflow-x: auto;

        padding-left: 0;

    }


    .navigation a {

        white-space: nowrap;

    }

}


@media (max-width: 550px) {

    main {

        padding: 30px 15px;

    }


    .message-box {

        padding: 20px;

    }


    .user-name {

        text-align: left;

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
                Account Support
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

    <a href="wallet.php">
        Wallet
    </a>

    <a
        href="messages.php"
        class="active"
    >
        Messages
    </a>

    <a href="profile.php">
        Profile
    </a>

</nav>



<!-- =====================================
     MAIN
===================================== -->

<main>


<section class="page-heading">

    <h2>
        Help & Support
    </h2>

    <p>
        Send a message to the administrator and view responses.
    </p>

</section>



<?php if (!empty($messageAlert)): ?>

<div
    class="
        alert
        <?php echo htmlspecialchars($messageType); ?>
    "
>

    <?php
    echo htmlspecialchars($messageAlert);
    ?>

</div>

<?php endif; ?>



<!-- =====================================
     SEND MESSAGE
===================================== -->

<section class="message-box">


    <h3>
        Send a Message
    </h3>


    <form method="POST">


        <label for="message">

            How can we help you?

        </label>


        <textarea
            id="message"
            name="message"
            placeholder="Write your message here..."
            required
        ></textarea>


        <button
            type="submit"
            name="send_message"
        >
            Send Message
        </button>


    </form>


</section>



<!-- =====================================
     MY MESSAGES
===================================== -->

<h2 class="messages-title">
    My Messages
</h2>



<?php if (count($messages) > 0): ?>


    <?php foreach ($messages as $item): ?>


        <?php

        $status =
            strtolower(
                $item["status"] ?? "open"
            );

        ?>


        <div class="message-card">


            <span
                class="
                    status
                    <?php
                    echo $status === "closed"
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


            <h4>
                Your Message
            </h4>


            <div class="message-text">

                <?php
                echo htmlspecialchars(
                    $item["message"]
                );
                ?>

            </div>



            <?php if (
                !empty(
                    $item["admin_reply"]
                )
            ): ?>


            <div class="reply">


                <h4>
                    Administrator Reply
                </h4>


                <p>

                    <?php
                    echo htmlspecialchars(
                        $item["admin_reply"]
                    );
                    ?>

                </p>


            </div>


            <?php endif; ?>


        </div>


    <?php endforeach; ?>


<?php else: ?>


<div class="empty">

    <h3>
        No Messages Yet
    </h3>

    <p>
        Your support messages will appear here.
    </p>

</div>


<?php endif; ?>


</main>


</body>

</html>