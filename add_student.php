
<?php

require_once "config/db.php";

$pageTitle = "Add New Student";

$error = "";


// Fetch available classes
$classes = $conn->query("
    SELECT Class_ID, Class_Name, Section
    FROM Classes
    ORDER BY Class_Name, Section
")->fetchAll();


// Fetch guardians
$guardians = $conn->query("
    SELECT Guardian_ID, Guardian_Name
    FROM Guardians
    ORDER BY Guardian_Name
")->fetchAll();


// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $admissionNo = trim($_POST['admission_no'] ?? '');
    $studentName = trim($_POST['student_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $classID = $_POST['class_id'] ?? '';
    $guardianID = $_POST['guardian_id'] ?? '';


    if (
        $admissionNo === '' ||
        $studentName === '' ||
        $classID === ''
    ) {

        $error = "Please fill in all required fields.";

    } elseif (
        !ctype_digit($classID) ||
        ($guardianID !== '' && !ctype_digit($guardianID))
    ) {

        $error = "Please select valid class and guardian records.";

    } elseif (
        $gender !== '' &&
        !in_array($gender, ['Male', 'Female', 'Other'], true)
    ) {

        $error = "Please select a valid gender.";

    } else {

        try {

            $stmt = $conn->prepare("
                INSERT INTO Students
                (
                    Admission_No,
                    Student_Name,
                    DOB,
                    Gender,
                    Phone,
                    Address,
                    Class_ID,
                    Guardian_ID
                )

                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([

                $admissionNo,
                $studentName,
                $dob !== '' ? $dob : null,
                $gender !== '' ? $gender : null,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                (int)$classID,
                $guardianID !== '' ? (int)$guardianID : null

            ]);

            header("Location: students.php?added=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "Admission number already exists, or a selected record is invalid.";
            } else {
                $error = "Unable to save student. Please check the entered details.";
            }

        }

    }

}

include "includes/header.php";

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>Register New Student</h2>
            <p>Enter the student's information below.</p>
        </div>

        <a href="students.php" class="secondary-btn">
            ← Back to Students
        </a>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="form-card">

        <form method="POST" action="add_student.php">

            <div class="form-grid">

                <div class="form-group">
                    <label>Admission Number *</label>

                    <input
                        type="text"
                        name="admission_no"
                        placeholder="Example: ADM011"
                        value="<?php echo htmlspecialchars($_POST['admission_no'] ?? ''); ?>"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Student Name *</label>

                    <input
                        type="text"
                        name="student_name"
                        placeholder="Enter student name"
                        value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Date of Birth</label>

                    <input
                        type="date"
                        name="dob"
                        value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"
                    >
                </div>


                <div class="form-group">
                    <label>Gender</label>

                    <select name="gender">

                        <option value="">Select Gender</option>

                        <option value="Male"
                            <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>
                            Female
                        </option>

                        <option value="Other"
                            <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>
                            Other
                        </option>

                    </select>
                </div>


                <div class="form-group">
                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Enter phone number"
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    >
                </div>


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


                <div class="form-group">
                    <label>Guardian</label>

                    <select name="guardian_id">

                        <option value="">Select Guardian (Optional)</option>

                        <?php foreach ($guardians as $guardian): ?>

                            <option
                                value="<?php echo $guardian['Guardian_ID']; ?>"
                                <?php echo (($_POST['guardian_id'] ?? '') == $guardian['Guardian_ID']) ? 'selected' : ''; ?>
                            >

                                <?php echo htmlspecialchars($guardian['Guardian_Name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <div class="form-group full-width">
                    <label>Address</label>

                    <textarea
                        name="address"
                        rows="3"
                        placeholder="Enter student's address"
                    ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

            </div>


            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Save Student
                </button>

                <a href="students.php" class="secondary-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
