
<?php

require_once "config/db.php";

$pageTitle = "Edit Student";

$error = "";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid student ID.");
}


// Fetch student
$stmt = $conn->prepare("
    SELECT *
    FROM Students
    WHERE Student_ID = ?
");

$stmt->execute([$id]);

$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}


// Fetch classes
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


// Update student
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
                UPDATE Students

                SET
                    Admission_No = ?,
                    Student_Name = ?,
                    DOB = ?,
                    Gender = ?,
                    Phone = ?,
                    Address = ?,
                    Class_ID = ?,
                    Guardian_ID = ?

                WHERE Student_ID = ?
            ");

            $stmt->execute([

                $admissionNo,
                $studentName,
                $dob !== '' ? $dob : null,
                $gender !== '' ? $gender : null,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                (int)$classID,
                $guardianID !== '' ? (int)$guardianID : null,
                $id

            ]);

            header("Location: students.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "Admission number already exists, or a selected record is invalid.";
            } else {
                $error = "Unable to update student. Please check the entered details.";
            }

        }

    }

}

include "includes/header.php";


// Display submitted values if validation failed
function fieldValue($field, $student) {

    $postNames = [
        'Admission_No' => 'admission_no',
        'Student_Name' => 'student_name',
        'DOB' => 'dob',
        'Gender' => 'gender',
        'Phone' => 'phone',
        'Address' => 'address',
        'Class_ID' => 'class_id',
        'Guardian_ID' => 'guardian_id'
    ];

    $postName = $postNames[$field] ?? $field;

    return htmlspecialchars(
        $_POST[$postName] ?? $student[$field] ?? ''
    );

}

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>Edit Student</h2>
            <p>Update the student's information.</p>
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

        <form method="POST" action="edit_student.php?id=<?php echo $id; ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Admission Number *</label>

                    <input
                        type="text"
                        name="admission_no"
                        value="<?php echo fieldValue('Admission_No', $student); ?>"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Student Name *</label>

                    <input
                        type="text"
                        name="student_name"
                        value="<?php echo fieldValue('Student_Name', $student); ?>"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Date of Birth</label>

                    <input
                        type="date"
                        name="dob"
                        value="<?php echo fieldValue('DOB', $student); ?>"
                    >
                </div>


                <div class="form-group">
                    <label>Gender</label>

                    <select name="gender">

                        <option value="">Select Gender</option>

                        <?php foreach (['Male', 'Female', 'Other'] as $genderOption): ?>

                            <option
                                value="<?php echo $genderOption; ?>"
                                <?php echo fieldValue('Gender', $student) === $genderOption ? 'selected' : ''; ?>
                            >
                                <?php echo $genderOption; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <div class="form-group">
                    <label>Phone Number</label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo fieldValue('Phone', $student); ?>"
                    >
                </div>


                <div class="form-group">
                    <label>Class *</label>

                    <select name="class_id" required>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?php echo $class['Class_ID']; ?>"
                                <?php echo fieldValue('Class_ID', $student) == $class['Class_ID'] ? 'selected' : ''; ?>
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

                        <option value="">No Guardian Assigned</option>

                        <?php foreach ($guardians as $guardian): ?>

                            <option
                                value="<?php echo $guardian['Guardian_ID']; ?>"
                                <?php echo fieldValue('Guardian_ID', $student) == $guardian['Guardian_ID'] ? 'selected' : ''; ?>
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
                    ><?php echo fieldValue('Address', $student); ?></textarea>
                </div>

            </div>


            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Update Student
                </button>

                <a href="students.php" class="secondary-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php include "includes/footer.php"; ?>
