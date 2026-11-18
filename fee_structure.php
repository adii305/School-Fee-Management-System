
<?php

require_once "config/db.php";

$pageTitle = "Fee Structure";

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        F.Fee_ID,
        F.Academic_Year,
        F.Tuition_Fee,
        F.Admission_Fee,
        F.Exam_Fee,
        F.Other_Charges,

        C.Class_Name,
        C.Section,

        (
            F.Tuition_Fee +
            F.Admission_Fee +
            F.Exam_Fee +
            F.Other_Charges
        ) AS Total_Fee

    FROM Fee_Structure F

    INNER JOIN Classes C
        ON F.Class_ID = C.Class_ID
";

if ($search !== '') {

    $sql .= "
        WHERE C.Class_Name LIKE :search
        OR C.Section LIKE :search
        OR F.Academic_Year LIKE :search
    ";

}

$sql .= " ORDER BY F.Academic_Year ASC, CAST(C.Class_Name AS UNSIGNED) ASC, C.Section ASC";
$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$fees = $stmt->fetchAll();

include "includes/header.php";

?>

<section class="page-section">

    <div class="page-heading">

        <div>
            <h2>Fee Structure</h2>
            <p>Manage class-wise academic fees.</p>
        </div>

        <a href="add_fee.php" class="primary-btn">
            + Add Fee Structure
        </a>

    </div>


    <!-- Search Form -->

    <form method="GET" action="fee_structure.php" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search class or academic year..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="primary-btn">
            Search
        </button>

        <a href="fee_structure.php" class="secondary-btn">
            Reset
        </a>

    </form>


    <!-- Success Messages -->

    <?php if (isset($_GET['added'])): ?>

        <div class="success-message">
            Fee structure added successfully!
        </div>

    <?php elseif (isset($_GET['updated'])): ?>

        <div class="success-message">
            Fee structure updated successfully!
        </div>

    <?php endif; ?>


    <!-- Fee Table -->

    <div class="table-section">

        <div class="section-heading">

            <div>
                <h2>All Fee Records</h2>
                <p><?php echo count($fees); ?> fee structure(s) found</p>
            </div>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Tuition Fee</th>
                        <th>Admission Fee</th>
                        <th>Exam Fee</th>
                        <th>Other Charges</th>
                        <th>Total Fee</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (count($fees) > 0): ?>

                        <?php foreach ($fees as $fee): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $fee['Class_Name'] . '-' . $fee['Section']
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($fee['Academic_Year']); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format($fee['Tuition_Fee'], 2); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format($fee['Admission_Fee'], 2); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format($fee['Exam_Fee'], 2); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format($fee['Other_Charges'], 2); ?>
                                </td>

                                <td class="amount">
                                    ₹<?php echo number_format($fee['Total_Fee'], 2); ?>
                                </td>

                                <td>

                                    <a
                                        href="edit_fee.php?id=<?php echo $fee['Fee_ID']; ?>"
                                        class="edit-btn"
                                    >
                                        Edit
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="empty-message">
                                No fee structures found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>
