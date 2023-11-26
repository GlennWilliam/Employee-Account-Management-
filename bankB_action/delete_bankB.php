<?php

include '../config.php';


if (isset($_GET['delete_product'])) {
    $product_id = $_GET['delete_product'];

    // Delete the product from the BankA table
    $delete_query = "DELETE FROM BankB WHERE product_id = '$product_id'";
    mysqli_query($conn, $delete_query);

    if (mysqli_affected_rows($conn) > 0) {
        $response = ['success' => true];
        $success_message = 'Product deleted successfully.';
    } else {
        $response = ['success' => false, 'error' => 'Error deleting product.'];
    }

    echo json_encode($response);
}
?>


