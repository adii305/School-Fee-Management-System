
<?php
require_once "config/db.php";

$pageTitle = "Record Payment";

$error = "";

$students = $conn->query("
    SELECT
        s.Student_ID,
        s.Student_Name,
        s.Admission_No,
        c.Class_Name,
        c.Section
    FROM Students s
    JOIN Classes c ON s.Class_ID = c.Class_ID
    ORDER BY s.Student_Name
")->fetchAll();

$years = $conn->query("
    SELECT DISTINCT Academic_Year
    FROM Fee_Structure
    ORDER BY Academic_Year DESC
")->fetchAll(PDO::FETCH_COLUMN);

$studentId = $_POST['student_id'] ?? '';
$academicYear = $_POST['academic_year'] ?? '';
$paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
$amount = $_POST['amount'] ?? '';
$paymentMode = $_POST['payment_mode'] ?? 'UPI';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (
            !ctype_digit((string)$studentId) ||
            !in_array($academicYear, $years, true) ||
            !is_numeric($amount) ||
            (float)$amount <= 0 ||
            !in_array($paymentMode, ['Cash', 'UPI', 'Card', 'Bank Transfer'], true)
        ) {
            throw new Exception("Please enter valid payment details.");
        }

        $dateParts = explode('-', $paymentDate);

        if (
            count($dateParts) !== 3 ||
            !checkdate(
                (int)$dateParts[1],
                (int)$dateParts[2],
                (int)$dateParts[0]
            )
        ) {
            throw new Exception("Please enter a valid payment date.");
        }

        // Find the student and their class.
        $stmt = $conn->prepare("
            SELECT Student_ID, Class_ID, Student_Name
            FROM Students
            WHERE Student_ID = ?
        ");

        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        if (!$student) {
            throw new Exception("Selected student was not found.");
        }

        // Find the fee structure matching the student's class and year.
        $stmt = $conn->prepare("
            SELECT Fee_ID,
                   Tuition_Fee,
                   Admission_Fee,
                   Exam_Fee,
                   Other_Charges
            FROM Fee_Structure
            WHERE Class_ID = ?
              AND Academic_Year = ?
        ");

        $stmt->execute([
            $student['Class_ID'],
            $academicYear
        ]);

        $fee = $stmt->fetch();

        if (!$fee) {
            throw new Exception(
                "No fee structure exists for this student's class and academic year."
            );
        }

        $feeTotal =
            (float)$fee['Tuition_Fee'] +
            (float)$fee['Admission_Fee'] +
            (float)$fee['Exam_Fee'] +
            (float)$fee['Other_Charges'];

        // Calculate how much the student has already paid.
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(Amount_Paid), 0)
            FROM Payments
            WHERE Student_ID = ?
              AND Fee_ID = ?
        ");

        $stmt->execute([
            $studentId,
            $fee['Fee_ID']
        ]);

        $alreadyPaid = (float)$stmt->fetchColumn();

        $balance = $feeTotal - $alreadyPaid;
        $paymentAmount = round((float)$amount, 2);

        if ($balance <= 0) {
            throw new Exception("This student's fee is already fully paid.");
        }

        if ($paymentAmount > $balance) {
            throw new Exception(
                "Payment exceeds the remaining balance of ₹" .
                number_format($balance, 2) . "."
            );
        }

        // Generate a unique receipt number.
        $receiptNo = "SFM-" . date('Ymd') . "-" .
                     strtoupper(bin2hex(random_bytes(4)));

        // Insert the payment using a prepared statement.
        $stmt = $conn->prepare("
            INSERT INTO Payments
            (
                Student_ID,
                Fee_ID,
                Payment_Date,
                Amount_Paid,
                Payment_Mode,
                Receipt_No
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $studentId,
            $fee['Fee_ID'],
            $paymentDate,
            $paymentAmount,
            $paymentMode,
            $receiptNo
        ]);

        $paymentId = $conn->lastInsertId();

        header("Location: view_receipt.php?id=" . $paymentId);
        exit;

    } catch (Exception $e) {

        $error = $e->getMessage();

    } catch (PDOException $e) {

        $error = "Unable to save the payment. Please check the entered details.";
    }
}

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Record New Payment</h2>
            <p>Enter the student's fee payment details.</p>
        </div>

        <a href="payments.php" class="secondary-btn">
            Back to Payments
        </a>
    </div>

    <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="form-card">

        <form method="POST">

            <div class="form-grid">

                <div class="form-group full-width">
                    <label>Select Student</label>

                    <select name="student_id" required>
                        <option value="">Choose a student</option>

                        <?php foreach ($students as $student): ?>

                            <option
                                value="<?php echo (int)$student['Student_ID']; ?>"
                                <?php echo (string)$studentId === (string)$student['Student_ID'] ? 'selected' : ''; ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $student['Student_Name'] .
                                    " (" . $student['Admission_No'] . ") - " .
                                    $student['Class_Name'] . "-" .
                                    $student['Section']
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Academic Year</label>

                    <select name="academic_year" required>
                        <option value="">Select academic year</option>

                        <?php foreach ($years as $year): ?>
                            <option
                                value="<?php echo htmlspecialchars($year); ?>"
                                <?php echo $academicYear === $year ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Payment Date</label>

                    <input
                        type="date"
                        name="payment_date"
                        value="<?php echo htmlspecialchars($paymentDate); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Amount Paid (₹)</label>

                    <input
                        type="number"
                        name="amount"
                        min="0.01"
                        step="0.01"
                        placeholder="Enter amount"
                        value="<?php echo htmlspecialchars($amount); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Payment Mode</label>

                    <select name="payment_mode" required>
                        <?php
                        $modes = ['Cash', 'UPI', 'Card', 'Bank Transfer'];
                        ?>

                        <?php foreach ($modes as $mode): ?>
                            <option
                                value="<?php echo $mode; ?>"
                                <?php echo $paymentMode === $mode ? 'selected' : ''; ?>
                            >
                                <?php echo $mode; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">
                    Save Payment
                </button>

                <a href="payments.php" class="secondary-btn">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
