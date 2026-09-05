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
    isset($_SESSION["role"])
    &&
    $_SESSION["role"] === "admin"
) {

    header("Location: admin.php");
    exit;

}


$userId = (int) $_SESSION["user_id"];


/* =====================================
   GET USER
===================================== */

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
   GET ACCOUNT
===================================== */

$account = null;

try {

    $accountStmt = $pdo->prepare("
        SELECT
            id,
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


$balance =
    (float) ($account["balance"] ?? 0);

$accountStatus =
    strtolower(
        $account["account_status"] ?? "inactive"
    );


/* =====================================
   VARIABLES
===================================== */

$error = "";

$success = "";

$showSlip = false;

$transferFee = 500.00;


/* =====================================
   CLEAR ACCOUNT TRANSFER
===================================== */

if (isset($_GET["clear"])) {

    unset($_SESSION["account_transfer"]);

    header("Location: transfer.php");

    exit;

}


/* =====================================
   CREATE TRANSFER
===================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["submit_transfer"])
) {

    $country =
        trim($_POST["country"] ?? "");

    $bank =
        trim($_POST["bank"] ?? "");

    $recipientName =
        trim($_POST["recipient_name"] ?? "");

    $accountNumber =
        trim($_POST["account_number"] ?? "");

    $routingCode =
        trim($_POST["routing_code"] ?? "");

    $iban =
        trim($_POST["iban"] ?? "");

    $swift =
        trim($_POST["swift"] ?? "");

    $amount =
        (float) ($_POST["amount"] ?? 0);


    if (!$account) {

        $error =
            "No account was found for your user.";

    }

    elseif ($accountStatus !== "active") {

        $error =
            "Your account must be active before creating a transfer request.";

    }

    elseif ($country === "") {

        $error =
            "Please select a destination country.";

    }

    elseif ($bank === "") {

        $error =
            "Please select a bank.";

    }

    elseif ($recipientName === "") {

        $error =
            "Please enter the recipient name.";

    }

    elseif ($amount <= 0) {

        $error =
            "Please enter a valid transfer amount.";

    }

    else {

        $reference =
            "TRF-" .
            date("Ymd") .
            "-" .
            strtoupper(
                substr(
                    md5(
                        uniqid(
                            (string) $userId,
                            true
                        )
                    ),
                    0,
                    8
                )
            );


        $_SESSION["account_transfer"] = [

            "reference" =>
                $reference,

            "country" =>
                $country,

            "bank" =>
                $bank,

            "recipient_name" =>
                $recipientName,

            "account_number" =>
                $accountNumber,

            "routing_code" =>
                $routingCode,

            "iban" =>
                $iban,

            "swift" =>
                $swift,

            "amount" =>
                $amount,

            "fee" =>
                $transferFee,

            "status" =>
                "Pending",

            "created_at" =>
                date("d M Y, h:i A")

        ];


        $showSlip = true;

        $success =
            "Your transfer request has been created.";

    }

}


/* =====================================
   LOAD EXISTING SLIP
===================================== */

if (
    isset($_SESSION["account_transfer"])
) {

    $showSlip = true;

    $transfer =
        $_SESSION["account_transfer"];

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
    Transfer | Crypto Circle Trading
</title>


<style>

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
            circle at 90% 5%,
            rgba(240,185,11,.12),
            transparent 25%
        ),
        #0b0e11;

    color: white;

}


/* HEADER */

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

    font-size: 25px;

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


.logout {

    padding: 12px 18px;

    background: #f0b90b;

    color: #111;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

}


/* NAVIGATION */

nav {

    display: flex;

    padding: 0 6%;

    background: #181a20;

    border-bottom:
        1px solid #2b3139;

    overflow-x: auto;

}


nav a {

    padding: 17px;

    color: #848e9c;

    text-decoration: none;

    white-space: nowrap;

}


nav a:hover {

    color: #f0b90b;

}


/* MAIN */

main {

    max-width: 900px;

    margin: auto;

    padding: 45px 20px;

}


.page-title {

    margin-bottom: 30px;

}


.page-title p {

    color: #848e9c;

}


/* CARD */

.card {

    padding: 30px;

    background: #181a20;

    border:
        1px solid #2b3139;

    border-radius: 14px;

}


.card h3 {

    margin-top: 0;

    color: #f0b90b;

}


/* FORM */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;

}


.form-group {

    margin-bottom: 18px;

}


.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #d1d4dc;

}


input,

select {

    width: 100%;

    padding: 14px;

    background: #0b0e11;

    border:
        1px solid #343a43;

    border-radius: 7px;

    color: white;

}


input:focus,

select:focus {

    outline: none;

    border-color: #f0b90b;

}


.button {

    width: 100%;

    padding: 15px;

    border: none;

    border-radius: 8px;

    background: #f0b90b;

    color: #111;

    font-weight: bold;

    cursor: pointer;

}


/* MESSAGE */

.message {

    padding: 15px;

    margin-bottom: 20px;

    border-radius: 8px;

}


.error {

    color: #f6465d;

    border:
        1px solid #f6465d;

    background:
        rgba(246,70,93,.10);

}


.success {

    color: #0ecb81;

    border:
        1px solid #0ecb81;

    background:
        rgba(14,203,129,.10);

}


/* SLIP */

.slip {

    overflow: hidden;

    background: white;

    color: #202020;

    border-radius: 15px;

    box-shadow:
        0 10px 40px
        rgba(0,0,0,.3);

}


.slip-header {

    padding: 28px;

    text-align: center;

    background: #f0b90b;

    color: #111;

}


.slip-header h2 {

    margin: 0 0 8px;

}


.slip-header p {

    margin: 0;

}


.slip-content {

    padding: 30px;

}


.slip-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 15px 0;

    border-bottom:
        1px solid #eeeeee;

}


.slip-row span {

    color: #777;

}


.pending {

    padding: 7px 13px;

    border-radius: 20px;

    background: #fff3cd;

    color: #856404;

}


.fee-box {

    margin-top: 25px;

    padding: 20px;

    border:
        1px solid #f0b90b;

    background: #fff9df;

    border-radius: 10px;

}


.fee-box h3 {

    margin-top: 0;

    color: #7a5b00;

}


.demo-payment {

    margin-top: 20px;

    padding: 18px;

    background: #f1f1f1;

    border-radius: 8px;

}


.demo-payment strong {

    display: block;

    margin-bottom: 10px;

}


.demo-address {

    padding: 12px;

    background: #ffffff;

    border:
        1px dashed #777;

    font-family: monospace;

    word-break: break-all;

}


.demo-notice {

    margin-top: 20px;

    padding: 15px;

    background: #eeeeee;

    border-radius: 8px;

    color: #555;

    line-height: 1.6;

    font-size: 13px;

}


.clear-button {

    display: block;

    margin-top: 25px;

    padding: 15px;

    text-align: center;

    text-decoration: none;

    background: #2b3139;

    color: white;

    border-radius: 8px;

}


/* MOBILE */

@media(max-width: 700px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }

    .form-grid {

        grid-template-columns: 1fr;

    }

    .slip-row {

        flex-direction: column;

        gap: 7px;

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
                Bank Transfer Center
            </p>

        </div>

    </div>


    <a
        href="logout.php"
        class="logout"
    >
        Sign Out
    </a>

</header>


<nav>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="transfer.php">
        Transfer
    </a>

    <a href="withdraw.php">
        Withdraw
    </a>

    <a href="transactions.php">
        Transactions
    </a>

    <a href="wallet.php">
        Wallet
    </a>

    <a href="profile.php">
        Profile
    </a>

</nav>


<main>


<div class="page-title">

    <h2>
        Bank Transfer
    </h2>

    <p>
        Enter the recipient bank information to create a transfer request.
    </p>

</div>


<?php if ($error !== ""): ?>

<div class="message error">

    <?php
    echo htmlspecialchars($error);
    ?>

</div>

<?php endif; ?>


<?php if (!$showSlip): ?>


<div class="card">

<h3>
    Recipient Information
</h3>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
    Destination Country
</label>

<select
    name="country"
    id="country"
    required
>

<option value="">
    Select Country
</option>

<option value="United States">
    United States
</option>

<option value="United Kingdom">
    United Kingdom
</option>

<option value="Nigeria">
    Nigeria
</option>

<option value="Canada">
    Canada
</option>

<option value="Germany">
    Germany
</option>

<option value="South Africa">
    South Africa
</option>

</select>

</div>


<div class="form-group">

<label>
    Select Bank
</label>

<select
    name="bank"
    id="bank"
    required
>

<option value="">
    Select Country First
</option>

</select>

</div>


</div>


<div class="form-group">

<label>
    Recipient Full Name
</label>

<input
    type="text"
    name="recipient_name"
    required
>

</div>


<div class="form-group">

<label
    id="accountLabel"
>
    Account Number
</label>

<input
    type="text"
    name="account_number"
    id="accountNumber"
>

</div>


<div class="form-grid">


<div class="form-group">

<label
    id="routingLabel"
>
    Routing Code
</label>

<input
    type="text"
    name="routing_code"
    id="routingCode"
>

</div>


<div class="form-group">

<label>
    IBAN
</label>

<input
    type="text"
    name="iban"
    id="iban"
>

</div>


</div>


<div class="form-group">

<label>
    SWIFT / BIC
</label>

<input
    type="text"
    name="swift"
    id="swift"
>

</div>


<div class="form-group">

<label>
    Transfer Amount
</label>

<input
    type="number"
    name="amount"
    min="1"
    step="0.01"
    required
>

</div>


<button
    type="submit"
    name="submit_transfer"
    class="button"
>

Create Transfer Request

</button>


</form>

</div>


<?php else: ?>


<div class="slip">


<div class="slip-header">

<h2>
    Transfer Payment Slip
</h2>

<p>
    Account Record
</p>

</div>


<div class="slip-content">


<div class="slip-row">

<span>
    Transfer Reference
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["reference"]
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Transfer Status
</span>

<strong class="pending">
    Pending
</strong>

</div>


<div class="slip-row">

<span>
    Recipient
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["recipient_name"]
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Country
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["country"]
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Bank
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["bank"]
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Account Number
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["account_number"]
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Transfer Amount
</span>

<strong>

$

<?php
echo number_format(
    (float)
    $transfer["amount"],
    2
);
?>

</strong>

</div>


<div class="slip-row">

<span>
    Created
</span>

<strong>

<?php
echo htmlspecialchars(
    $transfer["created_at"]
);
?>

</strong>

</div>


<div class="fee-box">

<h3>
    Account Processing Fee
</h3>

<p>

<strong>
$
<?php
echo number_format(
    (float)
    $transfer["fee"],
    2
);
?>
</strong>

account processing fee.

</p>

<p>

Fee Information — Reference Only

</p>

</div>


<div class="account-payment">

<strong>
Account Payment Location
</strong>

<p>
Payment Address — Reference Only
</p>

<div class="account-address">



PAYMENT ADDRESS DETAILS .bc1quehtlkycrau4kvc67nx0neme6r7dzvgcgswhxh




</div>

</div>


<div class="account-notice">

<strong>
Account Notice:
</strong>




Cryptocurrency and payment transactions are subject to applicable service availability and processing requirements. Transfer and payment records displayed on this page are provided for account reference purposes.

</div>


</div>

</div>


<a
    href="transfer.php?clear=1"
    class="clear-button"
>

Create Another Transfer

</a>


<?php endif; ?>


</main>


<script>

/* =====================================
   BANK LISTS
===================================== */

const banks = {


"United States": [

"Bank of America",

"Chase",

"Wells Fargo",

"Citibank",

"U.S. Bank",

"PNC Bank",

"Capital One",

"Truist"

],


"United Kingdom": [

"Barclays",

"HSBC",

"Lloyds Bank",

"NatWest",

"Santander UK",

"Nationwide",

"Halifax"

],


"Nigeria": [

"Access Bank",

"FirstBank",

"GTBank",

"Zenith Bank",

"United Bank for Africa",

"Fidelity Bank",

"Sterling Bank",

"Stanbic IBTC Bank",

"Union Bank",

"Ecobank"

],


"Canada": [

"Royal Bank of Canada",

"TD Canada Trust",

"Scotiabank",

"BMO",

"CIBC",

"National Bank of Canada"

],


"Germany": [

"Deutsche Bank",

"Commerzbank",

"DKB",

"ING Germany",

"Sparkasse",

"Volksbank"

],


"South Africa": [

"Standard Bank",

"First National Bank",

"Absa",

"Nedbank",

"Capitec"

]

};


/* =====================================
   ELEMENTS
===================================== */

const countrySelect =
    document.getElementById("country");

const bankSelect =
    document.getElementById("bank");

const routingLabel =
    document.getElementById("routingLabel");

const accountLabel =
    document.getElementById("accountLabel");

const iban =
    document.getElementById("iban");

const routingCode =
    document.getElementById("routingCode");


/* =====================================
   UPDATE BANKS
===================================== */

if (
    countrySelect
    &&
    bankSelect
) {

countrySelect.addEventListener(
    "change",
    function() {


        const country =
            this.value;


        bankSelect.innerHTML =
            '<option value="">Select Bank</option>';


        if (banks[country]) {


            banks[country].forEach(
                function(bank) {


                    const option =
                        document.createElement("option");


                    option.value =
                        bank;


                    option.textContent =
                        bank;


                    bankSelect.appendChild(option);


                }
            );


        }


        updateFields(country);


    }
);


}


/* =====================================
   CHANGE ACCOUNT FIELDS
===================================== */

function updateFields(country) {


if (country === "United States") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "ABA Routing Number";

    iban.placeholder =
        "Not normally required";


    routingCode.placeholder =
        "9-digit routing number";


}


else if (country === "United Kingdom") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Sort Code";

    iban.placeholder =
        "Optional IBAN";


    routingCode.placeholder =
        "6-digit sort code";

}


else if (country === "Nigeria") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Bank Code";

    iban.placeholder =
        "If applicable";

    routingCode.placeholder =
        "Bank routing code";

}


else if (country === "Germany") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Bank Code";

    iban.placeholder =
        "DE IBAN";

    routingCode.placeholder =
        "German bank code";

}


else if (country === "Canada") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Transit / Institution Number";

    iban.placeholder =
        "Not normally required";

    routingCode.placeholder =
        "Transit number";

}


else if (country === "South Africa") {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Branch Code";

    iban.placeholder =
        "Not normally required";

    routingCode.placeholder =
        "Branch code";

}


else {

    accountLabel.textContent =
        "Account Number";

    routingLabel.textContent =
        "Routing Code";

}


}

</script>


</body>

</html>