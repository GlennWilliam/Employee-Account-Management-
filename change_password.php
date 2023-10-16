<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['name'])) {
    header('location:login_form.php');
    exit;
}

$admin_name = $_SESSION['name'];

if (isset($_POST['change_password'])) {
    $old_password = md5($_POST['old_password']);
    $new_password = md5($_POST['new_password']);
    $confirm_new_password = md5($_POST['confirm_new_password']);

    // Retrieve admin information
    $select = "SELECT * FROM user_information WHERE first_name = '$admin_name' AND password = '$old_password' AND user_type = 'admin'";
    $result = mysqli_query($conn, $select);

    if (mysqli_num_rows($result) > 0) {
        if ($new_password === $confirm_new_password) {
            $update = "UPDATE user_information SET password = '$new_password' WHERE first_name = '$admin_name' AND user_type = 'admin'";
            mysqli_query($conn, $update);
            $success = "Password changed successfully.";
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Old password is incorrect.";
    }
}
?>

<!-- Include your HTML code for displaying errors and success messages -->
