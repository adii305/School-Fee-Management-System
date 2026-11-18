
<?php

require_once "config/db.php";

$pageTitle = "Dashboard";

$academicYear = "2026-2027";

// Total students
$studentQuery = $conn->query(
    "SELECT COUNT(*) FROM Students"
);

$totalStudents = $studentQuery->fetchColumn();


// Total fee collection
$collectionQuery = $conn->query(
    "SELECT COALESCE(SUM(Amount_Paid), 0) FROM Payments"
);

$totalCollection = $collectionQuery->fetchColumn();


// Total pending fees
$pendingQuery = $conn->prepare("
    SELECT COALESCE(SUM(student_fees.total_fee - student_fees.paid), 0)
    FROM (
        SELECT
            S.Student_ID,

            (
                F.Tuition_Fee +
                F.Admission_Fee +
                F.Exam_Fee +
                F.Other_Charges
            ) AS total_fee,

            COALESCE(SUM(P.Amount_Paid), 0) AS paid

        FROM Students S

        INNER JOIN Fee_Structure F
            ON S.Class_ID = F.Class_ID

        LEFT JOIN Payments P
            ON S.Student_ID = P.Student_ID
            AND F.Fee_ID = P.Fee_ID

        WHERE F.Academic_Year = ?

        GROUP BY S.Student_ID, F.Fee_ID,
                 F.Tuition_Fee, F.Admission_Fee,
                 F.Exam_Fee, F.Other_Charges

    ) AS student_fees
");

$pendingQuery->execute([$academicYear]);

$totalPending = $pendingQuery->fetchColumn();


// Total payment transactions
$paymentCountQuery = $conn->query(
    "SELECT COUNT(*) FROM Payments"
);

$totalPayments = $paymentCountQuery->fetchColumn();


// Recent payments
$recentQuery = $conn->query("
    SELECT
        P.Receipt_No,
        P.Payment_Date,
        P.Amount_Paid,
        P.Payment_Mode,
        S.Student_Name

    FROM Payments P

    INNER JOIN Students S
        ON P.Student_ID = S.Student_ID

    ORDER BY P.Payment_ID DESC

    LIMIT 5
");

$recentPayments = $recentQuery->fetchAll();


// Load common header
include "includes/header.php";

?>

<section class="welcome-section">

    <div>
        <h2>School Overview</h2>
        <p>Here's what's happening with your school fees.</p>
    </div>

    <div class="year-badge">
        Academic Year: 2026-2027
    </div>

</section>


<!-- Statistics Cards -->

<section class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon blue">♙</div>
        <p>Total Students</p>
        <h2><?php echo number_format($totalStudents); ?></h2>
        <small>Registered students</small>
    </div>


    <div class="stat-card">
        <div class="stat-icon green">₹</div>
        <p>Total Collection</p>
        <h2>₹<?php echo number_format($totalCollection, 2); ?></h2>
        <small>Payments received</small>
    </div>


    <div class="stat-card">
        <div class="stat-icon orange">◷</div>
        <p>Pending Fees</p>
        <h2>₹<?php echo number_format($totalPending, 2); ?></h2>
        <small>Outstanding amount</small>
    </div>


    <div class="stat-card">
        <div class="stat-icon purple">⇄</div>
        <p>Total Payments</p>
        <h2><?php echo number_format($totalPayments); ?></h2>
        <small>Recorded transactions</small>
    </div>

</section>


<!-- Recent Payments -->

<section class="table-section">

    <div class="section-heading">
        <div>
            <h2>Recent Payments</h2>
            <p>Latest fee transactions recorded in the system.</p>
        </div>

        <a href="payments.php" class="view-all">
            View All →
        </a>
    </div>


    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Student Name</th>
                    <th>Date</th>
                    <th>Payment Mode</th>
                    <th>Amount</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($recentPayments) > 0): ?>

                    <?php foreach ($recentPayments as $payment): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($payment['Receipt_No']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['Student_Name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['Payment_Date']); ?>
                            </td>

                            <td>
                                <span class="payment-mode">
                                    <?php echo htmlspecialchars($payment['Payment_Mode'] ?? 'Not specified'); ?>
                                </span>
                            </td>

                            <td class="amount">
                                ₹<?php echo number_format($payment['Amount_Paid'], 2); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty-message">
                            No payment records found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<?php include "includes/footer.php"; ?>
