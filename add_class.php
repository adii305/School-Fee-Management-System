
<?php
require_once "config/db.php";

$pageTitle = "Add New Class";

$error = "";

$className = trim($_POST['class_name'] ?? '');
$section = trim($_POST['section'] ?? '');
$teacher = trim($_POST['class_teacher'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($className === '' || $section === '') {

        $error = "Class name and section are required.";

    } elseif (strlen($className) > 30 || strlen($section) > 10) {

        $error = "Class name or section exceeds the allowed length.";

    } elseif (strlen($teacher) > 100) {

        $error = "Teacher name cannot exceed 100 characters.";

    } else {

        try {

            $stmt = $conn->prepare("
                INSERT INTO Classes
                (Class_Name, Section, Class_Teacher)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $className,
                $section,
                $teacher !== '' ? $teacher : null
            ]);

            header("Location: classes.php?added=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "This class and section already exist.";
            } else {
                $error = "Unable to add class. Please check your details.";
            }
        }
    }
}

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Add New Class</h2>
            <p>Enter the new class and section details.</p>
        </div>

        <a href="classes.php" class="secondary-btn">
            Back to Classes
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

                <div class="form-group">
                    <label>Class Name *</label>

                    <input
                        type="text"
                        name="class_name"
                        maxlength="30"
                        placeholder="Example: 8"
                        value="<?php echo htmlspecialchars($className); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Section *</label>

                    <input
                        type="text"
                        name="section"
                        maxlength="10"
                        placeholder="Example: A"
                        value="<?php echo htmlspecialchars($section); ?>"
                        required
                    >
                </div>

                <div class="form-group full-width">
                    <label>Class Teacher</label>

                    <input
                        type="text"
                        name="class_teacher"
                        maxlength="100"
                        placeholder="Enter class teacher's name"
                        value="<?php echo htmlspecialchars($teacher); ?>"
                    >
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Save Class
                </button>

                <a href="classes.php" class="secondary-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
