<?php
@include 'config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
}

// Fetch only users with user_type 'user' from the database
$select_users = "
SELECT ui.first_name, ui.last_name, ui.email,
    IFNULL(ur.role_name, 'No Role') AS role_name,
    GROUP_CONCAT(DISTINCT p.permission_name SEPARATOR ', ') AS permissions
FROM user_information ui
LEFT JOIN user_role_mapping urm ON ui.user_id = urm.user_id
LEFT JOIN user_role ur ON urm.role_id = ur.role_id
LEFT JOIN role_permission_mapping rpm ON ur.role_id = rpm.role_id
LEFT JOIN permission p ON rpm.permission_id = p.permission_id
WHERE ui.user_type = 'user'
GROUP BY ui.user_id";

$result_users = mysqli_query($conn, $select_users);


if (isset($_POST['assign_role'])) {
    $userId = $_POST["user_first_name"];
    $newRoleId = $_POST["role_id"];

    // Remove existing role mapping for the user
    $deleteMappingQuery = "DELETE FROM user_role_mapping WHERE user_id = '$userId'";
    mysqli_query($conn, $deleteMappingQuery);

    // Insert the new role assignment for the user
    $insertMappingQuery = "INSERT INTO user_role_mapping (user_id, role_id) VALUES ('$userId', '$newRoleId')";
    mysqli_query($conn, $insertMappingQuery);

    // Redirect back to the admin page or show a success message
    header("Location: ManageUser.php?message=Role assigned successfully");
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Admin Dashboard</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <div class="navigation">
            <ul>
                <li>
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="logo-apple"></ion-icon>
                        </span>
                        <span class="title">Logo</span>
                    </a>
                </li>

                <li>
                    <a href="admin_page.php">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="employeeinformation.php">
                        <span class="icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </span>
                        <span class="title">Employee Information</span>
                    </a>
                </li>

                
                <li>
                    <a href="add_user.php">
                        <span class="icon">
                            <ion-icon name="person-add-outline"></ion-icon>
                        </span>
                        <span class="title">Add User</span>
                    </a>
                </li>
                 

                    
                <li>
                    <a href="add_role.php">
                        <span class="icon">
                            <ion-icon name="key-outline"></ion-icon>
                        </span>
                        <span class="title">Edit Role</span>
                    </a>
                </li>

                <li>
                    <a href="add_role2.php">
                        <span class="icon">
                            <ion-icon name="unlink-outline"></ion-icon>
                        </span>
                        <span class="title">Add Role</span>
                    </a>
                </li>
                

                <li>
                    <a href="ManageUser.php">
                        <span class="icon">
                            <ion-icon name="settings-outline"></ion-icon>
                        </span>
                        <span class="title">Manage User</span>
                    </a>
                </li>
                

                <li>
                    <a href="logout.php">
                        <span class="icon">
                            <ion-icon name="log-out-outline"></ion-icon>
                        </span>
                        <span class="title">Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- ========================= Main ==================== -->
        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>

                <div class="search">
                    <label>
                        <input type="text" placeholder="Search here">
                        <ion-icon name="search-outline"></ion-icon>
                    </label>
                </div>

                <div class="user">
                    <img src="assets/imgs/customer01.jpg" alt="">
                </div>
            </div>

    <div class="add-user-form">
        <h3>Assign Role</h3>
        <form action="" method="post">

            <label for="user_first_name">Select User:</label>
            <select id="user_first_name" name="user_first_name" required>
                <?php
                // Fetch available user options from the user_information table with user_type = 'user'
                $userQuery = "SELECT user_id, first_name FROM user_information WHERE user_type = 'user'";
                $result = mysqli_query($conn, $userQuery);

                while ($row = mysqli_fetch_assoc($result)) {
                    $userId = $row['user_id'];
                    $firstName = $row['first_name'];
                    echo "<option value='$userId'>$firstName</option>";
                }
                ?>
            </select><br>

            <label for="role_id">Select Role:</label>
            <select id="role_id" name="role_id" required>
                <?php
                // Fetch available role options from the user_role table
                $roleQuery = "SELECT role_id, role_name FROM user_role";
                $result = mysqli_query($conn, $roleQuery);

                while ($row = mysqli_fetch_assoc($result)) {
                    $roleId = $row['role_id'];
                    $roleName = $row['role_name'];
                    echo "<option value='$roleId'>$roleName</option>";
                }
                ?>
            </select><br>

            <?php if (isset($errorMessage)) : ?>
                <p style="color: red;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>

            <input type="submit" name="assign_role" value="Assign Role" class="form-btn">
        </form>
    </div>


    <!-- =========== Scripts =========  -->
    <script src="assets/js/main.js"></script>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>