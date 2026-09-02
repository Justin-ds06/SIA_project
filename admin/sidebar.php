<?php

$adminName = $_SESSION["admin_name"] ?? "Administrator";

$currentPage = basename($_SERVER["PHP_SELF"]);

?>

<aside class="sidebar">

    <div class="sidebar-logo">
        COURT<span>22</span>
    </div>

    <div class="admin-label">
        ADMIN PANEL
    </div>

    <nav class="sidebar-nav">

        <a
            href="dashboard.php"
            class="<?= $currentPage === "dashboard.php" ? "active" : "" ?>"
        >
            <span>▣</span>
            Dashboard
        </a>

        <a
            href="bookings.php"
            class="<?= $currentPage === "bookings.php" ? "active" : "" ?>"
        >
            <span>▤</span>
            Bookings
        </a>

        <a
            href="users.php"
            class="<?= $currentPage === "users.php" ? "active" : "" ?>"
        >
            <span>♙</span>
            Users
        </a>

        <a
            href="sports.php"
            class="<?= $currentPage === "sports.php" ? "active" : "" ?>"
        >
            <span>⚽</span>
            Sports
        </a>

        <a
            href="courts.php"
            class="<?= $currentPage === "courts.php" ? "active" : "" ?>"
        >
            <span>▦</span>
            Courts
        </a>

        <a
            href="payments.php"
            class="<?= $currentPage === "payments.php" ? "active" : "" ?>"
        >
            <span>₱</span>
            Payments
        </a>

        <a
            href="reviews.php"
            class="<?= $currentPage === "reviews.php" ? "active" : "" ?>"
        >
            <span>★</span>
            Reviews
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="admin-user">

            <strong>
                <?= htmlspecialchars($adminName) ?>
            </strong>

            <small>
                Administrator
            </small>

        </div>

        <a
            href="logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </div>

</aside>