<?php

@include '../config.php';

session_start();

if(!isset($_SESSION['name'])){
   header('location:login_form.php');
}


// Check if the user's role is set in the session
$error_message = '';
$success_message = '';


function checkUserAccessUpdate($conn, $userId,  $bankName) {
    $query = "SELECT COUNT(*) FROM access_permissions ap
              INNER JOIN role_access_mapping ram ON ap.access_id = ram.access_id
              INNER JOIN user_role_mapping urm ON ram.role_id = urm.role_id
              WHERE urm.user_id = '$userId'
              AND ap.bank_name = '$bankName'
              AND ap.canUpdate = 1";

    $result = mysqli_query($conn, $query);
    $count = mysqli_fetch_row($result)[0];

    return $count > 0;
}

function checkUserAccessDelete($conn, $userId,  $bankName) {
    $query = "SELECT COUNT(*) FROM access_permissions ap
              INNER JOIN role_access_mapping ram ON ap.access_id = ram.access_id
              INNER JOIN user_role_mapping urm ON ram.role_id = urm.role_id
              WHERE urm.user_id = '$userId'
              AND ap.bank_name = '$bankName'
              AND ap.canDelete = 1";

    $result = mysqli_query($conn, $query);
    $count = mysqli_fetch_row($result)[0];

    return $count > 0;
}

function checkUserAccessPost($conn, $userId, $bankName) {
    $query = "SELECT COUNT(*) FROM access_permissions ap
              INNER JOIN role_access_mapping ram ON ap.access_id = ram.access_id
              INNER JOIN user_role_mapping urm ON ram.role_id = urm.role_id
              INNER JOIN user_information ui ON urm.user_id = ui.user_id
              WHERE ui.user_id = '$userId'
              AND ap.bank_name = '$bankName'
              AND ap.canPost = 1";

    $result = mysqli_query($conn, $query);

    $count = mysqli_fetch_row($result)[0];

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


            <div class="role-list">
                <h3>Bank A Information</h3>
                <?php
                    $select_query = "SELECT * FROM BankA";
                    $result = mysqli_query($conn, $select_query);
                    $userId = $_SESSION['user_id'];


                    $hasPostAccess = checkUserAccessPost($conn, $userId, 'Bank A');
                    if ($hasPostAccess) : ?>
                        <a href="../bankA_action/add_bankA.php" class="add-user-button">Add Product</a>
                <?php endif; ?>
                <!-- Product listing table -->
                <table>
                    <tr>
                        <th>Number</th>
                        <th>Product Name</th>
                        <th>Is Active</th>
                        <th>Nominal</th>
                        <th colspan="2">Actions</th>
                    </tr>

                    <?php
                    
                    $select_query = "SELECT * FROM BankA";
                    $result = mysqli_query($conn, $select_query);
                    $userId = $_SESSION['user_id'];

                    // Initialize a counter
                    $counter = 1;
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        $product_id = $row['product_id'];
                        $product_name = $row['product_name'];
                        $is_active = $row['isActive'];
                        $nominal = $row['Nominal'];

                        

                        // Check if user has access to update based on the access_permissions table
                        $hasUpdateAccess = checkUserAccessUpdate($conn, $userId, 'Bank A'); // Replace $userId with actual user ID
                        $hasDeleteAccess = checkUserAccessDelete($conn, $userId, 'Bank A');
                        
                    
                        
                        echo "<tr>";
                        echo "<td>$counter</td>";
                        echo "<td>$product_name</td>";
                        echo "<td>$is_active</td>";
                        echo "<td>$nominal</td>";
                        echo '<td class="actions-cell">';
                        
                        if ($hasUpdateAccess) {
                            echo '<a href="../bankA_action/edit_bankA.php?product_id=' . $product_id . '" class="edit-button">Edit</a>';
                        }
                        echo '</td>';
                        echo '<td class="actions-cell">';

                        if ($hasDeleteAccess) {
                            echo '<button class="delete-btn" data-productid="' . $product_id . '">Delete</button>';
                        }
                        echo '</td>';
                        echo "</tr>";

                        // Increment the counter
                        $counter++;

                        
                    }
                    ?>

                </table>
            </div>
        </div>
    </div>

    <!-- ====== Scripts ======= -->
    <script>
        // Handle Edit button click
        const editButtons = document.querySelectorAll('.edit-button');
        const editForm = document.querySelector('.edit-product-form');

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const productId = button.getAttribute('data-productid');
                const productName = button.parentElement.parentElement.querySelector('td:nth-child(2)').textContent;
                const isActive = button.parentElement.parentElement.querySelector('td:nth-child(3)').textContent === '1';
                const nominal = button.parentElement.parentElement.querySelector('td:nth-child(4)').textContent;

                document.getElementById('edit-product-id').value = productId;
                document.getElementById('updated-product-name').value = productName;
                document.getElementById('updated-is-active').checked = isActive;
                document.getElementById('updated-nominal').value = nominal;

                editForm.style.display = 'block';
            });
        });
    </script>

    <script>
        // Handle Delete button click
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const productId = button.getAttribute('data-productid');

                // Send AJAX request to delete_product.php
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `../bankA_action/delete_bankA.php?delete_product=${productId}`, true);

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            // Display success message
                            const successMessage = document.createElement('p');
                            successMessage.textContent = 'Product deleted successfully.';
                            successMessage.classList.add('success-message');
                            button.parentElement.appendChild(successMessage);

                            // Remove the row from the table
                            button.parentElement.parentElement.remove();
                        } else {
                            // Handle error
                            console.error('Error deleting product:', response.error);
                        }
                    }
                };

                xhr.send();
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