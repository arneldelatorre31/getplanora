<?php

session_start();
include 'connect.php';

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

$vendor_id = (int) $_SESSION['vendor_id'];
$listing_id = (int) ($_GET['listing_id'] ?? $_GET['id'] ?? 0);

if ($listing_id > 0) {
    $sql = "DELETE FROM listings WHERE listing_id = ? AND vendor_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $listing_id, $vendor_id);
    mysqli_stmt_execute($stmt);
}

header("Location: ../vendor/listings.php");
exit();

?>
