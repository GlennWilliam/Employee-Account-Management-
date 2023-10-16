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

// Fetch role information based on the selected role_id
if (isset($_GET['role_id'])) {
    $selectedRoleId = $_GET['role_id'];

    // Fetch role details
    $select_role = "SELECT role_name FROM user_role WHERE role_id = '$selectedRoleId'";
    $result_role = mysqli_query($conn, $select_role);

    if ($row_role = mysqli_fetch_assoc($result_role)) {
        $selectedRoleName = $row_role['role_name'];
    } else {
        // Redirect if the role doesn't exist
        header('location: roleinformation.php');
        exit;
    }
} else {
    // Redirect if no role_id is provided
    header('location: roleinformation.php');
    exit;
}

// Fetch available permissions
$select_permissions = "SELECT access_id, bank_name FROM access_permissions";
$result_permissions = mysqli_query($conn, $select_permissions);
$permissions = array();

while ($row_permission = mysqli_fetch_assoc($result_permissions)) {
    $permissions[$row_permission['access_id']] = $row_permission['bank_name'];
}

if (isset($_POST['update_role'])) {
    $roleId = $_POST["role_id"];
    $accessPermissions = $_POST["access_permissions"];
    $accessPermissions2 = $_POST["employee_access_permissions"];

    // Delete existing role access mappings
    $deleteMappingsQuery = "DELETE FROM role_access_mapping WHERE role_id = '$roleId'";
    mysqli_query($conn, $deleteMappingsQuery);

    $deleteMappingsQuery2 = "DELETE FROM employee_role_access_mapping WHERE role_id = '$roleId'";
    mysqli_query($conn, $deleteMappingsQuery2);

    // Delete existing access permissions
    $deleteAccessQuery = "DELETE FROM access_permissions WHERE access_id IN 
                          (SELECT access_id FROM role_access_mapping WHERE role_id = '$roleId')";
    mysqli_query($conn, $deleteAccessQuery);

    $deleteAccessQuery2 = "DELETE FROM employee_access_permissions WHERE employee_access_id IN 
                          (SELECT employee_access_id FROM employee_role_access_mapping WHERE role_id = '$roleId')";
    mysqli_query($conn, $deleteAccessQuery2);

    foreach ($accessPermissions as $bank => $actions) {
        $canView = isset($actions['canView']) ? 1 : 0;
        $canPost = isset($actions['canPost']) ? 1 : 0;
        $canUpdate = isset($actions['canUpdate']) ? 1 : 0;
        $canDelete = isset($actions['canDelete']) ? 1 : 0;
    
        // Insert access permissions into access_permissions table
        $insertAccessQuery = "INSERT INTO access_permissions (bank_name, canView, canPost, canUpdate, canDelete) 
                              VALUES ('$bank', '$canView', '$canPost', '$canUpdate', '$canDelete')";
        mysqli_query($conn, $insertAccessQuery);
    
        $newAccessId = mysqli_insert_id($conn);
    
        // Insert the access permissions into role_access_mapping table
        $insertAccessMappingQuery = "INSERT INTO role_access_mapping (role_id, access_id) 
                                    VALUES ('$roleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    
        // Insert the access permissions into role_permission_mapping table
        $insertPermissionMappingQuery = "INSERT INTO role_permission_mapping (role_id, permission_id) SELECT '$roleId', p.permission_id FROM permission p WHERE p.permission_name = '$bank'";
        mysqli_query($conn, $insertPermissionMappingQuery);
    }

    foreach ($accessPermissions2 as $bank2 => $actions) {
        $canView2 = isset($actions['canView']) ? 1 : 0;
        $canPost2 = isset($actions['canPost']) ? 1 : 0;
        $canUpdate2 = isset($actions['canUpdate']) ? 1 : 0;
        $canDelete2 = isset($actions['canDelete']) ? 1 : 0;
        $canDetails2 = isset($actions['canDetails']) ? 1 : 0;
    
        // Insert access permissions into access_permissions table
        $insertAccessQuery = "INSERT INTO employee_access_permissions (permission_name, canView, canPost, canUpdate, canDelete, canDetails) 
                              VALUES ('$bank2', '$canView2', '$canPost2', '$canUpdate2', '$canDelete2', $canDetails2)";
        mysqli_query($conn, $insertAccessQuery);
    
        $newAccessId = mysqli_insert_id($conn);
    
        // Insert the access permissions into role_access_mapping table
        $insertAccessMappingQuery = "INSERT INTO employee_role_access_mapping (role_id, employee_access_id) 
                                    VALUES ('$roleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    
        $insertAccessMappingQuery = "INSERT INTO employee_role_access_mapping (role_id, employee_access_id) 
                                    VALUES ('$newRoleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    }
    
    

    // Redirect back to the admin page or show a success message
    header("Location: update_role.php?message=Role updated successfully");
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
            

            
                   

           



            

            <div class="add-user-form">
            <?php
                if (isset($_GET['role_id'])) {
                    $selectedRoleId = $_GET['role_id'];
                    $selectedRoleName = ''; // Initialize selected role name
                    
                    // Retrieve the role name based on the selected role_id
                    $roleNameQuery = "SELECT role_name FROM user_role WHERE role_id = '$selectedRoleId'";
                    $roleNameResult = mysqli_query($conn, $roleNameQuery);
                    if ($roleNameResult && mysqli_num_rows($roleNameResult) > 0) {
                        $row = mysqli_fetch_assoc($roleNameResult);
                        $selectedRoleName = $row['role_name'];
                    }
                }
            ?>
                
                <h3>Edit Role Permissions: <?php echo $selectedRoleName; ?></h3>
                <div class="add-user-button-container">
                    <a href="roleinformation.php" class="add-user-button">Back</a>
                </div>
                <form action="" method="post">
                    <input type="hidden" name="role_id" value="<?php echo $selectedRoleId; ?>">

                    <div class="access-permissions">
                        <form action="" method="post">
                            <label for="access_permissions">Access Permissions:</label>


                            <?php
                            // List of banks (you can replace these with your actual bank names)
                            $banks = array("Bank A", "Bank B", "Bank C");
                            $features = array("Employee Information", "Role Information");

                            // Retrieve existing permissions for the selected role
                            $selectedRoleId = isset($_GET['role_id']) ? $_GET['role_id'] : '';

                            $selectedPermissions = array();
                            if ($selectedRoleId) {
                                $permissionsQuery = "SELECT bank_name, canView, canPost, canUpdate, canDelete FROM access_permissions
                                                    INNER JOIN role_access_mapping ON access_permissions.access_id = role_access_mapping.access_id
                                                    WHERE role_access_mapping.role_id = '$selectedRoleId'";
                            
                                $permissionsResult = mysqli_query($conn, $permissionsQuery);
                                while ($row = mysqli_fetch_assoc($permissionsResult)) {
                                    $selectedPermissions[$row['bank_name']] = array(
                                        'canView' => $row['canView'], // Include canView permission
                                        'canPost' => $row['canPost'],
                                        'canUpdate' => $row['canUpdate'],
                                        'canDelete' => $row['canDelete']
                                    );
                                }
                            }

                            $selectedFeatures = array();
                            if($selectedRoleId){
                                $permissionsQuery = "SELECT permission_name, canView, canPost, canUpdate, canDelete, canDetails FROM employee_access_permissions
                                INNER JOIN employee_role_access_mapping ON employee_access_permissions.employee_access_id = employee_role_access_mapping.employee_access_id
                                WHERE employee_role_access_mapping.role_id = '$selectedRoleId'";

                                $permissionsResult = mysqli_query($conn, $permissionsQuery);
                                while ($row = mysqli_fetch_assoc($permissionsResult)) {
                                    $selectedFeatures[$row['permission_name']] = array(
                                        'canView' => $row['canView'], // Include canView permission
                                        'canPost' => $row['canPost'],
                                        'canUpdate' => $row['canUpdate'],
                                        'canDelete' => $row['canDelete'],
                                        'canDetails' => $row['canDetails']
                                    );
                                }
                            }
                            

                            ?>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Bank</th>
                                        <th>View</th>
                                        <th>Add</th>
                                        <th>Update</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banks as $bank) : ?>
                                        <tr>
                                            <td><?php echo $bank; ?></td>
                                            <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canView]" value="1" <?php if (isset($selectedPermissions[$bank]['canView']) && $selectedPermissions[$bank]['canView'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canPost]" value="1" <?php if (isset($selectedPermissions[$bank]['canPost']) && $selectedPermissions[$bank]['canPost'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canUpdate]" value="1" <?php if (isset($selectedPermissions[$bank]['canUpdate']) && $selectedPermissions[$bank]['canUpdate'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canDelete]" value="1" <?php if (isset($selectedPermissions[$bank]['canDelete']) && $selectedPermissions[$bank]['canDelete'] == 1) echo 'checked'; ?>></td>
                                           

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Features</th>
                                        <th>View</th>
                                        <th>Add</th>
                                        <th>Update</th>
                                        <th>Delete</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($features as $bank2) : ?>
                                        <tr>
                                            <td><?php echo $bank2; ?></td>
                                            <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank2; ?>][canView]" value="1" <?php if (isset($selectedFeatures[$bank2]['canView']) && $selectedFeatures[$bank2]['canView'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank2; ?>][canPost]" value="1" <?php if (isset($selectedFeatures[$bank2]['canPost']) && $selectedFeatures[$bank2]['canPost'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank2; ?>][canUpdate]" value="1" <?php if (isset($selectedFeatures[$bank2]['canUpdate']) && $selectedFeatures[$bank2]['canUpdate'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank2; ?>][canDelete]" value="1" <?php if (isset($selectedFeatures[$bank2]['canDelete']) && $selectedFeatures[$bank2]['canDelete'] == 1) echo 'checked'; ?>></td>
                                            <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank2; ?>][canDetails]" value="1" <?php if (isset($selectedFeatures[$bank2]['canDetails']) && $selectedFeatures[$bank2]['canDetails'] == 1) echo 'checked'; ?>></td>
                                           

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <input type="submit" name="update_role" value="Update Role" class="form-btn">
                        </form>
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