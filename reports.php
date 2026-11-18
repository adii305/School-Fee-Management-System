
<?php
require_once "config/db.php";

$pageTitle = "Reports";

$years = $conn->query("
    SELECT DISTINCT Academic_Year
    FROM Fee_Structure
    ORDER BY Academic_Year ASC
")->fetchAll(PDO::FETCH_COLUMN);

$academicYear = $_GET['year'] ?? ($years[0] ?? '');

if (!in_array($academicYear, $years, true)) {
    $academicYear = $years[0] ?? '';
}

// Overall financial summary.
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS Total_Students,

        COALESCE(SUM(f.Total_Fee), 0) AS Expected_Fees,

        COALESCE(SUM(f.Total_Paid), 0) AS Collected_Fees,

        COALESCE(SUM(f.Total_Fee - f.Total_Paid), 0) AS Pending_Fees

    FROM (
        SELECT
            s.Student_ID,
            f.Fee_ID,

            (
                f.Tuition_Fee +
                f.Admission_Fee +
                f.Exam_Fee +
                f.Other_Charges
            ) AS Total_Fee,

            COALESCE(paid.Total_Paid, 0) AS Total_Paid

        FROM Students s

        JOIN Fee_Structure f
            ON s.Class_ID = f.Class_ID

        LEFT JOIN (
            SELECT
                Student_ID,
                Fee_ID,
                SUM(Amount_Paid) AS Total_Paid
            FROM Payments
            GROUP BY Student_ID, Fee_ID
        ) paid
            ON paid.Student_ID = s.Student_ID
           AND paid.Fee_ID = f.Fee_ID

        WHERE f.Academic_Year = ?
    ) f
");

$stmt->execute([$academicYear]);
$summary = $stmt->fetch();

// Class-wise financial report.
$stmt = $conn->prepare("
    SELECT
        c.Class_Name,
        c.Section,

        COUNT(s.Student_ID) AS Total_Students,

        SUM(
            f.Tuition_Fee +
            f.Admission_Fee +
            f.Exam_Fee +
            f.Other_Charges
        ) AS Expected_Fees,

        SUM(COALESCE(paid.Total_Paid, 0)) AS Collected_Fees,

        SUM(
            f.Tuition_Fee +
            f.Admission_Fee +
            f.Exam_Fee +
            f.Other_Charges -
            COALESCE(paid.Total_Paid, 0)
        ) AS Pending_Fees

    FROM Students s

    JOIN Classes c
        ON s.Class_ID = c.Class_ID

    JOIN Fee_Structure f
        ON s.Class_ID = f.Class_ID

    LEFT JOIN (
        SELECT
            Student_ID,
            Fee_ID,
            SUM(Amount_Paid) AS Total_Paid
        FROM Payments
        GROUP BY Student_ID, Fee_ID
    ) paid
        ON paid.Student_ID = s.Student_ID
       AND paid.Fee_ID = f.Fee_ID

    WHERE f.Academic_Year = ?

    GROUP BY c.Class_ID, c.Class_Name, c.Section

    ORDER BY CAST(c.Class_Name AS UNSIGNED) ASC, c.Section ASC
");

$stmt->execute([$academicYear]);
$classReports = $stmt->fetchAll();

// Payment mode report for the selected academic year.
$stmt = $conn->prepare("
    SELECT
        p.Payment_Mode,
        COUNT(*) AS Transactions,
        SUM(p.Amount_Paid) AS Total_Amount

    FROM Payments p

    JOIN Fee_Structure f
        ON p.Fee_ID = f.Fee_ID

    WHERE f.Academic_Year = ?

    GROUP BY p.Payment_Mode
    ORDER BY Total_Amount DESC
");

$stmt->execute([$academicYear]);
$modeReports = $stmt->fetchAll();

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Financial Reports</h2>
            <p>View the school's fee collection and outstanding balances.</p>
        </div>
    </div>

    <form method="GET" class="search-form">

        <select name="year">
            <?php foreach ($years as $year): ?>
                <option
                    value="<?php echo htmlspecialchars($year); ?>"
                    <?php echo $academicYear === $year ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($year); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="secondary-btn">
            Generate Report
        </button>

    </form>

    <h3>Financial Summary</h3>

    <div class="dashboard-stats">

        <div class="stat-card">
            <div class="stat-info">
                <p>Total Students</p>
                <h2><?php echo (int)$summary['Total_Students']; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <p>Expected Fees</p>
                <h2>
                    ₹<?php echo number_format((float)$summary['Expected_Fees'], 2); ?>
                </h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <p>Collected Fees</p>
                <h2>
                    ₹<?php echo number_format((float)$summary['Collected_Fees'], 2); ?>
                </h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <p>Pending Fees</p>
                <h2>
                    ₹<?php echo number_format((float)$summary['Pending_Fees'], 2); ?>
                </h2>
            </div>
        </div>

    </div>

    <h3>Class-wise Fee Collection</h3>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                    <th>Expected Fees</th>
                    <th>Collected Fees</th>
                    <th>Pending Fees</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($classReports) > 0): ?>

                    <?php foreach ($classReports as $report): ?>

                        <tr>
                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $report['Class_Name'] . '-' . $report['Section']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td><?php echo (int)$report['Total_Students']; ?></td>

                            <td>
                                ₹<?php echo number_format((float)$report['Expected_Fees'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$report['Collected_Fees'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$report['Pending_Fees'], 2); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No class records found.</td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <h3>Payment Mode Report</h3>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>Payment Mode</th>
                    <th>Number of Transactions</th>
                    <th>Total Collection</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($modeReports) > 0): ?>

                    <?php foreach ($modeReports as $report): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($report['Payment_Mode']); ?>
                            </td>

                            <td><?php echo (int)$report['Transactions']; ?></td>

                            <td>
                                ₹<?php echo number_format((float)$report['Total_Amount'], 2); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3">No payment records found.</td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p>
        Report generated for academic year:
        <strong><?php echo htmlspecialchars($academicYear); ?></strong>
    </p>

</section>

<?php include "includes/footer.php"; ?>
