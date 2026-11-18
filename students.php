
<?php

require_once "config/db.php";

$pageTitle = "Student Management";

// Search functionality
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        S.Student_ID,
        S.Admission_No,
        S.Student_Name,
        S.DOB,
        S.Gender,
        S.Phone,
        C.Class_Name,
        C.Section,
        G.Guardian_Name

    FROM Students S

    INNER JOIN Classes C
        ON S.Class_ID = C.Class_ID

    LEFT JOIN Guardians G
        ON S.Guardian_ID = G.Guardian_ID
";

if ($search !== '') {

    $sql .= "
        WHERE S.Student_Name LIKE :search
        OR S.Admission_No LIKE :search
    ";

}

$sql .= " ORDER BY S.Student_ID ASC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->execute([
        'search' => "%$search%"
    ]);
} else {
    $stmt->execute();
}

$students = $stmt->fetchAll();

include "includes/header.php";

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>All Students</h2>
            <p>Manage your school's student records.</p>
        </div>

        <a href="add_student.php" class="primary-btn">
            + Add Student
        </a>

    </div>

    <?php if (isset($_GET['added'])): ?>

        <div class="success-message">
            Student registered successfully!
        </div>

    <?php elseif (isset($_GET['updated'])): ?>

        <div class="success-message">
            Student details updated successfully!
        </div>

    <?php endif; ?>

    <!-- Search -->

    <form method="GET" action="students.php" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search by student name or admission number..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="primary-btn">
            Search
        </button>

        <a href="students.php" class="secondary-btn">
            Reset
        </a>

    </form>


    <!-- Student Table -->

    <div class="table-section">

        <div class="section-heading">
            <div>
                <h2>Student Records</h2>
                <p>
                    <?php echo count($students); ?> student(s) found
                </p>
            </div>
        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Admission No.</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Guardian</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $student): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($student['Admission_No']); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['Student_Name']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student['Class_Name'] . '-' . $student['Section']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['Gender'] ?? 'Not specified'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['Phone'] ?? 'Not provided'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['Guardian_Name'] ?? 'Not assigned'); ?>
                                </td>

                                <td>
                                    <a
                                        href="edit_student.php?id=<?php echo $student['Student_ID']; ?>"
                                        class="edit-btn"
                                    >
                                        Edit
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-message">
                                No student records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>
