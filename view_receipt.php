
<?php
require_once "config/db.php";

$id = $_GET['id'] ?? '';

if (!ctype_digit((string)$id)) {
    die("Invalid receipt request.");
}

$stmt = $conn->prepare("
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
        f.Academic_Year,
        f.Tuition_Fee,
        f.Admission_Fee,
        f.Exam_Fee,
        f.Other_Charges
    FROM Payments p
    JOIN Students s ON p.Student_ID = s.Student_ID
    JOIN Fee_Structure f ON p.Fee_ID = f.Fee_ID
    JOIN Classes c ON s.Class_ID = c.Class_ID
    WHERE p.Payment_ID = ?
");

$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Receipt not found.");
}

$pageTitle = "Payment Receipt";

$totalFee =
    (float)$receipt['Tuition_Fee'] +
    (float)$receipt['Admission_Fee'] +
    (float)$receipt['Exam_Fee'] +
    (float)$receipt['Other_Charges'];

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(Amount_Paid), 0)
    FROM Payments
    WHERE Student_ID = (
        SELECT Student_ID FROM Payments WHERE Payment_ID = ?
    )
    AND Fee_ID = (
        SELECT Fee_ID FROM Payments WHERE Payment_ID = ?
    )
");

$stmt->execute([$id, $id]);
$totalPaid = (float)$stmt->fetchColumn();

$balance = max(0, $totalFee - $totalPaid);

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Payment Receipt</h2>
            <p>Transaction confirmation and payment details.</p>
        </div>

        <button onclick="window.print()" class="primary-btn">
            Print Receipt
        </button>
    </div>

    <div class="form-card receipt-card">

        <div class="receipt-header">
            <h2>School Fee Management System</h2>
            <p>Official Fee Payment Receipt</p>
        </div>

        <hr>

        <div class="receipt-details">

            <p>
                <strong>Receipt Number:</strong>
                <?php echo htmlspecialchars($receipt['Receipt_No']); ?>
            </p>

            <p>
                <strong>Payment Date:</strong>
                <?php echo date('d-m-Y', strtotime($receipt['Payment_Date'])); ?>
            </p>

            <p>
                <strong>Student Name:</strong>
                <?php echo htmlspecialchars($receipt['Student_Name']); ?>
            </p>

            <p>
                <strong>Admission Number:</strong>
                <?php echo htmlspecialchars($receipt['Admission_No']); ?>
            </p>

            <p>
                <strong>Class:</strong>
                <?php
                echo htmlspecialchars(
                    $receipt['Class_Name'] . '-' . $receipt['Section']
                );
                ?>
            </p>

            <p>
                <strong>Academic Year:</strong>
                <?php echo htmlspecialchars($receipt['Academic_Year']); ?>
            </p>

            <p>
                <strong>Payment Mode:</strong>
                <?php echo htmlspecialchars($receipt['Payment_Mode']); ?>
            </p>

        </div>

        <hr>

        <h3>Fee Summary</h3>

        <table>
            <tr>
                <td>Tuition Fee</td>
                <td>₹<?php echo number_format((float)$receipt['Tuition_Fee'], 2); ?></td>
            </tr>

            <tr>
                <td>Admission Fee</td>
                <td>₹<?php echo number_format((float)$receipt['Admission_Fee'], 2); ?></td>
            </tr>

            <tr>
                <td>Examination Fee</td>
                <td>₹<?php echo number_format((float)$receipt['Exam_Fee'], 2); ?></td>
            </tr>

            <tr>
                <td>Other Charges</td>
                <td>₹<?php echo number_format((float)$receipt['Other_Charges'], 2); ?></td>
            </tr>

            <tr>
                <th>Total Fee</th>
                <th>₹<?php echo number_format($totalFee, 2); ?></th>
            </tr>

            <tr>
                <th>Total Paid So Far</th>
                <th>₹<?php echo number_format($totalPaid, 2); ?></th>
            </tr>

            <tr>
                <th>Remaining Balance</th>
                <th>₹<?php echo number_format($balance, 2); ?></th>
            </tr>

            <tr>
                <th>Amount Paid in This Transaction</th>
                <th>
                    ₹<?php echo number_format((float)$receipt['Amount_Paid'], 2); ?>
                </th>
            </tr>
        </table>

        <p class="receipt-thanks">
            Thank you. Please keep this receipt for your records.
        </p>

    </div>

    <a href="payments.php" class="secondary-btn">
        Back to Payment History
    </a>

</section>

<style>
.receipt-card {
    max-width: 850px;
    margin: 20px auto;
}

.receipt-header {
    text-align: center;
    padding: 15px;
}

.receipt-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 25px;
    margin: 20px 0;
}

.receipt-thanks {
    text-align: center;
    margin-top: 25px;
    font-weight: 600;
}

@media print {
    .sidebar,
    .topbar,
    .page-heading,
    .secondary-btn {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }

    .receipt-card {
        box-shadow: none !important;
        border: 1px solid #ccc;
        color: black;
    }

    body {
        background: white !important;
        color: black !important;
    }
}
</style>

<?php include "includes/footer.php"; ?>
