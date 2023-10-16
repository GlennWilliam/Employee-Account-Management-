<?php
@include 'config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
}

// Check if user ID is provided in the query parameter
if (!isset($_GET['user_id'])) {
    // Redirect to user list or show an error message
    header('location:employeeinformation.php?error=User ID not provided');
    exit(); // Terminate script
}

// Fetch user details based on the provided user ID
$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
$select_user_details = "SELECT * FROM user_information WHERE user_id = '$user_id'";
$result_user_details = mysqli_query($conn, $select_user_details);

// Check if user details were retrieved successfully
if (!$result_user_details || mysqli_num_rows($result_user_details) === 0) {
    // Redirect to user list or show an error message
    header('location:employeeinformation.php?error=User not found');
    exit(); // Terminate script
}

// Fetch the user details as an associative array
$user_details = mysqli_fetch_assoc($result_user_details);

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
    <link rel="stylesheet" href="css/dashboard.css">
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
                        <a href="admin_page.php">
                            <span class="icon">
                                <ion-icon name="home-outline"></ion-icon>
                            </span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>

                    <?php if (in_array('Employee Information', $accessibleFeatures) && in_array('canView', $accessiblePermissions2['Employee Information'])) : ?>
                        <li>
                            <a href="employeeinformation.php">
                                <span class="icon">
                                    <ion-icon name="people-outline"></ion-icon>
                                </span>
                                <span class="title">Employee Information</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array('Role Information', $accessibleFeatures) && in_array('canView', $accessiblePermissions2['Role Information'])) : ?>
                        <li>
                            <a href="roleinformation.php">
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
                                <a href="bankA.php" id="bankALink">
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
                                <a href="bankB.php" id="bankBLink">
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
                                <a href="bankC.php" id="bankCLink">
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
                        <a href="adminprofile.php">
                            <span class="icon">
                                <ion-icon name="information-outline"></ion-icon>
                            </span>
                            <span class="title">Profile</span>
                        </a>
                    </li>

                    <li class="hidden indent" id="admin-password-li">
                        <a href="adminpassword.php">
                            <span class="icon">
                                <ion-icon name="warning-outline"></ion-icon>
                            </span>
                            <span class="title">Password</span>
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
                        <img src="assets/imgs/customer01.jpg" alt="">
                    </div>
                    <div class="dropdown-content">
                        <a href="adminprofile.php">Admin Profile</a>
                        <a href="adminpassword.php">Admin Password</a>
                    </div>
                </div>
            </div>

            <div class="add-user-button-container">
                <a href="employeeinformation.php" class="add-user-button">Back</a>
            </div>

            <div class="container2">
                <h1>User Details</h1>
                <table>
                    <tr>
                        <th>Attribute</th>
                        <th>Value</th>
                    </tr>
                    
                    
                    <tr>
                        <td><strong>First Name:</strong></td>
                        <td><?php echo $user_details['first_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Name:</strong></td>
                        <td><?php echo $user_details['last_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo $user_details['email']; ?></td>
                    </tr>

                    <tr>
                        <td><strong>Password:</strong></td>
                        <td><?php echo str_repeat('*', strlen($user_details['password'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>User Roles:</strong></td>
                        <td>
                            <?php
                            // Fetch assigned roles for the user
                            $assignedRolesQuery = "SELECT ur.role_name FROM user_role_mapping urm JOIN user_role ur ON urm.role_id = ur.role_id WHERE urm.user_id = '{$user_details['user_id']}'";
                            $assignedRolesResult = mysqli_query($conn, $assignedRolesQuery);
                            $assignedRoleNames = [];

                            while ($assignedRole = mysqli_fetch_assoc($assignedRolesResult)) {
                                $assignedRoleNames[] = $assignedRole['role_name'];
                            }

                            echo implode(', ', $assignedRoleNames);
                            ?>
                        </td>
                    </tr>

                </table>
            </div>
    </div>

    
                                    
                         

   

    <!-- =========== Scripts =========  -->
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

    <script>
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown-content');
            const userDropdown = document.querySelector('.user-dropdown');

            if (!userDropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>

    <script>
        const toggleButton = document.querySelector('.toggle');
        const navigation = document.querySelector('.navigation');
        const navList = document.querySelector('.nav-list');

        toggleButton.addEventListener('click', () => {
            navigation.classList.toggle('active-navigation');
            navList.classList.toggle('active-list');
        });

        // Close the navigation when clicking outside of it
        document.addEventListener('click', (event) => {
            if (!navigation.contains(event.target) && !toggleButton.contains(event.target)) {
                navigation.classList.remove('active-navigation');
                body.classList.remove('no-scroll');
            }
        });

    </script>

    

    <script src="assets/js/main.js"></script>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>