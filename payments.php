
<?php
require_once "config/db.php";

$pageTitle = "Payment Management";

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        p.Payment_ID,
        p.Receipt_No,
        p.Payment_Date,
        p.Amount_Paid,
        p.Payment_Mode,
        s.Student_Name,
        s.Admission_No,
        c.Class_Name,
        c.Section,
        f.Academic_Year
    FROM Payments p
    JOIN Students s ON p.Student_ID = s.Student_ID
    JOIN Fee_Structure f ON p.Fee_ID = f.Fee_ID
    JOIN Classes c ON s.Class_ID = c.Class_ID
";

if ($search !== '') {
    $sql .= "
        WHERE s.Student_Name LIKE :search
           OR s.Admission_No LIKE :search
           OR p.Receipt_No LIKE :search
    ";
}

$sql .= " ORDER BY p.Payment_Date DESC, p.Payment_ID DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$payments = $stmt->fetchAll();

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Payment History</h2>
            <p>View and manage student fee transactions.</p>
        </div>

        <a href="add_payment.php" class="primary-btn">
            + Record Payment
        </a>
    </div>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search student, admission number or receipt..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="secondary-btn">Search</button>
        <a href="payments.php" class="secondary-btn">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Academic Year</th>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Amount Paid</th>
                    <th>Receipt</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($payments) > 0): ?>

                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($payment['Receipt_No']); ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($payment['Student_Name']); ?>
                                </strong>
                                <br>
                                <small>
                                    <?php echo htmlspecialchars($payment['Admission_No']); ?>
                                </small>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $payment['Class_Name'] . '-' . $payment['Section']
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['Academic_Year']); ?>
                            </td>

                            <td>
                                <?php echo date('d-m-Y', strtotime($payment['Payment_Date'])); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['Payment_Mode']); ?>
                            </td>

                            <td>
                                <strong>
                                    ₹<?php echo number_format((float)$payment['Amount_Paid'], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <a
                                    href="view_receipt.php?id=<?php echo (int)$payment['Payment_ID']; ?>"
                                    class="edit-btn"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">No payment records found.</td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p>
        Total transactions displayed:
        <strong><?php echo count($payments); ?></strong>
    </p>

</section>

<?php include "includes/footer.php"; ?>
