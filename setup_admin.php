<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: index.php");
    exit;
}

require "db.php";

/* Get all users and their accounts */
$stmt = $pdo->query(
    "SELECT
        users.id,
        users.username,
        users.role,
        users.created_at,
        accounts.account_number,
        accounts.account_type,
        accounts.account_status
    FROM users
    LEFT JOIN accounts
        ON users.id = accounts.user_id
    ORDER BY users.id DESC"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Statistics */
$totalUsers = 0;
$totalAdmins = 0;
$activeAccounts = 0;

foreach ($users as $user) {

    if ($user["role"] === "user") {
        $totalUsers++;
    }

    if ($user["role"] === "admin") {
        $totalAdmins++;
    }

    if ($user["account_status"] === "active") {
        $activeAccounts++;
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

    <title>Admin Dashboard | Crypto Circle Trading</title>

    <link rel="stylesheet" href="style.css?v=2">

</head>

<body>

<div class="crypto-watermark">₿</div>

<div class="bank-app">

    <header class="bank-header">

        <div class="bank-brand">

            <div class="brand-icon">C</div>

            <div>

                <h1>Crypto Circle Trading</h1>

                <p>Administrator Dashboard</p>

            </div>

        </div>

        <div class="header-user">

            <div>

                <strong>
                    <?php
                    echo htmlspecialchars($_SESSION["username"]);
                    ?>
                </strong>

                <p>Administrator</p>

            </div>

            <a href="logout.php" class="signout-btn">
                Sign Out
            </a>

        </div>

    </header>


    <nav class="bank-navigation">

        <a href="admin.php" class="active">
            Dashboard
        </a>

        <a href="#users">
            Users
        </a>

        <a href="#accounts">
            Accounts
        </a>

    </nav>


    <main class="bank-main">

        <div class="dashboard-welcome">

            <h2>Administration Overview</h2>

            <p>
                Review registered users and internal account records.
            </p>

        </div>


        <!-- STATISTICS -->

        <div class="stats-grid">

            <div class="stat-card">

                <p>Total Users</p>

                <h2>
                    <?php echo $totalUsers; ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>Administrators</p>

                <h2>
                    <?php echo $totalAdmins; ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>Active Accounts</p>

                <h2>
                    <?php echo $activeAccounts; ?>
                </h2>

            </div>

        </div>


        <!-- USERS TABLE -->

        <section id="users" class="admin-section">

            <h2 class="section-title">
                Registered Users
            </h2>


            <div class="admin-table-box">

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Username</th>

                                <th>Role</th>

                                <th>Account Number</th>

                                <th>Status</th>

                                <th>Created</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["id"]);
                                    ?>
                                </td>


                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $user["username"]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <span class="role-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            ucfirst($user["role"])
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["account_number"]
                                        ?? "No account"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst(
                                            $user["account_status"]
                                            ?? "Not available"
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["created_at"]
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>


        <!-- ACCOUNT SECTION -->

        <section
            id="accounts"
            class="admin-section"
        >

            <h2 class="section-title">
                Account Management
            </h2>

            <div class="admin-info-box">

                <h3>Internal Account Records</h3>

                <p>
                    Registered account information appears in the
                    administration table above.
                </p>

            </div>

        </section>

    </main>

</div>

</body>
</html>