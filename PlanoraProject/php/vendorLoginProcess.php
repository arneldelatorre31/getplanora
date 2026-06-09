<?php

session_start();

include 'connect.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM vendors WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../vendor/index.php?error=account_not_found");
    exit();
}

$vendor = $result->fetch_assoc();

if (!password_verify($password, $vendor['password'])) {
    header("Location: ../vendor/index.php?error=incorrect_password");
    exit();
}

$_SESSION['vendor_id'] = $vendor['id'];
$_SESSION['vendor_name'] = $vendor['full_name'];
$_SESSION['vendor_email'] = $vendor['email'];

header("Location: ../vendor/dashboard.php");
exit();

?>
