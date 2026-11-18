
<?php
$pageTitle = $pageTitle ?? "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?> | SchoolFee</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="app-container">

    <?php include "includes/sidebar.php"; ?>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p>Welcome to your school administration panel.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>School Admin</small>
                </div>
            </div>
        </header>
