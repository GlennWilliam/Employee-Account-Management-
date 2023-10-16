<?php
@include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];

        // Delete associated records first (e.g., user_role_mapping)
        $delete_associated_query = "DELETE FROM user_role_mapping WHERE user_id = $user_id";
        if (mysqli_query($conn, $delete_associated_query)) {
            // Now delete the user
            $delete_query = "DELETE FROM user_information WHERE user_id = $user_id";
            // Re-enable foreign key check
            if (mysqli_query($conn, $delete_query)) {
                // Redirect back to the page
                header("Location: employeeinformation.php");
                exit();
            } else {
                echo "Error deleting user: " . mysqli_error($conn);
            }
        } else {
            echo "Error deleting associated records: " . mysqli_error($conn);
        }
    }
}
?>
