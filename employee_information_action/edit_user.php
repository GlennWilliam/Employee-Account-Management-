<?php
// Include your config.php and start session
@include '../config.php';
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['name'])) {
    header('location:../login_page/login_form.php');
    exit(); // Terminate script
}

// Check if user ID is provided in the URL using the GET method
if (!isset($_GET['user_id'])) {
    // Redirect to user list or show an error message
    header('location:../main_page/employee_information.php?error=User not found');
    exit(); // Terminate script
}

// Fetch user details based on the provided user ID
$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
$select_user_query = "SELECT * FROM user_information WHERE user_id = '$user_id'";
$result_user = mysqli_query($conn, $select_user_query);
$user_data = mysqli_fetch_assoc($result_user);

// Check if user details were retrieved successfully
if (!$user_data) {
    // Redirect to user list or show an error message
    header('location:../main_page/employee_information.php?error=User not found');
    exit(); // Terminate script
}

// Fetch only users with user_type 'user' from the database
$select_users = "
SELECT ui.user_id, ui.first_name, ui.last_name, ui.email,
    GROUP_CONCAT(DISTINCT ur.role_name ORDER BY ur.role_name ASC SEPARATOR ', ') AS role_names
FROM user_information ui
LEFT JOIN user_role_mapping urm ON ui.user_id = urm.user_id
LEFT JOIN user_role ur ON urm.role_id = ur.role_id
WHERE ui.user_type = 'user'
GROUP BY ui.user_id";

$result_users = mysqli_query($conn, $select_users);

if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $updated_first_name = mysqli_real_escape_string($conn, $_POST['updated_first_name']);
    $updated_last_name = mysqli_real_escape_string($conn, $_POST['updated_last_name']);
    $updated_email = mysqli_real_escape_string($conn, $_POST['updated_email']);
    $userInputPassword = $_POST['current_password']; // User's input current password

    // Hash the user input password using MD5
    $hashedUserInputPassword = md5($userInputPassword);

    // Retrieve the hashed password from the database based on the user's ID
    $selectPasswordQuery = "SELECT password FROM user_information WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $selectPasswordQuery);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $hashedPasswordFromDB = $row['password'];

        // Compare the hashed passwords
        if ($hashedUserInputPassword === $hashedPasswordFromDB) {
            // Passwords match, user is authenticated
            // Proceed with updating the user information

            // Check if updated_password is provided
            if (!empty($_POST['updated_password'])) {
                $updated_password = md5($_POST['updated_password']); // Hash the new password using MD5
                $update_query = "UPDATE user_information SET first_name = '$updated_first_name', last_name = '$updated_last_name', email = '$updated_email', password = '$updated_password' WHERE user_id = '$user_id'";
            } else {
                // If updated_password is not provided, update without changing the password
                $update_query = "UPDATE user_information SET first_name = '$updated_first_name', last_name = '$updated_last_name', email = '$updated_email' WHERE user_id = '$user_id'";
            }

            mysqli_query($conn, $update_query);

           

            if (mysqli_affected_rows($conn) > 0) {
                $success_message = "User updated successfully";
                header("Location: ../main_page/employee_information.php");
            } else {
                $error_message = "Error updating user.";
            }
        } else {
            // Passwords do not match, authentication failed
            $error_message = "Incorrect password. Update failed.";
        }
    } else {
        // An error occurred while querying the database
        $error_message = "Database error.";
    }
  
}

if (isset($_POST['assign_role'])) {
    $userId = $_POST['user_id'];
    $roleIds = $_POST["role_id"];

    // Remove existing role mappings for the user
    $deleteMappingQuery = "DELETE FROM user_role_mapping WHERE user_id = '$userId'";
    mysqli_query($conn, $deleteMappingQuery);

    $deleteMappingQuery2 = "DELETE FROM employee_user_role_mapping WHERE user_id = '$userId'";
    mysqli_query($conn, $deleteMappingQuery2);

    // Insert the new role assignments for the user
    foreach ($roleIds as $roleId) {
        $insertMappingQuery = "INSERT INTO user_role_mapping (user_id, role_id) VALUES ('$userId', '$roleId')";
        mysqli_query($conn, $insertMappingQuery);

        $insertMappingQuery2 = "INSERT INTO employee_user_role_mapping (user_id, role_id) VALUES ('$userId', '$roleId')";
        mysqli_query($conn, $insertMappingQuery2);
    }

    // Redirect back to the edit user page or show a success message
    header("Location: ../main_page/employee_information.php?user_id=$userId&message=Roles assigned successfully");
    exit();
}


$select_access = "
SELECT ap.bank_name
    FROM user_information ui
    JOIN user_role_mapping urm ON ui.user_id = urm.user_id
    JOIN role_access_mapping ram ON urm.role_id = ram.role_id
    JOIN access_permissions ap ON ram.access_id = ap.access_id
    WHERE ui.first_name = ?";
    
$stmt = mysqli_prepare($conn, $select_access);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['name']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Store the accessible bank names in an array
$accessibleBanks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accessibleBanks[] = $row['bank_name'];
}

// Fetch accessible permissions for each bank from the database
$accessiblePermissions = [];
$select_permissions = "
SELECT ap.bank_name, ap.canView
    FROM user_information ui
    JOIN user_role_mapping urm ON ui.user_id = urm.user_id
    JOIN role_access_mapping ram ON urm.role_id = ram.role_id
    JOIN access_permissions ap ON ram.access_id = ap.access_id
    WHERE ui.first_name = ?";

$stmt = mysqli_prepare($conn, $select_permissions);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['name']);
mysqli_stmt_execute($stmt);
$result_permissions = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result_permissions)) {
    $bankName = $row['bank_name'];
    $canView = $row['canView'];

    // Build the accessible permissions array
    if (!isset($accessiblePermissions[$bankName])) {
        $accessiblePermissions[$bankName] = [];
    }

    if ($canView) {
        $accessiblePermissions[$bankName][] = 'canView';
    }
}

$select_access2 = "
SELECT ap.permission_name
    FROM user_information ui
    JOIN employee_user_role_mapping urm ON ui.user_id = urm.user_id
    JOIN employee_role_access_mapping ram ON urm.role_id = ram.role_id
    JOIN employee_access_permissions ap ON ram.employee_access_id = ap.employee_access_id
    WHERE ui.first_name = ?";

$stmt2 = mysqli_prepare($conn, $select_access2);
mysqli_stmt_bind_param($stmt2, "s", $_SESSION['name']);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

$accessibleFeatures = []; // Initialize an empty array to store retrieved information

while ($row = mysqli_fetch_assoc($result2)) {
    // Add each retrieved permission_name to the $accessibleFeatures array
    $accessibleFeatures[] = $row['permission_name'];
}

// Fetch accessible permissions for each bank from the database
$accessiblePermissions2 = [];
$select_permissions2 = "
SELECT ap.permission_name, ap.canView
    FROM user_information ui
    JOIN employee_user_role_mapping urm ON ui.user_id = urm.user_id
    JOIN employee_role_access_mapping ram ON urm.role_id = ram.role_id
    JOIN employee_access_permissions ap ON ram.employee_access_id = ap.employee_access_id
    WHERE ui.first_name = ?";

$stmt2 = mysqli_prepare($conn, $select_permissions2);
mysqli_stmt_bind_param($stmt2, "s", $_SESSION['name']);
mysqli_stmt_execute($stmt2);
$result_permissions2 = mysqli_stmt_get_result($stmt2);

while ($row = mysqli_fetch_assoc($result_permissions2)) {
    $bankName = $row['permission_name'];
    $canView = $row['canView'];

    // Build the accessible permissions array
    if (!isset($accessiblePermissions2[$bankName])) {
        $accessiblePermissions2[$bankName] = [];
    }

    if ($canView) {
        $accessiblePermissions2[$bankName][] = 'canView';
    }
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
    <link rel="stylesheet" href="../style/style.css">
</head>

<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <div class="navigation" id="navigation-container">
            <div class="nav-container">
                <ul class="nav-list">
                    <li>
                        <a href="#">
                            <span class="icon">
                                <ion-icon name="logo-apple"></ion-icon>
                            </span>
                            <span class="title">Logo</span>
                        </a>
                    </li>

                    <li>
                        <a href="../main_page/dashboard.php">
                            <span class="icon">
                                <ion-icon name="home-outline"></ion-icon>
                            </span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>

                    <?php if (in_array('Employee Information', $accessibleFeatures) && in_array('canView', $accessiblePermissions2['Employee Information'])) : ?>
                        <li>
                            <a href="../main_page/employee_information.php">
                                <span class="icon">
                                    <ion-icon name="people-circle-outline"></ion-icon>
                                </span>
                                <span class="title">Employee Information</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array('Role Information', $accessibleFeatures) && in_array('canView', $accessiblePermissions2['Role Information'])) : ?>
                        <li>
                            <a href="../main_page/role_information.php">
                                <span class="icon">
                                    <ion-icon name="people-outline"></ion-icon>
                                </span>
                                <span class="title">Role Information</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Display navigation link for Bank A if user has access and canView -->
                        <?php if (in_array('Bank A', $accessibleBanks) && in_array('canView', $accessiblePermissions['Bank A'])) : ?>
                            <li>
                                <a href="../bank/bankA.php" id="bankALink">
                                    <span class="icon">
                                        <ion-icon name="server-outline"></ion-icon>
                                    </span>
                                    <span class="title">Bank A</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Display navigation link for Bank B if user has access and canView -->
                        <?php if (in_array('Bank B', $accessibleBanks) && in_array('canView', $accessiblePermissions['Bank B'])) : ?>
                            <li>
                                <a href="../bank/bankB.php" id="bankBLink">
                                    <span class="icon">
                                        <ion-icon name="server-outline"></ion-icon>
                                    </span>
                                    <span class="title">Bank B</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Display navigation link for Bank C if user has access and canView -->
                        <?php if (in_array('Bank C', $accessibleBanks) && in_array('canView', $accessiblePermissions['Bank C'])) : ?>
                            <li>
                                <a href="../bank/bankC.php" id="bankCLink">
                                    <span class="icon">
                                        <ion-icon name="server-outline"></ion-icon>
                                    </span>
                                    <span class="title">Bank C</span>
                                </a>
                            </li>
                        <?php endif; ?>

                    <li>
                        <a href="#" class="menu-link" data-target="profile">
                            <span class="icon">
                                <ion-icon name="person-circle-outline"></ion-icon>
                            </span>
                            <span class="title">Profile</span>
                        </a>
                    </li>

                    <li class="hidden indent" id="admin-profile-li">
                        <a href="../profile/user_profile.php">
                            <span class="icon">
                                <ion-icon name="information-outline"></ion-icon>
                            </span>
                            <span class="title">Profile</span>
                        </a>
                    </li>

                    <li class="hidden indent" id="admin-password-li">
                        <a href="../profile/user_password.php">
                            <span class="icon">
                                <ion-icon name="warning-outline"></ion-icon>
                            </span>
                            <span class="title">Password</span>
                        </a>
                    </li>


                    <li>
                        <a href="../loginpage/logout.php">
                            <span class="icon">
                                <ion-icon name="log-out-outline"></ion-icon>
                            </span>
                            <span class="title">Sign Out</span>
                        </a>
                    </li>
                </ul>
            </div>
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

                <div class="user-dropdown">
                    <div class="user">
                        <img src="../assets/customer01.jpg" alt="">
                    </div>
                    <div class="dropdown-content" style="display: none;">
                        <a href="../profile/user_profile.php">Profile</a>
                        <a href="../profile/user_password.php">Password</a>
                    </div>
                </div>
            </div>


            <div class="add-user-button-container">
                <a href="../main_page/employee_information.php" class="add-user-button">Back</a>
            </div>

            <div class="container2">
                <h3>Employee Information</h3>

                <div class="assign-roles-section">
                <h3>Assign Roles</h3>
                <form action="" method="post" class="assign-roles-form">
                    <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">
                    <?php
                    // Fetch available role options from the user_role table
                    $roleQuery = "SELECT role_id, role_name FROM user_role";
                    $roleResult = mysqli_query($conn, $roleQuery);

                    // Get the roles assigned to the user
                    $assignedRoles = []; // Array to store assigned role IDs
                    $assignedRolesQuery = "SELECT role_id FROM user_role_mapping WHERE user_id = '{$user_data['user_id']}'";
                    $assignedRolesResult = mysqli_query($conn, $assignedRolesQuery);
                    while ($assignedRole = mysqli_fetch_assoc($assignedRolesResult)) {
                        $assignedRoles[] = $assignedRole['role_id'];
                    }

                    while ($rrow = mysqli_fetch_assoc($roleResult)) {
                        $roleId = $rrow['role_id'];
                        $roleName = $rrow['role_name'];
                        $isChecked = in_array($roleId, $assignedRoles) ? 'checked' : ''; // Check if the role is assigned

                        echo '<div>';
                        echo '<label>';
                        echo "<input type='checkbox' name='role_id[]' value='$roleId' $isChecked> $roleName";
                        echo '</label>';
                        echo '</div>';
                    }
                    ?>

                    <?php if (isset($errorMessage)) : ?>
                        <p class="error-message"><?php echo $errorMessage; ?></p>
                    <?php endif; ?>

                    <input type="submit" name="assign_role" value="Assign Roles" class="form-btn">
                </form>
            </div>

                <tr class="edit-user-form-row" id="edit-form-row-<?php echo $user_data['user_id']; ?>">
                    <!-- Edit user form -->
                    <div class="edit-user-form">
                        <h3>Edit User</h3>
                        <form action="" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">
                            <table class="edit-form-table">
                                <tr>
                                    <th>Attribute</th>
                                    <th>Value</th>
                                </tr>

                                <tr>
                                    <td><strong>First Name:</strong></td>
                                    <td><input type="text" name="updated_first_name" placeholder="First Name" required value="<?php echo $user_data['first_name']; ?>">
                                </tr>
                                <tr>
                                    <td><strong>Last Name:</strong></td>
                                    <td><input type="text" name="updated_last_name" placeholder="Last Name" required value="<?php echo $user_data['last_name']; ?>">
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><input type="email" name="updated_email" placeholder="Email" required value="<?php echo $user_data['email']; ?>">
                                </tr>

                                <tr>
                                    <td><strong>Password:</strong></td>
                                    <td><input type="password" name="current_password" placeholder="Current Password" required>
                                </tr>

                                <tr>
                                    <td><strong>New Password:</strong></td>
                                    <td><input type="password" name="updated_password" placeholder="New Password (Optional)">
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><input type="submit" name="edit_user" value="Edit User" class="form-btn">
                                </tr>
                            </table>
                        </form>
                    </div>
                </tr>
            </div> 
        </div>
    </div>

    <!-- =========== Scripts =========  -->

    <script>
        const profileLink = document.querySelector('.menu-link[data-target="profile"]');
        const adminProfileLi = document.getElementById('admin-profile-li');
        const adminPasswordLi = document.getElementById('admin-password-li');

        let linksVisible = false; // Keep track of the links' visibility state

        profileLink.addEventListener('click', () => {
            if (linksVisible) {
                adminProfileLi.classList.add('hidden');
                adminPasswordLi.classList.add('hidden');
            } else {
                adminProfileLi.classList.remove('hidden');
                adminPasswordLi.classList.remove('hidden');
            }

            linksVisible = !linksVisible; // Toggle the visibility state
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userTable = document.querySelector('table');

            userTable.addEventListener('click', function (event) {
                if (event.target.classList.contains('edit-user-btn')) {
                    const button = event.target;
                    const userId = button.getAttribute('data-userid');

                    // Toggle the visibility of the corresponding edit form
                    const editFormRow = document.getElementById('edit-form-row-' + userId);
                    if (editFormRow) {
                        editFormRow.classList.toggle('hidden');
                    }

                    // Hide the corresponding details row (if visible)
                    const detailsRow = document.querySelector('.details-row-' + userId);
                    if (detailsRow) {
                        detailsRow.classList.add('hidden');
                    }
                } else if (event.target.classList.contains('details-btn')) {
                    const button = event.target;
                    const userId = button.getAttribute('data-userid');

                    // Toggle the visibility of the corresponding details row
                    const detailsRow = document.querySelector('.details-row-' + userId);
                    if (detailsRow) {
                        detailsRow.classList.toggle('hidden');
                    }

                    // Hide the corresponding edit form (if visible)
                    const editFormRow = document.getElementById('edit-form-row-' + userId);
                    if (editFormRow) {
                        editFormRow.classList.add('hidden');
                    }
                }
            });
        });
    </script>

    <script>
        const profileLink = document.querySelector('.menu-link[data-target="profile"]');
        const adminProfileLi = document.getElementById('admin-profile-li');
        const adminPasswordLi = document.getElementById('admin-password-li');

        let linksVisible = false; // Keep track of the links' visibility state

        profileLink.addEventListener('click', () => {
            if (linksVisible) {
                adminProfileLi.classList.add('hidden');
                adminPasswordLi.classList.add('hidden');
            } else {
                adminProfileLi.classList.remove('hidden');
                adminPasswordLi.classList.remove('hidden');
            }

            linksVisible = !linksVisible; // Toggle the visibility state
        });
    </script>
    
    <script>
        const dropdown = document.querySelector('.dropdown-content');
        const userDropdown = document.querySelector('.user-dropdown');

        // Function to show the dropdown
        function showDropdown() {
            dropdown.style.display = 'block';
        }

        // Function to hide the dropdown
        function hideDropdown() {
            dropdown.style.display = 'none';
        }

        // Event listener to show the dropdown on mouseover
        userDropdown.addEventListener('mouseover', showDropdown);

        // Event listener to hide the dropdown when the cursor leaves the icon area
        userDropdown.addEventListener('mouseleave', hideDropdown);

        // Event listener to hide the dropdown when clicking outside
        document.addEventListener('click', function (event) {
            if (!userDropdown.contains(event.target)) {
                hideDropdown();
            }
        });
    </script>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>