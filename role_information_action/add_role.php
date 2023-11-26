<?php
@include '../config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
}


// Fetch only users with user_type 'user' from the database


if (isset($_POST['add_role'])) {
    $roleName = $_POST["role_name"];
    $accessPermissions = $_POST["access_permissions"];
    $accessPermissions2 = $_POST["employee_access_permissions"];
    $checkRoleQuery = "SELECT role_id FROM user_role WHERE role_name = '$roleName'";
    $result = mysqli_query($conn, $checkRoleQuery);

    if (mysqli_num_rows($result) > 0) {
        // Role with the same name already exists, show an error message
        header("Location: ../role_information_action/add_role.php?message=Role already exists");
        exit; // Stop further execution
    }

    // Insert new role into user_role table
    $insertRoleQuery = "INSERT INTO user_role (role_name) VALUES ('$roleName')";
    mysqli_query($conn, $insertRoleQuery);

    // Get the new role_id
    $newRoleId = mysqli_insert_id($conn);

    // Insert access permissions for each bank into access_permissions table
    foreach ($accessPermissions as $bank => $actions) {
        $canView = isset($actions['canView']) ? 1 : 0;
        $canPost = isset($actions['canPost']) ? 1 : 0;
        $canUpdate = isset($actions['canUpdate']) ? 1 : 0;
        $canDelete = isset($actions['canDelete']) ? 1 : 0;

        $insertAccessQuery = "INSERT INTO access_permissions (bank_name, canView, canPost, canUpdate, canDelete) 
                              VALUES ('$bank', '$canView', '$canPost', '$canUpdate', '$canDelete')";
        mysqli_query($conn, $insertAccessQuery);

        $newAccessId = mysqli_insert_id($conn);

        // Insert the access permissions into role_access_mapping table
        $insertAccessMappingQuery = "INSERT INTO role_access_mapping (role_id, access_id) 
                                    VALUES ('$newRoleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    }

    foreach ($accessPermissions2 as $bank2 => $actions) {
        $canView2 = isset($actions['canView']) ? 1 : 0;
        $canPost2 = isset($actions['canPost']) ? 1 : 0;
        $canUpdate2 = isset($actions['canUpdate']) ? 1 : 0;
        $canDelete2 = isset($actions['canDelete']) ? 1 : 0;
        $canDetails2 = isset($actions['canDetails']) ? 1 : 0;

        $insertAccessQuery = "INSERT INTO employee_access_permissions (permission_name, canView, canPost, canUpdate, canDelete, canDetails) 
        VALUES ('$bank2', '$canView2', '$canPost2', '$canUpdate2', '$canDelete2', '$canDetails2')";
;
        mysqli_query($conn, $insertAccessQuery);

        $newAccessId = mysqli_insert_id($conn);

        // Insert the access permissions into role_access_mapping table
        $insertAccessMappingQuery = "INSERT INTO employee_role_access_mapping (role_id, employee_access_id) 
                                    VALUES ('$newRoleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    }
    // Redirect to the admin page or show a success message
    header("Location: ../main_page/role_information.php?message=Role created successfully");
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


            <?php
                $banks = array("Bank A", "Bank B", "Bank C");
                $features = array("Employee Information", "Role Information")
            ?>

            <div class="add-user-button-container">
                <a href="../main_page/role_information.php" class="add-user-button">Back</a>
            </div>
            
            <div class="add-user-form">
                <h3>Add Role</h3>
                <div class="add-user-button-container">
                

                <form id="addRoleForm" action="" method="post">
                    <input type="text" id="role_name" name="role_name" placeholder="Role name" required><br>
                    <div class="access-permissions">
                        <label for="access_permissions">Access Permissions:</label><br>
                        <br>
                        <table>
                            <thead>
                                <tr>
                                    <th>Bank</th>
                                    <th>View</th>
                                    <th>Post</th>
                                    <th>Update</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banks as $bank) : ?>
                                    <tr>
                                        <td><?php echo $bank; ?></td>
                                        <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canView]" value="1"></td>
                                        <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canPost]" value="1"></td>
                                        <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canUpdate]" value="1"></td>
                                        <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canDelete]" value="1"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="access-permissions">
                        <label for="employee_access_permissions">Feature Permissions:</label><br>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>View</th>
                                    <th>Post</th>
                                    <th>Update</th>
                                    <th>Delete</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($features as $bank) : ?>
                                    <tr>
                                        <td><?php echo $bank; ?></td>
                                        <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank; ?>][canView]" value="1"></td>
                                        <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank; ?>][canPost]" value="1"></td>
                                        <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank; ?>][canUpdate]" value="1"></td>
                                        <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank; ?>][canDelete]" value="1"></td>
                                        <td><input type="checkbox" name="employee_access_permissions[<?php echo $bank; ?>][canDetails]" value="1"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <input type="submit" name="add_role" value="Add Role" class="form-btn">
                </form>
            </div>
        </div>
    </div>
    
    <!-- ====== ionicons ======= -->
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

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html