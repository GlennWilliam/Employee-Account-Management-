<?php
@include 'config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
}

function checkUserAccessUpdate($conn, $userId,  $permissionName) {
    $query = "SELECT COUNT(*) FROM employee_access_permissions ap
    INNER JOIN employee_role_access_mapping ram ON ap.employee_access_id = ram.employee_access_id
    INNER JOIN employee_user_role_mapping urm ON ram.role_id = urm.role_id
    WHERE urm.user_id = '$userId'
    AND ap.permission_name = '$permissionName'
    AND ap.canUpdate = 1";

    $result = mysqli_query($conn, $query);

    $count = (mysqli_fetch_row($result)[0]);
    return $count > 0;
}

function checkUserAccessDelete($conn, $userId,  $permissionName) {
    $query = "SELECT COUNT(*) FROM employee_access_permissions ap
    INNER JOIN employee_role_access_mapping ram ON ap.employee_access_id = ram.employee_access_id
    INNER JOIN employee_user_role_mapping urm ON ram.role_id = urm.role_id
    WHERE urm.user_id = '$userId'
    AND ap.permission_name = '$permissionName'
    AND ap.canDelete = 1";

    $result = mysqli_query($conn, $query);

    $count = (mysqli_fetch_row($result)[0]);
    return $count > 0;
}

function checkUserAccessPost($conn, $userId, $permissionName) {
    $query = "SELECT COUNT(*) FROM employee_access_permissions ap
            INNER JOIN employee_role_access_mapping ram ON ap.employee_access_id = ram.employee_access_id
            INNER JOIN employee_user_role_mapping urm ON ram.role_id = urm.role_id
            WHERE urm.user_id = '$userId'
            AND ap.permission_name = '$permissionName'
            AND ap.canPost = 1";

    $result = mysqli_query($conn, $query);

    $count = intval(mysqli_fetch_row($result)[0]);

    return $count > 0;
}


function checkUserAccessDetails($conn, $userId, $permissionName) {
    $query = "SELECT COUNT(*) FROM employee_access_permissions ap
    INNER JOIN employee_role_access_mapping ram ON ap.employee_access_id = ram.employee_access_id
    INNER JOIN employee_user_role_mapping urm ON ram.role_id = urm.role_id
    WHERE urm.user_id = '$userId'
    AND ap.permission_name = '$permissionName'
    AND ap.canDetails = 1";

    $result = mysqli_query($conn, $query);

    $count = (mysqli_fetch_row($result)[0]);
    return $count > 0;
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
                        <a href="adminprofile.php">Profile</a>
                        <a href="adminpassword.php">Password</a>
                    </div>
                </div>
            </div>

            <?php
                $userId = $_SESSION['user_id'];
                $hasUpdateAccess = checkUserAccessUpdate($conn, $userId, 'Role Information'); // Replace $userId with actual user ID
                $hasDeleteAccess = checkUserAccessDelete($conn, $userId, 'Role Information');
                $hasDetailsAccess = checkUserAccessDetails($conn, $userId, 'Role Information');

                $select_roles = "
                SELECT ur.role_id, ur.role_name, GROUP_CONCAT(DISTINCT ap.bank_name ORDER BY ap.bank_name ASC SEPARATOR ', ') AS permission_banks
                FROM user_role ur
                LEFT JOIN role_access_mapping ram ON ur.role_id = ram.role_id
                LEFT JOIN access_permissions ap ON ram.access_id = ap.access_id
                GROUP BY ur.role_id, ur.role_name";

                $result_roles = mysqli_query($conn, $select_roles);

            ?>

            

            <div class="role-list">
            
                
            <h3>Role Information</h3>
            <?php 
            $hasPostAccess = checkUserAccessPost($conn, $userId, 'Role Information');
            
            if ($hasPostAccess) : ?>
                <div class="add-user-button-container">
                    <a href="add_role2.php" class="form2-btn">Add Role</a>
                </div>
            <?php endif; ?>
        
            
            <table>
            <thead>
                <tr>
                
                    <th>Role Name</th>
                    <th>Permissions</th>
                    <th>Features</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch roles and permissions from the database
                $select_roles = "SELECT role_id, role_name FROM user_role";
                $result_roles = mysqli_query($conn, $select_roles);

                while ($row_role = mysqli_fetch_assoc($result_roles)) {
                    echo '<tr>';
                    echo '<td>' . $row_role['role_name'] . '</td>';
                    
                    // Fetch permissions for the current role
                    $role_id = $row_role['role_id'];
                    $select_permissions = "SELECT bank_name FROM access_permissions
                                          INNER JOIN role_access_mapping ON access_permissions.access_id = role_access_mapping.access_id
                                          WHERE role_access_mapping.role_id = '$role_id'";
                    $result_permissions = mysqli_query($conn, $select_permissions);
                    
                    echo '<td>';
                    while ($row_permission = mysqli_fetch_assoc($result_permissions)) {
                        echo $row_permission['bank_name'] . ', ';
                    }
                    echo '</td>';

                    $select_permissions2 = "SELECT permission_name FROM employee_access_permissions
                                          INNER JOIN employee_role_access_mapping ON employee_access_permissions.employee_access_id = employee_role_access_mapping.employee_access_id
                                          WHERE employee_role_access_mapping.role_id = '$role_id'";
                    $result_permissions2 = mysqli_query($conn, $select_permissions2);
                    
                    echo '<td>';
                    while ($row_permission = mysqli_fetch_assoc($result_permissions2)) {
                        echo $row_permission['permission_name'] . ', ';
                    }
                    echo '</td>';
                    
                    echo '<td>';
                        if ($hasDetailsAccess) {
                            echo '<a class="button-link" href="role_details.php?role_id=' . $role_id . '">Details</a>';
                        }
                    echo '</td>';

                    
                    if ($hasDeleteAccess) {
                        echo '<td><a class="button-link" href="delete_role.php?role_id=' . $role_id . '" onclick="return confirm(\'Are you sure you want to delete this role?\')">Delete</a></td>';
                    }
                    
                    
                    if ($hasUpdateAccess) {
                        echo '<td><a class="button-link" href="update_role.php?role_id=' . $role_id . '">Edit</a></td>'; // Add the Update button
                    }
                    
                    
                    
                    
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        </div>
    </div>

            
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const showDeleteRoleFormButton = document.getElementById("showDeleteRoleForm");
            const deleteRoleForm = document.getElementById("deleteRoleForm");

            showDeleteRoleFormButton.addEventListener("click", function () {
                // Toggle the visibility of the "Delete Role" form
                if (deleteRoleForm.style.display === "none") {
                    deleteRoleForm.style.display = "block";
                } else {
                    deleteRoleForm.style.display = "none";
                }
            });
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const showAddRoleFormButton = document.getElementById("showAddRoleForm");
            const addRoleForm = document.getElementById("addRoleForm");

            showAddRoleFormButton.addEventListener("click", function () {
                // Toggle the visibility of the "Add Role" form
                if (addRoleForm.style.display === "none") {
                    addRoleForm.style.display = "block";
                } else {
                    addRoleForm.style.display = "none";
                }
            });
        });
    </script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const roleDropdown = document.getElementById("role_id");
        
        // Function to reload the page with the selected role_id parameter
        function reloadPageWithRole(roleId) {
            const currentUrl = window.location.href;
            const updatedUrl = currentUrl.replace(/[\?&]role_id=[^&#]+/, "");
            const newUrl = updatedUrl + (updatedUrl.includes("?") ? "&" : "?") + "role_id=" + roleId;
            window.location.href = newUrl;
        }

        // Listen for role selection change
        roleDropdown.addEventListener("change", function () {
            const selectedRoleId = roleDropdown.value;
            
            // Check if a valid role is selected before reloading
            if (selectedRoleId !== "") {
                reloadPageWithRole(selectedRoleId);
            }
        });

        // On page load, select the "Select Role" option and reload only if not already on that option
        if (!roleDropdown.value && roleDropdown.options.length > 0) {
            if (window.location.search.indexOf("role_id=") === -1) {
                reloadPageWithRole(""); // Empty value to display initial "Select Role" option
            }
        }
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



    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>