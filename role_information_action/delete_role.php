<?php
@include '../config.php';

function deleteRole($conn, $role_id) {
    // Delete records from role_permission_mapping table
    $deletePermissionMappingQuery = "DELETE FROM role_permission_mapping WHERE role_id = '$role_id'";
    mysqli_query($conn, $deletePermissionMappingQuery);

    $deletePermissionMappingQuery2 = "DELETE FROM employee_role_permission_mapping WHERE role_id = '$role_id'";
    mysqli_query($conn, $deletePermissionMappingQuery2);


    // Delete records from role_access_mapping table
    $deleteRoleAccessMappingQuery = "DELETE FROM role_access_mapping WHERE access_id IN 
                                    (SELECT access_id FROM role_access_mapping WHERE role_id = '$role_id')";
    mysqli_query($conn, $deleteRoleAccessMappingQuery);

    $deleteRoleAccessMappingQuery2 = "DELETE FROM employee_role_access_mapping WHERE employee_access_id IN 
                                    (SELECT employee_access_id FROM employee_role_access_mapping WHERE role_id = '$role_id')";
    mysqli_query($conn, $deleteRoleAccessMappingQuery2);

    // Delete records from access_permissions table
    $deletePermissionsQuery = "DELETE FROM access_permissions WHERE access_id IN 
                              (SELECT access_id FROM role_access_mapping WHERE role_id = '$role_id')";
    mysqli_query($conn, $deletePermissionsQuery);

    $deletePermissionsQuery2 = "DELETE FROM employee_access_permissions WHERE employee_access_id IN 
                              (SELECT employee_access_id FROM employee_role_access_mapping WHERE role_id = '$role_id')";
    mysqli_query($conn, $deletePermissionsQuery2);

    // Delete records from user_role_mapping table
    $deleteUserRoleMappingQuery = "DELETE FROM user_role_mapping WHERE role_id = '$role_id'";
    mysqli_query($conn, $deleteUserRoleMappingQuery);

    $deleteUserRoleMappingQuery2 = "DELETE FROM employee_user_role_mapping WHERE role_id = '$role_id'";
    mysqli_query($conn, $deleteUserRoleMappingQuery2);

    // Delete role from user_role table
    $deleteRoleQuery = "DELETE FROM user_role WHERE role_id = '$role_id'";
    mysqli_query($conn, $deleteRoleQuery);



    // Return true if the deletion was successful, otherwise return false
    if (mysqli_affected_rows($conn) > 0) {
        return true;
    } else {
        return false;
    }
}


if (isset($_GET['role_id'])) {
    $role_id = $_GET['role_id'];

    // Assuming $conn is your database connection
    if (deleteRole($conn, $role_id)) {
        header("Location: ../main_page/role_information.php?message=Role deleted successfully");
    } else {
        echo "Role deletion failed";
    }
}
?>
