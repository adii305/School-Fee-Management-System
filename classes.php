
<?php
require_once "config/db.php";

$pageTitle = "Class Management";

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        c.Class_ID,
        c.Class_Name,
        c.Section,
        c.Class_Teacher,
        COUNT(s.Student_ID) AS Total_Students
    FROM Classes c
    LEFT JOIN Students s
        ON c.Class_ID = s.Class_ID
";

if ($search !== '') {
    $sql .= "
        WHERE c.Class_Name LIKE :search
           OR c.Section LIKE :search
           OR c.Class_Teacher LIKE :search
    ";
}

$sql .= "
    GROUP BY
        c.Class_ID,
        c.Class_Name,
        c.Section,
        c.Class_Teacher
    ORDER BY CAST(c.Class_Name AS UNSIGNED), c.Section
";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$classes = $stmt->fetchAll();

include "includes/header.php";
?>

<section class="page-section">

    <div class="page-heading">
        <div>
            <h2>Class Management</h2>
            <p>View and manage school classes and sections.</p>
        </div>

        <a href="add_class.php" class="primary-btn">
            + Add New Class
        </a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="success-message">
            New class added successfully!
        </div>
    <?php endif; ?>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search class, section or teacher..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="secondary-btn">
            Search
        </button>

        <a href="classes.php" class="secondary-btn">
            Reset
        </a>

    </form>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>Class ID</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Class Teacher</th>
                    <th>Total Students</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($classes) > 0): ?>

                    <?php foreach ($classes as $class): ?>

                        <tr>
                            <td>
                                <?php echo (int)$class['Class_ID']; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($class['Class_Name']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($class['Section']); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $class['Class_Teacher'] ?: 'Not Assigned'
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo (int)$class['Total_Students']; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No classes found.</td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p>
        Total classes:
        <strong><?php echo count($classes); ?></strong>
    </p>

</section>

<?php include "includes/footer.php"; ?>
