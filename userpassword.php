<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('location: login.php'); // Redirect to login page
    exit();
}

@include 'config.php';

$error = array();

$select_access = "
SELECT ap.bank_name
    FROM user_information ui
    JOIN user_role_mapping urm ON ui.user_id = urm.user_id
    JOIN role_access_mapping ram ON urm.role_id = ram.role_id
    JOIN access_permissions ap ON ram.access_id = ap.access_id
    WHERE ui.first_name = ?";
    
$stmt = mysqli_prepare($conn, $select_access);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_name']);
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
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_name']);
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

if (isset($_POST['submit'])) {
    $currentPassword = md5($_POST['current_password']);
    $newPassword = md5($_POST['new_password']);
    $confirmNewPassword = md5($_POST['confirm_new_password']);

    $userId = $_SESSION['user_id'];

    // Fetch the admin's current password from the database
    $selectQuery = "SELECT password FROM user_information WHERE user_id = '$userId'";
    $result = mysqli_query($conn, $selectQuery);
    $row = mysqli_fetch_assoc($result);

    if ($currentPassword === $row['password'] && $newPassword === $confirmNewPassword) {
        // Update the password in the database
        $updateQuery = "UPDATE user_information SET password = '$newPassword' WHERE user_id = '$userId'";
        mysqli_query($conn, $updateQuery);

        // Redirect to admin page
        header('location: userpassword.php');
        exit();
    } else {
        $error[] = 'Passwords do not match or current password is incorrect.';
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
                        <a href="user_page.php">
                            <span class="icon">
                                <ion-icon name="home-outline"></ion-icon>
                            </span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>

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
                        <a href="userprofile.php">
                            <span class="icon">
                                <ion-icon name="information-outline"></ion-icon>
                            </span>
                            <span class="title">User Profile</span>
                        </a>
                    </li>

                    <li class="hidden indent" id="admin-password-li">
                        <a href="userpassword.php">
                            <span class="icon">
                                <ion-icon name="warning-outline"></ion-icon>
                            </span>
                            <span class="title">User Password</span>
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
                        <a href="userprofile.php">Profile</a>
                        <a href="userpassword.php">Password</a>
                    </div>
                </div>
            </div>

        
            <div class="form-container">
                <form action="" method="post">
                    <h3>Change Password</h3>
                    <?php
                    if (!empty($error)) {
                        foreach ($error as $err) {
                            echo '<span class="error-msg">' . $err . '</span>';
                        }
                    }
                    ?>
                    <input type="password" name="current_password" required placeholder="Current Password">
                    <input type="password" name="new_password" required placeholder="New Password">
                    <input type="password" name="confirm_new_password" required placeholder="Confirm New Password">
                    <input type="submit" name="submit" value="Change Password" class="form-btn">
                </form>
            </div>











    <!-- Include your navigation and topbar code here -->

    <!-- Include your scripts here -->
     <!-- ====== ionicons ======= -->
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
    
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>

</body>

</html>
