
<?php

require_once "config/db.php";

$pageTitle = "Edit Fee Structure";

$error = "";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid fee structure ID.");
}


// Fetch existing fee record

$stmt = $conn->prepare("
    SELECT *
    FROM Fee_Structure
    WHERE Fee_ID = ?
");

$stmt->execute([$id]);

$fee = $stmt->fetch();

if (!$fee) {
    die("Fee structure not found.");
}


// Fetch classes

$classes = $conn->query("
    SELECT Class_ID, Class_Name, Section
    FROM Classes
    ORDER BY Class_Name, Section
")->fetchAll();


// Process update

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $classID = $_POST['class_id'] ?? '';
    $academicYear = trim($_POST['academic_year'] ?? '');

    $tuition = trim($_POST['tuition_fee'] ?? '');
    $admission = trim($_POST['admission_fee'] ?? '0');
    $exam = trim($_POST['exam_fee'] ?? '0');
    $other = trim($_POST['other_charges'] ?? '0');


    if (
        $classID === '' ||
        $academicYear === '' ||
        $tuition === ''
    ) {

        $error = "Please fill in all required fields.";

    } elseif (
        !ctype_digit($classID) ||
        !preg_match('/^\d{4}-\d{4}$/', $academicYear)
    ) {

        $error = "Please select a valid class and academic year.";

    } elseif (
        !is_numeric($tuition) ||
        !is_numeric($admission) ||
        !is_numeric($exam) ||
        !is_numeric($other) ||
        (float)$tuition < 0 ||
        (float)$admission < 0 ||
        (float)$exam < 0 ||
        (float)$other < 0
    ) {

        $error = "Fee amounts must be valid non-negative numbers.";

    } else {

        try {

            $stmt = $conn->prepare("
                UPDATE Fee_Structure

                SET
                    Class_ID = ?,
                    Academic_Year = ?,
                    Tuition_Fee = ?,
                    Admission_Fee = ?,
                    Exam_Fee = ?,
                    Other_Charges = ?

                WHERE Fee_ID = ?
            ");

            $stmt->execute([
                (int)$classID,
                $academicYear,
                $tuition,
                $admission,
                $exam,
                $other,
                $id
            ]);

            header("Location: fee_structure.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "A fee structure already exists for this class and academic year.";
            } else {
                $error = "Unable to update fee structure. Please check your entries.";
            }

        }

    }

}


// Display submitted values if validation fails

function feeValue($field, $fee) {

    $postNames = [
        'Class_ID' => 'class_id',
        'Academic_Year' => 'academic_year',
        'Tuition_Fee' => 'tuition_fee',
        'Admission_Fee' => 'admission_fee',
        'Exam_Fee' => 'exam_fee',
        'Other_Charges' => 'other_charges'
    ];

    $postName = $postNames[$field] ?? $field;

    return htmlspecialchars(
        $_POST[$postName] ?? $fee[$field] ?? ''
    );

}

include "includes/header.php";

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>Edit Fee Structure</h2>
            <p>Update the fee details for this class.</p>
        </div>

        <a href="fee_structure.php" class="secondary-btn">
            ← Back to Fee Structure
        </a>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="form-card">

        <form method="POST" action="edit_fee.php?id=<?php echo $id; ?>">

            <div class="form-grid">


                <div class="form-group">

                    <label>Class *</label>

                    <select name="class_id" required>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?php echo $class['Class_ID']; ?>"
                                <?php echo feeValue('Class_ID', $fee) == $class['Class_ID'] ? 'selected' : ''; ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $class['Class_Name'] . '-' . $class['Section']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Academic Year *</label>

                    <input
                        type="text"
                        name="academic_year"
                        pattern="[0-9]{4}-[0-9]{4}"
                        value="<?php echo feeValue('Academic_Year', $fee); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Tuition Fee (₹) *</label>

                    <input
                        type="number"
                        name="tuition_fee"
                        min="0"
                        step="0.01"
                        value="<?php echo feeValue('Tuition_Fee', $fee); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Admission Fee (₹)</label>

                    <input
                        type="number"
                        name="admission_fee"
                        min="0"
                        step="0.01"
                        value="<?php echo feeValue('Admission_Fee', $fee); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Exam Fee (₹)</label>

                    <input
                        type="number"
                        name="exam_fee"
                        min="0"
                        step="0.01"
                        value="<?php echo feeValue('Exam_Fee', $fee); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Other Charges (₹)</label>

                    <input
                        type="number"
                        name="other_charges"
                        min="0"
                        step="0.01"
                        value="<?php echo feeValue('Other_Charges', $fee); ?>"
                    >

                </div>

            </div>


            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Update Fee Structure
                </button>

                <a href="fee_structure.php" class="secondary-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
