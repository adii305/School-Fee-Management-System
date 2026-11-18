
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="brand">
        <div class="brand-icon">S</div>
        <div>
            <h2>School<span>Fee</span></h2>
            <small>Management System</small>
        </div>
    </div>

    <p class="menu-heading">MAIN MENU</p>

    <nav class="menu">

        <a href="dashboard.php"
           class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <span>▦</span> Dashboard
        </a>

        <a href="students.php"
           class="<?php echo in_array($currentPage, ['students.php', 'add_student.php', 'edit_student.php']) ? 'active' : ''; ?>">
            <span>♙</span> Students
        </a>

        <a href="classes.php"
        class="<?php echo in_array($currentPage, ['classes.php', 'add_class.php']) ? 'active' : ''; ?>">
            <span>▤</span> Classes
        </a>

        <a href="fee_structure.php">
            <span>₹</span> Fee Structure
        </a>

        <a href="payments.php"
        class="<?php echo in_array($currentPage, ['payments.php', 'add_payment.php', 'view_receipt.php']) ? 'active' : ''; ?>">
            <span>⇄</span> Payments
        </a>

        <a href="pending_fees.php"
        class="<?php echo $currentPage === 'pending_fees.php' ? 'active' : ''; ?>">
            <span>◷</span> Pending Fees
        </a>

        <a href="reports.php"
        class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
            <span>▥</span> Reports
        </a>

    </nav>

    <div class="sidebar-bottom">
        <p>Academic Year</p>
        <strong>2026 - 2027</strong>
    </div>

</aside>
