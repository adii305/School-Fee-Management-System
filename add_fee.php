
<?php

require_once "config/db.php";

$pageTitle = "Add Fee Structure";

$error = "";


// Fetch classes from database

$classes = $conn->query("
    SELECT Class_ID, Class_Name, Section
    FROM Classes
    ORDER BY Class_Name, Section
")->fetchAll();


// Handle form submission

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $classID = $_POST['class_id'] ?? '';
    $academicYear = trim($_POST['academic_year'] ?? '');

    $tuition = trim($_POST['tuition_fee'] ?? '');
    $admission = trim($_POST['admission_fee'] ?? '0');
    $exam = trim($_POST['exam_fee'] ?? '0');
    $other = trim($_POST['other_charges'] ?? '0');


    // Validate required fields

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
                INSERT INTO Fee_Structure
                (
                    Class_ID,
                    Academic_Year,
                    Tuition_Fee,
                    Admission_Fee,
                    Exam_Fee,
                    Other_Charges
                )

                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int)$classID,
                $academicYear,
                $tuition,
                $admission,
                $exam,
                $other
            ]);

            header("Location: fee_structure.php?added=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "A fee structure already exists for this class and academic year.";
            } else {
                $error = "Unable to add fee structure. Please check your entries.";
            }

        }

    }

}

include "includes/header.php";

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>Add Fee Structure</h2>
            <p>Create a new class-wise fee record.</p>
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

        <form method="POST" action="add_fee.php">

            <div class="form-grid">


                <!-- Class -->

                <div class="form-group">

                    <label>Class *</label>

                    <select name="class_id" required>

                        <option value="">Select Class</option>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?php echo $class['Class_ID']; ?>"
                                <?php echo (($_POST['class_id'] ?? '') == $class['Class_ID']) ? 'selected' : ''; ?>
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


                <!-- Academic Year -->

                <div class="form-group">

                    <label>Academic Year *</label>

                    <input
                        type="text"
                        name="academic_year"
                        placeholder="Example: 2027-2028"
                        pattern="[0-9]{4}-[0-9]{4}"
                        value="<?php echo htmlspecialchars($_POST['academic_year'] ?? '2026-2027'); ?>"
                        required
                    >

                </div>


                <!-- Tuition Fee -->

                <div class="form-group">

                    <label>Tuition Fee (₹) *</label>

                    <input
                        type="number"
                        name="tuition_fee"
                        min="0"
                        step="0.01"
                        placeholder="Enter tuition fee"
                        value="<?php echo htmlspecialchars($_POST['tuition_fee'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- Admission Fee -->

                <div class="form-group">

                    <label>Admission Fee (₹)</label>

                    <input
                        type="number"
                        name="admission_fee"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($_POST['admission_fee'] ?? '0'); ?>"
                    >

                </div>


                <!-- Exam Fee -->

                <div class="form-group">

                    <label>Exam Fee (₹)</label>

                    <input
                        type="number"
                        name="exam_fee"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($_POST['exam_fee'] ?? '0'); ?>"
                    >

                </div>


                <!-- Other Charges -->

                <div class="form-group">

                    <label>Other Charges (₹)</label>

                    <input
                        type="number"
                        name="other_charges"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($_POST['other_charges'] ?? '0'); ?>"
                    >

                </div>

            </div>


            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Save Fee Structure
                </button>

                <a href="fee_structure.php" class="secondary-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
