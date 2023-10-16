<?php
@include 'config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
}




if (isset($_POST['add_role'])) {
    $roleName = $_POST["role_name"];
    $accessPermissions = $_POST["access_permissions"];
    $accessPermissions2 = $_POST["employee_access_permissions"];

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

    foreach ($accessPermissions2 as $bank => $actions) {
        $canView = isset($actions['canView']) ? 1 : 0;
        $canPost = isset($actions['canPost']) ? 1 : 0;
        $canUpdate = isset($actions['canUpdate']) ? 1 : 0;
        $canDelete = isset($actions['canDelete']) ? 1 : 0;
        $canDetails = isset($actions['canDetails']) ? 1 : 0;

        $insertAccessQuery = "INSERT INTO access_permissions (permission_name, canView, canPost, canUpdate, canDelete, canDetails) 
                              VALUES ('$bank', '$canView', '$canPost', '$canUpdate', '$canDelete', '$canDetails)";
        mysqli_query($conn, $insertAccessQuery);

        $newAccessId = mysqli_insert_id($conn);

        // Insert the access permissions into role_access_mapping table
        $insertAccessMappingQuery = "INSERT INTO role_access_mapping (role_id, access_id) 
                                    VALUES ('$newRoleId', '$newAccessId')";
        mysqli_query($conn, $insertAccessMappingQuery);
    }
    // Redirect to the admin page or show a success message
    header("Location: add_role.php?message=Role created successfully");
}

if (isset($_POST['update_role'])) {
    $roleId = $_POST["role_id"];
    $accessPermissions = $_POST["access_permissions"];

    // Delete existing role access mappings
    $deleteMappingsQuery = "DELETE FROM role_access_mapping WHERE role_id = '$roleId'";
    mysqli_query($conn, $deleteMappingsQuery);

    // Delete existing access permissions
    $deleteAccessQuery = "DELETE FROM access_permissions WHERE access_id IN 
                          (SELECT access_id FROM role_access_mapping WHERE role_id = '$roleId')";
    mysqli_query($conn, $deleteAccessQuery);

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
    

    // Redirect back to the admin page or show a success message
    header("Location: add_role.php?message=Role updated successfully");
}

// Check if a role deletion request is submitted
if (isset($_GET['delete_role']) && isset($_GET['role_id'])) {
    $roleToDelete = $_GET['role_id'];

    // Delete role from user_role table
    $deleteRoleQuery = "DELETE FROM user_role WHERE role_id = '$roleToDelete'";
    mysqli_query($conn, $deleteRoleQuery);

    // Delete corresponding role_access_mapping and access_permissions entries
    $deleteMappingQuery = "DELETE FROM role_access_mapping WHERE role_id = '$roleToDelete'";
    mysqli_query($conn, $deleteMappingQuery);

    $deletePermissionsQuery = "DELETE FROM access_permissions WHERE access_id IN 
                              (SELECT access_id FROM role_access_mapping WHERE role_id = '$roleToDelete')";
    mysqli_query($conn, $deletePermissionsQuery);

    // Redirect back to the admin page or show a success message
    header("Location: add_role.php?message=Role deleted successfully");
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
        <div class="navigation" id="navigation">
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

                <li>
                    <a href="employeeinformation.php">
                        <span class="icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </span>
                        <span class="title">Employee Information</span>
                    </a>
                </li>

                 

                    
                <li>
                    <a href="roleinformation.php">
                        <span class="icon">
                            <ion-icon name="key-outline"></ion-icon>
                        </span>
                        <span class="title">Role Information</span>
                    </a>
                </li>

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
                        <span class="title">Admin Profile</span>
                    </a>
                </li>

                <li class="hidden indent" id="admin-password-li">
                    <a href="adminpassword.php">
                        <span class="icon">
                            <ion-icon name="warning-outline"></ion-icon>
                        </span>
                        <span class="title">Admin Password</span>
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
                <div class="add-user-button-container">
                    <a href="add_role2.php" class="form2-btn">Add Role</a>
                </div>
                   

            <div class="delete-role-form" style="display: none;">
                <h3>Delete Role</h3>
                <button id="showDeleteRoleForm">Show Delete Role Form</button>
                <form id="deleteRoleForm" action="" method="get">
                    <input type="hidden" name="delete_role" value="1">
                    <label for="role_to_delete">Select Role:</label>
                    <select id="role_to_delete" name="role_id" required>
                        <option value="" disabled selected>Select Role</option>
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
                    <input type="submit" value="Delete Role" class="form-btn">
                </form>
            </div>

           





            <div class="add-user-form">
                <h3>Edit Role Permissions</h3>
                <form action="" method="post">
                    <!-- Select Role Dropdown -->
                    <label for="role_id">Select Role:</label>
                    <select id="role_id" name="role_id" required>
                        <option value="" disabled selected>Select Role</option>
                        <?php
                        // Fetch available role options from the user_role table
                        $roleQuery = "SELECT role_id, role_name FROM user_role";
                        $result = mysqli_query($conn, $roleQuery);

                        $selectedRoleId = isset($_GET['role_id']) ? $_GET['role_id'] : ''; // Get role_id from URL parameter
                        $selectedRoleName = ''; // Initialize selected role name

                        while ($row = mysqli_fetch_assoc($result)) {
                            $roleId = $row['role_id'];
                            $roleName = $row['role_name'];
                            if ($roleId === $selectedRoleId) {
                                $selectedRoleName = $roleName; // Set selected role name
                                echo "<option value='$roleId' selected>$roleName</option>";
                            } else {
                                echo "<option value='$roleId'>$roleName</option>";
                            }
                        }
                        ?>
                    </select><br>

                    <div class="access-permissions">
                        <form action="" method="post">
                            <label for="access_permissions">Access Permissions:</label>


                            <?php
                            // List of banks (you can replace these with your actual bank names)
                            $banks = array("Bank A", "Bank B", "Bank C");

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
                            <input type="submit" name="update_role" value="Update Role" class="form-btn">
                        </form>
                    </div>

                    <div class="access-permissions">
                        <form action="" method="post">
                            <label for="access_permissions2">Access Permissions:</label>


                            <?php
                            // List of banks (you can replace these with your actual bank names)
                            $banks = array("Employee Information", "Role Information");

                            // Retrieve existing permissions for the selected role
                            $selectedRoleId = isset($_GET['role_id']) ? $_GET['role_id'] : '';

                            $selectedPermissions = array();
                            if ($selectedRoleId) {
                                $permissionsQuery = "SELECT permission_name, canView, canPost, canUpdate, canDelete, canDetails FROM employee_access_permissions
                                                    INNER JOIN employee_role_access_mapping ON employee_access_permissions.employee_access_id = employee_role_access_mapping.employee_access_id
                                                    WHERE employee_role_access_mapping.role_id = '$selectedRoleId'";
                                
                                
                            
                                $permissionsResult = mysqli_query($conn, $permissionsQuery);
                                while ($row = mysqli_fetch_assoc($permissionsResult)) {
                                    $selectedPermissions[$row['permission_name']] = array(
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
                                        <th>Feature</th>
                                        <th>View</th>
                                        <th>Add</th>
                                        <th>Update</th>
                                        <th>Delete</th>
                                        <th>Details</th>
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
                                            <td><input type="checkbox" name="access_permissions[<?php echo $bank; ?>][canDetails]" value="1" <?php if (isset($selectedPermissions[$bank]['canDetails']) && $selectedPermissions[$bank]['canDetails'] == 1) echo 'checked'; ?>></td>
                                           

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