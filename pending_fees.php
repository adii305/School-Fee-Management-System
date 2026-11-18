
<?php
require_once "config/db.php";

$pageTitle = "Pending Fees";

$years = $conn->query("
    SELECT DISTINCT Academic_Year
    FROM Fee_Structure
    ORDER BY Academic_Year DESC
")->fetchAll(PDO::FETCH_COLUMN);

$academicYear = $_GET['year'] ?? ($years[0] ?? '');
$search = trim($_GET['search'] ?? '');

if (!in_array($academicYear, $years, true)) {
    $academicYear = $years[0] ?? '';
}

$sql = "
    SELECT
        s.Student_ID,
        s.Student_Name,
        s.Admission_No,
        c.Class_Name,
        c.Section,
        f.Academic_Year,
        f.Fee_ID,

        (
            f.Tuition_Fee +
            f.Admission_Fee +
            f.Exam_Fee +
            f.Other_Charges
        ) AS Total_Fee,

        COALESCE(paid.Total_Paid, 0) AS Total_Paid,

        (
            f.Tuition_Fee +
            f.Admission_Fee +
            f.Exam_Fee +
            f.Other_Charges -
            COALESCE(paid.Total_Paid, 0)
        ) AS Pending_Fee

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

    WHERE f.Academic_Year = :year
";

$params = ['year' => $academicYear];

if ($search !== '') {
    $sql .= "
        AND (
            s.Student_Name LIKE :search
            OR s.Admission_No LIKE :search
        )
    ";

    $params['search'] = "%$search%";
}

$sql .= "
    AND (
        f.Tuition_Fee +
        f.Admission_Fee +
        f.Exam_Fee +
        f.Other_Charges -
        COALESCE(paid.Total_Paid, 0)
    ) > 0

    ORDER BY c.Class_Name, c.Section, s.Student_Name
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$pendingStudents = $stmt->fetchAll();

$totalPending = 0;

foreach ($pendingStudents as $student) {
    $totalPending += (float)$student['Pending_Fee'];
}

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Pending Fee Records</h2>
            <p>Track students who have outstanding fee balances.</p>
        </div>
    </div>

    <div class="dashboard-stats">

        <div class="stat-card">
            <div class="stat-info">
                <p>Students with Pending Fees</p>
                <h2><?php echo count($pendingStudents); ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <p>Total Outstanding Amount</p>
                <h2>₹<?php echo number_format($totalPending, 2); ?></h2>
            </div>
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

        <input
            type="text"
            name="search"
            placeholder="Search student name or admission number..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="secondary-btn">Filter</button>

        <a href="pending_fees.php" class="secondary-btn">Reset</a>

    </form>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Admission No.</th>
                    <th>Class</th>
                    <th>Total Fee</th>
                    <th>Paid Amount</th>
                    <th>Pending Amount</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($pendingStudents) > 0): ?>

                    <?php foreach ($pendingStudents as $student): ?>

                        <tr>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($student['Student_Name']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['Admission_No']); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $student['Class_Name'] . '-' . $student['Section']
                                );
                                ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$student['Total_Fee'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$student['Total_Paid'], 2); ?>
                            </td>

                            <td>
                                <strong>
                                    ₹<?php echo number_format((float)$student['Pending_Fee'], 2); ?>
                                </strong>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No pending fee records found for this academic year.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p>
        Academic Year:
        <strong><?php echo htmlspecialchars($academicYear); ?></strong>
    </p>

</section>

<?php include "includes/footer.php"; ?>
