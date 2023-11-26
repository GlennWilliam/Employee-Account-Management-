<?php
@include '../config.php';

session_start();

function checkUserAccessUpdate($conn, $userId,  $permissionName) {
    $query = "SELECT COUNT(*) FROM employee_access_permissions ap
              INNER JOIN employee_role_access_mapping ram ON ap.employee_access_id = ram.employee_access_id
              INNER JOIN employee_user_role_mapping urm ON ram.role_id = urm.role_id
              WHERE urm.user_id = '$userId'
              AND ap.permission_name = '$permissionName'
              AND ap.canUpdate = 1";

    $result = mysqli_query($conn, $query);
    $count = mysqli_fetch_row($result)[0];

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
    $count = mysqli_fetch_row($result)[0];

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

    $count = mysqli_fetch_row($result)[0];

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

    $count = mysqli_fetch_row($result)[0];

    return $count > 0;
}


// Fetch only users with user_type 'user' from the database
$select_users = "
SELECT ui.user_id, ui.first_name, ui.last_name, ui.email,
    GROUP_CONCAT(DISTINCT ur.role_name ORDER BY ur.role_name ASC SEPARATOR ', ') AS role_names
FROM user_information ui
LEFT JOIN user_role_mapping urm ON ui.user_id = urm.user_id
LEFT JOIN user_role ur ON urm.role_id = ur.role_id
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


            
            <?php $userId = $_SESSION['user_id']; ?>

            <?php 
                $a = 0;
                $accessTypes = array('Post', 'Update', 'Delete', 'Details');

                foreach ($accessTypes as $accessType) {
                    $hasAccess = call_user_func('checkUserAccess' . $accessType, $conn, $userId, 'Employee Information');
                    if ($hasAccess) {
                        $a++;
                    }
                }
            ?>

            <?php
                $hasUpdateAccess = checkUserAccessUpdate($conn, $userId, 'Employee Information'); 
                $hasDeleteAccess = checkUserAccessDelete($conn, $userId, 'Employee Information');
                $hasDetailsAccess = checkUserAccessDetails($conn, $userId, 'Employee Information');

                $select_users = "
                SELECT ui.user_id, ui.first_name, ui.last_name, ui.email,
                    GROUP_CONCAT(DISTINCT ur.role_name ORDER BY ur.role_name ASC SEPARATOR ', ') AS role_names
                FROM user_information ui
                LEFT JOIN user_role_mapping urm ON ui.user_id = urm.user_id
                LEFT JOIN user_role ur ON urm.role_id = ur.role_id
                GROUP BY ui.user_id";


                $result_users = mysqli_query($conn, $select_users);

            ?>


        <div class="user-list">
            <h3>Employee Information</h3>
            <?php 
            $hasPostAccess = checkUserAccessPost($conn, $userId, 'Employee Information');
            
            if ($hasPostAccess) : ?>
                <div class="add-user-button-container">
                    <a href="../employee_information_action/add_user.php" class="form2-btn">Add User</a>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <?php
                        // Define an array of action labels and permissions
                        $actions = array(
                            'Details' => $hasUpdateAccess,
                            'Delete' => $hasDeleteAccess,
                            'Edit' => $hasUpdateAccess
                        );

                        // Filter out actions that the user has access to
                        $accessibleActions = array_filter($actions);

                        // Calculate the colspan for actions
                        $a = count($accessibleActions);

                        // Output the colspan attribute
                        echo '<th colspan="' . $a . '" style="text-align: center;">Actions</th>';
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Initialize a counter
                    $counter = 1;
                    $userId = $_SESSION['user_id'];

                    while ($row = mysqli_fetch_array($result_users)) : ?>
                        <tr>
                            <td><?php echo $counter; ?></td>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>

                            <?php foreach ($accessibleActions as $actionLabel => $hasAccess) : ?>
                                <td>
                                    <?php if ($actionLabel === 'Delete') : ?>
                                        <form action="../employee_information_action/delete_user.php" method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <button type="submit" class="edit-user-btn3" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                        </form>
                                    <?php elseif ($actionLabel === 'Edit') : ?>
                                        <button class="edit-user-btn" data-userid="<?php echo $row['user_id']; ?>">Edit</button>
                                    <?php else : ?>
                                        <a href="../employee_information_action/edit_user.php?user_id=<?php echo $row['user_id']; ?>" class="details-btn"><?php echo $actionLabel; ?></a>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <?php 
                            // Increment the counter
                            $counter++;
                            ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>


    </div>

    <!-- =========== Scripts =========  -->

    
   
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-user-btn');

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const userId = button.getAttribute('data-userid');
                    // Modify the URL by adding the user ID as a query parameter
                    const newUrl = `../employee_information_action/edit_user.php?user_id=${userId}`;
                    window.location.href = newUrl;
                });
            });
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